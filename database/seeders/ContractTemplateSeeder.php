<?php

namespace Database\Seeders;

use App\Models\ContractTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class ContractTemplateSeeder extends Seeder
{
  public function run(): void
  {
    $templatesPath = resource_path('views/contracts/templates');

    // Standard Template
    ContractTemplate::create([
      'name' => 'Standard Advertising Agreement',
      'description' => 'Our comprehensive standard advertising rental agreement with full terms and conditions.',
      'content' => 'standard/advertising-agreement',
      'template_type' => 'standard',
      'is_default' => true,
      'is_active' => true,
      'variables' => [
        [
          'name' => 'contract_number',
          'description' => 'Unique contract reference number'
        ],
        [
          'name' => 'client_name',
          'description' => 'Client\'s full name'
        ],
        [
          'name' => 'client_company',
          'description' => 'Client\'s company name'
        ],
        [
          'name' => 'start_date',
          'description' => 'Contract start date'
        ],
        [
          'name' => 'end_date',
          'description' => 'Contract end date'
        ],
        [
          'name' => 'total_amount',
          'description' => 'Total contract amount'
        ],
        [
          'name' => 'currency',
          'description' => 'Contract currency'
        ],
        [
          'name' => 'billboards',
          'description' => 'List of billboards included in the contract'
        ]
      ],
      'settings' => [
        'header_enabled' => true,
        'footer_enabled' => true,
        'page_numbering' => true,
        'table_of_contents' => true,
        'terms_sections' => [
          'payment',
          'maintenance',
          'liability',
          'termination',
          'disputes'
        ]
      ]
    ]);

    // Premium Template
    ContractTemplate::create([
      'name' => 'Premium Advertising Agreement',
      'description' => 'Enhanced agreement template with additional legal protections and detailed terms.',
      'content' => 'premium/advertising-agreement',
      'template_type' => 'premium',
      'is_default' => false,
      'is_active' => true,
      'variables' => [
        [
          'name' => 'contract_number',
          'description' => 'Unique contract reference number'
        ],
        [
          'name' => 'client_name',
          'description' => 'Client\'s full name'
        ],
        [
          'name' => 'client_company',
          'description' => 'Client\'s company name'
        ],
        [
          'name' => 'start_date',
          'description' => 'Contract start date'
        ],
        [
          'name' => 'end_date',
          'description' => 'Contract end date'
        ],
        [
          'name' => 'total_amount',
          'description' => 'Total contract amount'
        ],
        [
          'name' => 'currency',
          'description' => 'Contract currency'
        ],
        [
          'name' => 'billboards',
          'description' => 'List of billboards included in the contract'
        ],
        [
          'name' => 'special_terms',
          'description' => 'Any special terms or conditions'
        ]
      ],
      'settings' => [
        'header_enabled' => true,
        'footer_enabled' => true,
        'page_numbering' => true,
        'table_of_contents' => true,
        'terms_sections' => [
          'payment',
          'maintenance',
          'liability',
          'termination',
          'disputes',
          'confidentiality',
          'intellectual_property',
          'force_majeure',
          'insurance'
        ]
      ]
    ]);
  }
}
