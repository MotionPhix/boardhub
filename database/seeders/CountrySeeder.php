<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
  public function run(): void
  {
    $countries = [
      ['code' => 'MW', 'name' => 'Malawi', 'is_default' => true],
      ['code' => 'ZM', 'name' => 'Zambia'],
      ['code' => 'ZW', 'name' => 'Zimbabwe'],
      ['code' => 'MZ', 'name' => 'Mozambique'],
      ['code' => 'TZ', 'name' => 'Tanzania'],
      ['code' => 'ZA', 'name' => 'South Africa'],
    ];

    foreach ($countries as $country) {
      Country::create($country);
    }
  }
}
