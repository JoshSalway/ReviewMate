<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('halaxy_integrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->text('api_key')->nullable();
            $table->boolean('auto_send_reviews')->default(true);
            $table->timestamp('last_polled_at')->nullable();
            $table->timestamps();
        });

        // Migrate existing data from businesses table
        DB::table('businesses')
            ->whereNotNull('halaxy_api_key')
            ->get()
            ->each(function ($business) {
                DB::table('halaxy_integrations')->insert([
                    'business_id'       => $business->id,
                    'api_key'           => $business->halaxy_api_key,
                    'auto_send_reviews' => $business->halaxy_auto_send_reviews ?? true,
                    'last_polled_at'    => $business->halaxy_last_polled_at,
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]);
            });

        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn([
                'halaxy_api_key',
                'halaxy_auto_send_reviews',
                'halaxy_last_polled_at',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->text('halaxy_api_key')->nullable();
            $table->boolean('halaxy_auto_send_reviews')->default(true);
            $table->timestamp('halaxy_last_polled_at')->nullable();
        });

        Schema::dropIfExists('halaxy_integrations');
    }
};
