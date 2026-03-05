<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cliniko_integrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->text('api_key')->nullable();
            $table->string('shard')->nullable();
            $table->boolean('auto_send_reviews')->default(true);
            $table->timestamp('last_polled_at')->nullable();
            $table->timestamps();
        });

        // Migrate existing data from businesses table
        DB::table('businesses')
            ->whereNotNull('cliniko_api_key')
            ->get()
            ->each(function ($business) {
                DB::table('cliniko_integrations')->insert([
                    'business_id'       => $business->id,
                    'api_key'           => $business->cliniko_api_key,
                    'shard'             => $business->cliniko_shard,
                    'auto_send_reviews' => $business->cliniko_auto_send_reviews ?? true,
                    'last_polled_at'    => $business->cliniko_last_polled_at,
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]);
            });

        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn([
                'cliniko_api_key',
                'cliniko_shard',
                'cliniko_auto_send_reviews',
                'cliniko_last_polled_at',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->text('cliniko_api_key')->nullable();
            $table->string('cliniko_shard')->nullable();
            $table->boolean('cliniko_auto_send_reviews')->default(true);
            $table->timestamp('cliniko_last_polled_at')->nullable();
        });

        Schema::dropIfExists('cliniko_integrations');
    }
};
