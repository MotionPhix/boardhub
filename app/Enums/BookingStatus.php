<?php

namespace App\Enums;

enum BookingStatus: string
{
  case PENDING = 'pending';
  case CONFIRMED = 'confirmed';
  case IN_USE = 'in_use';
  case COMPLETED = 'completed';
  case CANCELLED = 'cancelled';

  public function label(): string
  {
    return match($this) {
      self::PENDING => 'Pending',
      self::CONFIRMED => 'Confirmed',
      self::IN_USE => 'In Use',
      self::COMPLETED => 'Completed',
      self::CANCELLED => 'Cancelled',
    };
  }

  public function color(): string
  {
    return match($this) {
      self::PENDING => 'warning',
      self::CONFIRMED => 'info',
      self::IN_USE => 'success',
      self::COMPLETED => 'gray',
      self::CANCELLED => 'danger',
    };
  }
}


