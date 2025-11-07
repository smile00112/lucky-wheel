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
        Schema::create('guests', function (Blueprint $table) {
            $table->id();
            $table->string('email')->nullable()->index(); // Email (опционально)
            $table->string('phone')->nullable()->index(); // Телефон (опционально)
            $table->string('name')->nullable(); // Имя гостя
            $table->string('ip_address', 45)->nullable()->index(); // IP адрес
            $table->text('user_agent')->nullable(); // Браузер/устройство
            $table->json('metadata')->nullable(); // Дополнительная информация (JSON)
            $table->timestamps();
            
            $table->index(['email', 'phone']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guests');
    }
};
