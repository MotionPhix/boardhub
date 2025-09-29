<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // Reference data first (order matters due to foreign keys)
            CountrySeeder::class,
            StateSeeder::class,
            CitySeeder::class,

            // Application data
            TenantSeeder::class,
            UserSeeder::class,
        ]);
    }
}
