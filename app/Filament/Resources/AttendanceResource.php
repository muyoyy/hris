<?php

namespace App\Filament\Resources;

use App\Domain\Attendance\Models\Attendance;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\AttendanceResource\Pages;
use BackedEnum;
use UnitEnum;
use Filament\Tables\Filters\TrashedFilter;

class AttendanceResource extends Resource
{
    protected static ?string $model = Attendance::class;

    protected static string | UnitEnum | null $navigationGroup = 'HRIS';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-clock';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\Select::make('employee_id')
                ->relationship('employee', 'full_name')
                ->label('Employee')
                ->searchable()
                ->preload()
                ->required(),
            Forms\Components\DatePicker::make('work_date')
                ->required()
                ->unique(ignoreRecord: true, modifyRuleUsing: function ($rule, callable $get) {
                    return $rule->where(fn ($query) => $query->where('employee_id', $get('employee_id')));
                }),
            Forms\Components\DateTimePicker::make('check_in_at'),
            Forms\Components\DateTimePicker::make('check_out_at'),
            Forms\Components\Select::make('status')
                ->label('Status')
                ->options([
                    'HADIR' => 'Hadir',
                    'TELAT' => 'Telat',
                    'IZIN' => 'Izin',
                    'SAKIT' => 'Sakit',
                    'ALPHA' => 'Alpha',
                ])
                ->native(false)
                ->required()
                ->default('HADIR'),
            Forms\Components\Textarea::make('note')
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('employee.full_name')
                    ->label('Employee')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('work_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('check_in_at')
                    ->since()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('check_out_at')
                    ->since()
                    ->toggleable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'HADIR',
                        'warning' => 'TELAT',
                        'info' => ['IZIN', 'SAKIT'],
                        'danger' => 'ALPHA',
                    ]),
            ])
            ->filters([
                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['from'] ?? null, fn ($q, $date) => $q->whereDate('work_date', '>=', $date))
                            ->when($data['until'] ?? null, fn ($q, $date) => $q->whereDate('work_date', '<=', $date));
                    }),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'HADIR' => 'Hadir',
                        'TELAT' => 'Telat',
                        'IZIN' => 'Izin',
                        'SAKIT' => 'Sakit',
                        'ALPHA' => 'Alpha',
                    ]),
                Tables\Filters\SelectFilter::make('employee_id')
                    ->relationship('employee', 'full_name')
                    ->label('Employee'),
                TrashedFilter::make(),
            ])
            ->actions([
                Actions\EditAction::make()
                    ->visible(fn () => static::canManage()),
                Actions\DeleteAction::make()
                    ->visible(fn () => static::canManage()),
                Actions\RestoreAction::make()
                    ->visible(fn () => static::canManage()),
                Actions\ForceDeleteAction::make()
                    ->visible(fn () => static::canManage()),
            ])
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
        return auth()->user()?->hasRole('admin') ?? false;
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
            'index' => Pages\ListAttendances::route('/'),
            'create' => Pages\CreateAttendance::route('/create'),
            'edit' => Pages\EditAttendance::route('/{record}/edit'),
        ];
    }

    protected static function canManage(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public static function canView($record): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }
}
