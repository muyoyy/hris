<?php

namespace App\Filament\Pages;

use App\Models\Employee;
use App\Models\Payslip;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Response;
use UnitEnum;
use BackedEnum;

class PayslipReport extends Page implements HasForms, HasTable
{
    use Forms\Concerns\InteractsWithForms;
    use Tables\Concerns\InteractsWithTable;

    protected static string | UnitEnum | null $navigationGroup = 'HRIS';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $title = 'Payslip Report';

    protected string $view = 'filament.pages.reports.payslip-report';

    public array $filters = [];

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public function mount(): void
    {
        $this->form->fill([
            'date_from' => now()->subMonths(2)->startOfMonth()->toDateString(),
            'date_to' => now()->endOfMonth()->toDateString(),
            'status' => null,
            'department' => null,
        ]);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->statePath('filters')
            ->schema([
                Forms\Components\DatePicker::make('date_from')
                    ->label('Start Date')
                    ->required(),
                Forms\Components\DatePicker::make('date_to')
                    ->label('End Date')
                    ->required(),
                Forms\Components\Select::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'issued' => 'Issued',
                    ])
                    ->placeholder('All Status'),
                Forms\Components\Select::make('department')
                    ->options(
                        Employee::query()
                            ->whereNotNull('department')
                            ->distinct()
                            ->orderBy('department')
                            ->pluck('department', 'department')
                            ->toArray(),
                    )
                    ->placeholder('All Departments'),
            ])->columns([
                'default' => 2,
                'md' => 4,
            ])->live();
    }

    protected function getTableQuery(): Builder
    {
        $filters = $this->filters;

        return Payslip::query()
            ->with('employee')
            ->when($filters['date_from'] ?? null, fn (Builder $q, $from) => $q->whereDate('period_start', '>=', $from))
            ->when($filters['date_to'] ?? null, fn (Builder $q, $to) => $q->whereDate('period_start', '<=', $to))
            ->when($filters['status'] ?? null, fn (Builder $q, $status) => $q->where('status', $status))
            ->when($filters['department'] ?? null, function (Builder $q, $dept) {
                $q->whereHas('employee', fn (Builder $e) => $e->where('department', $dept));
            })
            ->orderBy('period_start', 'desc');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('period_start')->date()->sortable(),
                Tables\Columns\TextColumn::make('period_end')->date()->sortable(),
                Tables\Columns\TextColumn::make('employee.employee_code')->label('Employee Code')->toggleable(),
                Tables\Columns\TextColumn::make('employee.full_name')->label('Full Name')->searchable(),
                Tables\Columns\TextColumn::make('employee.department')->label('Department')->toggleable(),
                Tables\Columns\TextColumn::make('net_pay')->money('idr', true)->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'draft',
                        'success' => 'issued',
                    ]),
                Tables\Columns\TextColumn::make('issued_at')
                    ->dateTime()
                    ->toggleable(),
            ])
            ->paginated(false)
            ->striped()
            ->headerActions([
                Actions\Action::make('export_csv')
                    ->label('Export CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(fn () => $this->exportCsv()),
            ]);
    }

    public function getSummary(): array
    {
        $query = $this->getTableQuery();
        $total = (clone $query)->count();
        $totalNet = (clone $query)->sum('net_pay');
        $issued = (clone $query)->where('status', 'issued')->count();

        return [
            'total' => $total,
            'net_pay' => $totalNet,
            'issued' => $issued,
        ];
    }

    public function exportCsv()
    {
        $records = $this->getTableQuery()->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="payslip-report.csv"',
        ];

        $callback = function () use ($records) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['period_start', 'period_end', 'employee_code', 'full_name', 'department', 'net_pay', 'status', 'issued_at']);

            foreach ($records as $row) {
                fputcsv($handle, [
                    $row->period_start,
                    $row->period_end,
                    $row->employee?->employee_code,
                    $row->employee?->full_name,
                    $row->employee?->department,
                    $row->net_pay,
                    $row->status,
                    optional($row->issued_at)?->toDateTimeString(),
                ]);
            }

            fclose($handle);
        };

        return Response::stream($callback, 200, $headers);
    }
}
