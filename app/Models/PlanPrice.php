<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Number;

class PlanPrice extends Model
{
    /** @use HasFactory<\Database\Factories\PlanPriceFactory> */
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'plan_id',
        'gateway',
        'currency',
        'price',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function formattedPrice(): string
    {
        return Number::currency(floatval($this->price), in: $this->currency);
    }
}
