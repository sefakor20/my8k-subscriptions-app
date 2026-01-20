<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TicketCategory;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
    /** @use HasFactory<\Database\Factories\SupportTicketFactory> */
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'user_id',
        'subscription_id',
        'order_id',
        'subject',
        'category',
        'priority',
        'status',
        'assigned_to',
        'first_response_at',
        'resolved_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'category' => TicketCategory::class,
            'priority' => TicketPriority::class,
            'status' => TicketStatus::class,
            'first_response_at' => 'datetime',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function assignedAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportMessage::class);
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', [
            TicketStatus::Open->value,
            TicketStatus::InProgress->value,
            TicketStatus::WaitingCustomer->value,
        ]);
    }

    public function scopeClosed($query)
    {
        return $query->whereIn('status', [
            TicketStatus::Resolved->value,
            TicketStatus::Closed->value,
        ]);
    }

    public function scopeUnassigned($query)
    {
        return $query->whereNull('assigned_to');
    }

    public function scopeAssignedTo($query, string $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeByCategory($query, TicketCategory $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByPriority($query, TicketPriority $priority)
    {
        return $query->where('priority', $priority);
    }

    public function isOpen(): bool
    {
        return $this->status->isOpen();
    }

    public function isClosed(): bool
    {
        return $this->status->isClosed();
    }

    public function isAssigned(): bool
    {
        return $this->assigned_to !== null;
    }

    public function needsFirstResponse(): bool
    {
        return $this->first_response_at === null && $this->isOpen();
    }
}
