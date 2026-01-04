<?php

namespace App\Filament\Pages;

use App\Models\Employee;
use App\Models\LeaveRequest;
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

class LeaveReport extends Page implements HasForms, HasTable
{
    use Forms\Concerns\InteractsWithForms;
    use Tables\Concerns\InteractsWithTable;

    protected static string | UnitEnum | null $navigationGroup = 'HRIS';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-briefcase';

    protected static ?string $title = 'Leave Requests Report';

    protected string $view = 'filament.pages.reports.leave-report';

    public array $filters = [];

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public function mount(): void
    {
        $this->form->fill([
            'date_from' => now()->subDays(30)->toDateString(),
            'date_to' => now()->toDateString(),
            'status' => null,
            'type' => null,
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
                        'PENDING' => 'Pending',
                        'APPROVED' => 'Approved',
                        'REJECTED' => 'Rejected',
                    ])
                    ->placeholder('All Status'),
                Forms\Components\Select::make('type')
                    ->options([
                        'IZIN' => 'Izin',
                        'SAKIT' => 'Sakit',
                    ])
                    ->placeholder('All Types'),
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
                'md' => 5,
            ])->live();
    }

    protected function getTableQuery(): Builder
    {
        $filters = $this->filters;

        return LeaveRequest::query()
            ->with(['employee', 'approver'])
            ->when($filters['date_from'] ?? null, fn (Builder $q, $from) => $q->whereDate('start_date', '>=', $from))
            ->when($filters['date_to'] ?? null, fn (Builder $q, $to) => $q->whereDate('start_date', '<=', $to))
            ->when($filters['status'] ?? null, fn (Builder $q, $status) => $q->where('status', $status))
            ->when($filters['type'] ?? null, fn (Builder $q, $type) => $q->where('type', $type))
            ->when($filters['department'] ?? null, function (Builder $q, $dept) {
                $q->whereHas('employee', fn (Builder $e) => $e->where('department', $dept));
            })
            ->orderBy('start_date', 'desc');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('start_date')->date()->sortable(),
                Tables\Columns\TextColumn::make('end_date')->date()->sortable(),
                Tables\Columns\TextColumn::make('employee.employee_code')->label('Employee Code')->toggleable(),
                Tables\Columns\TextColumn::make('employee.full_name')->label('Full Name')->searchable(),
                Tables\Columns\TextColumn::make('employee.department')->label('Department')->toggleable(),
                Tables\Columns\BadgeColumn::make('type'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'PENDING',
                        'success' => 'APPROVED',
                        'danger' => 'REJECTED',
                    ]),
                Tables\Columns\TextColumn::make('approver.name')
                    ->label('Approver')
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
        $pending = (clone $query)->where('status', 'PENDING')->count();
        $approved = (clone $query)->where('status', 'APPROVED')->count();
        $rejected = (clone $query)->where('status', 'REJECTED')->count();

        return [
            'pending' => $pending,
            'approved' => $approved,
            'rejected' => $rejected,
        ];
    }

    public function exportCsv()
    {
        $records = $this->getTableQuery()->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="leave-report.csv"',
        ];

        $callback = function () use ($records) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['start_date', 'end_date', 'employee_code', 'full_name', 'department', 'type', 'status', 'approver']);

            foreach ($records as $row) {
                fputcsv($handle, [
                    $row->start_date,
                    $row->end_date,
                    $row->employee?->employee_code,
                    $row->employee?->full_name,
                    $row->employee?->department,
                    $row->type,
                    $row->status,
                    $row->approver?->name,
                ]);
            }

            fclose($handle);
        };

        return Response::stream($callback, 200, $headers);
    }
}
