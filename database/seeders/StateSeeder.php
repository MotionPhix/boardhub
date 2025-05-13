<?php

namespace Database\Seeders;

use App\Models\State;
use Illuminate\Database\Seeder;

class StateSeeder extends Seeder
{
  public function run(): void
  {
    $states = [
      // Malawi
      [
        'code' => 'MW-N',
        'name' => 'Northern Region',
        'country_code' => 'MW',
      ],
      [
        'code' => 'MW-C',
        'name' => 'Central Region',
        'country_code' => 'MW',
      ],
      [
        'code' => 'MW-S',
        'name' => 'Southern Region',
        'country_code' => 'MW',
      ],
      // Zambia
      [
        'code' => 'ZM-01',
        'name' => 'Western Province',
        'country_code' => 'ZM',
      ],
      [
        'code' => 'ZM-02',
        'name' => 'Central Province',
        'country_code' => 'ZM',
      ],
      [
        'code' => 'ZM-03',
        'name' => 'Eastern Province',
        'country_code' => 'ZM',
      ],
      // Zimbabwe
      [
        'code' => 'ZW-BU',
        'name' => 'Bulawayo',
        'country_code' => 'ZW',
      ],
      [
        'code' => 'ZW-HA',
        'name' => 'Harare',
        'country_code' => 'ZW',
      ],
      [
        'code' => 'ZW-MA',
        'name' => 'Manicaland',
        'country_code' => 'ZW',
      ],
    ];

    foreach ($states as $state) {
      State::create($state);
    }
  }
}
