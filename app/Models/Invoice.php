<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Number;

class Invoice extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'order_id',
        'user_id',
        'invoice_number',
        'status',
        'subtotal',
        'tax_amount',
        'total',
        'currency',
        'line_items',
        'customer_details',
        'company_details',
        'pdf_path',
        'issued_at',
        'paid_at',
        'voided_at',
        'void_reason',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'order_id' => 'string',
            'user_id' => 'string',
            'status' => InvoiceStatus::class,
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'line_items' => 'array',
            'customer_details' => 'array',
            'company_details' => 'array',
            'issued_at' => 'datetime',
            'paid_at' => 'datetime',
            'voided_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopePaid($query)
    {
        return $query->where('status', InvoiceStatus::Paid);
    }

    public function scopeIssued($query)
    {
        return $query->where('status', InvoiceStatus::Issued);
    }

    public function scopeNotVoid($query)
    {
        return $query->where('status', '!=', InvoiceStatus::Void);
    }

    public function isPaid(): bool
    {
        return $this->status->isPaid();
    }

    public function isVoid(): bool
    {
        return $this->status->isVoid();
    }

    public function canBeVoided(): bool
    {
        return $this->status->canBeVoided();
    }

    public function formattedTotal(): string
    {
        return Number::currency(floatval($this->total), in: $this->currency);
    }

    public function formattedSubtotal(): string
    {
        return Number::currency(floatval($this->subtotal), in: $this->currency);
    }

    public function formattedTaxAmount(): string
    {
        return Number::currency(floatval($this->tax_amount), in: $this->currency);
    }

    /**
     * Generate a unique invoice number in format: INV-YYYY-NNNNN
     */
    public static function generateInvoiceNumber(): string
    {
        $year = now()->year;
        $prefix = "INV-{$year}-";

        // Get the latest invoice number for this year
        $latestInvoice = static::query()
            ->where('invoice_number', 'like', "{$prefix}%")
            ->orderByDesc('invoice_number')
            ->first();

        if ($latestInvoice) {
            // Extract sequence number and increment
            $sequence = (int) mb_substr($latestInvoice->invoice_number, -5);
            $nextSequence = $sequence + 1;
        } else {
            $nextSequence = 1;
        }

        return $prefix . mb_str_pad((string) $nextSequence, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Mark invoice as void.
     */
    public function void(?string $reason = null): bool
    {
        if (! $this->canBeVoided()) {
            return false;
        }

        return $this->update([
            'status' => InvoiceStatus::Void,
            'voided_at' => now(),
            'void_reason' => $reason,
        ]);
    }

    /**
     * Get the filename for the PDF.
     */
    public function getPdfFilename(): string
    {
        return "invoice-{$this->invoice_number}.pdf";
    }
}
