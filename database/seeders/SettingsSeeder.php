<?php

namespace Database\Seeders;

use App\Models\Settings;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
  public function run(): void
  {
    // Company Profile Settings
    Settings::updateOrCreate(
      ['key' => 'company_profile'],
      [
        'value' => [
          'name' => 'Your Company Name',
          'email' => 'info@company.com',
          'phone' => null,
          'address' => [
            'street' => null,
            'city' => null,
            'state' => null,
            'country' => null,
          ],
          'registration_number' => null,
          'tax_number' => null,
        ],
        'group' => 'company'
      ]
    );

    // Document Settings
    Settings::updateOrCreate(
      ['key' => 'document_settings'],
      [
        'value' => [
          'default_contract_terms' => null,
          'contract_footer_text' => null,
        ],
        'group' => 'documents'
      ]
    );

    // Currency Settings
    Settings::updateOrCreate(
      ['key' => 'currency_settings'],
      [
        'value' => [
          [
            'code' => 'MWK',
            'symbol' => 'MK',
            'name' => 'Malawian Kwacha',
            'is_default' => true,
          ]
        ],
        'group' => 'system'
      ]
    );

    // Localization Settings
    Settings::updateOrCreate(
      ['key' => 'localization'],
      [
        'value' => [
          'timezone' => 'Africa/Blantyre',
          'locale' => 'en',
          'date_format' => 'Y-m-d',
          'time_format' => 'H:i:s'
        ],
        'group' => 'system'
      ]
    );

    // Billboard Code Format Settings
    Settings::updateOrCreate(
      ['key' => 'billboard_code_format'],
      [
        'value' => [
          'prefix' => 'BH',
          'separator' => '-',
          'counter_length' => 5
        ],
        'group' => 'billboards'
      ]
    );
  }
}
