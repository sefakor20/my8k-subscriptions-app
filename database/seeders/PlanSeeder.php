<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\BillingInterval;
use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Basic Monthly',
                'slug' => 'basic-monthly',
                'description' => 'Perfect for individuals looking for essential IPTV entertainment. Includes 5,000+ live channels, 10,000+ VOD movies, and SD/HD quality streaming.',
                'price' => 9.99,
                'currency' => 'USD',
                'billing_interval' => BillingInterval::Monthly,
                'duration_days' => 30,
                'max_devices' => 1,
                'features' => [
                    'channels' => 5000,
                    'vod_movies' => 10000,
                    'vod_series' => 5000,
                    'hd_quality' => true,
                    '4k_quality' => false,
                    'epg' => true,
                    'catch_up' => false,
                    'anti_freeze' => true,
                ],
                'is_active' => true,
                'woocommerce_id' => '1001',
                'my8k_plan_code' => 'PLAN_BASIC_M',
            ],
            [
                'name' => 'Standard Monthly',
                'slug' => 'standard-monthly',
                'description' => 'Great value for small families. Stream on up to 2 devices with 10,000+ live channels, 25,000+ VOD content, HD quality, and 7-day catch-up.',
                'price' => 19.99,
                'currency' => 'USD',
                'billing_interval' => BillingInterval::Monthly,
                'duration_days' => 30,
                'max_devices' => 2,
                'features' => [
                    'channels' => 10000,
                    'vod_movies' => 25000,
                    'vod_series' => 8000,
                    'hd_quality' => true,
                    '4k_quality' => false,
                    'epg' => true,
                    'catch_up' => true,
                    'anti_freeze' => true,
                ],
                'is_active' => true,
                'woocommerce_id' => '1002',
                'my8k_plan_code' => 'PLAN_STANDARD_M',
            ],
            [
                'name' => 'Premium Monthly',
                'slug' => 'premium-monthly',
                'description' => 'Ultimate IPTV experience with 25,000+ channels, 50,000+ VOD library, 4K Ultra HD support, up to 5 simultaneous connections, and advanced features.',
                'price' => 34.99,
                'currency' => 'USD',
                'billing_interval' => BillingInterval::Monthly,
                'duration_days' => 30,
                'max_devices' => 5,
                'features' => [
                    'channels' => 25000,
                    'vod_movies' => 50000,
                    'vod_series' => 15000,
                    'hd_quality' => true,
                    '4k_quality' => true,
                    'epg' => true,
                    'catch_up' => true,
                    'anti_freeze' => true,
                ],
                'is_active' => true,
                'woocommerce_id' => '1003',
                'my8k_plan_code' => 'PLAN_PREMIUM_M',
            ],
            [
                'name' => 'Premium Yearly',
                'slug' => 'premium-yearly',
                'description' => 'Save 30% with our annual Premium plan. All premium features with 12 months of uninterrupted access to 25,000+ channels, 50,000+ VOD, and 4K streaming.',
                'price' => 299.99,
                'currency' => 'USD',
                'billing_interval' => BillingInterval::Yearly,
                'duration_days' => 365,
                'max_devices' => 5,
                'features' => [
                    'channels' => 25000,
                    'vod_movies' => 50000,
                    'vod_series' => 15000,
                    'hd_quality' => true,
                    '4k_quality' => true,
                    'epg' => true,
                    'catch_up' => true,
                    'anti_freeze' => true,
                ],
                'is_active' => true,
                'woocommerce_id' => '1004',
                'my8k_plan_code' => 'PLAN_PREMIUM_Y',
            ],
            [
                'name' => 'Standard Quarterly',
                'slug' => 'standard-quarterly',
                'description' => 'Save 15% with 3 months prepaid. Perfect for households wanting consistent entertainment with 10,000+ channels and quality streaming on 2 devices.',
                'price' => 49.99,
                'currency' => 'USD',
                'billing_interval' => BillingInterval::Quarterly,
                'duration_days' => 90,
                'max_devices' => 2,
                'features' => [
                    'channels' => 10000,
                    'vod_movies' => 25000,
                    'vod_series' => 8000,
                    'hd_quality' => true,
                    '4k_quality' => false,
                    'epg' => true,
                    'catch_up' => true,
                    'anti_freeze' => true,
                ],
                'is_active' => true,
                'woocommerce_id' => '1005',
                'my8k_plan_code' => 'PLAN_STANDARD_Q',
            ],
        ];

        foreach ($plans as $planData) {
            Plan::create($planData);
        }
    }
}
