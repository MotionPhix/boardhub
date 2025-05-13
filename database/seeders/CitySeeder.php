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
        'state_code' => 'MW-S',
        'country_code' => 'MW',
      ],
      [
        'name' => 'Lilongwe',
        'code' => 'LL',
        'state_code' => 'MW-C',
        'country_code' => 'MW',
      ],
      [
        'name' => 'Mzuzu',
        'code' => 'MZ',
        'state_code' => 'MW-N',
        'country_code' => 'MW',
      ],
    ];

    foreach ($cities as $city) {
      City::create($city);
    }
  }
}
