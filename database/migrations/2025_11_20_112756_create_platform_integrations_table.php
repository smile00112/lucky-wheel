<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('platform_integrations', function (Blueprint $table) {
            $table->id();
            $table->string('platform')->unique(); // telegram, vk, max
            $table->string('bot_token')->nullable();
            $table->string('bot_username')->nullable();
            $table->string('webhook_url')->nullable();
            $table->boolean('is_active')->default(false);
            $table->json('settings')->nullable(); // Дополнительные настройки
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('platform_integrations');
    }
};
