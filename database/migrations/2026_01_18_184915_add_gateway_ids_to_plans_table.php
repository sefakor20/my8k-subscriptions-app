<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            // Paystack plan code (for recurring subscriptions)
            $table->string('paystack_plan_code')->nullable()->after('woocommerce_id');

            // Stripe price ID
            $table->string('stripe_price_id')->nullable()->after('paystack_plan_code');

            // Index for gateway lookups
            $table->index('paystack_plan_code');
            $table->index('stripe_price_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropIndex(['paystack_plan_code']);
            $table->dropIndex(['stripe_price_id']);
            $table->dropColumn(['paystack_plan_code', 'stripe_price_id']);
        });
    }
};
