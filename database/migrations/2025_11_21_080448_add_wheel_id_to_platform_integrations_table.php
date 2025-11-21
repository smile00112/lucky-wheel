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
            $table->foreignId('wheel_id')->nullable()->after('platform')->constrained()->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('platform_integrations', function (Blueprint $table) {
            $table->dropForeign(['wheel_id']);
            $table->dropColumn('wheel_id');
        });
    }
};
