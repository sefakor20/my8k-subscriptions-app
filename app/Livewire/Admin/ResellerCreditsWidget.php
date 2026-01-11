<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Services\Admin\ResellerCreditsService;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ResellerCreditsWidget extends Component
{
    /**
     * Get usage metrics
     */
    #[Computed]
    public function metrics(): array
    {
        return app(ResellerCreditsService::class)->calculateUsageMetrics();
    }

    /**
     * Refresh credit balance
     */
    public function refreshBalance(): void
    {
        app(ResellerCreditsService::class)->clearCache();
        app(ResellerCreditsService::class)->logBalanceSnapshot('Manual refresh from dashboard');

        unset($this->metrics);

        $this->dispatch('balance-refreshed');
    }

    /**
     * Render the component
     */
    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.reseller-credits-widget');
    }
}
