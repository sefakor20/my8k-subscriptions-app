<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BillingInterval;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Number;

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
        'paystack_plan_code',
        'stripe_price_id',
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
     * Get all prices for this plan
     */
    public function prices(): HasMany
    {
        return $this->hasMany(PlanPrice::class);
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
        return Number::currency(floatval($this->price), in: $this->currency);
    }

    /**
     * Get price record for a specific gateway and currency.
     * Falls back to default price (null gateway) or base plan price.
     */
    public function getPriceFor(?string $gateway, string $currency): ?PlanPrice
    {
        // 1. Try exact gateway + currency match
        $price = $this->prices()
            ->where('gateway', $gateway)
            ->where('currency', $currency)
            ->where('is_active', true)
            ->first();

        if ($price) {
            return $price;
        }

        // 2. Try default price (null gateway) for currency
        $price = $this->prices()
            ->whereNull('gateway')
            ->where('currency', $currency)
            ->where('is_active', true)
            ->first();

        if ($price) {
            return $price;
        }

        // 3. Fallback to base plan price if currency matches
        if ($this->currency === $currency) {
            $fallback = new PlanPrice();
            $fallback->price = $this->price;
            $fallback->currency = $this->currency;
            $fallback->gateway = null;
            $fallback->plan_id = $this->id;

            return $fallback;
        }

        return null;
    }

    /**
     * Get the amount for a specific gateway and currency.
     * Returns base price if no specific price is found.
     */
    public function getAmountFor(?string $gateway, string $currency): float
    {
        $price = $this->getPriceFor($gateway, $currency);

        return $price ? floatval($price->price) : floatval($this->price);
    }

    /**
     * Get the currency to use for a specific gateway.
     * Returns the gateway's default currency from config.
     */
    public function getCurrencyFor(string $gateway): string
    {
        return match ($gateway) {
            'paystack' => config('services.paystack.currency', 'GHS'),
            'stripe' => config('services.stripe.currency', 'USD'),
            default => $this->currency ?? 'USD',
        };
    }

    /**
     * Get formatted price for a specific gateway and currency.
     */
    public function formattedPriceFor(?string $gateway, string $currency): string
    {
        $price = $this->getPriceFor($gateway, $currency);

        if ($price) {
            return Number::currency(floatval($price->price), in: $price->currency);
        }

        return $this->formattedPrice();
    }

    /**
     * Check if plan has a price for a specific gateway/currency combination.
     */
    public function hasPriceFor(?string $gateway, string $currency): bool
    {
        return $this->getPriceFor($gateway, $currency) !== null;
    }
}
