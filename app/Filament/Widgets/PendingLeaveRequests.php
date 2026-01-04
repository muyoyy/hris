<?php

namespace App\Filament\Widgets;

use App\Domain\Leave\Models\LeaveRequest;
use Filament\Forms;
use Filament\Tables;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class PendingLeaveRequests extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['admin', 'manager', 'owner']) ?? false;
    }

    protected function getTableQuery(): Builder
    {
        return LeaveRequest::query()
            ->with(['employee', 'approver'])
            ->where('status', 'PENDING')
            ->orderBy('start_date', 'asc');
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
            Tables\Columns\BadgeColumn::make('type')
                ->label('Tipe'),
            Tables\Columns\TextColumn::make('start_date')
                ->label('Mulai')
                ->date(),
            Tables\Columns\TextColumn::make('end_date')
                ->label('Selesai')
                ->date(),
            Tables\Columns\TextColumn::make('reason')
                ->label('Alasan')
                ->limit(40),
            Tables\Columns\BadgeColumn::make('status')
                ->colors([
                    'warning' => 'PENDING',
                    'success' => 'APPROVED',
                    'danger' => 'REJECTED',
                ])
                ->icon('heroicon-o-clock'),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            Tables\Actions\Action::make('approve')
                ->label('Approve')
                ->color('success')
                ->icon('heroicon-o-check')
                ->visible(fn () => $this->canManage())
                ->form([
                    Forms\Components\Textarea::make('admin_note')
                        ->label('Catatan Admin')
                        ->nullable()
                        ->columnSpanFull(),
                ])
                ->action(function (LeaveRequest $record, array $data): void {
                    $record->update([
                        'status' => 'APPROVED',
                        'approver_id' => auth()->id(),
                        'admin_note' => $data['admin_note'] ?? null,
                    ]);
                }),
            Tables\Actions\Action::make('reject')
                ->label('Reject')
                ->color('danger')
                ->icon('heroicon-o-x-mark')
                ->visible(fn () => $this->canManage())
                ->form([
                    Forms\Components\Textarea::make('admin_note')
                        ->label('Catatan Admin')
                        ->required()
                        ->columnSpanFull(),
                ])
                ->action(function (LeaveRequest $record, array $data): void {
                    $record->update([
                        'status' => 'REJECTED',
                        'approver_id' => auth()->id(),
                        'admin_note' => $data['admin_note'],
                    ]);
                }),
        ];
    }

    protected function getTableHeading(): ?string
    {
        return 'Pending Leave Requests';
    }

    protected function canManage(): bool
    {
        return auth()->user()?->hasAnyRole(['admin', 'manager', 'owner']) ?? false;
    }
}
