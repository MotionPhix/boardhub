<?php

namespace Database\Seeders;

use App\Models\Settings;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
  public function run(): void
  {
    // Company Profile Settings
    Settings::set('company_profile', [
      'name' => 'BoardHub',
      'email' => 'info@boardhub.com',
      'phone' => null,
      'address' => null,
      'registration_number' => null,
      'tax_number' => null,
    ], 'company');

    // Currency Settings
    Settings::set('default_currency', [
      'code' => 'MWK',
      'symbol' => 'MK',
      'name' => 'Malawian Kwacha',
    ], 'currency');

    // Localization Settings
    Settings::set('localization', [
      'timezone' => 'Africa/Blantyre',
      'locale' => 'en',
      'date_format' => 'Y-m-d',
      'time_format' => 'H:i',
    ], 'system');

    // Document Settings
    Settings::set('document_settings', [
      'contract_footer_text' => 'For any queries regarding this contract, please contact us.',
      'default_payment_terms' => [
        [
          'days' => 30,
          'description' => 'Net 30',
        ],
        [
          'days' => 15,
          'description' => 'Net 15',
        ],
        [
          'days' => 7,
          'description' => 'Net 7',
        ],
      ],
      'default_contract_terms' => "1. Payment Terms\n2. Duration\n3. Responsibilities\n4. Termination Conditions",
    ], 'documents');

    // Billboard Code Settings
    Settings::set('billboard_code_format', [
      'prefix' => 'BH',
      'cities' => [
        ['code' => 'LL', 'name' => 'Lilongwe'],
        ['code' => 'BT', 'name' => 'Blantyre'],
        ['code' => 'MZ', 'name' => 'Mzuzu'],
        ['code' => 'ZA', 'name' => 'Zomba'],
      ],
      'separator' => '-',
      'counter_length' => 5,
    ], 'billboards');
  }
}
