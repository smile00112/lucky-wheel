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
        Schema::create('wheels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Владелец колеса (для SaaS)
            $table->string('name'); // Название колеса
            $table->text('description')->nullable(); // Описание
            $table->string('slug')->unique(); // URL-дружественный идентификатор
            $table->boolean('is_active')->default(true); // Активно ли колесо
            $table->json('settings')->nullable(); // Настройки колеса (цвета, количество секторов и т.д.)
            $table->integer('spins_limit')->nullable(); // Лимит вращений на гостя (null = без лимита)
            $table->timestamp('starts_at')->nullable(); // Дата начала акции
            $table->timestamp('ends_at')->nullable(); // Дата окончания акции
            $table->timestamps();
            
            $table->index(['user_id', 'is_active']);
            $table->index('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wheels');
    }
};
