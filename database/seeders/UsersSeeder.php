<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::where('name', 'admin')->firstOrFail();
        $employeeRole = Role::where('name', 'employee')->firstOrFail();

        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin HRIS',
                'password' => Hash::make('12345678'),
                'is_admin' => true,
            ],
        );
        $admin->syncRoles([$adminRole]);

        foreach (range(1, 5) as $index) {
            $user = User::firstOrCreate(
                ['email' => "employee{$index}@example.com"],
                [
                    'name' => "Employee {$index}",
                    'password' => Hash::make('password123'),
                    'is_admin' => false,
                ],
            );
            $user->syncRoles([$employeeRole]);
        }
    }
}
