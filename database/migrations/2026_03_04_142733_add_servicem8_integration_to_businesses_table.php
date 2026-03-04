<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->unique()->after('id');
            $table->text('servicem8_access_token')->nullable();
            $table->text('servicem8_refresh_token')->nullable();
            $table->timestamp('servicem8_token_expires_at')->nullable();
            $table->boolean('servicem8_auto_send_reviews')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn([
                'uuid',
                'servicem8_access_token',
                'servicem8_refresh_token',
                'servicem8_token_expires_at',
                'servicem8_auto_send_reviews',
            ]);
        });
    }
};
