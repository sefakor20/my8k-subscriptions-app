<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BillingInterval;
use Illuminate\Support\Number;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Plan extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'currency',
        'billing_interval',
        'duration_days',
        'max_devices',
        'features',
        'is_active',
        'woocommerce_id',
        'my8k_plan_code',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'price' => 'decimal:2',
            'billing_interval' => BillingInterval::class,
            'duration_days' => 'integer',
            'max_devices' => 'integer',
            'features' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get all subscriptions for this plan
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Scope query to only active plans
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get formatted price with currency symbol
     */
    public function formattedPrice(): string
    {
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
        ];

        $symbol = $symbols[$this->currency] ?? $this->currency;

        // return $symbol . number_format($this->price, 2);

        return Number::currency(floatval($this->price), precision: 2, locale: $symbol);
    }
}
