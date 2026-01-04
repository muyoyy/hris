<?php

namespace App\Filament\Widgets;

use App\Domain\Attendance\Models\Attendance;
use App\Domain\Employee\Models\Employee;
use App\Domain\Leave\Models\LeaveRequest;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class HrisStats extends BaseWidget
{
    protected function getCards(): array
    {
        $today = Carbon::today($this->timezone());

        return [
            Stat::make('Total Employees', Employee::count()),
            Stat::make('Pending Leave', LeaveRequest::where('status', 'PENDING')->count()),
            Stat::make('Today Attendance', Attendance::whereDate('work_date', $today)->count()),
        ];
    }

    protected function timezone(): string
    {
        return 'Asia/Jakarta';
    }
}
