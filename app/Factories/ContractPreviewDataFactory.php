<?php

namespace App\Factories;

use App\Models\Currency;
use App\Models\Settings;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ContractPreviewDataFactory
{
  protected Carbon $currentDate;
  protected string $currentUser;

  public function __construct(string $currentDate, string $currentUser)
  {
    $this->currentDate = Carbon::parse($currentDate);
    $this->currentUser = $currentUser;
  }

  public function create(string $template = 'standard'): array
  {
    $contract = $this->createContract();

    return [
      'contract' => $contract,
      'settings' => app(Settings::class),
      'date' => $this->currentDate->format('Y-m-d H:i:s'),
      'generatedBy' => $this->currentUser
    ];
  }

  protected function createContract(): \stdClass
  {
    $contract = new \stdClass();

    // Required contract fields from Contract model and migration
    $contract->contract_number = 'CNT-' . $this->currentDate->format('Y') . '-' . str_pad((1), 5, '0', STR_PAD_LEFT);
    $contract->start_date = $this->currentDate;
    $contract->end_date = $this->currentDate->copy()->addMonths(12);
    $contract->contract_total = 50000.00;
    $contract->contract_discount = 5000.00;
    $contract->contract_final_amount = 45000.00;
    $contract->currency_code = Currency::getDefault()->code ?? 'MWK';
    $contract->agreement_status = 'draft';
    $contract->notes = null;
    $contract->created_at = $this->currentDate;
    $contract->updated_at = $this->currentDate;

    // Currency relationship
    $contract->currency = new \stdClass();
    $contract->currency->code = $contract->currency_code;
    $contract->currency->name = 'Malawi Kwacha';
    $contract->currency->symbol = 'MK';
    $contract->currency->created_at = $this->currentDate;
    $contract->currency->updated_at = $this->currentDate;

    // Client relationship based on Client model
    $contract->client = $this->createClient();

    // Billboard relationship with pivot data
    $contract->billboards = $this->createBillboards();

    return $contract;
  }

  protected function createClient(): \stdClass
  {
    $client = new \stdClass();
    $client->name = 'Sample Client Ltd';
    $client->email = 'contact@sampleclient.com';
    $client->phone = '+265 999 999 999';
    $client->company = 'Sample Client Ltd';
    $client->street = '123 Business Avenue';
    $client->city = 'Lilongwe';
    $client->state = 'Central Region';
    $client->country = 'Malawi';
    $client->created_at = $this->currentDate;
    $client->updated_at = $this->currentDate;
    return $client;
  }

  protected function createBillboard(
    string $code,
    string $name,
    string $size,
    float $basePrice,
    array $location
  ): \stdClass {
    $billboard = new \stdClass();

    // Billboard fields from Billboard model
    $billboard->code = $code;
    $billboard->name = $name;
    $billboard->size = $size;
    $billboard->base_price = $basePrice;
    $billboard->physical_status = 'operational';
    $billboard->is_active = true;
    $billboard->created_at = $this->currentDate;
    $billboard->updated_at = $this->currentDate;

    // Location relationship
    $billboard->location = $this->createLocation($location);

    // Pivot data from BillboardContract relationship
    $billboard->pivot = new \stdClass();
    $billboard->pivot->billboard_base_price = $basePrice;
    $billboard->pivot->billboard_discount_amount = 2500.00;
    $billboard->pivot->billboard_final_price = $basePrice - 2500.00;
    $billboard->pivot->booking_status = 'pending';
    $billboard->pivot->notes = null;
    $billboard->pivot->created_at = $this->currentDate;
    $billboard->pivot->updated_at = $this->currentDate;

    return $billboard;
  }

  protected function createBillboards(): Collection
  {
    return collect([
      $this->createBillboard(
        'BLT-LLW-001',
        'City Centre Main',
        '14m x 6m',
        25000.00,
        [
          'name' => 'City Centre',
          'city' => 'Lilongwe',
          'state' => 'Central Region',
          'country' => 'Malawi',
        ]
      ),
      $this->createBillboard(
        'BLT-LLW-002',
        'Area 47 Junction',
        '12m x 4m',
        20000.00,
        [
          'name' => 'Area 47',
          'city' => 'Lilongwe',
          'state' => 'Central Region',
          'country' => 'Malawi',
        ]
      )
    ]);
  }

  protected function createLocation(array $data): \stdClass
  {
    $location = new \stdClass();
    $location->name = $data['name'];
    $location->created_at = $this->currentDate;
    $location->updated_at = $this->currentDate;

    $location->city = new \stdClass();
    $location->city->name = $data['city'];
    $location->city->created_at = $this->currentDate;
    $location->city->updated_at = $this->currentDate;

    $location->state = new \stdClass();
    $location->state->name = $data['state'];
    $location->state->created_at = $this->currentDate;
    $location->state->updated_at = $this->currentDate;

    $location->country = new \stdClass();
    $location->country->name = $data['country'];
    $location->country->created_at = $this->currentDate;
    $location->country->updated_at = $this->currentDate;

    return $location;
  }
}
