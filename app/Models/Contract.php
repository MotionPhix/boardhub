<?php

namespace App\Models;

use App\Mail\ContractMail;
use App\Notifications\ContractSignedNotification;
use App\Traits\HasMoney;
use App\Traits\HasUuid;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Contract extends Model implements HasMedia
{
  use HasFactory,
    SoftDeletes,
    InteractsWithMedia,
    HasUuid,
    HasMoney;

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
    'signed_at' => 'datetime',
    'signatures' => 'array',
    'metadata' => 'array',
  ];

  // Status Constants
  const STATUS_DRAFT = 'draft';
  const STATUS_ACTIVE = 'active';
  const STATUS_COMPLETED = 'completed';
  const STATUS_CANCELLED = 'cancelled';
  const STATUS_EXPIRED = 'expired';

  const NOTIFICATION_THRESHOLDS = [30, 14, 7, 3, 1];

  /**
   * Boot the model.
   */
  protected static function boot()
  {
    parent::boot();

    static::creating(function ($contract) {
      if (!$contract->contract_number) {
        $contract->contract_number = static::generateContractNumber();
      }

      if (!$contract->currency_code) {
        $contract->currency_code = Currency::getDefault()->code ?? 'MWK';
      }
    });

    static::saved(function ($contract) {
      $contract->updateBillboardPivots();
    });
  }

  /**
   * Generate a unique contract number.
   */
  protected static function generateContractNumber(): string
  {
    return 'CNT-' . date('Y') . '-' . str_pad(
        (static::count() + 1),
        5,
        '0',
        STR_PAD_LEFT
      );
  }

  public function generatePdf(?string $generatedBy = null): string
  {
    // Get the contract template content and replace variables
    // $content = $this->prepareContractContent();
    $settings = app(Settings::class);
    $localization = Settings::getLocalization();
    $currencies = app(Currency::class);

    // Generate PDF using the template
    $pdf = PDF::loadView('contracts.contract-template', [
      'contract' => $this,
      'localization' => $localization,
      'currencies' => $currencies,
      'settings' => $settings,
      'generatedBy' => $generatedBy ?? auth()->user()->name ?? 'System',
      'date' => now()
        ->setTimezone($localization['timezone'])
        ->format($localization['date_format'] . ' ' . $localization['time_format'])
    ]);

    // Set PDF options
    $pdf->setPaper('a4');
    $pdf->setOption('margin-top', '2.5cm');
    $pdf->setOption('margin-bottom', '2.5cm');
    $pdf->setOption('margin-left', '2cm');
    $pdf->setOption('margin-right', '2cm');

    return $pdf->output();
  }

  // Add a method to handle content preparation for version control
  public function createContractVersion(): ContractVersion
  {
    $latestVersion = $this->versions()->latest()->first();
    $versionNumber = $latestVersion ? $latestVersion->version + 1 : 1;

    return $this->versions()->create([
      'version' => $versionNumber,
      'content' => $this->generatePdf(auth()->user()->name),
      'metadata' => [
        'created_by' => auth()->id(),
        'created_at' => now()->toDateTimeString(),
        'template_id' => $this->contract_template_id,
        'agreement_status' => $this->agreement_status,
        'total_amount' => $this->contract_final_amount,
        'currency_code' => $this->currency_code,
      ]
    ]);
  }

  // Add method to handle signatures
  public function addSignature(string $type, string $signature): void
  {
    $signatures = $this->signatures ?? [];
    $signatures[$type] = $signature;

    $this->update([
      'signatures' => $signatures,
      'signed_at' => $type === 'client' ? now() : $this->signed_at,
      'agreement_status' => $type === 'client' ? self::STATUS_ACTIVE : $this->agreement_status,
    ]);

    // Create a new version after signing
    $this->createContractVersion();

    if ($type === 'client') {
      // Store the signed contract in media library
      $pdf = $this->generatePdf($this->client->name);
      $this->addMediaFromString($pdf)
        ->usingFileName($this->contract_number . '_signed.pdf')
        ->withCustomProperties([
          'signed_by' => $this->client->name,
          'signed_at' => now()->toDateTimeString(),
        ])
        ->toMediaCollection('signed_contracts');

      // Notify relevant parties
      $this->notifyContractSigned();
    }
  }

  // Add method to notify about contract signing
  protected function notifyContractSigned(): void
  {
    $recipients = $this->getNotificationRecipients();

    foreach ($recipients as $recipient) {
      $recipient->notify(new ContractSignedNotification($this));
    }
  }

  public function markAsGenerated(): void
  {
    if ($this->agreement_status === 'draft') {
      $this->update(['agreement_status' => 'active']);

      // Update billboard booking statuses
      $this->billboards()->updateExistingPivot(
        $this->billboards->pluck('id'),
        ['booking_status' => 'in_use']
      );
    }
  }

  public function emailToClient(): void
  {
    if (!$this->hasMedia('contract_documents')) {
      throw new \Exception('No contract document available to email');
    }

    $contractFile = $this->getFirstMedia('contract_documents');

    Mail::to($this->client->email)
      ->send(new ContractMail($this, $contractFile));

    $this->markAsGenerated();
  }

  /**
   * Update billboard pivot records with calculated prices.
   */
  protected function updateBillboardPivots(): void
  {
    if (!$this->billboards()->exists()) {
      return;
    }

    $billboardCount = $this->billboards->count();
    $discountPerBillboard = $billboardCount > 0
      ? $this->contract_discount / $billboardCount
      : 0;

    $this->billboards->each(function ($billboard) use ($discountPerBillboard) {
      $this->billboards()->updateExistingPivot($billboard->id, [
        'billboard_base_price' => $billboard->base_price,
        'billboard_discount_amount' => $discountPerBillboard,
        'billboard_final_price' => $billboard->base_price - $discountPerBillboard,
      ]);
    });

    $this->calculateTotals();
  }

  /**
   * Calculate contract totals from billboard pivot data.
   */
  public function calculateTotals(): void
  {
    // Using a subquery to get the aggregates
    $totals = DB::table('billboard_contract')
      ->select(
        DB::raw('SUM(billboard_base_price) as total_base'),
        DB::raw('SUM(billboard_discount_amount) as total_discount'),
        DB::raw('SUM(billboard_final_price) as total_final')
      )
      ->where('contract_id', $this->id)
      ->first();

    // Update the contract with the new totals
    $this->update([
      'contract_total' => $totals->total_base ?? 0,
      'contract_discount' => $totals->total_discount ?? 0,
      'contract_final_amount' => $totals->total_final ?? 0,
    ]);
  }

  /**
   * Relationships
   */
  public function parentContract(): BelongsTo
  {
    return $this->belongsTo(static::class, 'parent_contract_id');
  }

  public function renewals(): HasMany
  {
    return $this->hasMany(static::class, 'parent_contract_id');
  }

  public function billboards(): BelongsToMany
  {
    return $this->belongsToMany(Billboard::class, 'billboard_contract')
      ->using(BillboardContract::class)
      ->withPivot([
        'billboard_base_price',
        'billboard_discount_amount',
        'billboard_final_price',
        'booking_status',
        'notes'
      ])
      ->withTimestamps();
  }

  public function client(): BelongsTo
  {
    return $this->belongsTo(Client::class);
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

  /**
   * Status and Expiry Methods
   */
  public function getStatusAttribute(): string
  {
    if ($this->agreement_status !== self::STATUS_ACTIVE) {
      return static::getAgreementStatuses()[$this->agreement_status];
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

  /**
   * Notification Methods
   */
  public function getNotificationRecipients(): Collection
  {
    return
      User::role(['admin', 'manager'])
        ->where('is_active', true)
        ->whereNotIn('id', [auth()->id])
        ->get();
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
    return !$this->last_notification_sent_at
      || $this->last_notification_sent_at->diffInHours(now()) >= 24;
  }

  /**
   * Query Scopes
   */

  #[Scope]
  public function active(Builder $query, bool $withExpired = false): void
  {
    $baseQuery = $query->where('agreement_status', self::STATUS_ACTIVE);

    if (!$withExpired) {
      $baseQuery->whereDate('end_date', '>=', now());
    }
  }

  #[Scope]
  public function expiringWithin(Builder $query, int $days): void
  {
    $query
      ->where('agreement_status', self::STATUS_ACTIVE)
      ->whereDate('end_date', '<=', now()->addDays($days))
      ->whereDate('end_date', '>', now());
  }

  #[Scope]
  public function needsRenewal(Builder $query): void
  {
    $query
      ->active()
      ->expiringWithin(30)
      ->whereDoesntHave('renewals');
  }

  #[Scope]
  public function upcoming(Builder $query): void
  {
    $query
      ->where('agreement_status', self::STATUS_DRAFT)
      ->orWhere(function ($query) {
        $query->where('agreement_status', self::STATUS_ACTIVE)
          ->where('start_date', '>', now());
      });
  }

  #[Scope]
  public function expired(Builder $query): void
  {
    $query
      ->where('agreement_status', self::STATUS_ACTIVE)
      ->whereDate('end_date', '<', now());
  }

  public function needsRenewalCheck(): bool
  {
    return $this->isExpiringWithinDays(30) &&
      !$this->renewals()->exists() &&
      $this->agreement_status === self::STATUS_ACTIVE;
  }

  /**
   * Static Methods
   */
  public static function getExpiringContracts(?array $days = null): Collection
  {
    $days = $days ?? self::NOTIFICATION_THRESHOLDS;

    return static::query()
      ->with(['billboards', 'client', 'users'])
      ->where('agreement_status', self::STATUS_ACTIVE)
      ->whereDoesntHave('renewals')
      ->where('end_date', '>', now())
      ->where('end_date', '<=', now()->addDays(max($days)))
      ->get()
      ->filter(fn($contract) => in_array($contract->days_until_expiry, $days));
  }

  // Price calculation methods
  public function calculateBaseAmount(): void
  {
    $this->base_amount = $this->billboards()
      ->sum('billboard_contract.billboard_base_price');
  }
}
