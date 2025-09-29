<?php

namespace Database\Seeders;

use App\Models\City;
use Illuminate\Database\Seeder;

class CitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cities = [
            // Central Region cities
            [
                'name' => 'Lilongwe',
                'code' => 'LL',
                'state_code' => 'C',
                'country_code' => 'MW',
                'is_active' => true,
            ],
            [
                'name' => 'Kasungu',
                'code' => 'KS',
                'state_code' => 'C',
                'country_code' => 'MW',
                'is_active' => true,
            ],
            [
                'name' => 'Salima',
                'code' => 'SL',
                'state_code' => 'C',
                'country_code' => 'MW',
                'is_active' => true,
            ],
            // Southern Region cities
            [
                'name' => 'Blantyre',
                'code' => 'BT',
                'state_code' => 'S',
                'country_code' => 'MW',
                'is_active' => true,
            ],
            [
                'name' => 'Zomba',
                'code' => 'ZB',
                'state_code' => 'S',
                'country_code' => 'MW',
                'is_active' => true,
            ],
            [
                'name' => 'Chiradzulu',
                'code' => 'CH',
                'state_code' => 'S',
                'country_code' => 'MW',
                'is_active' => true,
            ],
            [
                'name' => 'Karonga',
                'code' => 'KR',
                'state_code' => 'S',
                'country_code' => 'MW',
                'is_active' => true,
            ],
            // Northern Region cities
            [
                'name' => 'Mzuzu',
                'code' => 'MZ',
                'state_code' => 'N',
                'country_code' => 'MW',
                'is_active' => true,
            ],
            [
                'name' => 'Rumphi',
                'code' => 'RP',
                'state_code' => 'N',
                'country_code' => 'MW',
                'is_active' => true,
            ],
            [
                'name' => 'Nkhata Bay',
                'code' => 'NB',
                'state_code' => 'N',
                'country_code' => 'MW',
                'is_active' => true,
            ],
            // Other countries
            [
                'name' => 'Lusaka',
                'code' => 'LK',
                'state_code' => 'LS',
                'country_code' => 'ZM',
                'is_active' => true,
            ],
            [
                'name' => 'Ndola',
                'code' => 'ND',
                'state_code' => 'CP',
                'country_code' => 'ZM',
                'is_active' => true,
            ],
            [
                'name' => 'Harare',
                'code' => 'HR',
                'state_code' => 'HR',
                'country_code' => 'ZW',
                'is_active' => true,
            ],
            [
                'name' => 'Maputo',
                'code' => 'MP',
                'state_code' => 'MP',
                'country_code' => 'MZ',
                'is_active' => true,
            ],
            [
                'name' => 'Dar es Salaam',
                'code' => 'DS',
                'state_code' => 'DR',
                'country_code' => 'TZ',
                'is_active' => true,
            ],
        ];

        foreach ($cities as $city) {
            City::firstOrCreate(
                ['code' => $city['code'], 'country_code' => $city['country_code']],
                $city
            );
        }
    }
}
