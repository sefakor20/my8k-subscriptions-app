<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('streaming_apps', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type'); // mag, m3u, enigma2
            $table->string('platform'); // android, windows, smart_tv, linux_box, ios, macos, fire_tv
            $table->string('version')->nullable();
            $table->string('download_url');
            $table->string('downloader_code')->nullable();
            $table->string('short_url')->nullable();
            $table->boolean('is_recommended')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
            $table->index('platform');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('streaming_apps');
    }
};
