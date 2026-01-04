<?php

namespace App\Filament\Widgets;

use App\Domain\Attendance\Models\Attendance;
use Filament\Tables;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class PendingCheckoutToday extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['admin', 'manager', 'owner']) ?? false;
    }

    protected function getTableQuery(): Builder
    {
        $today = Carbon::today($this->timezone());

        return Attendance::query()
            ->with('employee')
            ->whereDate('work_date', $today)
            ->whereNotNull('check_in_at')
            ->whereNull('check_out_at')
            ->orderBy('check_in_at');
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('employee.employee_code')
                ->label('Kode')
                ->sortable(),
            Tables\Columns\TextColumn::make('employee.full_name')
                ->label('Nama')
                ->sortable()
                ->searchable(),
            Tables\Columns\TextColumn::make('employee.department')
                ->label('Dept')
                ->sortable()
                ->searchable(),
            Tables\Columns\TextColumn::make('check_in_at')
                ->label('Check-in')
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
        return 'Belum Absen Pulang Hari Ini';
    }

    protected function timezone(): string
    {
        return 'Asia/Jakarta';
    }
}
