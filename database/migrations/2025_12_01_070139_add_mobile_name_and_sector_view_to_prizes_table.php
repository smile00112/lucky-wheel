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
            $table->string('mobile_name')->nullable()->after('name');
            $table->enum('sector_view', ['text_with_image', 'only_image', 'only_text'])->default('text_with_image')->after('mobile_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prizes', function (Blueprint $table) {
            $table->dropColumn(['mobile_name', 'sector_view']);
        });
    }
};
