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
            $table->string('company_name')->nullable();
            $table->string('logo')->nullable();
            $table->text('email_template')->nullable();
            $table->text('pdf_template')->nullable();
            $table->boolean('use_wheel_email_settings')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wheels', function (Blueprint $table) {
            $table->dropColumn([
                'company_name',
                'logo',
                'email_template',
                'pdf_template',
                'use_wheel_email_settings',
            ]);
        });
    }
};

