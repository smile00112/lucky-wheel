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
        Schema::create('spins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wheel_id')->constrained()->onDelete('cascade'); // Связь с колесом
            $table->foreignId('guest_id')->constrained()->onDelete('cascade'); // Связь с гостем
            $table->foreignId('prize_id')->nullable()->constrained()->onDelete('set null'); // Выигранный приз (может быть null)
            $table->string('ip_address', 45)->nullable(); // IP адрес при вращении
            $table->text('user_agent')->nullable(); // Браузер/устройство
            $table->enum('status', ['pending', 'completed', 'claimed', 'expired'])->default('completed'); // Статус выигрыша
            $table->timestamp('claimed_at')->nullable(); // Дата получения приза
            $table->json('metadata')->nullable(); // Дополнительная информация
            $table->timestamps();
            
            $table->index(['wheel_id', 'guest_id']);
            $table->index('prize_id');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spins');
    }
};
