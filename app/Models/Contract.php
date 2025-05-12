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
use Illuminate\Support\Facades\DB;
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
    'contract_total',
    'contract_discount',
    'contract_final_amount',
    'currency_code',
    'agreement_status',
    'notes',
    'last_notification_sent_at',
    'notification_count',
  ];

  protected $casts = [
    'start_date' => 'datetime',
    'end_date' => 'datetime',
    'contract_total' => 'decimal:2',
    'contract_discount' => 'decimal:2',
    'contract_final_amount' => 'decimal:2',
    'last_notification_sent_at' => 'datetime',
    'notification_count' => 'integer',
  ];

  // Agreement status constants
  const STATUS_DRAFT = 'draft';
  const STATUS_ACTIVE = 'active';
  const STATUS_COMPLETED = 'completed';
  const STATUS_CANCELLED = 'cancelled';
  const STATUS_EXPIRED = 'expired';

  // Notification thresholds in days
  const NOTIFICATION_THRESHOLDS = [30, 14, 7, 3, 1];

  protected static function boot()
  {
    parent::boot();

    static::creating(function ($contract) {
      // Generate contract number
      if (!$contract->contract_number) {
        $contract->contract_number = 'CNT-' . date('Y') . '-' .
          str_pad((Contract::count() + 1), 5, '0', STR_PAD_LEFT);
      }

      // Set default currency if not set
      if (!$contract->currency_code) {
        $contract->currency_code = Settings::getDefaultCurrency()['code'] ?? 'MWK';
      }
    });

    static::saved(function ($contract) {
      // Update billboard pivot data if billboards are attached
      if ($contract->billboards) {
        $billboardCount = $contract->billboards->count();
        $discountPerBillboard = $billboardCount > 0 ? $contract->contract_discount / $billboardCount : 0;

        foreach ($contract->billboards as $billboard) {
          $contract->billboards()->updateExistingPivot($billboard->id, [
            'billboard_base_price' => $billboard->base_price,
            'billboard_discount' => $discountPerBillboard,
            'billboard_final_price' => $billboard->base_price - $discountPerBillboard,
          ]);
        }
      }
    });
  }

  // Relationships
  public function parentContract(): BelongsTo
  {
    return $this->belongsTo(Contract::class, 'parent_contract_id');
  }

  public function renewals(): HasMany
  {
    return $this->hasMany(Contract::class, 'parent_contract_id');
  }

  public function billboards(): BelongsToMany
  {
    return $this->belongsToMany(Billboard::class, 'billboard_contract')
      ->using(BillboardContract::class)
      ->withPivot([
        'base_price',
        'discount_amount',
        'final_price',
        'booking_status',
        'notes'
      ])
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
      ->withPivot(['role']);
  }

  // Media collections
  public function registerMediaCollections(): void
  {
    $this->addMediaCollection('contract_documents')
      ->useDisk('public');

    $this->addMediaCollection('signed_contracts')
      ->singleFile()
      ->useDisk('public');
  }

  // Status related methods
  public static function getAgreementStatuses(): array
  {
    return [
      self::STATUS_DRAFT => 'Draft',
      self::STATUS_ACTIVE => 'Active',
      self::STATUS_COMPLETED => 'Completed',
      self::STATUS_CANCELLED => 'Cancelled',
      self::STATUS_EXPIRED => 'Expired',
    ];
  }

  public function getStatusAttribute(): string
  {
    if ($this->agreement_status !== self::STATUS_ACTIVE) {
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

  // Expiry related methods
  public function getDaysUntilExpiryAttribute(): int
  {
    return $this->end_date->startOfDay()->diffInDays(now()->startOfDay());
  }

  public function isExpiringWithinDays(int $days): bool
  {
    return $this->days_until_expiry <= $days &&
      $this->days_until_expiry > 0 &&
      $this->agreement_status === self::STATUS_ACTIVE;
  }

  public function needsRenewal(): bool
  {
    return $this->isExpiringWithinDays(30) &&
      !$this->renewals()->exists() &&
      $this->agreement_status === self::STATUS_ACTIVE;
  }

  // Notification related methods
  public function getNotificationRecipients(): Collection
  {
    return $this->users
      ->merge(
        User::role(['admin', 'manager'])
          ->where('is_active', true)
          ->whereNotIn('id', $this->users->pluck('id'))
          ->get()
      );
  }

  public function recordNotificationSent(): void
  {
    $this->update([
      'last_notification_sent_at' => now(),
      'notification_count' => $this->notification_count + 1,
    ]);
  }

  public function shouldSendNotification(): bool
  {
    if (!$this->last_notification_sent_at) {
      return true;
    }

    return $this->last_notification_sent_at->diffInHours(now()) >= 24;
  }

  // Query scopes
  public function scopeActive($query)
  {
    return $query->where('agreement_status', self::STATUS_ACTIVE);
  }

  public function calculateTotals()
  {
    $billboardTotals = $this->billboards()
      ->select(DB::raw('
        SUM(billboard_contract.billboard_base_price) as total_base,
        SUM(billboard_contract.billboard_discount) as total_discount,
        SUM(billboard_contract.billboard_final_price) as total_final
      '))
      ->first();

    $this->update([
      'contract_total' => $billboardTotals->total_base ?? 0,
      'contract_discount' => $billboardTotals->total_discount ?? 0,
      'contract_final_amount' => $billboardTotals->total_final ?? 0,
    ]);
  }

  public function scopeExpiringWithin($query, int $days)
  {
    return $query
      ->where('agreement_status', self::STATUS_ACTIVE)
      ->whereDate('end_date', '<=', now()->addDays($days))
      ->whereDate('end_date', '>', now());
  }

  public function scopeNeedsRenewal($query)
  {
    return $query
      ->active()
      ->expiringWithin(30)
      ->whereDoesntHave('renewals');
  }

  // Static methods
  public static function getExpiringContracts(array $days = null): Collection
  {
    $days = $days ?? self::NOTIFICATION_THRESHOLDS;

    return static::query()
      ->with(['billboards', 'client', 'users'])
      ->where('agreement_status', self::STATUS_ACTIVE)
      ->whereDoesntHave('renewals')
      ->where('end_date', '>', now())
      ->where('end_date', '<=', now()->addDays(max($days)))
      ->get()
      ->filter(fn ($contract) => in_array($contract->days_until_expiry, $days));
  }

  // Price calculation methods
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
}
