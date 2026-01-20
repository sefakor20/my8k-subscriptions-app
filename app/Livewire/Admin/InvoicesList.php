<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoicesList extends Component
{
    use WithPagination;

    #[Url(as: 'search')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = '';

    #[Url(as: 'from')]
    public string $dateFrom = '';

    #[Url(as: 'to')]
    public string $dateTo = '';

    public int $perPage = 50;

    /**
     * Get filtered and paginated invoices
     */
    #[Computed]
    public function invoices(): LengthAwarePaginator
    {
        $query = Invoice::query()
            ->with(['user', 'order']);

        // Search filter
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('invoice_number', 'like', "%{$this->search}%")
                    ->orWhereHas('user', function ($userQuery) {
                        $userQuery->where('name', 'like', "%{$this->search}%")
                            ->orWhere('email', 'like', "%{$this->search}%");
                    });
            });
        }

        // Status filter
        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        // Date from filter
        if ($this->dateFrom) {
            $query->whereDate('issued_at', '>=', $this->dateFrom);
        }

        // Date to filter
        if ($this->dateTo) {
            $query->whereDate('issued_at', '<=', $this->dateTo);
        }

        return $query->latest()->paginate($this->perPage);
    }

    /**
     * Get all available invoice statuses
     */
    #[Computed]
    public function statuses(): array
    {
        return InvoiceStatus::cases();
    }

    /**
     * Reset all filters
     */
    public function resetFilters(): void
    {
        $this->search = '';
        $this->statusFilter = '';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->resetPage();
    }

    /**
     * Download an invoice PDF
     */
    public function download(string $invoiceId): StreamedResponse
    {
        $invoice = Invoice::findOrFail($invoiceId);
        $invoiceService = app(InvoiceService::class);

        return response()->streamDownload(function () use ($invoiceService, $invoice) {
            echo $invoiceService->getPdfContent($invoice);
        }, $invoice->getPdfFilename(), [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * Void an invoice
     */
    public function voidInvoice(string $invoiceId): void
    {
        $invoice = Invoice::findOrFail($invoiceId);
        $invoiceService = app(InvoiceService::class);

        if ($invoiceService->voidInvoice($invoice)) {
            session()->flash('success', "Invoice {$invoice->invoice_number} has been voided.");
        } else {
            session()->flash('error', 'Unable to void this invoice.');
        }
    }

    /**
     * Update search query and reset pagination
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Update status filter and reset pagination
     */
    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    /**
     * Update date from filter and reset pagination
     */
    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    /**
     * Update date to filter and reset pagination
     */
    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    /**
     * Render the component
     */
    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.invoices-list')
            ->layout('components.layouts.app');
    }
}
