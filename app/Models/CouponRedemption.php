<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CouponRedemption extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'coupon_id',
        'user_id',
        'order_id',
        'discount_amount',
        'original_amount',
        'final_amount',
        'currency',
        'trial_days_added',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'coupon_id' => 'string',
            'user_id' => 'string',
            'order_id' => 'string',
            'discount_amount' => 'decimal:2',
            'original_amount' => 'decimal:2',
            'final_amount' => 'decimal:2',
            'trial_days_added' => 'integer',
        ];
    }

    /**
     * Get the coupon that was redeemed.
     */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    /**
     * Get the user who redeemed the coupon.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the order associated with this redemption.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
