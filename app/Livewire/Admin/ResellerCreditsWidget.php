<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Services\Admin\ResellerCreditsService;
use Exception;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ResellerCreditsWidget extends Component
{
    public ?string $error = null;

    /**
     * Get usage metrics
     */
    #[Computed]
    public function metrics(): array
    {
        try {
            $metrics = app(ResellerCreditsService::class)->calculateUsageMetrics();
            $this->error = $metrics['error'] ?? null;

            return $metrics;
        } catch (Exception $e) {
            Log::error('Widget failed to load metrics', ['error' => $e->getMessage()]);
            $this->error = 'Unable to load credit information';

            return [
                'currentBalance' => 0.0,
                'change24h' => 0.0,
                'change7d' => 0.0,
                'avgDailyUsage' => 0.0,
                'estimatedDepletionDays' => null,
                'alertLevel' => 'unknown',
                'error' => $this->error,
            ];
        }
    }

    /**
     * Refresh credit balance
     */
    public function refreshBalance(): void
    {
        try {
            $result = app(ResellerCreditsService::class)->logBalanceSnapshot('Manual refresh from dashboard');

            if ($result === null) {
                $this->error = 'Failed to refresh from My8K API';
            } else {
                app(ResellerCreditsService::class)->clearCache();
                $this->error = null;
                unset($this->metrics);
                $this->dispatch('balance-refreshed');
            }
        } catch (Exception $e) {
            Log::error('Failed to refresh credits', ['error' => $e->getMessage()]);
            $this->error = 'Failed to refresh: ' . $e->getMessage();
        }
    }

    /**
     * Render the component
     */
    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.reseller-credits-widget');
    }
}
