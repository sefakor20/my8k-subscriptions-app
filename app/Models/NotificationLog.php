<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\NotificationCategory;
use App\Enums\NotificationLogStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationLog extends Model
{
    /** @use HasFactory<\Database\Factories\NotificationLogFactory> */
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'user_id',
        'notification_type',
        'category',
        'channel',
        'subject',
        'metadata',
        'status',
        'failure_reason',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'category' => NotificationCategory::class,
            'status' => NotificationLogStatus::class,
            'metadata' => 'array',
            'sent_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getShortTypeAttribute(): string
    {
        return class_basename($this->notification_type);
    }

    public function scopeWithStatus($query, NotificationLogStatus $status)
    {
        return $query->where('status', $status);
    }

    public function scopeSent($query)
    {
        return $query->where('status', NotificationLogStatus::Sent);
    }

    public function scopeBlocked($query)
    {
        return $query->where('status', NotificationLogStatus::Blocked);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', NotificationLogStatus::Failed);
    }

    public function scopeForUser($query, User $user)
    {
        return $query->where('user_id', $user->id);
    }

    public function scopeForCategory($query, NotificationCategory $category)
    {
        return $query->where('category', $category);
    }
}
