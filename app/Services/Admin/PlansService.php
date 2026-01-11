<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Enums\BillingInterval;
use App\Models\Plan;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Exception;

class PlansService
{
    /**
     * Get all plans with optional active filter
     */
    public function getPlans(?bool $activeOnly = null): Collection
    {
        $query = Plan::query()
            ->withCount('subscriptions')
            ->orderBy('is_active', 'desc')
            ->orderBy('created_at', 'desc');

        if ($activeOnly !== null) {
            $query->where('is_active', $activeOnly);
        }

        return $query->get();
    }

    /**
     * Get a single plan by ID
     */
    public function getPlan(string $planId): ?Plan
    {
        return Plan::withCount('subscriptions')->find($planId);
    }

    /**
     * Create a new plan
     */
    public function createPlan(array $data): Plan
    {
        // Auto-generate slug if not provided
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        // Ensure features is array
        if (isset($data['features']) && is_string($data['features'])) {
            $data['features'] = json_decode($data['features'], true) ?? [];
        }

        return Plan::create($data);
    }

    /**
     * Update an existing plan
     */
    public function updatePlan(string $planId, array $data): Plan
    {
        $plan = Plan::findOrFail($planId);

        // Auto-generate slug if name changed
        if (isset($data['name']) && $data['name'] !== $plan->name && empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        // Ensure features is array
        if (isset($data['features']) && is_string($data['features'])) {
            $data['features'] = json_decode($data['features'], true) ?? [];
        }

        $plan->update($data);

        return $plan->fresh();
    }

    /**
     * Toggle plan active status
     */
    public function toggleActive(string $planId): Plan
    {
        $plan = Plan::findOrFail($planId);
        $plan->update(['is_active' => ! $plan->is_active]);

        return $plan->fresh();
    }

    /**
     * Delete a plan (only if no subscriptions exist)
     */
    public function deletePlan(string $planId): bool
    {
        $plan = Plan::withCount('subscriptions')->findOrFail($planId);

        if ($plan->subscriptions_count > 0) {
            throw new Exception("Cannot delete plan with existing subscriptions. This plan has {$plan->subscriptions_count} subscription(s).");
        }

        return $plan->delete();
    }

    /**
     * Check if plan can be deleted
     */
    public function canDelete(string $planId): bool
    {
        $plan = Plan::withCount('subscriptions')->findOrFail($planId);

        return $plan->subscriptions_count === 0;
    }

    /**
     * Get available billing intervals
     */
    public function getBillingIntervals(): array
    {
        return array_map(
            fn(BillingInterval $interval) => [
                'value' => $interval->value,
                'label' => ucfirst($interval->value),
            ],
            BillingInterval::cases(),
        );
    }

    /**
     * Get available currencies
     */
    public function getCurrencies(): array
    {
        return [
            ['value' => 'USD', 'label' => 'USD ($)'],
            ['value' => 'EUR', 'label' => 'EUR (€)'],
            ['value' => 'GBP', 'label' => 'GBP (£)'],
        ];
    }

    /**
     * Validate plan data
     */
    public function validatePlanData(array $data, ?string $planId = null): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                $planId ? "unique:plans,slug,{$planId}" : 'unique:plans,slug',
            ],
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'currency' => 'required|string|in:USD,EUR,GBP',
            'billing_interval' => 'required|in:monthly,quarterly,yearly',
            'duration_days' => 'required|integer|min:1',
            'max_devices' => 'nullable|integer|min:1',
            'woocommerce_id' => [
                'required',
                'string',
                $planId ? "unique:plans,woocommerce_id,{$planId}" : 'unique:plans,woocommerce_id',
            ],
            'my8k_plan_code' => 'required|string|max:255',
            'features' => 'nullable|json',
            'is_active' => 'boolean',
        ];

        return validator($data, $rules)->validate();
    }
}
