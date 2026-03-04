<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->text('halaxy_api_key')->nullable();
            $table->boolean('halaxy_auto_send_reviews')->default(true);
            $table->timestamp('halaxy_last_polled_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn([
                'halaxy_api_key',
                'halaxy_auto_send_reviews',
                'halaxy_last_polled_at',
            ]);
        });
    }
};
