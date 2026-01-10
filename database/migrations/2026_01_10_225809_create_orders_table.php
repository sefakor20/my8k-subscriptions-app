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
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('subscription_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('woocommerce_order_id')->unique();
            $table->string('status'); // Stores OrderStatus enum value
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3);
            $table->string('payment_method')->nullable();
            $table->timestamp('paid_at');
            $table->timestamp('provisioned_at')->nullable();
            $table->string('idempotency_key')->unique();
            $table->json('webhook_payload')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['status', 'created_at']);
            $table->index('woocommerce_order_id');
            $table->index('idempotency_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
