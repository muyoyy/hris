<?php

namespace App\Filament\Employee\Pages;

use App\Domain\Attendance\Models\Attendance;
use App\Domain\Employee\Models\Employee;
use App\Domain\Leave\Models\LeaveRequest;
use App\Domain\Payroll\Models\Payslip;
use Filament\Pages\Dashboard;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Widgets\AccountWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class EmployeeDashboard extends Dashboard
{
    protected static ?string $navigationLabel = 'Dashboard';

    public function getHeaderWidgets(): array
    {
        return [
            AccountWidget::class,
            \App\Filament\Employee\Widgets\EmployeeStats::class,
        ];
    }

    public function getWidgets(): array
    {
        return [
            \App\Filament\Employee\Widgets\RecentAttendances::class,
            \App\Filament\Employee\Widgets\RecentLeaves::class,
            \App\Filament\Employee\Widgets\RecentPayslips::class,
        ];
    }

    protected function getActions(): array
    {
        return [
            Actions\Action::make('checkIn')
                ->label('Absen Masuk (buka halaman selfie)')
                ->icon('heroicon-o-arrow-down-left')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Absen Masuk')
                ->modalDescription('Kamu akan diarahkan ke halaman selfie untuk absen masuk.')
                ->modalSubmitActionLabel('Buka Halaman')
                ->modalCancelActionLabel('Batal')
                ->modalIcon('heroicon-o-camera')
                ->action(fn () => redirect()->route('filament.employee.pages.my-attendance')),
            Actions\Action::make('checkOut')
                ->label('Absen Pulang (buka halaman selfie)')
                ->icon('heroicon-o-arrow-up-right')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Absen Pulang')
                ->modalDescription('Kamu akan diarahkan ke halaman selfie untuk absen pulang.')
                ->modalSubmitActionLabel('Buka Halaman')
                ->modalCancelActionLabel('Batal')
                ->modalIcon('heroicon-o-camera')
                ->action(fn () => redirect()->route('filament.employee.pages.my-attendance')),
            Actions\Action::make('requestLeave')
                ->label('Ajukan Izin / Sakit')
                ->icon('heroicon-o-paper-airplane')
                ->color('info')
                ->disabled(fn () => $this->missingEmployee())
                ->form([
                    Forms\Components\Select::make('type')
                        ->label('Tipe')
                        ->options([
                            'IZIN' => 'Izin',
                            'SAKIT' => 'Sakit',
                        ])
                        ->required(),
                    Forms\Components\DatePicker::make('start_date')
                        ->label('Mulai')
                        ->required(),
                    Forms\Components\DatePicker::make('end_date')
                        ->label('Selesai')
                        ->required()
                        ->afterOrEqual('start_date'),
                    Forms\Components\Textarea::make('reason')
                        ->label('Alasan')
                        ->required()
                        ->columnSpanFull(),
                ])
                ->action(function (array $data): void {
                    $employee = $this->employee();

                    if (! $employee) {
                        $this->sendMissingEmployeeNotification();

                        return;
                    }

                    LeaveRequest::create([
                        'employee_id' => $employee->id,
                        'start_date' => $data['start_date'],
                        'end_date' => $data['end_date'],
                        'type' => $data['type'],
                        'status' => 'PENDING',
                        'reason' => $data['reason'],
                    ]);

                    Notification::make()
                        ->title('Pengajuan izin / sakit dikirim')
                        ->success()
                        ->send();
                }),
        ];
    }

    protected function hasCheckedInToday(): bool
    {
        $employee = $this->employee();

        if (! $employee) {
            return false;
        }

        return Attendance::where('employee_id', $employee->id)
            ->whereDate('work_date', Carbon::today($this->timezone()))
            ->exists();
    }

    protected function canCheckOut(): bool
    {
        $employee = $this->employee();

        if (! $employee) {
            return false;
        }

        return Attendance::where('employee_id', $employee->id)
            ->whereDate('work_date', Carbon::today($this->timezone()))
            ->whereNotNull('check_in_at')
            ->whereNull('check_out_at')
            ->exists();
    }

    protected function sendMissingEmployeeNotification(): void
    {
        Notification::make()
            ->title('Profil karyawan belum terdaftar')
            ->danger()
            ->body('Silakan hubungi admin untuk melengkapi data karyawan Anda.')
            ->send();
    }

    protected function missingEmployee(): bool
    {
        return ! $this->employee();
    }
    protected function employee(): ?Employee
    {
        return once(function () {
            return auth()->user()?->employee;
        });
    }

    protected function checkInThreshold(): string
    {
        return '09:00';
    }

    protected function timezone(): string
    {
        return 'Asia/Jakarta';
    }
}
