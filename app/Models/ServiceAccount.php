<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ServiceAccountStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceAccount extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'subscription_id',
        'user_id',
        'my8k_account_id',
        'username',
        'password',
        'server_url',
        'max_connections',
        'status',
        'activated_at',
        'expires_at',
        'last_extended_at',
        'my8k_metadata',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'subscription_id' => 'string',
            'user_id' => 'string',
            'username' => 'encrypted',
            'password' => 'encrypted',
            'status' => ServiceAccountStatus::class,
            'max_connections' => 'integer',
            'activated_at' => 'datetime',
            'expires_at' => 'datetime',
            'last_extended_at' => 'datetime',
            'my8k_metadata' => 'array',
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

    public function scopeActive($query)
    {
        return $query->where('status', ServiceAccountStatus::Active);
    }

    public function scopeExpired($query)
    {
        return $query->where('status', ServiceAccountStatus::Expired);
    }

    public function scopeExpiring($query, int $days = 7)
    {
        return $query->where('status', ServiceAccountStatus::Active)
            ->whereBetween('expires_at', [now(), now()->addDays($days)]);
    }

    public function isActive(): bool
    {
        return $this->status->isUsable() && $this->expires_at->isFuture();
    }

    public function hasExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function daysUntilExpiry(): int
    {
        return (int) now()->diffInDays($this->expires_at, false);
    }

    public function getM3uUrl(): string
    {
        return $this->server_url . '/get.php?username=' . $this->username . '&password=' . $this->password . '&type=m3u_plus&output=ts';
    }

    public function getEpgUrl(): string
    {
        return $this->server_url . '/xmltv.php?username=' . $this->username . '&password=' . $this->password;
    }
}
