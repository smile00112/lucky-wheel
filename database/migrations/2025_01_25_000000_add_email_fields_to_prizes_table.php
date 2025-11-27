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
            $table->string('email_name')->nullable()->after('name');
            $table->text('email_text_after_congratulation')->nullable()->after('email_name');
            $table->text('email_coupon_after_code_text')->nullable()->after('email_text_after_congratulation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prizes', function (Blueprint $table) {
            $table->dropColumn(['email_name', 'email_text_after_congratulation', 'email_coupon_after_code_text']);
        });
    }
};

