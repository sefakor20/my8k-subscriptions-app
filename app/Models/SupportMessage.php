<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportMessage extends Model
{
    /** @use HasFactory<\Database\Factories\SupportMessageFactory> */
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'support_ticket_id',
        'user_id',
        'message',
        'is_internal_note',
        'attachments',
    ];

    protected function casts(): array
    {
        return [
            'is_internal_note' => 'boolean',
            'attachments' => 'array',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopePublic($query)
    {
        return $query->where('is_internal_note', false);
    }

    public function scopeInternal($query)
    {
        return $query->where('is_internal_note', true);
    }

    public function isFromAdmin(): bool
    {
        return $this->user->is_admin ?? false;
    }

    public function isFromCustomer(): bool
    {
        return !$this->isFromAdmin();
    }

    public function hasAttachments(): bool
    {
        return !empty($this->attachments);
    }
}
