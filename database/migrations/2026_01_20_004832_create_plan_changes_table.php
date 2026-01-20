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
        Schema::create('plan_changes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('subscription_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('from_plan_id')->constrained('plans');
            $table->foreignUuid('to_plan_id')->constrained('plans');
            $table->foreignUuid('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type'); // upgrade, downgrade
            $table->string('status'); // pending, completed, failed, cancelled, scheduled
            $table->string('execution_type'); // immediate, scheduled
            $table->decimal('proration_amount', 10, 2);
            $table->decimal('credit_amount', 10, 2)->default(0);
            $table->string('currency', 10);
            $table->json('calculation_details')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['subscription_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index('scheduled_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_changes');
    }
};
