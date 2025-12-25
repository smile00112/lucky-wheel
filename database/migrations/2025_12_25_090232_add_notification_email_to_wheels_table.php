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
            $table->string('notification_email')->nullable()->after('use_wheel_email_settings');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wheels', function (Blueprint $table) {
            $table->dropColumn('notification_email');
        });
    }
};
