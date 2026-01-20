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
        Schema::table('plan_changes', function (Blueprint $table) {
            $table->string('payment_reference')->nullable()->after('metadata');
            $table->string('payment_gateway')->nullable()->after('payment_reference');

            $table->index('payment_reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plan_changes', function (Blueprint $table) {
            $table->dropIndex(['payment_reference']);
            $table->dropColumn(['payment_reference', 'payment_gateway']);
        });
    }
};
