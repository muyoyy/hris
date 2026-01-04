<?php

namespace App\Filament\Employee\Widgets;

use App\Domain\Attendance\Models\Attendance;
use App\Domain\Leave\Models\LeaveRequest;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class EmployeeStats extends BaseWidget
{
    protected function getCards(): array
    {
        $employeeId = $this->employeeId();

        if (! $employeeId) {
            return [
                Stat::make('Profil karyawan belum terdaftar', '-')
                    ->description('Hubungi admin untuk melengkapi data Anda')
                    ->color('danger'),
            ];
        }

        $now = Carbon::now($this->timezone());
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();

        $attendanceQuery = Attendance::where('employee_id', $employeeId)
            ->whereBetween('work_date', [$startOfMonth, $endOfMonth]);

        $leavePending = LeaveRequest::where('employee_id', $employeeId)
            ->where('status', 'PENDING')
            ->count();

        return [
            Stat::make('Hadir bulan ini', (clone $attendanceQuery)->where('status', 'HADIR')->count()),
            Stat::make('Telat bulan ini', (clone $attendanceQuery)->where('status', 'TELAT')->count()),
            Stat::make('Izin/Sakit bulan ini', (clone $attendanceQuery)->whereIn('status', ['IZIN', 'SAKIT'])->count()),
            Stat::make('Alpha bulan ini', (clone $attendanceQuery)->where('status', 'ALPHA')->count()),
            Stat::make('Izin pending', $leavePending),
        ];
    }

    protected function employeeId(): ?int
    {
        return auth()->user()?->employee?->id;
    }

    protected function timezone(): string
    {
        return 'Asia/Jakarta';
    }
}
