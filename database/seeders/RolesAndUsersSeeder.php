<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class RolesAndUsersSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $managerRole = Role::firstOrCreate(['name' => 'manager']);
        $ownerRole = Role::firstOrCreate(['name' => 'owner']);

        $admin = User::firstOrCreate(
            ['email' => 'admin@hris.test'],
            [
                'name' => 'Admin HRIS',
                'password' => Hash::make('password123'),
                'is_admin' => true,
            ],
        );
        $admin->syncRoles([$adminRole]);

        $manager = User::firstOrCreate(
            ['email' => 'manager@hris.test'],
            [
                'name' => 'Manager HRIS',
                'password' => Hash::make('password123'),
                'is_admin' => false,
            ],
        );
        $manager->syncRoles([$managerRole]);

        $owner = User::firstOrCreate(
            ['email' => 'owner@hris.test'],
            [
                'name' => 'Owner HRIS',
                'password' => Hash::make('password123'),
                'is_admin' => false,
            ],
        );
        $owner->syncRoles([$ownerRole]);
    }
}
