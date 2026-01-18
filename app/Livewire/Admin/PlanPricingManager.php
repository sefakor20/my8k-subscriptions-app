<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\PlanPrice;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Component;

class PlanPricingManager extends Component
{
    public ?string $planId = null;

    public ?string $editingPriceId = null;

    public ?string $gateway = null;

    public string $currency = 'GHS';

    public string $price = '';

    public bool $isActive = true;

    public bool $showForm = false;

    public function mount(?string $planId = null): void
    {
        $this->planId = $planId;
    }

    public function getPricesProperty(): Collection
    {
        if (! $this->planId) {
            return collect();
        }

        return PlanPrice::where('plan_id', $this->planId)
            ->orderBy('gateway')
            ->orderBy('currency')
            ->get();
    }

    public function showAddForm(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function editPrice(string $priceId): void
    {
        $price = PlanPrice::find($priceId);

        if ($price && $price->plan_id === $this->planId) {
            $this->editingPriceId = $priceId;
            $this->gateway = $price->gateway;
            $this->currency = $price->currency;
            $this->price = (string) $price->price;
            $this->isActive = $price->is_active;
            $this->showForm = true;
        }
    }

    public function savePrice(): void
    {
        $this->validate([
            'currency' => 'required|string|size:3',
            'price' => 'required|numeric|min:0',
        ]);

        $data = [
            'plan_id' => $this->planId,
            'gateway' => $this->gateway ?: null,
            'currency' => mb_strtoupper($this->currency),
            'price' => (float) $this->price,
            'is_active' => $this->isActive,
        ];

        if ($this->editingPriceId) {
            $existingPrice = PlanPrice::find($this->editingPriceId);

            if ($existingPrice && $existingPrice->plan_id === $this->planId) {
                $existingPrice->update($data);
                session()->flash('pricing-success', 'Price updated successfully.');
            }
        } else {
            $exists = PlanPrice::where('plan_id', $this->planId)
                ->where('gateway', $data['gateway'])
                ->where('currency', $data['currency'])
                ->exists();

            if ($exists) {
                $this->addError('currency', 'A price for this gateway/currency combination already exists.');

                return;
            }

            PlanPrice::create($data);
            session()->flash('pricing-success', 'Price added successfully.');
        }

        $this->resetForm();
    }

    public function deletePrice(string $priceId): void
    {
        $price = PlanPrice::find($priceId);

        if ($price && $price->plan_id === $this->planId) {
            $price->delete();
            session()->flash('pricing-success', 'Price deleted successfully.');
        }
    }

    public function toggleActive(string $priceId): void
    {
        $price = PlanPrice::find($priceId);

        if ($price && $price->plan_id === $this->planId) {
            $price->update(['is_active' => ! $price->is_active]);
        }
    }

    public function cancelForm(): void
    {
        $this->resetForm();
    }

    protected function resetForm(): void
    {
        $this->editingPriceId = null;
        $this->gateway = null;
        $this->currency = 'GHS';
        $this->price = '';
        $this->isActive = true;
        $this->showForm = false;
        $this->resetValidation();
    }

    public function getGatewayOptions(): array
    {
        return [
            '' => 'Default (Any Gateway)',
            'paystack' => 'Paystack',
            'stripe' => 'Stripe',
        ];
    }

    public function getCurrencyOptions(): array
    {
        return [
            'GHS' => 'GHS (Ghana Cedi)',
            'USD' => 'USD (US Dollar)',
            'NGN' => 'NGN (Nigerian Naira)',
            'EUR' => 'EUR (Euro)',
            'GBP' => 'GBP (British Pound)',
            'KES' => 'KES (Kenyan Shilling)',
            'ZAR' => 'ZAR (South African Rand)',
        ];
    }

    public function render(): View
    {
        return view('livewire.admin.plan-pricing-manager', [
            'prices' => $this->prices,
            'gatewayOptions' => $this->getGatewayOptions(),
            'currencyOptions' => $this->getCurrencyOptions(),
        ]);
    }
}
