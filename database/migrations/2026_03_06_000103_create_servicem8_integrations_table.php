<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('servicem8_integrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->boolean('auto_send_reviews')->default(true);
            $table->timestamps();
        });

        // Migrate existing data from businesses table
        DB::table('businesses')
            ->whereNotNull('servicem8_access_token')
            ->get()
            ->each(function ($business) {
                DB::table('servicem8_integrations')->insert([
                    'business_id'       => $business->id,
                    'access_token'      => $business->servicem8_access_token,
                    'refresh_token'     => $business->servicem8_refresh_token,
                    'token_expires_at'  => $business->servicem8_token_expires_at,
                    'auto_send_reviews' => $business->servicem8_auto_send_reviews ?? true,
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]);
            });

        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn([
                'servicem8_access_token',
                'servicem8_refresh_token',
                'servicem8_token_expires_at',
                'servicem8_auto_send_reviews',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->text('servicem8_access_token')->nullable();
            $table->text('servicem8_refresh_token')->nullable();
            $table->timestamp('servicem8_token_expires_at')->nullable();
            $table->boolean('servicem8_auto_send_reviews')->default(true);
        });

        Schema::dropIfExists('servicem8_integrations');
    }
};
