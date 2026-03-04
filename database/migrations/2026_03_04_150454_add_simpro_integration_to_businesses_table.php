<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->text('simpro_access_token')->nullable();
            $table->text('simpro_refresh_token')->nullable();
            $table->timestamp('simpro_token_expires_at')->nullable();
            $table->string('simpro_company_url')->nullable();
            $table->boolean('simpro_auto_send_reviews')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn([
                'simpro_access_token',
                'simpro_refresh_token',
                'simpro_token_expires_at',
                'simpro_company_url',
                'simpro_auto_send_reviews',
            ]);
        });
    }
};
