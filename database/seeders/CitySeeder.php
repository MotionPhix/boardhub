<?php

namespace Database\Seeders;

use App\Models\City;
use Illuminate\Database\Seeder;

class CitySeeder extends Seeder
{
  public function run(): void
  {
    $cities = [
      [
        'name' => 'Blantyre',
        'code' => 'BT',
        'state' => 'Southern Region',
        'country_code' => 'MW',
      ],
      [
        'name' => 'Lilongwe',
        'code' => 'LL',
        'state' => 'Central Region',
        'country_code' => 'MW',
      ],
      [
        'name' => 'Mzuzu',
        'code' => 'MZ',
        'state' => 'Northern Region',
        'country_code' => 'MW',
      ],
    ];

    foreach ($cities as $city) {
      City::create($city);
    }
  }
}
