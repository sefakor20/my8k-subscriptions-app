<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\NotificationCategory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    /** @use HasFactory<\Database\Factories\NotificationPreferenceFactory> */
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'user_id',
        'category',
        'channel',
        'is_enabled',
    ];

    protected function casts(): array
    {
        return [
            'category' => NotificationCategory::class,
            'is_enabled' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    public function scopeDisabled($query)
    {
        return $query->where('is_enabled', false);
    }

    public function scopeForCategory($query, NotificationCategory $category)
    {
        return $query->where('category', $category);
    }

    public function scopeForChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }
}
