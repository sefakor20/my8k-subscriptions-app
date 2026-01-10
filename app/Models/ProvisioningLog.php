<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProvisioningAction;
use App\Enums\ProvisioningStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProvisioningLog extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'subscription_id',
        'order_id',
        'service_account_id',
        'action',
        'status',
        'attempt_number',
        'job_id',
        'my8k_request',
        'my8k_response',
        'error_message',
        'error_code',
        'duration_ms',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'subscription_id' => 'string',
            'order_id' => 'string',
            'service_account_id' => 'string',
            'action' => ProvisioningAction::class,
            'status' => ProvisioningStatus::class,
            'attempt_number' => 'integer',
            'my8k_request' => 'array',
            'my8k_response' => 'array',
            'duration_ms' => 'integer',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function serviceAccount(): BelongsTo
    {
        return $this->belongsTo(ServiceAccount::class);
    }

    public function scopeSuccess($query)
    {
        return $query->where('status', ProvisioningStatus::Success);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', ProvisioningStatus::Failed);
    }

    public function scopeForAction($query, ProvisioningAction $action)
    {
        return $query->where('action', $action);
    }

    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    public function isSuccessful(): bool
    {
        return $this->status->isSuccessful();
    }

    public function hasFailed(): bool
    {
        return $this->status->isFailed();
    }

    public function getDurationSeconds(): float
    {
        return round($this->duration_ms / 1000, 2);
    }
}
