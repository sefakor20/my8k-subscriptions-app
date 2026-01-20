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
            $table->string('currency', 3)->nullable()->after('metadata');
            $table->decimal('credit_balance', 10, 2)->default(0)->after('currency');
            $table->foreignUuid('scheduled_plan_id')->nullable()->after('credit_balance')->constrained('plans')->nullOnDelete();
            $table->timestamp('plan_change_scheduled_at')->nullable()->after('scheduled_plan_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropForeign(['scheduled_plan_id']);
            $table->dropColumn(['currency', 'credit_balance', 'scheduled_plan_id', 'plan_change_scheduled_at']);
        });
    }
};
