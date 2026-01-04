<?php

namespace App\Filament\Employee\Widgets;

use App\Domain\Leave\Models\LeaveRequest;
use Filament\Tables;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentLeaves extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    protected function getTableQuery(): Builder
    {
        $employeeId = $this->employeeId();

        if (! $employeeId) {
            return LeaveRequest::query()->whereRaw('1 = 0');
        }

        return LeaveRequest::query()
            ->where('employee_id', $employeeId)
            ->orderByDesc('created_at')
            ->limit(5);
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('start_date')
                ->label('Mulai')
                ->date(),
            Tables\Columns\TextColumn::make('end_date')
                ->label('Selesai')
                ->date(),
            Tables\Columns\BadgeColumn::make('type')
                ->label('Tipe'),
            Tables\Columns\BadgeColumn::make('status')
                ->colors([
                    'warning' => 'PENDING',
                    'success' => 'APPROVED',
                    'danger' => 'REJECTED',
                ]),
            Tables\Columns\TextColumn::make('admin_note')
                ->label('Catatan Admin')
                ->limit(40),
        ];
    }

    protected function getTableHeading(): ?string
    {
        return 'Pengajuan izin terbaru';
    }

    protected function employeeId(): ?int
    {
        return auth()->user()?->employee?->id;
    }
}
