<?php

namespace App\Filament\Resources;

use App\Domain\Leave\Models\LeaveRequest;
use App\Filament\Resources\LeaveRequestResource\Pages;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Tables\Filters\TrashedFilter;
use UnitEnum;

class LeaveRequestResource extends Resource
{
    protected static ?string $model = LeaveRequest::class;

    protected static string | UnitEnum | null $navigationGroup = 'HRIS';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-flag';

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\Select::make('employee_id')
                ->relationship('employee', 'full_name')
                ->searchable()
                ->preload()
                ->required(),
            Forms\Components\Select::make('type')
                ->options([
                    'IZIN' => 'Izin',
                    'SAKIT' => 'Sakit',
                ])
                ->required(),
            Forms\Components\DatePicker::make('start_date')->required(),
            Forms\Components\DatePicker::make('end_date')->required(),
            Forms\Components\Textarea::make('reason')
                ->columnSpanFull(),
            Forms\Components\Select::make('status')
                ->options([
                    'PENDING' => 'Pending',
                    'APPROVED' => 'Approved',
                    'REJECTED' => 'Rejected',
                ])
                ->default('PENDING')
                ->required(),
            Forms\Components\Hidden::make('approver_id'),
            Forms\Components\Textarea::make('admin_note')
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee.full_name')
                    ->label('Employee')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('type'),
                Tables\Columns\TextColumn::make('start_date')->date(),
                Tables\Columns\TextColumn::make('end_date')->date(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'PENDING',
                        'success' => 'APPROVED',
                        'danger' => 'REJECTED',
                    ]),
                Tables\Columns\TextColumn::make('admin_note')
                    ->label('Catatan')
                    ->limit(30)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('approver.name')
                    ->label('Approver')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'PENDING' => 'Pending',
                        'APPROVED' => 'Approved',
                        'REJECTED' => 'Rejected',
                    ]),
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'IZIN' => 'Izin',
                        'SAKIT' => 'Sakit',
                    ]),
                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['from'] ?? null, fn ($q, $date) => $q->whereDate('start_date', '>=', $date))
                        ->when($data['until'] ?? null, fn ($q, $date) => $q->whereDate('start_date', '<=', $date))),
                TrashedFilter::make(),
            ])
            ->actions([
                Actions\EditAction::make()
                    ->visible(fn () => static::canManage()),
                Actions\Action::make('approve')
                    ->label('Approve')
                    ->color('success')
                    ->visible(fn (LeaveRequest $record) => static::canApprove() && $record->status === 'PENDING')
                    ->form([
                        Forms\Components\Textarea::make('admin_note')
                            ->label('Admin Note')
                            ->placeholder('Optional note')
                            ->columnSpanFull(),
                    ])
                    ->action(function (LeaveRequest $record, array $data) {
                        $record->update([
                            'status' => 'APPROVED',
                            'approver_id' => auth()->id(),
                            'admin_note' => $data['admin_note'] ?? null,
                        ]);
                    }),
                Actions\Action::make('reject')
                    ->label('Reject')
                    ->color('danger')
                    ->visible(fn (LeaveRequest $record) => static::canApprove() && $record->status === 'PENDING')
                    ->form([
                        Forms\Components\Textarea::make('admin_note')
                            ->label('Admin Note')
                            ->required()
                            ->columnSpanFull(),
                    ])
                    ->action(function (LeaveRequest $record, array $data) {
                        $record->update([
                            'status' => 'REJECTED',
                            'admin_note' => $data['admin_note'] ?? null,
                            'approver_id' => auth()->id(),
                        ]);
                    }),
                Actions\DeleteAction::make()->visible(fn () => static::canManage()),
                Actions\RestoreAction::make()->visible(fn () => static::canManage()),
                Actions\ForceDeleteAction::make()->visible(fn () => static::canManage()),
            ])
            ->actionsColumnLabel('Aksi')
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make()->visible(fn () => static::canManage()),
                    Actions\RestoreBulkAction::make()->visible(fn () => static::canManage()),
                    Actions\ForceDeleteBulkAction::make()->visible(fn () => static::canManage()),
                ]),
            ]);
    }

    public static function canCreate(): bool
    {
        return static::canManage();
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyRole(['admin', 'manager', 'owner']) ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return static::canManage();
    }

    public static function canEdit($record): bool
    {
        return static::canManage();
    }

    public static function canDelete($record): bool
    {
        return static::canManage();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeaveRequests::route('/'),
            'create' => Pages\CreateLeaveRequest::route('/create'),
            'edit' => Pages\EditLeaveRequest::route('/{record}/edit'),
        ];
    }

    protected static function canApprove(): bool
    {
        return auth()->user()?->hasAnyRole(['admin', 'manager', 'owner']) ?? false;
    }

    protected static function canManage(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public static function canView($record): bool
    {
        return auth()->user()?->hasAnyRole(['admin', 'manager', 'owner']) ?? false;
    }
}
