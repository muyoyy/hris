<?php

namespace App\Filament\Employee\Widgets;

use App\Domain\Attendance\Models\Attendance;
use Filament\Tables;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentAttendances extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    protected function getTableQuery(): Builder
    {
        $employeeId = $this->employeeId();

        if (! $employeeId) {
            return Attendance::query()->whereRaw('1 = 0');
        }

        return Attendance::query()
            ->where('employee_id', $employeeId)
            ->orderByDesc('work_date')
            ->limit(7);
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('work_date')
                ->label('Tanggal')
                ->date(),
            Tables\Columns\TextColumn::make('check_in_at')
                ->label('Check-in')
                ->time(),
            Tables\Columns\TextColumn::make('check_out_at')
                ->label('Check-out')
                ->time(),
            Tables\Columns\BadgeColumn::make('status')
                ->colors([
                    'success' => 'HADIR',
                    'warning' => 'TELAT',
                    'info' => ['IZIN', 'SAKIT'],
                    'danger' => 'ALPHA',
                ]),
        ];
    }

    protected function getTableHeading(): ?string
    {
        return 'Absensi 7 hari terakhir';
    }

    protected function employeeId(): ?int
    {
        return auth()->user()?->employee?->id;
    }
}
