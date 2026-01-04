<?php

namespace App\Filament\Employee\Resources;

use App\Domain\Leave\Models\LeaveRequest;
use App\Filament\Employee\Resources\MyLeaveResource\Pages;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Actions;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;
use BackedEnum;
use Filament\Schemas\Schema;

class MyLeaveResource extends Resource
{
    protected static ?string $model = LeaveRequest::class;

    protected static string|UnitEnum|null $navigationGroup = 'Employee';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasRole('employee') ?? false;
    }

    protected static function getEmployeeId(): ?int
    {
        return auth()->user()?->employee?->id;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('employee_id', static::getEmployeeId());
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\Hidden::make('employee_id')
                ->default(fn () => static::getEmployeeId()),
            Forms\Components\Select::make('type')
                ->options([
                    'IZIN' => 'Izin',
                    'SAKIT' => 'Sakit',
                ])
                ->required(),
            Forms\Components\DatePicker::make('start_date')->required(),
            Forms\Components\DatePicker::make('end_date')->required(),
            Forms\Components\Textarea::make('reason')->required()->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\BadgeColumn::make('type'),
                Tables\Columns\TextColumn::make('start_date')->date(),
                Tables\Columns\TextColumn::make('end_date')->date(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'PENDING',
                        'success' => 'APPROVED',
                        'danger' => 'REJECTED',
                    ]),
                Tables\Columns\TextColumn::make('admin_note')->limit(40),
            ])
            ->filters([])
            ->actions([
                Actions\EditAction::make()
                    ->visible(fn ($record) => $record->status === 'PENDING'),
                Actions\DeleteAction::make()
                    ->visible(fn ($record) => $record->status === 'PENDING'),
            ])
            ->bulkActions([]);
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->hasRole('employee') ?? false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole('employee') ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMyLeaves::route('/'),
            'create' => Pages\CreateMyLeave::route('/create'),
            'edit' => Pages\EditMyLeave::route('/{record}/edit'),
        ];
    }
}
