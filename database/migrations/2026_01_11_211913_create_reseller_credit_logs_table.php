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
        Schema::create('reseller_credit_logs', function (Blueprint $table) {
            $table->id();
            $table->decimal('balance', 10, 2);
            $table->decimal('previous_balance', 10, 2)->nullable();
            $table->decimal('change_amount', 10, 2)->nullable();
            $table->enum('change_type', ['debit', 'credit', 'adjustment', 'snapshot'])->nullable();
            $table->string('reason')->nullable();
            $table->foreignId('related_provisioning_log_id')->nullable()->constrained('provisioning_logs')->onDelete('set null');
            $table->json('api_response')->nullable();
            $table->timestamps();

            $table->index('created_at');
            $table->index(['balance', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reseller_credit_logs');
    }
};
