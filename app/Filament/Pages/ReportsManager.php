<?php

namespace App\Filament\Pages;

use App\Domain\Attendance\Models\Attendance;
use App\Domain\Employee\Models\Employee;
use App\Domain\Leave\Models\LeaveRequest;
use App\Domain\Payroll\Models\Payslip;
use Filament\Forms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Pages\Page;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;
use BackedEnum;
use UnitEnum;

class ReportsManager extends Page implements HasForms
{
    use InteractsWithForms;
    use InteractsWithFormActions;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Reports (Manager)';

    protected static ?string $title = 'Reports (Manager)';

    protected static string|UnitEnum|null $navigationGroup = 'Reports';

    protected string $view = 'filament.pages.reports-manager';

    public ?array $filters = [];

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasAnyRole(['admin', 'manager', 'owner']) ?? false;
    }

    public function mount(): void
    {
        $now = Carbon::now($this->timezone());
        $this->form->fill([
            'start_date' => $now->copy()->startOfMonth()->toDateString(),
            'end_date' => $now->toDateString(),
            'department' => null,
        ]);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->statePath('filters')
            ->schema([
                Forms\Components\DatePicker::make('start_date')
                    ->label('Start Date')
                    ->required(),
                Forms\Components\DatePicker::make('end_date')
                    ->label('End Date')
                    ->required()
                    ->afterOrEqual('start_date'),
                Forms\Components\Select::make('department')
                    ->label('Department')
                    ->options(fn () => Employee::query()
                        ->whereNotNull('department')
                        ->distinct()
                        ->orderBy('department')
                        ->pluck('department', 'department')
                        ->toArray())
                    ->searchable()
                    ->placeholder('All'),
            ])
            ->columns(3);
    }

    public function getAttendanceSummary(): array
    {
        [$start, $end, $department] = $this->range();

        $query = Attendance::query()
            ->whereBetween('work_date', [$start, $end])
            ->whereHas('employee');

        if ($department) {
            $query->whereHas('employee', fn ($q) => $q->where('department', $department));
        }

        $statuses = ['HADIR', 'TELAT', 'ALPHA', 'IZIN', 'SAKIT'];

        return collect($statuses)
            ->mapWithKeys(fn ($status) => [$status => (clone $query)->where('status', $status)->count()])
            ->toArray();
    }

    public function getLeaveSummary(): array
    {
        [$start, $end, $department] = $this->range();

        $query = LeaveRequest::query()
            ->whereBetween('start_date', [$start, $end])
            ->whereHas('employee');

        if ($department) {
            $query->whereHas('employee', fn ($q) => $q->where('department', $department));
        }

        $statuses = ['PENDING', 'APPROVED', 'REJECTED'];

        return collect($statuses)
            ->mapWithKeys(fn ($status) => [$status => (clone $query)->where('status', $status)->count()])
            ->toArray();
    }

    public function getPayslipSummary(): array
    {
        [$start, $end, $department] = $this->range();

        $query = Payslip::query()
            ->where('status', 'issued')
            ->whereDate('period_start', '<=', $end)
            ->whereDate('period_end', '>=', $start)
            ->whereHas('employee');

        if ($department) {
            $query->whereHas('employee', fn ($q) => $q->where('department', $department));
        }

        return [
            'count' => $query->count(),
            'net_pay_sum' => $query->sum('net_pay'),
        ];
    }

    protected function range(): array
    {
        $start = Carbon::parse($this->filters['start_date'] ?? Carbon::now($this->timezone())->startOfMonth())->startOfDay();
        $end = Carbon::parse($this->filters['end_date'] ?? Carbon::now($this->timezone()))->endOfDay();
        $department = $this->filters['department'] ?? null;

        return [$start, $end, $department];
    }

    protected function timezone(): string
    {
        return 'Asia/Jakarta';
    }
}
