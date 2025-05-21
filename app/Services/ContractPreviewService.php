<?php

namespace App\Services;

use App\Models\ContractTemplate;
use App\Models\Settings;
use Illuminate\Support\Facades\Storage;
use Spatie\Browsershot\Browsershot;
use Illuminate\Support\Str;

class ContractPreviewService
{
  public function generatePreview(ContractTemplate $template): string
  {
    $previewPath = 'contract-previews/' . Str::slug($template->name) . '-' . $template->id . '.pdf';

    // Generate a sample contract with dummy data
    $sampleData = $this->getSampleData();

    // Render the template with sample data
    $html = view('contracts.templates.' . $template->content, $sampleData)->render();

    // Generate PDF preview
    $tempPath = storage_path('app/temp/' . Str::random() . '.pdf');

    Browsershot::html($html)
      ->format('A4')
      ->margins(25, 25, 25, 25)
      ->showBackground()
      ->timeout(120)
      ->save($tempPath);

    // Convert first page to image
    $previewImagePath = 'public/contract-previews/' . Str::slug($template->name) . '-' . $template->id . '.png';

    Browsershot::html($html)
      ->format('A4')
      ->windowSize(1024, 1448)
      ->save(Storage::path($previewImagePath));

    // Store the PDF preview
    Storage::put($previewPath, file_get_contents($tempPath));

    // Clean up temp file
    unlink($tempPath);

    return Storage::url($previewImagePath);
  }

  private function getSampleData(): array
  {
    return [
      'contract' => new \stdClass([
        'contract_number' => 'SAMPLE-2025-001',
        'start_date' => now(),
        'end_date' => now()->addMonths(12),
        'contract_total' => 50000,
        'contract_discount' => 5000,
        'contract_final_amount' => 45000,
        'tax_rate' => 15,
        'tax_amount' => 6750,
        'currency' => new \stdClass([
          'symbol' => '$',
          'code' => 'USD',
          'name' => 'US Dollar'
        ]),
        'client' => new \stdClass([
          'name' => 'Sample Client Ltd',
          'company' => 'Sample Client Ltd',
          'street' => '123 Business Avenue',
          'city' => 'New York',
          'state' => 'NY',
          'country' => 'United States',
          'phone' => '+1 234 567 8900',
          'email' => 'contact@sampleclient.com'
        ]),
        'billboards' => collect([
          new \stdClass([
            'code' => 'BB-001',
            'size' => '14m x 6m',
            'dimensions' => '14m x 6m',
            'type' => 'Digital',
            'visibility_rating' => 'Premium',
            'name' => 'Times Square North',
            'location' => new \stdClass([
              'name' => 'Times Square',
              'city' => new \stdClass(['name' => 'New York']),
              'state' => new \stdClass(['name' => 'NY']),
              'country' => new \stdClass(['name' => 'United States'])
            ]),
            'pivot' => new \stdClass([
              'billboard_base_price' => 25000
            ])
          ]),
          new \stdClass([
            'code' => 'BB-002',
            'size' => '12m x 4m',
            'dimensions' => '12m x 4m',
            'type' => 'Static',
            'visibility_rating' => 'High',
            'name' => 'Broadway Central',
            'location' => new \stdClass([
              'name' => 'Broadway',
              'city' => new \stdClass(['name' => 'New York']),
              'state' => new \stdClass(['name' => 'NY']),
              'country' => new \stdClass(['name' => 'United States'])
            ]),
            'pivot' => new \stdClass([
              'billboard_base_price' => 20000
            ])
          ])
        ])
      ]),
      'settings' => app(Settings::class),
      'date' => now()->format('Y-m-d'),
      'generatedBy' => 'System'
    ];
  }
}
