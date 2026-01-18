<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentGateway;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'subscription_id',
        'user_id',
        'woocommerce_order_id',
        'status',
        'amount',
        'currency',
        'payment_method',
        'payment_gateway',
        'gateway_transaction_id',
        'gateway_session_id',
        'gateway_metadata',
        'paid_at',
        'provisioned_at',
        'idempotency_key',
        'webhook_payload',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'subscription_id' => 'string',
            'user_id' => 'string',
            'status' => OrderStatus::class,
            'payment_gateway' => PaymentGateway::class,
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'provisioned_at' => 'datetime',
            'webhook_payload' => 'array',
            'gateway_metadata' => 'array',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function provisioningLogs(): HasMany
    {
        return $this->hasMany(ProvisioningLog::class);
    }

    public function scopePendingProvisioning($query)
    {
        return $query->where('status', OrderStatus::PendingProvisioning);
    }

    public function scopeProvisioned($query)
    {
        return $query->where('status', OrderStatus::Provisioned);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', OrderStatus::ProvisioningFailed);
    }

    public function isProvisioned(): bool
    {
        return $this->status->isProvisioned();
    }

    public function needsProvisioning(): bool
    {
        return $this->status->needsProvisioning();
    }

    public function hasFailed(): bool
    {
        return $this->status === OrderStatus::ProvisioningFailed;
    }

    public function formattedAmount(): string
    {
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
        ];

        $symbol = $symbols[$this->currency] ?? $this->currency;

        return $symbol . number_format($this->amount, 2);
    }
}
