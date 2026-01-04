<?php

namespace App\Filament\Employee\Resources;

use App\Domain\Attendance\Models\Attendance;
use App\Filament\Employee\Resources\MyAttendanceResource\Pages;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;
use BackedEnum;
use Filament\Schemas\Schema;

class MyAttendanceResource extends Resource
{
    protected static ?string $model = Attendance::class;

    protected static string|UnitEnum|null $navigationGroup = 'Employee';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clock';

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
            // Employees create via buttons; manual create disabled
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('work_date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('work_date')->date()->sortable(),
                Tables\Columns\TextColumn::make('check_in_at')->since(),
                Tables\Columns\TextColumn::make('check_out_at')->since(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'HADIR',
                        'warning' => 'TELAT',
                        'info' => ['IZIN', 'SAKIT'],
                        'danger' => 'ALPHA',
                    ]),
                Tables\Columns\TextColumn::make('note')->limit(40),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('month')
                    ->label('Month')
                    ->options(
                        collect(range(1, 12))->mapWithKeys(fn ($m) => [$m => sprintf('%02d', $m)])
                    )
                    ->query(function (Builder $query, array $data) {
                        return $query->when($data['value'] ?? null, fn ($q, $month) => $q->whereMonth('work_date', $month));
                    }),
            ])
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
            'index' => Pages\ListMyAttendances::route('/'),
        ];
    }
}
