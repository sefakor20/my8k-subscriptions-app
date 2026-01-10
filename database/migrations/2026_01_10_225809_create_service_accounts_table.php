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
        Schema::create('service_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('subscription_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('my8k_account_id')->unique();
            $table->string('username'); // Encrypted via model cast
            $table->string('password'); // Encrypted via model cast
            $table->string('server_url');
            $table->integer('max_connections');
            $table->string('status'); // Stores ServiceAccountStatus enum value
            $table->timestamp('activated_at');
            $table->timestamp('expires_at');
            $table->timestamp('last_extended_at')->nullable();
            $table->json('my8k_metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['expires_at', 'status']);
            $table->index('my8k_account_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_accounts');
    }
};
