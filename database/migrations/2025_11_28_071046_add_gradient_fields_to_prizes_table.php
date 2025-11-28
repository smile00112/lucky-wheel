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
        Schema::table('prizes', function (Blueprint $table) {
            $table->boolean('use_gradient')->default(false)->after('color');
            $table->string('gradient_start')->nullable()->after('use_gradient');
            $table->string('gradient_end')->nullable()->after('gradient_start');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prizes', function (Blueprint $table) {
            $table->dropColumn(['use_gradient', 'gradient_start', 'gradient_end']);
        });
    }
};
