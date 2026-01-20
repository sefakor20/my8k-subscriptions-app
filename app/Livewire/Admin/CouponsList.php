<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\Coupon;
use App\Services\CouponService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class CouponsList extends Component
{
    use WithPagination;

    public ?bool $activeFilter = null;

    public string $search = '';

    public ?string $selectedCouponId = null;

    /**
     * Get all coupons with filters and pagination
     */
    #[Computed]
    public function coupons(): LengthAwarePaginator
    {
        $query = Coupon::query()
            ->withCount('redemptions')
            ->with('plans:id,name')
            ->orderByDesc('created_at');

        if ($this->activeFilter !== null) {
            $query->where('is_active', $this->activeFilter);
        }

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('code', 'like', "%{$this->search}%")
                    ->orWhere('name', 'like', "%{$this->search}%");
            });
        }

        return $query->paginate(15);
    }

    /**
     * Reset pagination when filters change
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedActiveFilter(): void
    {
        $this->resetPage();
    }

    /**
     * Show create coupon modal
     */
    public function createCoupon(): void
    {
        $this->dispatch('open-coupon-form-modal', mode: 'create');
    }

    /**
     * Show edit coupon modal
     */
    public function editCoupon(string $couponId): void
    {
        $this->selectedCouponId = $couponId;
        $this->dispatch('open-coupon-form-modal', mode: 'edit', couponId: $couponId);
    }

    /**
     * Toggle coupon active status
     */
    public function toggleActive(string $couponId): void
    {
        $coupon = Coupon::find($couponId);

        if ($coupon) {
            app(CouponService::class)->toggleActive($coupon);
            unset($this->coupons);
            session()->flash('success', 'Coupon status updated successfully.');
        }
    }

    /**
     * Delete a coupon
     */
    public function deleteCoupon(string $couponId): void
    {
        $coupon = Coupon::withCount('redemptions')->find($couponId);

        if (! $coupon) {
            session()->flash('error', 'Coupon not found.');

            return;
        }

        if ($coupon->redemptions_count > 0) {
            session()->flash('error', 'Cannot delete coupon with existing redemptions.');

            return;
        }

        $coupon->plans()->detach();
        $coupon->delete();

        unset($this->coupons);
        session()->flash('success', 'Coupon deleted successfully.');
    }

    /**
     * Check if coupon can be deleted
     */
    public function canDelete(string $couponId): bool
    {
        $coupon = Coupon::withCount('redemptions')->find($couponId);

        return $coupon && $coupon->redemptions_count === 0;
    }

    /**
     * Filter by active status
     */
    public function filterActive(?bool $active): void
    {
        $this->activeFilter = $active;
        unset($this->coupons);
    }

    /**
     * Clear search
     */
    public function clearSearch(): void
    {
        $this->search = '';
        $this->resetPage();
        unset($this->coupons);
    }

    /**
     * Refresh coupons list after form submission
     */
    #[On('coupon-saved')]
    public function refreshCoupons(string $message = ''): void
    {
        unset($this->coupons);
        if ($message !== '') {
            session()->flash('success', $message);
        }
    }

    /**
     * Render the component
     */
    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.coupons-list')
            ->layout('components.layouts.app');
    }
}
