<?php

namespace Database\Seeders;

use App\Models\Settings;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
  public function run(): void
  {
    Settings::set('default_currency', [
      'code' => 'MWK',
      'symbol' => 'MK',
      'name' => 'Malawian Kwacha',
    ], 'currency');

    Settings::set('timezone', 'Africa/Blantyre', 'system');
  }
}
