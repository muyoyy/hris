<?php

namespace App\Filament\Employee\Resources;

use App\Domain\Payroll\Models\Payslip;
use App\Filament\Employee\Resources\MyPayslipResource\Pages;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;
use BackedEnum;
use Filament\Schemas\Schema;

class MyPayslipResource extends Resource
{
    protected static ?string $model = Payslip::class;

    protected static string|UnitEnum|null $navigationGroup = 'Employee';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

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
        return parent::getEloquentQuery()
            ->where('employee_id', static::getEmployeeId())
            ->where('status', 'issued');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('period_start')->label('Start')->date(),
                Tables\Columns\TextColumn::make('period_end')->label('End')->date(),
                Tables\Columns\TextColumn::make('net_pay')->money('idr'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'info' => 'issued',
                        'success' => 'paid',
                    ]),
            ])
            ->filters([])
            ->actions([])
            ->bulkActions([]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole('employee') ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMyPayslips::route('/'),
        ];
    }
}
