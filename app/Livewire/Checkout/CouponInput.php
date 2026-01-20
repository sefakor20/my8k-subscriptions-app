<?php

declare(strict_types=1);

namespace App\Livewire\Checkout;

use App\Models\Plan;
use App\Services\CouponService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class CouponInput extends Component
{
    public string $planId;

    public string $gateway;

    public string $couponCode = '';

    public bool $isValidating = false;

    public ?array $appliedCoupon = null;

    public ?string $errorMessage = null;

    public function mount(string $planId, string $gateway): void
    {
        $this->planId = $planId;
        $this->gateway = $gateway;
    }

    public function applyCoupon(CouponService $couponService): void
    {
        $this->isValidating = true;
        $this->errorMessage = null;
        $this->appliedCoupon = null;

        if (empty(trim($this->couponCode))) {
            $this->errorMessage = 'Please enter a coupon code';
            $this->isValidating = false;

            return;
        }

        $user = Auth::user();
        $plan = Plan::find($this->planId);

        if (! $plan) {
            $this->errorMessage = 'Invalid plan selected';
            $this->isValidating = false;

            return;
        }

        $result = $couponService->validateCoupon(
            $this->couponCode,
            $user,
            $plan,
            $this->gateway,
        );

        if ($result['valid']) {
            $this->appliedCoupon = [
                'coupon_id' => $result['coupon']->id,
                'code' => $result['coupon']->code,
                'name' => $result['coupon']->name,
                'discount' => $result['discount'],
                'original_amount' => $result['original_amount'],
                'final_amount' => $result['final_amount'],
                'currency' => $result['currency'],
                'formatted_discount' => $result['coupon']->formattedDiscount(),
                'trial_days' => $result['trial_days'],
            ];
            $this->dispatch('coupon-applied', couponCode: $this->couponCode);
        } else {
            $this->errorMessage = $result['error'];
        }

        $this->isValidating = false;
    }

    public function removeCoupon(): void
    {
        $this->appliedCoupon = null;
        $this->couponCode = '';
        $this->errorMessage = null;
        $this->dispatch('coupon-removed');
    }

    public function updatedGateway(): void
    {
        // Re-validate if coupon was already applied when gateway changes
        if ($this->appliedCoupon !== null) {
            $this->applyCoupon(app(CouponService::class));
        }
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.checkout.coupon-input');
    }
}
