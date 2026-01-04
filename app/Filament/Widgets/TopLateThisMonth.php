<?php

namespace App\Filament\Widgets;

use App\Domain\Attendance\Models\Attendance;
use Filament\Tables;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TopLateThisMonth extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['admin', 'manager', 'owner']) ?? false;
    }

    protected function getTableQuery(): Builder
    {
        $now = Carbon::now($this->timezone());

        return Attendance::query()
            ->select('employee_id', DB::raw('MIN(id) as id'), DB::raw('COUNT(*) as total_telat'))
            ->whereBetween('work_date', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()])
            ->where('status', 'TELAT')
            ->whereHas('employee')
            ->with('employee')
            ->groupBy('employee_id')
            ->orderByDesc('total_telat')
            ->orderBy('employee_id')
            ->limit(10);
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
            Tables\Columns\BadgeColumn::make('total_telat')
                ->label('Total Telat')
                ->colors([
                    'danger',
                ]),
        ];
    }

    protected function getTableHeading(): ?string
    {
        return 'Top 10 Terlambat Bulan Ini';
    }

    protected function timezone(): string
    {
        return 'Asia/Jakarta';
    }
}
