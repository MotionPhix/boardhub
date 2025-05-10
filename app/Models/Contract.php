<?php

namespace App\Models;

use App\Traits\HasMoney;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Contract extends Model implements HasMedia
{
  use HasFactory, SoftDeletes, InteractsWithMedia, HasUuid, HasMoney;

  protected $fillable = [
    'client_id',
    'parent_contract_id',
    'contract_number',
    'start_date',
    'end_date',
    'base_amount',
    'discount_amount',
    'total_amount',
    'agreement_status',
    'payment_terms',
    'notes',
    'last_notification_sent_at',
    'notification_count',
  ];

  protected $casts = [
    'start_date' => 'datetime',
    'end_date' => 'datetime',
    'base_amount' => 'decimal:2',
    'discount_amount' => 'decimal:2',
    'total_amount' => 'decimal:2',
    'last_notification_sent_at' => 'datetime',
    'notification_count' => 'integer',
  ];

  // Agreement status constants
  const AGREEMENT_STATUS_DRAFT = 'draft';
  const AGREEMENT_STATUS_ACTIVE = 'active';
  const AGREEMENT_STATUS_COMPLETED = 'completed';
  const AGREEMENT_STATUS_CANCELLED = 'cancelled';
  const AGREEMENT_STATUS_EXPIRED = 'expired';

  // Notification thresholds in days
  const NOTIFICATION_THRESHOLDS = [30, 14, 7, 3, 1];

  public function renewals(): HasMany
  {
    return $this->hasMany(Contract::class, 'parent_contract_id');
  }

  public function billboards(): BelongsToMany
  {
    return $this->belongsToMany(Billboard::class, 'billboard_contract')
      ->using(BillboardContract::class)
      ->withPivot(['base_price', 'discount_amount', 'final_price', 'booking_status', 'notes'])
      ->withTimestamps();
  }

  public function client(): BelongsTo
  {
    return $this->belongsTo(Client::class);
  }

  public function users(): BelongsToMany
  {
    return $this->belongsToMany(User::class, 'contract_user')
      ->withTimestamps()
      ->withPivot(['role']); // roles like 'owner', 'manager', etc.
  }

  public function registerMediaCollections(): void
  {
    $this->addMediaCollection('contract_documents')
      ->useDisk('public');

    $this->addMediaCollection('signed_contracts')
      ->singleFile()
      ->useDisk('public');
  }

  public static function getAgreementStatuses(): array
  {
    return [
      self::AGREEMENT_STATUS_DRAFT => 'Draft',
      self::AGREEMENT_STATUS_ACTIVE => 'Active',
      self::AGREEMENT_STATUS_COMPLETED => 'Completed',
      self::AGREEMENT_STATUS_CANCELLED => 'Cancelled',
    ];
  }

  protected static function boot()
  {
    parent::boot();

    static::creating(function ($contract) {
      // Generate contract number if not set
      if (!$contract->contract_number) {
        $contract->contract_number = 'CNT-' . date('Y') . '-' .
          str_pad((Contract::count() + 1), 5, '0', STR_PAD_LEFT);
      }

      // Set total_amount based on base and discount
      $contract->total_amount = $contract->base_amount - $contract->discount_amount;
    });

    static::updating(function ($contract) {
      // Recalculate total amount when base or discount changes
      if ($contract->isDirty(['base_amount', 'discount_amount'])) {
        $contract->total_amount = $contract->base_amount - $contract->discount_amount;
      }
    });
  }

  public function parent(): BelongsTo
  {
    return $this->belongsTo(Contract::class, 'parent_contract_id');
  }

  public function formatAmount($amount)
  {
    $currency = Settings::getDefaultCurrency();
    return $currency['symbol'] . ' ' . number_format($amount, 2);
  }

  public function getFormattedBaseAmountAttribute()
  {
    return $this->formatAmount($this->base_amount);
  }

  public function getFormattedDiscountAmountAttribute()
  {
    return $this->formatAmount($this->discount_amount);
  }

  public function getFormattedTotalAmountAttribute()
  {
    return $this->formatAmount($this->total_amount);
  }

  public function calculateBaseAmount(): void
  {
    $this->base_amount = $this->billboards()
      ->sum('billboard_contract.base_price');
  }

  public function updatePricing(float $discountAmount = 0): void
  {
    $this->discount_amount = $discountAmount;
    $this->calculateBaseAmount();
    $this->total_amount = $this->base_amount - $this->discount_amount;
    $this->save();
  }

  /**
   * Get the number of days until the contract expires
   */
  public function getDaysUntilExpiryAttribute(): int
  {
    return $this->end_date->startOfDay()->diffInDays(now()->startOfDay());
  }

  /**
   * Check if the contract is expiring within the given number of days
   */
  public function isExpiringWithinDays(int $days): bool
  {
    return $this->days_until_expiry <= $days &&
      $this->days_until_expiry > 0 &&
      $this->agreement_status === self::AGREEMENT_STATUS_ACTIVE;
  }

  /**
   * Check if the contract needs renewal
   */
  public function needsRenewal(): bool
  {
    return $this->isExpiringWithinDays(30) &&
      !$this->renewals()->exists() &&
      $this->agreement_status === self::AGREEMENT_STATUS_ACTIVE;
  }

  /**
   * Get all users who should be notified about this contract
   */
  public function getNotificationRecipients(): Collection
  {
    // Get users directly associated with the contract
    $contractUsers = $this->users;

    // Get users with specific roles who should be notified
    $roleUsers = User::role(['admin', 'manager'])
      ->where('is_active', true)
      ->whereNotIn('id', $contractUsers->pluck('id'))
      ->get();

    return $contractUsers->merge($roleUsers);
  }

  /**
   * Record that a notification was sent
   */
  public function recordNotificationSent(): void
  {
    $this->update([
      'last_notification_sent_at' => now(),
      'notification_count' => $this->notification_count + 1,
    ]);
  }

  /**
   * Check if we should send a notification based on the notification history
   */
  public function shouldSendNotification(): bool
  {
    if (!$this->last_notification_sent_at) {
      return true;
    }

    // Don't send more than one notification per day
    return $this->last_notification_sent_at->diffInHours(now()) >= 24;
  }

  /**
   * Get contracts that need expiry notifications
   */
  public static function getExpiringContracts(array $days = null): Collection
  {
    $days = $days ?? self::NOTIFICATION_THRESHOLDS;

    return static::query()
      ->with(['billboards', 'client', 'users'])
      ->where('agreement_status', self::AGREEMENT_STATUS_ACTIVE)
      ->whereDoesntHave('renewals')
      ->where('end_date', '>', now())
      ->where('end_date', '<=', now()->addDays(max($days)))
      ->get()
      ->filter(fn ($contract) => in_array($contract->days_until_expiry, $days));
  }

  /**
   * Get the contract's current status for display
   */
  public function getStatusAttribute(): string
  {
    if ($this->agreement_status !== self::AGREEMENT_STATUS_ACTIVE) {
      return self::getAgreementStatuses()[$this->agreement_status];
    }

    if ($this->end_date->isPast()) {
      return 'Expired';
    }

    if ($this->isExpiringWithinDays(30)) {
      return 'Expiring Soon';
    }

    return 'Active';
  }

  /**
   * Scope a query to only include active contracts
   */
  public function scopeActive($query)
  {
    return $query->where('agreement_status', self::AGREEMENT_STATUS_ACTIVE);
  }

  /**
   * Scope a query to only include contracts expiring within given days
   */
  public function scopeExpiringWithin($query, int $days)
  {
    return $query
      ->where('agreement_status', self::AGREEMENT_STATUS_ACTIVE)
      ->whereDate('end_date', '<=', now()->addDays($days))
      ->whereDate('end_date', '>', now());
  }

  /**
   * Scope a query to only include contracts that need renewal
   */
  public function scopeNeedsRenewal($query)
  {
    return $query
      ->active()
      ->expiringWithin(30)
      ->whereDoesntHave('renewals');
  }
}
