<?php

namespace App\Filament\Pages;

use App\Models\Attendance;
use App\Models\Employee;
use Filament\Forms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Actions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Response;
use UnitEnum;
use BackedEnum;

class AttendanceReport extends Page implements HasForms, HasTable
{
    use Forms\Concerns\InteractsWithForms;
    use Tables\Concerns\InteractsWithTable;

    protected static string | UnitEnum | null $navigationGroup = 'HRIS';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $title = 'Attendance Report';

    protected string $view = 'filament.pages.reports.attendance-report';

    public array $filters = [];

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public function mount(): void
    {
        $this->form->fill([
            'date_from' => now()->subDays(7)->toDateString(),
            'date_to' => now()->toDateString(),
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
                        'HADIR' => 'Hadir',
                        'TELAT' => 'Telat',
                        'IZIN' => 'Izin',
                        'SAKIT' => 'Sakit',
                        'ALPHA' => 'Alpha',
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

        return Attendance::query()
            ->with('employee')
            ->when($filters['date_from'] ?? null, fn (Builder $q, $from) => $q->whereDate('work_date', '>=', $from))
            ->when($filters['date_to'] ?? null, fn (Builder $q, $to) => $q->whereDate('work_date', '<=', $to))
            ->when($filters['status'] ?? null, fn (Builder $q, $status) => $q->where('status', $status))
            ->when($filters['department'] ?? null, function (Builder $q, $dept) {
                $q->whereHas('employee', fn (Builder $e) => $e->where('department', $dept));
            })
            ->orderBy('work_date', 'desc');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('work_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('employee.employee_code')
                    ->label('Employee Code')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('employee.full_name')
                    ->label('Full Name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('employee.department')
                    ->label('Department')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'HADIR',
                        'warning' => 'TELAT',
                        'info' => ['IZIN', 'SAKIT'],
                        'danger' => 'ALPHA',
                    ]),
                Tables\Columns\TextColumn::make('check_in_at')
                    ->label('Check In')
                    ->since()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('check_out_at')
                    ->label('Check Out')
                    ->since()
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
        $hadir = (clone $query)->where('status', 'HADIR')->count();
        $telat = (clone $query)->where('status', 'TELAT')->count();
        $alpha = (clone $query)->where('status', 'ALPHA')->count();

        return [
            'total' => $total,
            'hadir' => $hadir,
            'telat' => $telat,
            'alpha' => $alpha,
        ];
    }

    public function exportCsv()
    {
        $records = $this->getTableQuery()->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="attendance-report.csv"',
        ];

        $callback = function () use ($records) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['work_date', 'employee_code', 'full_name', 'department', 'status', 'check_in_at', 'check_out_at']);

            foreach ($records as $row) {
                fputcsv($handle, [
                    $row->work_date,
                    $row->employee?->employee_code,
                    $row->employee?->full_name,
                    $row->employee?->department,
                    $row->status,
                    optional($row->check_in_at)->toDateTimeString(),
                    optional($row->check_out_at)->toDateTimeString(),
                ]);
            }

            fclose($handle);
        };

        return Response::stream($callback, 200, $headers);
    }
}
