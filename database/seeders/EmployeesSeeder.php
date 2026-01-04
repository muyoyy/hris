<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class EmployeesSeeder extends Seeder
{
    public function run(): void
    {
        $departments = ['IT', 'HR', 'Finance', 'Operations'];
        $positions = ['Staff', 'Supervisor'];
        $statuses = collect(['active', 'active', 'active', 'inactive']);

        foreach (range(1, 5) as $index) {
            $user = User::where('email', "employee{$index}@example.com")->first();

            Employee::firstOrCreate(
                ['employee_code' => sprintf('EMP-%04d', $index)],
                [
                    'user_id' => $user?->id,
                    'full_name' => "Employee {$index}",
                    'email' => $user?->email,
                    'phone' => '08' . fake()->numerify('##########'),
                    'department' => fake()->randomElement($departments),
                    'position' => fake()->randomElement($positions),
                    'hired_at' => Carbon::createFromDate(fake()->numberBetween(2023, 2025), fake()->numberBetween(1, 12), fake()->numberBetween(1, 28)),
                    'status' => $statuses->random(),
                ],
            );
        }
    }
}
