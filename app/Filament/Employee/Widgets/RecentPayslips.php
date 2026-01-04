<?php

namespace App\Filament\Employee\Widgets;

use App\Domain\Payroll\Models\Payslip;
use Filament\Tables;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class RecentPayslips extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    protected function getTableQuery(): Builder
    {
        $employeeId = $this->employeeId();

        if (! $employeeId) {
            return Payslip::query()->whereRaw('1 = 0');
        }

        $twoMonthsAgo = Carbon::now($this->timezone())->subMonths(2)->startOfDay();

        return Payslip::query()
            ->where('employee_id', $employeeId)
            ->where('status', 'issued')
            ->whereDate('period_start', '>=', $twoMonthsAgo)
            ->orderByDesc('period_start');
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('period_start')
                ->label('Periode Mulai')
                ->date(),
            Tables\Columns\TextColumn::make('period_end')
                ->label('Periode Selesai')
                ->date(),
            Tables\Columns\TextColumn::make('net_pay')
                ->label('Net Pay')
                ->money('idr', true),
            Tables\Columns\TextColumn::make('issued_at')
                ->label('Issued')
                ->dateTime(),
        ];
    }

    protected function getTableHeading(): ?string
    {
        return 'Payslip (2 bulan terakhir)';
    }

    protected function employeeId(): ?int
    {
        return auth()->user()?->employee?->id;
    }

    protected function timezone(): string
    {
        return 'Asia/Jakarta';
    }
}
