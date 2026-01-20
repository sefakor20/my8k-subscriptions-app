<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PlanChangeType;
use App\Models\Plan;
use App\Models\Subscription;

class ProrationCalculator
{
    /**
     * Calculate the number of days remaining in the current billing period.
     */
    public function getDaysRemaining(Subscription $subscription): int
    {
        $now = now();
        $expiresAt = $subscription->expires_at;

        if ($expiresAt->isPast()) {
            return 0;
        }

        return (int) $now->diffInDays($expiresAt, false);
    }

    /**
     * Calculate the total days in the current billing period.
     */
    public function getTotalDays(Subscription $subscription): int
    {
        $plan = $subscription->plan;

        return $plan->duration_days;
    }

    /**
     * Calculate unused credit from the current plan.
     * Credit = (Current Plan Price / Duration) × Days Remaining
     */
    public function calculateUnusedCredit(
        Subscription $subscription,
        ?string $gateway = null,
        ?string $currency = null,
    ): float {
        $plan = $subscription->plan;
        $daysRemaining = $this->getDaysRemaining($subscription);
        $totalDays = $this->getTotalDays($subscription);

        if ($totalDays === 0 || $daysRemaining <= 0) {
            return 0;
        }

        $currency = $currency ?? $subscription->currency ?? $plan->currency;
        $planPrice = $plan->getAmountFor($gateway, $currency);

        $dailyRate = $planPrice / $totalDays;
        $unusedCredit = $dailyRate * $daysRemaining;

        return round($unusedCredit, 2);
    }

    /**
     * Calculate the prorated cost for the new plan.
     * Cost = (New Plan Price / Duration) × Days Remaining
     */
    public function calculateProratedCost(
        Plan $newPlan,
        int $daysRemaining,
        ?string $gateway = null,
        string $currency = 'USD',
    ): float {
        $totalDays = $newPlan->duration_days;

        if ($totalDays === 0 || $daysRemaining <= 0) {
            return 0;
        }

        $planPrice = $newPlan->getAmountFor($gateway, $currency);
        $dailyRate = $planPrice / $totalDays;
        $proratedCost = $dailyRate * $daysRemaining;

        return round($proratedCost, 2);
    }

    /**
     * Calculate full proration for a plan change.
     *
     * @return array{
     *     type: PlanChangeType,
     *     days_remaining: int,
     *     total_days: int,
     *     current_plan_price: float,
     *     new_plan_price: float,
     *     unused_credit: float,
     *     prorated_cost: float,
     *     amount_due: float,
     *     credit_to_apply: float,
     *     currency: string
     * }
     */
    public function calculate(
        Subscription $subscription,
        Plan $newPlan,
        ?string $gateway = null,
    ): array {
        $currentPlan = $subscription->plan;
        $currency = $subscription->currency ?? $currentPlan->currency;

        $daysRemaining = $this->getDaysRemaining($subscription);
        $totalDays = $this->getTotalDays($subscription);

        $currentPlanPrice = $currentPlan->getAmountFor($gateway, $currency);
        $newPlanPrice = $newPlan->getAmountFor($gateway, $currency);

        $unusedCredit = $this->calculateUnusedCredit($subscription, $gateway, $currency);
        $proratedCost = $this->calculateProratedCost($newPlan, $daysRemaining, $gateway, $currency);

        // Determine if upgrade or downgrade
        $type = $newPlanPrice > $currentPlanPrice
            ? PlanChangeType::Upgrade
            : PlanChangeType::Downgrade;

        // Calculate amount due or credit
        $difference = $proratedCost - $unusedCredit;

        $amountDue = 0;
        $creditToApply = 0;

        if ($difference > 0) {
            // Customer owes money (upgrade)
            $amountDue = round($difference, 2);
        } else {
            // Customer gets credit (downgrade)
            $creditToApply = round(abs($difference), 2);
        }

        return [
            'type' => $type,
            'days_remaining' => $daysRemaining,
            'total_days' => $totalDays,
            'current_plan_price' => $currentPlanPrice,
            'new_plan_price' => $newPlanPrice,
            'unused_credit' => $unusedCredit,
            'prorated_cost' => $proratedCost,
            'amount_due' => $amountDue,
            'credit_to_apply' => $creditToApply,
            'currency' => $currency,
        ];
    }

    /**
     * Check if the plan change is an upgrade.
     */
    public function isUpgrade(Subscription $subscription, Plan $newPlan, ?string $gateway = null): bool
    {
        $currentPlan = $subscription->plan;
        $currency = $subscription->currency ?? $currentPlan->currency;

        $currentPlanPrice = $currentPlan->getAmountFor($gateway, $currency);
        $newPlanPrice = $newPlan->getAmountFor($gateway, $currency);

        return $newPlanPrice > $currentPlanPrice;
    }

    /**
     * Check if the plan change is a downgrade.
     */
    public function isDowngrade(Subscription $subscription, Plan $newPlan, ?string $gateway = null): bool
    {
        return ! $this->isUpgrade($subscription, $newPlan, $gateway);
    }
}
