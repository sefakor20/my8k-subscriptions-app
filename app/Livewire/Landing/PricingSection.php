<?php

declare(strict_types=1);

namespace App\Livewire\Landing;

use App\Enums\BillingInterval;
use App\Models\Plan;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class PricingSection extends Component
{
    public string $selectedInterval = 'monthly';

    public function setInterval(string $interval): void
    {
        $this->selectedInterval = $interval;
    }

    /**
     * Get available billing intervals
     *
     * @return array<BillingInterval>
     */
    #[Computed]
    public function intervals(): array
    {
        return BillingInterval::cases();
    }

    /**
     * Get plans filtered by selected billing interval
     */
    #[Computed]
    public function plans(): Collection
    {
        return Plan::query()
            ->active()
            ->where('billing_interval', $this->selectedInterval)
            ->orderBy('price')
            ->get();
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.landing.pricing-section');
    }
}
