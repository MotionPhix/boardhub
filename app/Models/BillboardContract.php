<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Traits\HasMoney;
use Illuminate\Database\Eloquent\Relations\Pivot;

class BillboardContract extends Pivot
{
  use HasMoney;

  protected $table = 'billboard_contract';

  protected $fillable = [
    'billboard_id',
    'contract_id',
    'billboard_base_price',
    'billboard_discount_amount',
    'billboard_final_price',
    'booking_status',
    'notes',
  ];

  protected $casts = [
    'billboard_base_price' => 'decimal:2',
    'billboard_discount_amount' => 'decimal:2',
    'billboard_final_price' => 'decimal:2',
    'booking_status' => BookingStatus::class,
  ];

  public function billboard()
  {
    return $this->belongsTo(Billboard::class);
  }

  public function contract()
  {
    return $this->belongsTo(Contract::class);
  }

  public function isAvailableForBooking(): bool
  {
    if (!$this->contract) {
      return true;
    }

    // Check if contract is expired or cancelled
    if ($this->contract->agreement_status === 'cancelled' ||
      ($this->contract->end_date && $this->contract->end_date->isPast())) {
      return true;
    }

    // Check if booking is completed or cancelled
    return in_array($this->booking_status, [
      BookingStatus::COMPLETED,
      BookingStatus::CANCELLED,
    ]);
  }

  protected static function boot()
  {
    parent::boot();

    static::creating(function ($pivot) {
      // Set billboard_base_price if not set
      if (!$pivot->billboard_base_price) {
        $pivot->billboard_base_price = Billboard::find($pivot->billboard_id)->base_price;
      }

      // Calculate billboard_final_price
      $pivot->billboard_final_price = $pivot->billboard_base_price - ($pivot->billboard_discount_amount ?? 0);

      // Get the contract
      $contract = Contract::find($pivot->contract_id);

      // Set initial booking status based on contract status
      $pivot->booking_status = $contract->agreement_status === 'active'
        ? BookingStatus::IN_USE
        : BookingStatus::PENDING;

      // Check if billboard is already in use or pending
      $existingBooking = static::where('billboard_id', $pivot->billboard_id)
        ->where('id', '!=', $pivot->id)
        ->whereIn('booking_status', [BookingStatus::IN_USE->value, BookingStatus::PENDING->value])
        ->whereHas('contract', function ($query) use ($contract) {
          $query->where('id', '!=', $contract->id)
            ->where(function ($q) {
              $q->where('agreement_status', 'active')
                ->orWhere('agreement_status', 'draft');
            })
            ->where(function ($q) use ($contract) {
              if ($contract->start_date && $contract->end_date) {
                $q->where(function ($q) use ($contract) {
                  $q->whereBetween('start_date', [$contract->start_date, $contract->end_date])
                    ->orWhereBetween('end_date', [$contract->start_date, $contract->end_date])
                    ->orWhere(function ($q) use ($contract) {
                      $q->where('start_date', '<=', $contract->start_date)
                        ->where('end_date', '>=', $contract->end_date);
                    });
                });
              }
            });
        })
        ->first();

      if ($existingBooking) {
        throw new \Exception(
          "Billboard is already booked in contract #{$existingBooking->contract->contract_number}."
        );
      }
    });

    static::updating(function ($pivot) {
      if ($pivot->isDirty(['billboard_base_price', 'billboard_discount_amount'])) {
        $pivot->billboard_final_price = $pivot->billboard_base_price - ($pivot->billboard_discount_amount ?? 0);
      }

      // Handle status changes based on contract status
      if ($pivot->isDirty('booking_status')) {
        $contract = Contract::find($pivot->contract_id);

        // If contract is active, booking must be IN_USE
        if ($contract->agreement_status === 'active' &&
          $pivot->booking_status !== BookingStatus::IN_USE) {
          throw new \Exception("Billboards in active contracts must have 'In Use' status.");
        }

        // If contract is draft, booking must be PENDING
        if ($contract->agreement_status === 'draft' &&
          !in_array($pivot->booking_status, [BookingStatus::PENDING->value, BookingStatus::CANCELLED->value])) {
          throw new \Exception("Billboards in draft contracts can only be 'Pending' or 'Cancelled'.");
        }
      }
    });
  }
}
