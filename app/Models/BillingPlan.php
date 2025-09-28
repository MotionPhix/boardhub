<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class BillingPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'price',
        'annual_price',
        'trial_days',
        'is_popular',
        'is_active',
        'features',
        'limits',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'annual_price' => 'decimal:2',
            'trial_days' => 'integer',
            'is_popular' => 'boolean',
            'is_active' => 'boolean',
            'features' => 'array',
            'limits' => 'array',
            'sort_order' => 'integer',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(TenantSubscription::class);
    }

    public function featureRules(): HasMany
    {
        return $this->hasMany(PlanFeatureRule::class);
    }

    public function features(): BelongsToMany
    {
        return $this->belongsToMany(PlanFeature::class, 'plan_feature_rules')
            ->withPivot(['is_enabled', 'limits'])
            ->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('price');
    }

    public function hasFeature(string $featureName): bool
    {
        return $this->features()
            ->where('plan_features.name', $featureName)
            ->wherePivot('is_enabled', true)
            ->exists();
    }

    public function getFeatureLimit(string $featureName, string $limitKey): ?int
    {
        $rule = $this->featureRules()
            ->whereHas('feature', fn($q) => $q->where('name', $featureName))
            ->first();

        return $rule?->limits[$limitKey] ?? null;
    }
}
