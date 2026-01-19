<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Mail\InvoiceGenerated;
use App\Models\Invoice;
use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class InvoiceService
{
    /**
     * Process a complete invoice workflow for an order.
     * Creates invoice, generates PDF, and sends email.
     */
    public function processOrderInvoice(Order $order): Invoice
    {
        // Check if invoice already exists (idempotency)
        $existingInvoice = $order->invoice;
        if ($existingInvoice) {
            Log::info('Invoice already exists for order', [
                'order_id' => $order->id,
                'invoice_id' => $existingInvoice->id,
            ]);

            return $existingInvoice;
        }

        // Create the invoice
        $invoice = $this->createInvoiceForOrder($order);

        // Generate PDF
        $this->generatePdf($invoice);

        // Send email notification
        $this->sendInvoiceEmail($invoice);

        return $invoice;
    }

    /**
     * Create an invoice for an order.
     */
    public function createInvoiceForOrder(Order $order): Invoice
    {
        $user = $order->user;
        $subscription = $order->subscription;
        $plan = $subscription?->plan;

        $subtotal = (float) $order->amount;
        $taxRate = config('invoice.defaults.tax_rate', 0);
        $taxAmount = $subtotal * ($taxRate / 100);
        $total = $subtotal + $taxAmount;

        $lineItems = [
            [
                'description' => $plan?->name ?? 'IPTV Subscription',
                'details' => $plan?->description ?? '',
                'quantity' => 1,
                'unit_price' => $subtotal,
                'amount' => $subtotal,
            ],
        ];

        $customerDetails = [
            'name' => $user->name,
            'email' => $user->email,
        ];

        $companyDetails = [
            'name' => config('invoice.company.name'),
            'address' => config('invoice.company.address'),
            'city' => config('invoice.company.city'),
            'country' => config('invoice.company.country'),
            'phone' => config('invoice.company.phone'),
            'email' => config('invoice.company.email'),
            'website' => config('invoice.company.website'),
            'vat_number' => config('invoice.company.vat_number'),
        ];

        $invoice = Invoice::create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'invoice_number' => Invoice::generateInvoiceNumber(),
            'status' => InvoiceStatus::Paid,
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total' => $total,
            'currency' => $order->currency ?? config('invoice.defaults.currency', 'USD'),
            'line_items' => $lineItems,
            'customer_details' => $customerDetails,
            'company_details' => $companyDetails,
            'issued_at' => now(),
            'paid_at' => $order->paid_at ?? now(),
        ]);

        Log::info('Invoice created', [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'order_id' => $order->id,
        ]);

        return $invoice;
    }

    /**
     * Generate and store the PDF for an invoice.
     */
    public function generatePdf(Invoice $invoice): string
    {
        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
        ]);

        $filename = $invoice->getPdfFilename();
        $storagePath = config('invoice.storage.path', 'invoices');
        $fullPath = "{$storagePath}/{$filename}";

        Storage::disk(config('invoice.storage.disk', 'local'))
            ->put($fullPath, $pdf->output());

        $invoice->update(['pdf_path' => $fullPath]);

        Log::info('Invoice PDF generated', [
            'invoice_id' => $invoice->id,
            'path' => $fullPath,
        ]);

        return $fullPath;
    }

    /**
     * Get the PDF content for an invoice.
     */
    public function getPdfContent(Invoice $invoice): string
    {
        if ($invoice->pdf_path && Storage::disk(config('invoice.storage.disk', 'local'))->exists($invoice->pdf_path)) {
            return Storage::disk(config('invoice.storage.disk', 'local'))->get($invoice->pdf_path);
        }

        // Generate on-the-fly if not stored
        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
        ]);

        return $pdf->output();
    }

    /**
     * Download the invoice PDF.
     */
    public function downloadPdf(Invoice $invoice): Response
    {
        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
        ]);

        return $pdf->download($invoice->getPdfFilename());
    }

    /**
     * Stream the invoice PDF (inline display).
     */
    public function streamPdf(Invoice $invoice): Response
    {
        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
        ]);

        return $pdf->stream($invoice->getPdfFilename());
    }

    /**
     * Send invoice email to the customer.
     */
    public function sendInvoiceEmail(Invoice $invoice): void
    {
        try {
            Mail::to($invoice->user->email)
                ->send(new InvoiceGenerated($invoice));

            Log::info('Invoice email sent', [
                'invoice_id' => $invoice->id,
                'email' => $invoice->user->email,
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to send invoice email', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Void an invoice.
     */
    public function voidInvoice(Invoice $invoice, ?string $reason = null): bool
    {
        if (! $invoice->canBeVoided()) {
            Log::warning('Cannot void invoice - already voided', [
                'invoice_id' => $invoice->id,
            ]);

            return false;
        }

        $voided = $invoice->void($reason);

        if ($voided) {
            Log::info('Invoice voided', [
                'invoice_id' => $invoice->id,
                'reason' => $reason,
            ]);
        }

        return $voided;
    }

    /**
     * Regenerate PDF for an existing invoice.
     */
    public function regeneratePdf(Invoice $invoice): string
    {
        // Delete old PDF if exists
        if ($invoice->pdf_path) {
            Storage::disk(config('invoice.storage.disk', 'local'))->delete($invoice->pdf_path);
        }

        return $this->generatePdf($invoice);
    }
}
