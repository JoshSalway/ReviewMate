<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->text('cliniko_api_key')->nullable();
            $table->string('cliniko_shard')->nullable();
            $table->boolean('cliniko_auto_send_reviews')->default(true);
            $table->timestamp('cliniko_last_polled_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn([
                'cliniko_api_key',
                'cliniko_shard',
                'cliniko_auto_send_reviews',
                'cliniko_last_polled_at',
            ]);
        });
    }
};
