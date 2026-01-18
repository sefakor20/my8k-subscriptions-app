<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentGateway;
use App\Enums\PaymentTransactionStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentTransaction extends Model
{
    /** @use HasFactory<\Database\Factories\PaymentTransactionFactory> */
    use HasFactory;

    use HasUuids;

    protected $fillable = [
        'order_id',
        'user_id',
        'payment_gateway',
        'reference',
        'gateway_transaction_id',
        'status',
        'amount',
        'currency',
        'gateway_response',
        'webhook_payload',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'order_id' => 'string',
            'user_id' => 'string',
            'payment_gateway' => PaymentGateway::class,
            'status' => PaymentTransactionStatus::class,
            'amount' => 'decimal:2',
            'gateway_response' => 'array',
            'webhook_payload' => 'array',
            'verified_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', PaymentTransactionStatus::Pending);
    }

    public function scopeSuccessful($query)
    {
        return $query->where('status', PaymentTransactionStatus::Success);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', PaymentTransactionStatus::Failed);
    }

    public function scopeForGateway($query, PaymentGateway $gateway)
    {
        return $query->where('payment_gateway', $gateway);
    }

    public function isSuccessful(): bool
    {
        return $this->status->isSuccessful();
    }

    public function isPending(): bool
    {
        return $this->status->isPending();
    }

    public function markAsSuccess(array $gatewayResponse = []): void
    {
        $this->update([
            'status' => PaymentTransactionStatus::Success,
            'gateway_response' => $gatewayResponse,
            'verified_at' => now(),
        ]);
    }

    public function markAsFailed(array $gatewayResponse = []): void
    {
        $this->update([
            'status' => PaymentTransactionStatus::Failed,
            'gateway_response' => $gatewayResponse,
            'verified_at' => now(),
        ]);
    }

    public function formattedAmount(): string
    {
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'GHS' => '₦',
            'GHS' => 'GH₵',
            'KES' => 'KSh',
            'ZAR' => 'R',
        ];

        $symbol = $symbols[$this->currency] ?? $this->currency . ' ';

        return $symbol . number_format((float) $this->amount, 2);
    }
}
