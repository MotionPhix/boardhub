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

      // Always set booking_status to IN_USE when creating
      $pivot->booking_status = BookingStatus::IN_USE;

      // Check if billboard is already in use
      $existingBooking = static::where('billboard_id', $pivot->billboard_id)
        ->whereIn('booking_status', [BookingStatus::IN_USE->value, BookingStatus::PENDING->value])
        ->whereHas('contract', function ($query) use ($pivot) {
          $query->where('id', '!=', $pivot->contract_id)
            ->whereIn('agreement_status', ['draft', 'active'])
            ->where(function ($q) {
              $q->whereDate('end_date', '>=', now())
                ->orWhereNull('end_date');
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

      // If trying to change status from IN_USE to anything else
      if ($pivot->isDirty('booking_status') && $pivot->getOriginal('booking_status') === BookingStatus::IN_USE) {
        // Only allow if contract is expired or cancelled
        $contract = Contract::find($pivot->contract_id);
        if (!$contract || (!$contract->end_date?->isPast() && $contract->agreement_status !== 'cancelled')) {
          throw new \Exception("Cannot change booking status while contract is active.");
        }
      }
    });

    static::updated(function ($pivot) {
      // If status changed to COMPLETED or CANCELLED, update billboard availability
      if ($pivot->isDirty('booking_status') &&
        in_array($pivot->booking_status, [BookingStatus::COMPLETED, BookingStatus::CANCELLED])) {
        Billboard::where('id', $pivot->billboard_id)
          ->update(['is_active' => true]);
      }
    });
  }
}
