<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Services\InvoiceService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MyInvoices extends Component
{
    use WithPagination;

    public int $perPage = 10;

    /**
     * Get user's invoices
     */
    #[Computed]
    public function invoices(): LengthAwarePaginator
    {
        return auth()->user()->invoices()
            ->with(['order'])
            ->latest()
            ->paginate($this->perPage);
    }

    /**
     * Download an invoice PDF
     */
    public function download(string $invoiceId): StreamedResponse
    {
        $invoice = auth()->user()->invoices()->findOrFail($invoiceId);
        $invoiceService = app(InvoiceService::class);

        return response()->streamDownload(function () use ($invoiceService, $invoice) {
            echo $invoiceService->getPdfContent($invoice);
        }, $invoice->getPdfFilename(), [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * Render the component
     */
    public function render(): \Illuminate\View\View
    {
        return view('livewire.dashboard.my-invoices')
            ->layout('components.layouts.app');
    }
}
