<?php

namespace App\Filament\Widgets;

use App\Domain\Attendance\Models\Attendance;
use App\Domain\Employee\Models\Employee;
use App\Domain\Leave\Models\LeaveRequest;
use App\Domain\Payroll\Models\Payslip;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class ManagerOwnerStats extends BaseWidget
{
    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['admin', 'manager', 'owner']) ?? false;
    }

    protected function getCards(): array
    {
        $now = Carbon::now($this->timezone());
        $today = $now->copy()->startOfDay();
        $monthStart = $now->copy()->startOfMonth();
        $monthEnd = $now->copy()->endOfMonth();

        $todayAttendance = Attendance::query()->whereDate('work_date', $today);
        $monthAttendance = Attendance::query()->whereBetween('work_date', [$monthStart, $monthEnd]);

        $pendingToday = LeaveRequest::query()
            ->where('status', 'PENDING')
            ->whereDate('start_date', $today)
            ->count();

        $pendingMonth = LeaveRequest::query()
            ->where('status', 'PENDING')
            ->whereBetween('start_date', [$monthStart, $monthEnd])
            ->count();

        $netPayThisMonth = Payslip::query()
            ->where('status', 'issued')
            ->whereBetween('period_start', [$monthStart, $monthEnd])
            ->sum('net_pay');

        $activeEmployees = Employee::query()->where('status', 'active')->count();

        return [
            Stat::make('Hadir hari ini', $todayAttendance->clone()->where('status', 'HADIR')->count()),
            Stat::make('Telat hari ini', $todayAttendance->clone()->where('status', 'TELAT')->count()),
            Stat::make('Alpha hari ini', $todayAttendance->clone()->where('status', 'ALPHA')->count()),
            Stat::make('Pending leave hari ini', $pendingToday)
                ->description("Pending bulan ini: {$pendingMonth}"),
            Stat::make('Hadir bulan ini', $monthAttendance->clone()->where('status', 'HADIR')->count()),
            Stat::make('Telat bulan ini', $monthAttendance->clone()->where('status', 'TELAT')->count()),
            Stat::make('Alpha bulan ini', $monthAttendance->clone()->where('status', 'ALPHA')->count()),
            Stat::make('Net pay issued bulan ini', 'Rp ' . number_format($netPayThisMonth ?? 0, 0, ',', '.')),
            Stat::make('Karyawan aktif', $activeEmployees),
        ];
    }

    protected function timezone(): string
    {
        return 'Asia/Jakarta';
    }
}
