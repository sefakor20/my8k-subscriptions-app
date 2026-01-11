<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Services\Admin\PlansService;
use Livewire\Attributes\On;
use Livewire\Component;
use Exception;

class PlanFormModal extends Component
{
    public bool $show = false;

    public string $mode = 'create'; // 'create' or 'edit'

    public ?string $planId = null;

    // Form fields
    public string $name = '';

    public string $slug = '';

    public string $description = '';

    public string $price = '';

    public string $currency = 'USD';

    public string $billing_interval = 'monthly';

    public string $duration_days = '';

    public string $max_devices = '1';

    public string $woocommerce_id = '';

    public string $my8k_plan_code = '';

    public string $features = '[]';

    public bool $is_active = true;

    /**
     * Open the modal
     */
    #[On('open-plan-form-modal')]
    public function openModal(string $mode, ?string $planId = null): void
    {
        $this->mode = $mode;
        $this->planId = $planId;

        if ($mode === 'edit' && $planId) {
            $this->loadPlan($planId);
        } else {
            $this->resetForm();
        }

        $this->show = true;
    }

    /**
     * Load plan data for editing
     */
    protected function loadPlan(string $planId): void
    {
        $service = app(PlansService::class);
        $plan = $service->getPlan($planId);

        if ($plan) {
            $this->name = $plan->name;
            $this->slug = $plan->slug;
            $this->description = $plan->description ?? '';
            $this->price = (string) $plan->price;
            $this->currency = $plan->currency;
            $this->billing_interval = $plan->billing_interval->value;
            $this->duration_days = (string) $plan->duration_days;
            $this->max_devices = (string) $plan->max_devices;
            $this->woocommerce_id = $plan->woocommerce_id;
            $this->my8k_plan_code = $plan->my8k_plan_code;
            $this->features = json_encode($plan->features ?? [], JSON_PRETTY_PRINT);
            $this->is_active = $plan->is_active;
        }
    }

    /**
     * Save the plan
     */
    public function save(): void
    {
        $data = [
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description ?: null,
            'price' => $this->price,
            'currency' => $this->currency,
            'billing_interval' => $this->billing_interval,
            'duration_days' => $this->duration_days,
            'max_devices' => $this->max_devices,
            'woocommerce_id' => $this->woocommerce_id,
            'my8k_plan_code' => $this->my8k_plan_code,
            'features' => $this->features,
            'is_active' => $this->is_active,
        ];

        try {
            $service = app(PlansService::class);

            // Validate
            $validated = $service->validatePlanData($data, $this->planId);

            // Create or update
            if ($this->mode === 'create') {
                $service->createPlan($validated);
                session()->flash('success', 'Plan created successfully.');
            } else {
                $service->updatePlan($this->planId, $validated);
                session()->flash('success', 'Plan updated successfully.');
            }

            $this->dispatch('plan-saved');
            $this->closeModal();
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Re-throw to show validation errors
            throw $e;
        } catch (Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    /**
     * Close the modal
     */
    public function closeModal(): void
    {
        $this->show = false;
        $this->resetForm();
    }

    /**
     * Reset form fields
     */
    protected function resetForm(): void
    {
        $this->planId = null;
        $this->name = '';
        $this->slug = '';
        $this->description = '';
        $this->price = '';
        $this->currency = 'USD';
        $this->billing_interval = 'monthly';
        $this->duration_days = '';
        $this->max_devices = '1';
        $this->woocommerce_id = '';
        $this->my8k_plan_code = '';
        $this->features = '[]';
        $this->is_active = true;
        $this->resetValidation();
    }

    /**
     * Get billing intervals for dropdown
     */
    public function getBillingIntervals(): array
    {
        $service = app(PlansService::class);

        return $service->getBillingIntervals();
    }

    /**
     * Get currencies for dropdown
     */
    public function getCurrencies(): array
    {
        $service = app(PlansService::class);

        return $service->getCurrencies();
    }

    /**
     * Render the component
     */
    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.plan-form-modal', [
            'billingIntervals' => $this->getBillingIntervals(),
            'currencies' => $this->getCurrencies(),
        ]);
    }
}
