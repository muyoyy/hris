<?php

namespace App\Filament\Resources;

use App\Domain\Payroll\Models\Payslip;
use App\Filament\Resources\PayslipResource\Pages;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Tables\Filters\TrashedFilter;
use Illuminate\Validation\Rule;
use Illuminate\Support\Collection;

class PayslipResource extends Resource
{
    protected static ?string $model = Payslip::class;

    protected static string | UnitEnum | null $navigationGroup = 'HRIS';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Payslips';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\Select::make('employee_id')
                ->relationship('employee', 'full_name')
                ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->employee_code} - {$record->full_name}")
                ->searchable()
                ->preload()
                ->required(),
            Forms\Components\DatePicker::make('period_start')
                ->required()
                ->unique(ignoreRecord: true, modifyRuleUsing: fn (Rule $rule, callable $get) => $rule->where(fn ($query) => $query
                    ->where('employee_id', $get('employee_id'))
                    ->where('period_end', $get('period_end')))),
            Forms\Components\DatePicker::make('period_end')
                ->required(),
            Forms\Components\TextInput::make('basic_salary')
                ->numeric()
                ->required()
                ->default(0)
                ->prefix('Rp'),
            Forms\Components\Repeater::make('allowances')
                ->schema([
                    Forms\Components\TextInput::make('label')->required(),
                    Forms\Components\TextInput::make('amount')
                        ->numeric()
                        ->required()
                        ->default(0)
                        ->prefix('Rp'),
                ])
                ->label('Allowances')
                ->columnSpanFull()
                ->default([]),
            Forms\Components\Repeater::make('deductions')
                ->schema([
                    Forms\Components\TextInput::make('label')->required(),
                    Forms\Components\TextInput::make('amount')
                        ->numeric()
                        ->required()
                        ->default(0)
                        ->prefix('Rp'),
                ])
                ->label('Deductions')
                ->columnSpanFull()
                ->default([]),
            Forms\Components\TextInput::make('net_pay')
                ->numeric()
                ->required()
                ->dehydrateStateUsing(fn ($state, callable $get) => self::calculateNetPay(
                    $get('basic_salary'),
                    collect($get('allowances') ?? []),
                    collect($get('deductions') ?? []),
                ))
                ->dehydrated(true)
                ->disabled()
                ->prefix('Rp')
                ->helperText('Auto: basic + allowances - deductions'),
            Forms\Components\Select::make('status')
                ->options([
                    'draft' => 'Draft',
                    'issued' => 'Issued',
                ])
                ->default('draft')
                ->required(),
            Forms\Components\DateTimePicker::make('issued_at')
                ->label('Issued At')
                ->disabled(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee.full_name')
                    ->label('Employee')
                    ->sortable()
                    ->searchable(['employee.full_name', 'employee.employee_code']),
                Tables\Columns\TextColumn::make('period_start')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('period_end')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('net_pay')
                    ->money('idr', true)
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'draft',
                        'success' => 'issued',
                    ]),
                Tables\Columns\TextColumn::make('issued_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'issued' => 'Issued',
                    ]),
                Tables\Filters\SelectFilter::make('employee_id')
                    ->relationship('employee', 'full_name')
                    ->label('Employee'),
                Tables\Filters\Filter::make('period_start')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(fn ($query, $data) => $query
                        ->when($data['from'] ?? null, fn ($q, $v) => $q->whereDate('period_start', '>=', $v))
                        ->when($data['until'] ?? null, fn ($q, $v) => $q->whereDate('period_start', '<=', $v))),
                TrashedFilter::make(),
            ])
            ->actions([
                Actions\EditAction::make()
                    ->visible(fn () => static::canManage()),
                Actions\Action::make('issue')
                    ->label('Issue')
                    ->color('success')
                    ->icon('heroicon-o-check')
                    ->visible(fn (Payslip $record) => static::canManage() && $record->status === 'draft')
                    ->requiresConfirmation()
                    ->action(fn (Payslip $record) => $record->update([
                        'status' => 'issued',
                        'issued_at' => now(),
                    ])),
                Actions\DeleteAction::make()->visible(fn () => static::canManage()),
                Actions\RestoreAction::make()->visible(fn () => static::canManage()),
                Actions\ForceDeleteAction::make()->visible(fn () => static::canManage()),
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
            'index' => Pages\ListPayslips::route('/'),
            'create' => Pages\CreatePayslip::route('/create'),
            'edit' => Pages\EditPayslip::route('/{record}/edit'),
        ];
    }

    private static function calculateNetPay($basicSalary, Collection $allowances, Collection $deductions): float
    {
        $allowTotal = $allowances->sum(fn ($item) => (float) ($item['amount'] ?? 0));
        $deductTotal = $deductions->sum(fn ($item) => (float) ($item['amount'] ?? 0));

        return (float) ($basicSalary ?? 0) + $allowTotal - $deductTotal;
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
