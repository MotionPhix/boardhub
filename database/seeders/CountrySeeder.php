<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $countries = [
            [
                'code' => 'MW',
                'name' => 'Malawi',
                'is_active' => true,
                'is_default' => true,
            ],
            [
                'code' => 'ZM',
                'name' => 'Zambia',
                'is_active' => true,
                'is_default' => false,
            ],
            [
                'code' => 'ZW',
                'name' => 'Zimbabwe',
                'is_active' => true,
                'is_default' => false,
            ],
            [
                'code' => 'MZ',
                'name' => 'Mozambique',
                'is_active' => true,
                'is_default' => false,
            ],
            [
                'code' => 'TZ',
                'name' => 'Tanzania',
                'is_active' => true,
                'is_default' => false,
            ],
        ];

        foreach ($countries as $country) {
            Country::firstOrCreate(
                ['code' => $country['code']],
                $country
            );
        }
    }
}
