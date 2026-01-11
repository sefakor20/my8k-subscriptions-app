<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResellerCreditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'balance',
        'previous_balance',
        'change_amount',
        'change_type',
        'reason',
        'related_provisioning_log_id',
        'api_response',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:2',
            'previous_balance' => 'decimal:2',
            'change_amount' => 'decimal:2',
            'api_response' => 'array',
        ];
    }

    public function provisioningLog(): BelongsTo
    {
        return $this->belongsTo(ProvisioningLog::class, 'related_provisioning_log_id');
    }

    public function scopeDebits($query)
    {
        return $query->where('change_type', 'debit');
    }

    public function scopeCredits($query)
    {
        return $query->where('change_type', 'credit');
    }

    public function scopeSnapshots($query)
    {
        return $query->where('change_type', 'snapshot');
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function isDebit(): bool
    {
        return $this->change_type === 'debit';
    }

    public function isCredit(): bool
    {
        return $this->change_type === 'credit';
    }

    public function isSnapshot(): bool
    {
        return $this->change_type === 'snapshot';
    }
}
