<?php

namespace Database\Seeders;

use App\Models\Settings;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
  public function run(): void
  {
    // Company Profile Settings
    Settings::set(
      Settings::KEY_COMPANY_PROFILE,
      [
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
      Settings::GROUP_COMPANY
    );

    // Document Settings
    Settings::set(
      Settings::KEY_DOCUMENT_SETTINGS,
      [
        'default_contract_terms' => null,
        'contract_footer_text' => null,
      ],
      Settings::GROUP_DOCUMENTS
    );

    // Currency Settings
    Settings::set(
      Settings::KEY_CURRENCY_SETTINGS,
      [
        [
          'code' => 'MWK',
          'symbol' => 'MK',
          'name' => 'Malawian Kwacha',
          'is_default' => true,
        ]
      ],
      Settings::GROUP_SYSTEM
    );

    // Localization Settings
    Settings::set(
      Settings::KEY_LOCALIZATION,
      [
        'timezone' => 'Africa/Blantyre',
        'locale' => 'en',
        'date_format' => 'Y-m-d',
        'time_format' => 'H:i:s'
      ],
      Settings::GROUP_SYSTEM
    );

    // Billboard Code Format Settings
    Settings::set(
      Settings::KEY_BILLBOARD_CODE_FORMAT,
      [
        'prefix' => 'BH',
        'separator' => '-',
        'counter_length' => 5
      ],
      Settings::GROUP_BILLBOARDS
    );
  }
}
