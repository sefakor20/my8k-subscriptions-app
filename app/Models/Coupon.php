<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CouponDiscountType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Number;

class Coupon extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'code',
        'name',
        'description',
        'discount_type',
        'discount_value',
        'trial_extension_days',
        'max_redemptions',
        'max_redemptions_per_user',
        'minimum_order_amount',
        'first_time_customer_only',
        'currency',
        'valid_from',
        'valid_until',
        'is_active',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'discount_type' => CouponDiscountType::class,
            'discount_value' => 'decimal:2',
            'trial_extension_days' => 'integer',
            'max_redemptions' => 'integer',
            'max_redemptions_per_user' => 'integer',
            'minimum_order_amount' => 'decimal:2',
            'first_time_customer_only' => 'boolean',
            'valid_from' => 'datetime',
            'valid_until' => 'datetime',
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    /**
     * Get all plans this coupon applies to.
     */
    public function plans(): BelongsToMany
    {
        return $this->belongsToMany(Plan::class, 'coupon_plan')
            ->withTimestamps();
    }

    /**
     * Get all redemptions for this coupon.
     */
    public function redemptions(): HasMany
    {
        return $this->hasMany(CouponRedemption::class);
    }

    /**
     * Scope to active coupons only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to valid coupons (active and within date range).
     */
    public function scopeValid($query)
    {
        return $query->active()
            ->where(function ($q) {
                $q->whereNull('valid_from')
                    ->orWhere('valid_from', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', now());
            });
    }

    /**
     * Scope to find by code (case-insensitive).
     */
    public function scopeByCode($query, string $code)
    {
        return $query->whereRaw('UPPER(code) = ?', [mb_strtoupper(trim($code))]);
    }

    /**
     * Check if the coupon is currently valid.
     */
    public function isValid(): bool
    {
        return $this->is_active
            && ($this->valid_from === null || $this->valid_from <= now())
            && ($this->valid_until === null || $this->valid_until >= now())
            && ! $this->isExhausted();
    }

    /**
     * Check if the coupon has expired.
     */
    public function isExpired(): bool
    {
        return $this->valid_until !== null && $this->valid_until < now();
    }

    /**
     * Check if the coupon has reached its maximum redemptions.
     */
    public function isExhausted(): bool
    {
        return $this->max_redemptions !== null
            && $this->redemptions()->count() >= $this->max_redemptions;
    }

    /**
     * Check if the coupon applies to a specific plan.
     */
    public function appliesToPlan(Plan $plan): bool
    {
        // If no plans specified, applies to all
        if ($this->plans()->count() === 0) {
            return true;
        }

        return $this->plans()->where('plans.id', $plan->id)->exists();
    }

    /**
     * Get the remaining number of redemptions available.
     */
    public function getRemainingRedemptions(): ?int
    {
        if ($this->max_redemptions === null) {
            return null;
        }

        return max(0, $this->max_redemptions - $this->redemptions()->count());
    }

    /**
     * Get the number of times a user has redeemed this coupon.
     */
    public function getUserRedemptionCount(User $user): int
    {
        return $this->redemptions()->where('user_id', $user->id)->count();
    }

    /**
     * Check if a user can still use this coupon.
     */
    public function canBeUsedBy(User $user): bool
    {
        return $this->getUserRedemptionCount($user) < $this->max_redemptions_per_user;
    }

    /**
     * Get a human-readable formatted discount string.
     */
    public function formattedDiscount(): string
    {
        return match ($this->discount_type) {
            CouponDiscountType::Percentage => $this->discount_value . '% off',
            CouponDiscountType::FixedAmount => Number::currency(
                floatval($this->discount_value),
                in: $this->currency ?? 'USD',
            ) . ' off',
            CouponDiscountType::TrialExtension => '+' . $this->trial_extension_days . ' days trial',
        };
    }

    /**
     * Calculate the discount amount for a given order amount and currency.
     */
    public function calculateDiscount(float $amount, string $currency): float
    {
        return match ($this->discount_type) {
            CouponDiscountType::Percentage => round($amount * ($this->discount_value / 100), 2),
            CouponDiscountType::FixedAmount => $this->currency === $currency
                ? min(floatval($this->discount_value), $amount)
                : 0,
            CouponDiscountType::TrialExtension => 0,
        };
    }

    /**
     * Get the total number of redemptions.
     */
    public function getRedemptionCount(): int
    {
        return $this->redemptions()->count();
    }
}
