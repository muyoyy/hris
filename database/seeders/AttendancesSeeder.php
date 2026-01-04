<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Seeder;

class AttendancesSeeder extends Seeder
{
    public function run(): void
    {
        $employees = Employee::all();
        // Seed 5 data terbaru saja per karyawan
        foreach ($employees as $employee) {
            foreach (range(0, 4) as $i) {
                $date = now()->subDays($i)->startOfDay();
                $status = $this->pickStatus();
                $checkIn = null;
                $checkOut = null;

                if (in_array($status, ['HADIR', 'TELAT'])) {
                    $checkIn = $this->randomTime($date, $status === 'TELAT' ? '08:31' : '08:00', $status === 'TELAT' ? '09:15' : '08:45');
                    $checkOut = $this->randomTime($date, '17:00', '18:15');
                }

                $record = Attendance::withTrashed()
                    ->where('employee_id', $employee->id)
                    ->whereDate('work_date', $date)
                    ->first();

                if ($record && $record->trashed()) {
                    $record->restore();
                }

                $data = [
                    'employee_id' => $employee->id,
                    'work_date' => $date->toDateString(),
                    'check_in_at' => $checkIn,
                    'check_out_at' => $checkOut,
                    'status' => $status,
                    'note' => $status === 'ALPHA' ? 'Tidak hadir' : null,
                ];

                if ($record) {
                    $record->fill($data)->save();
                    continue;
                }

                Attendance::create($data);
            }
        }
    }

    private function pickStatus(): string
    {
        return fake()->randomElement([
            'HADIR', 'HADIR', 'HADIR', 'HADIR', 'HADIR',
            'TELAT',
            'IZIN',
            'SAKIT',
            'ALPHA',
        ]);
    }

    private function randomTime(Carbon $date, string $from, string $to): Carbon
    {
        $start = Carbon::parse($from, $date->timezone)->setDate($date->year, $date->month, $date->day);
        $end = Carbon::parse($to, $date->timezone)->setDate($date->year, $date->month, $date->day);

        return $start->addMinutes(fake()->numberBetween(0, $end->diffInMinutes($start)));
    }
}
