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
        // Обновляем все NULL значения на #ffffff
        \DB::table('prizes')->whereNull('text_color')->update(['text_color' => '#ffffff']);
        
        Schema::table('prizes', function (Blueprint $table) {
            $table->string('text_color')->default('#ffffff')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prizes', function (Blueprint $table) {
            $table->string('text_color')->nullable()->change();
        });
    }
};
