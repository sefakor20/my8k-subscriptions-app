<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\PlanPrice;
use Illuminate\Database\Seeder;

class PlanPriceSeeder extends Seeder
{
    /**
     * USD to GHS conversion rate (approximate).
     * This should be updated to reflect current exchange rates.
     */
    private const USD_TO_GHS_RATE = 15.0;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = Plan::where('is_active', true)->get();

        foreach ($plans as $plan) {
            $this->createPricesForPlan($plan);
        }
    }

    private function createPricesForPlan(Plan $plan): void
    {
        $basePriceUsd = floatval($plan->price);
        $ghsPrice = round($basePriceUsd * self::USD_TO_GHS_RATE, 2);

        // Default USD price (no gateway specified)
        PlanPrice::firstOrCreate(
            [
                'plan_id' => $plan->id,
                'gateway' => null,
                'currency' => 'USD',
            ],
            [
                'price' => $basePriceUsd,
                'is_active' => true,
            ],
        );

        // Paystack GHS price
        PlanPrice::firstOrCreate(
            [
                'plan_id' => $plan->id,
                'gateway' => 'paystack',
                'currency' => 'GHS',
            ],
            [
                'price' => $ghsPrice,
                'is_active' => true,
            ],
        );

        // Stripe USD price
        PlanPrice::firstOrCreate(
            [
                'plan_id' => $plan->id,
                'gateway' => 'stripe',
                'currency' => 'USD',
            ],
            [
                'price' => $basePriceUsd,
                'is_active' => true,
            ],
        );
    }
}
