<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Services\InvoiceService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\Response;

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
    public function download(string $invoiceId): Response
    {
        $invoice = auth()->user()->invoices()->findOrFail($invoiceId);
        $invoiceService = app(InvoiceService::class);

        return $invoiceService->downloadPdf($invoice);
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
