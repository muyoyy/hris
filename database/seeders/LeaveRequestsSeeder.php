<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class LeaveRequestsSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@example.com')->first();
        $employees = Employee::inRandomOrder()->take(5)->get();
        $types = ['IZIN', 'SAKIT'];
        $statuses = ['PENDING', 'APPROVED', 'REJECTED'];

        foreach ($employees as $employee) {
            $start = Carbon::now()->subDays(fake()->numberBetween(1, 20))->startOfDay();
            $end = (clone $start)->addDays(fake()->numberBetween(0, 2));
            $status = fake()->randomElement($statuses);

            LeaveRequest::create([
                'employee_id' => $employee->id,
                'type' => fake()->randomElement($types),
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'reason' => fake()->sentence(),
                'status' => $status,
                'approver_id' => $status === 'PENDING' ? null : $admin?->id,
                'admin_note' => $status === 'REJECTED' ? fake()->sentence() : null,
            ]);
        }
    }
}
