<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Payslip;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class PayslipsSeeder extends Seeder
{
    public function run(): void
    {
        $employees = Employee::all();
        $start = now()->copy()->subMonth()->startOfMonth();
        $end = now()->copy()->subMonth()->endOfMonth();

        foreach ($employees as $employee) {
            $basicSalary = fake()->numberBetween(4_000_000, 7_000_000);
            $allowances = [
                ['label' => 'Transport', 'amount' => 500_000],
                ['label' => 'Makan', 'amount' => 300_000],
            ];
            $deductions = fake()->boolean(50)
                ? [['label' => 'Alpha', 'amount' => 200_000]]
                : [];

            $netPay = $basicSalary
                + collect($allowances)->sum('amount')
                - collect($deductions)->sum('amount');

            $status = fake()->randomElement(['draft', 'issued']);

            Payslip::updateOrCreate(
                [
                    'employee_id' => $employee->id,
                    'period_start' => $start->toDateString(),
                    'period_end' => $end->toDateString(),
                ],
                [
                    'basic_salary' => $basicSalary,
                    'allowances' => $allowances,
                    'deductions' => $deductions,
                    'net_pay' => $netPay,
                    'status' => $status,
                    'issued_at' => $status === 'issued' ? Carbon::now() : null,
                ],
            );
        }
    }
}
