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
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'user_id' => 'string',
            'plan_id' => 'string',
            'service_account_id' => 'string',
            'status' => SubscriptionStatus::class,
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'suspended_at' => 'datetime',
            'last_renewal_at' => 'datetime',
            'next_renewal_at' => 'datetime',
            'auto_renew' => 'boolean',
            'metadata' => 'array',
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
}
