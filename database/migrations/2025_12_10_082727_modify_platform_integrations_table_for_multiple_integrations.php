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
        Schema::table('platform_integrations', function (Blueprint $table) {
            // Убираем уникальность с platform
            $table->dropUnique(['platform']);
            
            // Добавляем составной уникальный индекс на (platform, wheel_id)
            // В MySQL/MariaDB NULL значения в уникальном индексе не считаются дубликатами
            // Это позволит иметь несколько интеграций одной платформы, но по одной на колесо
            $table->unique(['platform', 'wheel_id'], 'platform_wheel_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('platform_integrations', function (Blueprint $table) {
            // Убираем составной индекс
            $table->dropUnique('platform_wheel_unique');
            
            // Возвращаем уникальность на platform
            $table->unique('platform');
        });
    }
};
