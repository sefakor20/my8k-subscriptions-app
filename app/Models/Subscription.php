<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SubscriptionStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Subscription extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'user_id',
        'plan_id',
        'service_account_id',
        'status',
        'woocommerce_subscription_id',
        'starts_at',
        'expires_at',
        'cancelled_at',
        'suspended_at',
        'suspension_reason',
        'last_renewal_at',
        'next_renewal_at',
        'auto_renew',
        'metadata',
        'currency',
        'credit_balance',
        'scheduled_plan_id',
        'plan_change_scheduled_at',
        'payment_failed_at',
        'payment_failure_count',
        'suspension_warning_sent',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'user_id' => 'string',
            'plan_id' => 'string',
            'service_account_id' => 'string',
            'scheduled_plan_id' => 'string',
            'status' => SubscriptionStatus::class,
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'suspended_at' => 'datetime',
            'last_renewal_at' => 'datetime',
            'next_renewal_at' => 'datetime',
            'plan_change_scheduled_at' => 'datetime',
            'auto_renew' => 'boolean',
            'credit_balance' => 'decimal:2',
            'metadata' => 'array',
            'payment_failed_at' => 'datetime',
            'payment_failure_count' => 'integer',
            'suspension_warning_sent' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function serviceAccount(): HasOne
    {
        return $this->hasOne(ServiceAccount::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function provisioningLogs(): HasMany
    {
        return $this->hasMany(ProvisioningLog::class);
    }

    public function planChanges(): HasMany
    {
        return $this->hasMany(PlanChange::class);
    }

    public function scheduledPlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'scheduled_plan_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', SubscriptionStatus::Active);
    }

    public function scopeExpiringSoon($query, int $days = 7)
    {
        return $query->where('status', SubscriptionStatus::Active)
            ->whereBetween('expires_at', [now(), now()->addDays($days)]);
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now())
            ->where('status', '!=', SubscriptionStatus::Expired);
    }

    public function isActive(): bool
    {
        return $this->status->isActive() && $this->expires_at->isFuture();
    }

    public function hasExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function daysUntilExpiry(): int
    {
        return (int) now()->diffInDays($this->expires_at, false);
    }

    public function hasPendingPlanChange(): bool
    {
        return $this->scheduled_plan_id !== null;
    }

    public function hasCredit(): bool
    {
        return $this->credit_balance > 0;
    }

    public function addCredit(float $amount): bool
    {
        return $this->update([
            'credit_balance' => $this->credit_balance + $amount,
        ]);
    }

    public function useCredit(float $amount): float
    {
        $creditToUse = min($amount, (float) $this->credit_balance);

        $this->update([
            'credit_balance' => $this->credit_balance - $creditToUse,
        ]);

        return $creditToUse;
    }

    public function clearScheduledPlanChange(): bool
    {
        return $this->update([
            'scheduled_plan_id' => null,
            'plan_change_scheduled_at' => null,
        ]);
    }

    /**
     * Record a payment failure for this subscription.
     */
    public function recordPaymentFailure(): bool
    {
        return $this->update([
            'payment_failed_at' => $this->payment_failed_at ?? now(),
            'payment_failure_count' => $this->payment_failure_count + 1,
        ]);
    }

    /**
     * Clear payment failure tracking after successful payment.
     */
    public function clearPaymentFailure(): bool
    {
        return $this->update([
            'payment_failed_at' => null,
            'payment_failure_count' => 0,
            'suspension_warning_sent' => false,
        ]);
    }

    /**
     * Check if subscription has a pending payment failure.
     */
    public function hasPaymentFailure(): bool
    {
        return $this->payment_failed_at !== null;
    }

    /**
     * Check if subscription is in grace period (has failed payment but not yet expired).
     */
    public function isInGracePeriod(): bool
    {
        return $this->hasPaymentFailure()
            && $this->status === SubscriptionStatus::Active
            && $this->expires_at->isFuture();
    }

    /**
     * Check if grace period has expired and subscription is ready for suspension.
     */
    public function gracePeriodExpired(): bool
    {
        return $this->hasPaymentFailure()
            && $this->status === SubscriptionStatus::Active
            && $this->expires_at->isPast();
    }

    /**
     * Mark that suspension warning has been sent.
     */
    public function markSuspensionWarningSent(): bool
    {
        return $this->update([
            'suspension_warning_sent' => true,
        ]);
    }

    /**
     * Scope for subscriptions with payment failures ready for suspension.
     */
    public function scopeReadyForSuspension($query)
    {
        return $query->where('status', SubscriptionStatus::Active)
            ->whereNotNull('payment_failed_at')
            ->where('expires_at', '<=', now());
    }

    /**
     * Scope for subscriptions in grace period needing a warning.
     */
    public function scopeNeedingSuspensionWarning($query, int $daysBeforeExpiry = 2)
    {
        return $query->where('status', SubscriptionStatus::Active)
            ->whereNotNull('payment_failed_at')
            ->where('suspension_warning_sent', false)
            ->whereBetween('expires_at', [now(), now()->addDays($daysBeforeExpiry)]);
    }
}
