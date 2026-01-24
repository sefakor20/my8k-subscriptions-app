<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StreamingAppPlatform;
use App\Enums\StreamingAppType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StreamingApp extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'name',
        'description',
        'type',
        'platform',
        'version',
        'download_url',
        'downloader_code',
        'short_url',
        'is_recommended',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'type' => StreamingAppType::class,
            'platform' => StreamingAppPlatform::class,
            'is_recommended' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRecommended($query)
    {
        return $query->where('is_recommended', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function scopeByPlatform($query, StreamingAppPlatform $platform)
    {
        return $query->where('platform', $platform);
    }

    public function scopeByType($query, StreamingAppType $type)
    {
        return $query->where('type', $type);
    }
}
