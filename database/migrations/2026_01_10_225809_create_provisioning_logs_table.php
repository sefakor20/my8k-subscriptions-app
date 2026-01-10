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
        Schema::create('provisioning_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('subscription_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignUuid('order_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignUuid('service_account_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('action'); // Stores ProvisioningAction enum value
            $table->string('status'); // Stores ProvisioningStatus enum value
            $table->integer('attempt_number')->default(1);
            $table->string('job_id')->nullable();
            $table->json('my8k_request')->nullable();
            $table->json('my8k_response')->nullable();
            $table->text('error_message')->nullable();
            $table->string('error_code')->nullable();
            $table->integer('duration_ms')->nullable();
            $table->timestamps();

            $table->index(['subscription_id', 'created_at']);
            $table->index(['status', 'created_at']);
            $table->index(['action', 'status']);
            $table->index('order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provisioning_logs');
    }
};
