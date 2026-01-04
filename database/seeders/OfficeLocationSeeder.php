<?php

namespace Database\Seeders;

use App\Models\OfficeLocation;
use Illuminate\Database\Seeder;

class OfficeLocationSeeder extends Seeder
{
    public function run(): void
    {
        OfficeLocation::firstOrCreate(
            ['name' => 'Kost Sakinah Putri Muslimah Sumbersari'],
            [
                'lat' => -8.1661093,
                'lng' => 113.7239617,
                'radius_m' => 200,
                'is_active' => true,
            ],
        );
    }
}
