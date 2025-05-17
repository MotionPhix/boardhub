<?php

namespace Database\Seeders;

use App\Models\Currency;
use App\Models\Settings;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
  public function run(): void
  {
    // Create default settings
    Settings::create([
      'company_name' => 'Your Company Name',
      'company_email' => 'info@company.com',
      'timezone' => 'Africa/Blantyre',
      'locale' => 'en',
      'date_format' => 'Y-m-d',
      'time_format' => 'H:i:s',
      'billboard_code_prefix' => 'BH',
      'billboard_code_separator' => '-',
      'billboard_code_counter_length' => 5,
    ]);

    // Create default currency
    Currency::create([
      'code' => 'MWK',
      'symbol' => 'MK',
      'name' => 'Malawian Kwacha',
      'is_default' => true,
    ]);
  }
}
