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
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->timestamp('payment_failed_at')->nullable()->after('suspension_reason');
            $table->unsignedInteger('payment_failure_count')->default(0)->after('payment_failed_at');
            $table->boolean('suspension_warning_sent')->default(false)->after('payment_failure_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['payment_failed_at', 'payment_failure_count', 'suspension_warning_sent']);
        });
    }
};
