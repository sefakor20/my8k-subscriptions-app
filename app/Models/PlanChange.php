<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PlanChangeExecutionType;
use App\Enums\PlanChangeStatus;
use App\Enums\PlanChangeType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Number;

class PlanChange extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'subscription_id',
        'user_id',
        'from_plan_id',
        'to_plan_id',
        'order_id',
        'type',
        'status',
        'execution_type',
        'proration_amount',
        'credit_amount',
        'currency',
        'calculation_details',
        'scheduled_at',
        'executed_at',
        'failed_at',
        'failure_reason',
        'metadata',
        'payment_reference',
        'payment_gateway',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'subscription_id' => 'string',
            'user_id' => 'string',
            'from_plan_id' => 'string',
            'to_plan_id' => 'string',
            'order_id' => 'string',
            'type' => PlanChangeType::class,
            'status' => PlanChangeStatus::class,
            'execution_type' => PlanChangeExecutionType::class,
            'proration_amount' => 'decimal:2',
            'credit_amount' => 'decimal:2',
            'calculation_details' => 'array',
            'metadata' => 'array',
            'scheduled_at' => 'datetime',
            'executed_at' => 'datetime',
            'failed_at' => 'datetime',
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

    public function fromPlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'from_plan_id');
    }

    public function toPlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'to_plan_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', PlanChangeStatus::Pending);
    }

    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('status', PlanChangeStatus::Scheduled);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', PlanChangeStatus::Completed);
    }

    public function scopeDueForExecution(Builder $query): Builder
    {
        return $query->where('status', PlanChangeStatus::Scheduled)
            ->where('scheduled_at', '<=', now());
    }

    public function isUpgrade(): bool
    {
        return $this->type->isUpgrade();
    }

    public function isDowngrade(): bool
    {
        return $this->type->isDowngrade();
    }

    public function isPending(): bool
    {
        return $this->status->isPending();
    }

    public function isScheduled(): bool
    {
        return $this->status->isScheduled();
    }

    public function isCompleted(): bool
    {
        return $this->status->isCompleted();
    }

    public function canBeCancelled(): bool
    {
        return $this->status->canBeCancelled();
    }

    public function requiresPayment(): bool
    {
        return $this->proration_amount > 0;
    }

    public function formattedProrationAmount(): string
    {
        return Number::currency(floatval($this->proration_amount), in: $this->currency);
    }

    public function formattedCreditAmount(): string
    {
        return Number::currency(floatval($this->credit_amount), in: $this->currency);
    }

    public function markAsCompleted(): bool
    {
        return $this->update([
            'status' => PlanChangeStatus::Completed,
            'executed_at' => now(),
        ]);
    }

    public function markAsFailed(string $reason): bool
    {
        return $this->update([
            'status' => PlanChangeStatus::Failed,
            'failed_at' => now(),
            'failure_reason' => $reason,
        ]);
    }

    public function markAsCancelled(): bool
    {
        if (! $this->canBeCancelled()) {
            return false;
        }

        return $this->update([
            'status' => PlanChangeStatus::Cancelled,
        ]);
    }
}
