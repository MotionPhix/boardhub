<?php

namespace Database\Seeders;

use App\Models\State;
use Illuminate\Database\Seeder;

class StateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $states = [
            // Malawi regions
            [
                'code' => 'C',
                'name' => 'Central Region',
                'country_code' => 'MW',
                'is_active' => true,
            ],
            [
                'code' => 'S',
                'name' => 'Southern Region',
                'country_code' => 'MW',
                'is_active' => true,
            ],
            [
                'code' => 'N',
                'name' => 'Northern Region',
                'country_code' => 'MW',
                'is_active' => true,
            ],
            // Other countries
            [
                'code' => 'CP',
                'name' => 'Copperbelt',
                'country_code' => 'ZM',
                'is_active' => true,
            ],
            [
                'code' => 'LS',
                'name' => 'Lusaka',
                'country_code' => 'ZM',
                'is_active' => true,
            ],
            [
                'code' => 'HR',
                'name' => 'Harare',
                'country_code' => 'ZW',
                'is_active' => true,
            ],
            [
                'code' => 'MP',
                'name' => 'Maputo',
                'country_code' => 'MZ',
                'is_active' => true,
            ],
            [
                'code' => 'DR',
                'name' => 'Dar es Salaam',
                'country_code' => 'TZ',
                'is_active' => true,
            ],
        ];

        foreach ($states as $state) {
            State::firstOrCreate(
                ['code' => $state['code'], 'country_code' => $state['country_code']],
                $state
            );
        }
    }
}
