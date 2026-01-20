<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Enums\CouponDiscountType;
use App\Models\Coupon;
use App\Models\Plan;
use App\Services\CouponService;
use Exception;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class CouponFormModal extends Component
{
    public bool $show = false;

    public string $mode = 'create';

    public ?string $couponId = null;

    // Form fields
    public string $code = '';

    public string $name = '';

    public string $description = '';

    public string $discount_type = 'percentage';

    public string $discount_value = '';

    public string $trial_extension_days = '';

    public string $max_redemptions = '';

    public string $max_redemptions_per_user = '1';

    public string $minimum_order_amount = '';

    public bool $first_time_customer_only = false;

    public string $currency = '';

    public ?string $valid_from = null;

    public ?string $valid_until = null;

    public bool $is_active = true;

    /** @var array<string> */
    public array $selected_plans = [];

    /**
     * Open the modal
     */
    #[On('open-coupon-form-modal')]
    public function openModal(string $mode, ?string $couponId = null): void
    {
        $this->mode = $mode;
        $this->couponId = $couponId;

        if ($mode === 'edit' && $couponId) {
            $this->loadCoupon($couponId);
        } else {
            $this->resetForm();
        }

        $this->show = true;
    }

    /**
     * Load coupon data for editing
     */
    protected function loadCoupon(string $couponId): void
    {
        $coupon = Coupon::with('plans')->find($couponId);

        if ($coupon) {
            $this->code = $coupon->code;
            $this->name = $coupon->name;
            $this->description = $coupon->description ?? '';
            $this->discount_type = $coupon->discount_type->value;
            $this->discount_value = (string) $coupon->discount_value;
            $this->trial_extension_days = $coupon->trial_extension_days !== null ? (string) $coupon->trial_extension_days : '';
            $this->max_redemptions = $coupon->max_redemptions !== null ? (string) $coupon->max_redemptions : '';
            $this->max_redemptions_per_user = (string) $coupon->max_redemptions_per_user;
            $this->minimum_order_amount = $coupon->minimum_order_amount !== null ? (string) $coupon->minimum_order_amount : '';
            $this->first_time_customer_only = $coupon->first_time_customer_only;
            $this->currency = $coupon->currency ?? '';
            $this->valid_from = $coupon->valid_from?->format('Y-m-d');
            $this->valid_until = $coupon->valid_until?->format('Y-m-d');
            $this->is_active = $coupon->is_active;
            $this->selected_plans = $coupon->plans->pluck('id')->toArray();
        }
    }

    /**
     * Generate a random coupon code
     */
    public function generateCode(): void
    {
        $this->code = app(CouponService::class)->generateCode();
    }

    /**
     * Save the coupon
     */
    public function save(): void
    {
        $data = $this->validateCouponData();

        try {
            $service = app(CouponService::class);

            if ($this->mode === 'create') {
                $service->createCoupon($data);
                $this->dispatch('coupon-saved', message: 'Coupon created successfully.');
            } else {
                $coupon = Coupon::find($this->couponId);
                if ($coupon) {
                    $service->updateCoupon($coupon, $data);
                    $this->dispatch('coupon-saved', message: 'Coupon updated successfully.');
                }
            }

            $this->closeModal();
        } catch (Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    /**
     * Validate coupon data
     *
     * @return array<string, mixed>
     */
    protected function validateCouponData(): array
    {
        $rules = [
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('coupons', 'code')->ignore($this->couponId),
            ],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'discount_type' => ['required', Rule::enum(CouponDiscountType::class)],
            'discount_value' => 'required|numeric|min:0',
            'trial_extension_days' => 'nullable|integer|min:1',
            'max_redemptions' => 'nullable|integer|min:1',
            'max_redemptions_per_user' => 'required|integer|min:1',
            'minimum_order_amount' => 'nullable|numeric|min:0',
            'first_time_customer_only' => 'boolean',
            'currency' => 'nullable|string|max:3',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after_or_equal:valid_from',
            'is_active' => 'boolean',
            'selected_plans' => 'array',
        ];

        // Additional validation for percentage
        if ($this->discount_type === 'percentage') {
            $rules['discount_value'] = 'required|numeric|min:0|max:100';
        }

        // Trial extension days required for trial_extension type
        if ($this->discount_type === 'trial_extension') {
            $rules['trial_extension_days'] = 'required|integer|min:1';
        }

        // Currency required for fixed_amount type
        if ($this->discount_type === 'fixed_amount') {
            $rules['currency'] = 'required|string|max:3';
        }

        $validator = Validator::make([
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'discount_type' => $this->discount_type,
            'discount_value' => $this->discount_value,
            'trial_extension_days' => $this->trial_extension_days ?: null,
            'max_redemptions' => $this->max_redemptions ?: null,
            'max_redemptions_per_user' => $this->max_redemptions_per_user,
            'minimum_order_amount' => $this->minimum_order_amount ?: null,
            'first_time_customer_only' => $this->first_time_customer_only,
            'currency' => $this->currency ?: null,
            'valid_from' => $this->valid_from,
            'valid_until' => $this->valid_until,
            'is_active' => $this->is_active,
            'selected_plans' => $this->selected_plans,
        ], $rules);

        $validated = $validator->validate();

        // Map selected_plans to plan_ids for the service
        $validated['plan_ids'] = $validated['selected_plans'];
        unset($validated['selected_plans']);

        return $validated;
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
        $this->couponId = null;
        $this->code = '';
        $this->name = '';
        $this->description = '';
        $this->discount_type = 'percentage';
        $this->discount_value = '';
        $this->trial_extension_days = '';
        $this->max_redemptions = '';
        $this->max_redemptions_per_user = '1';
        $this->minimum_order_amount = '';
        $this->first_time_customer_only = false;
        $this->currency = '';
        $this->valid_from = null;
        $this->valid_until = null;
        $this->is_active = true;
        $this->selected_plans = [];
        $this->resetValidation();
    }

    /**
     * Get available discount types
     *
     * @return array<array{value: string, label: string, description: string}>
     */
    public function getDiscountTypes(): array
    {
        return collect(CouponDiscountType::cases())->map(fn($type) => [
            'value' => $type->value,
            'label' => $type->label(),
            'description' => $type->description(),
        ])->toArray();
    }

    /**
     * Get available plans for selection
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Plan>
     */
    public function getPlans(): \Illuminate\Database\Eloquent\Collection
    {
        return Plan::where('is_active', true)->orderBy('name')->get();
    }

    /**
     * Render the component
     */
    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.coupon-form-modal', [
            'discountTypes' => $this->getDiscountTypes(),
            'plans' => $this->getPlans(),
        ]);
    }
}
