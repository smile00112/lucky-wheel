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
        Schema::create('prizes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wheel_id')->constrained()->onDelete('cascade'); // Связь с колесом
            $table->string('name'); // Название приза
            $table->text('description')->nullable(); // Описание приза
            $table->text('text_for_winner')->nullable(); // Текст для победителя
            $table->enum('type', ['discount', 'free_item', 'cash', 'points', 'other'])->default('other'); // Тип приза
            $table->string('value')->nullable(); // Значение приза (например, "10%", "1000 руб", "Бесплатный товар")
            $table->decimal('probability', 5, 2)->default(0); // Вероятность выпадения в процентах (0-100)
            $table->string('image')->nullable(); // Изображение приза
            $table->string('color')->nullable(); // Цвет сектора на колесе
            $table->boolean('is_active')->default(true); // Активен ли приз
            $table->integer('sort')->default(0); // Порядок отображения
            $table->integer('quantity_limit')->nullable(); // Лимит количества призов (null = без лимита)
            $table->integer('quantity_day_limit')->nullable(); // Лимит призов в день
            $table->integer('quantity_guest_limit')->nullable(); // Лимит призов на гостя
            $table->integer('quantity_used')->default(0); // Количество использованных призов
            $table->timestamps();
            
            $table->index(['wheel_id', 'is_active']);
            $table->index('sort');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prizes');
    }
};
