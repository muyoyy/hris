<?php

namespace App\Filament\Employee\Pages;

use App\Domain\Attendance\Models\Attendance;
use App\Models\OfficeLocation;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use UnitEnum;
use BackedEnum;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Features\SupportFileUploads\WithFileUploads;

class MyAttendance extends Page
{
    use WithFileUploads;

    protected static ?string $title = 'My Attendance';

    protected static string | UnitEnum | null $navigationGroup = 'Attendance';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-camera';

    protected string $view = 'filament.employee.pages.my-attendance';

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public function submitAttendance(string $type, array $payload): void
    {
        if (! in_array($type, ['check-in', 'check-out'])) {
            $this->notifyError('Jenis absen tidak valid.');
            return;
        }

        $employee = auth()->user()?->employee;
        if (! $employee) {
            $this->notifyError('Profil karyawan tidak ditemukan.');
            return;
        }

        $lat = $payload['lat'] ?? null;
        $lng = $payload['lng'] ?? null;
        $accuracy = $payload['accuracy'] ?? null;
        $photoData = $payload['photo'] ?? null;
        /** @var TemporaryUploadedFile|null $photoFile */
        $photoFile = $payload['photo_file'] ?? null;

        try {
            [$lat, $lng, $accuracy] = $this->validateLocationInput($lat, $lng, $accuracy);
            $photoPath = $photoFile
                ? $this->storeUploadedPhoto($photoFile, $employee->employee_code, $type)
                : $this->storePhoto($photoData, $employee->employee_code, $type);
            [$locationStatus, $distance] = $this->determineLocationStatus($lat, $lng, $accuracy);
        } catch (\RuntimeException $e) {
            $this->notifyError($e->getMessage());
            return;
        }

        $today = Carbon::today($this->timezone());

        if ($type === 'check-in') {
            try {
                DB::transaction(function () use ($employee, $today, $photoPath, $lat, $lng, $accuracy, $locationStatus, $distance) {
                    $existing = Attendance::withTrashed()
                        ->where('employee_id', $employee->id)
                        ->whereDate('work_date', $today)
                        ->lockForUpdate()
                        ->first();

                    if ($existing && $existing->check_in_at) {
                        throw new \RuntimeException('Sudah check-in hari ini.');
                    }

                    $now = Carbon::now($this->timezone());
                    $status = $now->gt(Carbon::today($this->timezone())->setTimeFromTimeString('09:00')) ? 'TELAT' : 'HADIR';

                    if ($existing) {
                        if ($existing->trashed()) {
                            $existing->restore();
                        }
                        $existing->update([
                            'check_in_at' => $now,
                            'status' => $status,
                            'check_in_photo_path' => $photoPath,
                            'check_in_lat' => $lat,
                            'check_in_lng' => $lng,
                            'check_in_accuracy' => $accuracy,
                            'location_status' => $locationStatus,
                            'distance_m' => $distance,
                        ]);
                    } else {
                        Attendance::create([
                            'employee_id' => $employee->id,
                            'work_date' => $today->toDateString(),
                            'check_in_at' => $now,
                            'status' => $status,
                            'check_in_photo_path' => $photoPath,
                            'check_in_lat' => $lat,
                            'check_in_lng' => $lng,
                            'check_in_accuracy' => $accuracy,
                            'location_status' => $locationStatus,
                            'distance_m' => $distance,
                        ]);
                    }
                });
            } catch (\RuntimeException $e) {
                $this->notifyError($e->getMessage());
                return;
            }

            $this->notifySuccess('Check-in berhasil.');
            return;
        }

        $attendance = Attendance::where('employee_id', $employee->id)
            ->whereDate('work_date', $today)
            ->whereNotNull('check_in_at')
            ->whereNull('check_out_at')
            ->first();

        if (! $attendance) {
            $this->notifyError('Belum check-in atau sudah check-out.');
            return;
        }

        $attendance->update([
            'check_out_at' => Carbon::now($this->timezone()),
            'check_out_photo_path' => $photoPath,
            'check_out_lat' => $lat,
            'check_out_lng' => $lng,
            'check_out_accuracy' => $accuracy,
            'location_status' => $locationStatus,
            'distance_m' => $distance,
        ]);

        $this->notifySuccess('Check-out berhasil.');
    }

    protected function validateLocationInput($lat, $lng, $accuracy): array
    {
        if ($lat === null || $lng === null) {
            throw new \RuntimeException('Lokasi wajib diambil.');
        }

        if ($accuracy === null || $accuracy > 100) {
            throw new \RuntimeException('Lokasi tidak akurat (lebih dari 100m).');
        }

        return [(float) $lat, (float) $lng, (int) $accuracy];
    }

    protected function storePhoto(?string $dataUrl, string $employeeCode, string $type): string
    {
        if (! $dataUrl) {
            throw new \RuntimeException('Foto wajib diambil.');
        }

        $data = explode(',', $dataUrl);
        $binary = base64_decode(end($data));

        if ($binary === false) {
            throw new \RuntimeException('Format foto tidak valid.');
        }

        $size = strlen($binary);
        if ($size > 5 * 1024 * 1024) {
            throw new \RuntimeException('Ukuran foto maksimal 5MB.');
        }

        $filename = 'attendance/' . $employeeCode . '/' . $type . '-' . Str::random(10) . '.png';

        Storage::disk('public')->put($filename, $binary);

        return $filename;
    }

    protected function storeUploadedPhoto(TemporaryUploadedFile $file, string $employeeCode, string $type): string
    {
        $mime = $file->getMimeType();
        if (! $mime || ! str_starts_with($mime, 'image/')) {
            throw new \RuntimeException('File harus berupa gambar.');
        }

        $size = $file->getSize();
        if ($size && $size > 5 * 1024 * 1024) {
            throw new \RuntimeException('Ukuran foto maksimal 5MB.');
        }

        $filename = $type . '-' . Str::random(12) . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('attendance/' . $employeeCode, $filename, 'public');

        return $path;
    }

    protected function determineLocationStatus(float $lat, float $lng, int $accuracy): array
    {
        $offices = OfficeLocation::where('is_active', true)->get();
        if ($offices->isEmpty()) {
            throw new \RuntimeException('Lokasi kantor belum diset.');
        }

        // Cari kantor terdekat
        $nearest = null;
        $minDistance = null;
        foreach ($offices as $office) {
            $distance = $this->haversine($lat, $lng, (float) $office->lat, (float) $office->lng);
            if ($minDistance === null || $distance < $minDistance) {
                $minDistance = $distance;
                $nearest = $office;
            }
        }

        if ($nearest && $minDistance !== null) {
            if ($minDistance > $nearest->radius_m) {
                throw new \RuntimeException('Di luar area kantor (jarak ' . round($minDistance) . 'm, radius ' . $nearest->radius_m . 'm).');
            }

            return ['IN_AREA', (int) round($minDistance)];
        }

        throw new \RuntimeException('Gagal menentukan lokasi kantor.');
    }

    protected function haversine(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000; // meters
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    protected function notifyError(string $message): void
    {
        Notification::make()
            ->title($message)
            ->danger()
            ->send();
    }

    protected function notifySuccess(string $message): void
    {
        Notification::make()
            ->title($message)
            ->success()
            ->send();
    }

    protected function timezone(): string
    {
        return 'Asia/Jakarta';
    }
}
