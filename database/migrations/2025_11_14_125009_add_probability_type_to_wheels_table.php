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
        Schema::table('wheels', function (Blueprint $table) {
            $table->enum('probability_type', ['random', 'weighted'])->default('random')->after('refresh_hour');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wheels', function (Blueprint $table) {
            $table->dropColumn('probability_type');
        });
    }
};
