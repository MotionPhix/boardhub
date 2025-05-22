<?php

namespace App\Services;

use App\Models\ContractTemplate;
use App\Models\Settings;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Beganovich\Snappdf\Snappdf;
use Illuminate\Support\Facades\File;

class ContractPreviewService
{
  public function generatePreview(ContractTemplate $template): string
  {
    // Generate paths
    $previewPath = 'contract-previews/' . Str::slug($template->name) . '-' . $template->id . '.pdf';
    //$previewImagePath = 'public/contract-previews/' . Str::slug($template->name) . '-' . $template->id . '.png';

    // Create directories if they don't exist
    Storage::makeDirectory('contract-previews');
    Storage::makeDirectory('public/contract-previews');

    // Generate a sample contract with dummy data
    $sampleData = $this->getSampleData();

    // Render the template with sample data
    $html = view('contracts.templates.' . $template->content, $sampleData)->render();

    try {
      // Initialize Snappdf
      $snappdf = new Snappdf();

      // Generate PDF
      $pdf = $snappdf
        ->setHtml($html)
        //->setOption('format', 'A4')
        /*->setOption('margin', [
          'top' => '25mm',
          'right' => '25mm',
          'bottom' => '25mm',
          'left' => '25mm',
        ])*/
        ->generate();

      // Save PDF
      return Storage::put($previewPath, $pdf);

      /*// Generate PNG preview (first page only)
      $png = $snappdf
        ->setHtml($html)
        /*->setOption('format', 'A4')
        ->setOption('margin', [
          'top' => '25mm',
          'right' => '25mm',
          'bottom' => '25mm',
          'left' => '25mm',
        ])
        ->setOption('clip', [
          'x' => 0,
          'y' => 0,
          'width' => 800,
          'height' => 1132, // Approximate A4 ratio at 800px width
        ])
        ->generate();

      // Save PNG
      Storage::put($previewImagePath, $png);

      return Storage::url($previewImagePath);*/
    } catch (\Exception $e) {
      Log::error('Contract preview generation failed: ' . $e->getMessage(), [
        'template_id' => $template->id,
        'exception' => $e,
      ]);

      throw $e;
    }
  }

  /*private function getSampleData(): array
  {
    $now = now();

    return [
      'contract' => new \stdClass([
        'contract_number' => 'SAMPLE-' . $now->format('Y') . '-001',
        'start_date' => $now,
        'end_date' => $now->addMonths(12),
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
      'date' => $now->format('Y-m-d'),
      'generatedBy' => 'MotionPhix'
    ];
  }*/

  private function getSampleData(): array
  {
    $contract = new \stdClass();

    // Add timestamps
    $contract->created_at = now()->setDateTime(2025, 5, 22, 3, 57, 36);
    $contract->updated_at = now()->setDateTime(2025, 5, 22, 3, 57, 36);

    $contract->contract_number = 'SAMPLE-2025-001';
    $contract->start_date = now()->setDateTime(2025, 5, 22, 3, 57, 36);
    $contract->end_date = now()->setDateTime(2025, 5, 22, 3, 57, 36)->addMonths(12);
    $contract->contract_total = 50000;
    $contract->contract_discount = 5000;
    $contract->contract_final_amount = 45000;
    $contract->tax_rate = 15;
    $contract->tax_amount = 6750;

    // Currency details
    $contract->currency = new \stdClass();
    $contract->currency->symbol = '$';
    $contract->currency->code = 'USD';
    $contract->currency->name = 'US Dollar';
    $contract->currency->created_at = now()->setDateTime(2025, 5, 22, 3, 57, 36);
    $contract->currency->updated_at = now()->setDateTime(2025, 5, 22, 3, 57, 36);

    // Client details
    $contract->client = new \stdClass();
    $contract->client->name = 'Sample Client Ltd';
    $contract->client->company = 'Sample Client Ltd';
    $contract->client->street = '123 Business Avenue';
    $contract->client->city = 'New York';
    $contract->client->state = 'NY';
    $contract->client->country = 'United States';
    $contract->client->phone = '+1 234 567 8900';
    $contract->client->email = 'contact@sampleclient.com';
    $contract->client->created_at = now()->setDateTime(2025, 5, 22, 3, 57, 36);
    $contract->client->updated_at = now()->setDateTime(2025, 5, 22, 3, 57, 36);

    // Billboards collection
    $contract->billboards = collect([
      $this->createBillboard(
        'BB-001',
        '14m x 6m',
        'Digital',
        'Premium',
        'Times Square North',
        'Times Square',
        'New York',
        'NY',
        'United States',
        25000
      ),
      $this->createBillboard(
        'BB-002',
        '12m x 4m',
        'Static',
        'High',
        'Broadway Central',
        'Broadway',
        'New York',
        'NY',
        'United States',
        20000
      )
    ]);

    return [
      'contract' => $contract,
      'settings' => app(Settings::class),
      'date' => '2025-05-22 03:57:36',
      'generatedBy' => 'MotionPhix'
    ];
  }

  private function createBillboard(
    string $code,
    string $size,
    string $type,
    string $visibilityRating,
    string $name,
    string $location,
    string $city,
    string $state,
    string $country,
    float $basePrice
  ): \stdClass {
    $billboard = new \stdClass();
    $billboard->code = $code;
    $billboard->size = $size;
    $billboard->dimensions = $size;
    $billboard->type = $type;
    $billboard->visibility_rating = $visibilityRating;
    $billboard->name = $name;
    $billboard->created_at = now()->setDateTime(2025, 5, 22, 3, 57, 36);
    $billboard->updated_at = now()->setDateTime(2025, 5, 22, 3, 57, 36);

    $billboard->location = new \stdClass();
    $billboard->location->name = $location;
    $billboard->location->created_at = now()->setDateTime(2025, 5, 22, 3, 57, 36);
    $billboard->location->updated_at = now()->setDateTime(2025, 5, 22, 3, 57, 36);

    $billboard->location->city = new \stdClass();
    $billboard->location->city->name = $city;
    $billboard->location->city->created_at = now()->setDateTime(2025, 5, 22, 3, 57, 36);
    $billboard->location->city->updated_at = now()->setDateTime(2025, 5, 22, 3, 57, 36);

    $billboard->location->state = new \stdClass();
    $billboard->location->state->name = $state;
    $billboard->location->state->created_at = now()->setDateTime(2025, 5, 22, 3, 57, 36);
    $billboard->location->state->updated_at = now()->setDateTime(2025, 5, 22, 3, 57, 36);

    $billboard->location->country = new \stdClass();
    $billboard->location->country->name = $country;
    $billboard->location->country->created_at = now()->setDateTime(2025, 5, 22, 3, 57, 36);
    $billboard->location->country->updated_at = now()->setDateTime(2025, 5, 22, 3, 57, 36);

    $billboard->pivot = new \stdClass();
    $billboard->pivot->billboard_base_price = $basePrice;
    $billboard->pivot->created_at = now()->setDateTime(2025, 5, 22, 3, 57, 36);
    $billboard->pivot->updated_at = now()->setDateTime(2025, 5, 22, 3, 57, 36);

    return $billboard;
  }
}
