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
        Schema::table('orders', function (Blueprint $table) {
            // Make woocommerce_order_id nullable for non-WooCommerce orders
            $table->string('woocommerce_order_id')->nullable()->change();

            // Payment gateway identifier (paystack, stripe, woocommerce)
            $table->string('payment_gateway', 50)->default('woocommerce')->after('payment_method');

            // Gateway-specific transaction/reference ID
            $table->string('gateway_transaction_id')->nullable()->after('payment_gateway');

            // Gateway-specific checkout/session ID
            $table->string('gateway_session_id')->nullable()->after('gateway_transaction_id');

            // Gateway response metadata
            $table->json('gateway_metadata')->nullable()->after('gateway_session_id');

            // Index for gateway lookups
            $table->index(['payment_gateway', 'gateway_transaction_id'], 'orders_gateway_transaction_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_gateway_transaction_index');
            $table->dropColumn([
                'payment_gateway',
                'gateway_transaction_id',
                'gateway_session_id',
                'gateway_metadata',
            ]);

            // Restore woocommerce_order_id to non-nullable
            $table->string('woocommerce_order_id')->nullable(false)->change();
        });
    }
};
