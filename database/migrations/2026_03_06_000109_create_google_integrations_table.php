<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('google_integrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->string('account_id')->nullable();
            $table->string('location_id')->nullable();
            $table->decimal('rating', 3, 2)->nullable();
            $table->unsignedInteger('review_count')->nullable();
            $table->timestamp('stats_updated_at')->nullable();
            $table->timestamps();
        });

        // Migrate existing data from businesses table
        DB::table('businesses')
            ->whereNotNull('google_access_token')
            ->get()
            ->each(function ($business) {
                DB::table('google_integrations')->insert([
                    'business_id'      => $business->id,
                    'access_token'     => $business->google_access_token,
                    'refresh_token'    => $business->google_refresh_token,
                    'token_expires_at' => $business->google_token_expires_at,
                    'account_id'       => $business->google_account_id,
                    'location_id'      => $business->google_location_id,
                    'rating'           => $business->google_rating,
                    'review_count'     => $business->google_review_count,
                    'stats_updated_at' => $business->google_stats_updated_at,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]);
            });

        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn([
                'google_access_token',
                'google_refresh_token',
                'google_token_expires_at',
                'google_account_id',
                'google_location_id',
                'google_rating',
                'google_review_count',
                'google_stats_updated_at',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->text('google_access_token')->nullable();
            $table->text('google_refresh_token')->nullable();
            $table->timestamp('google_token_expires_at')->nullable();
            $table->string('google_account_id')->nullable();
            $table->string('google_location_id')->nullable();
            $table->decimal('google_rating', 3, 2)->nullable();
            $table->unsignedInteger('google_review_count')->nullable();
            $table->timestamp('google_stats_updated_at')->nullable();
        });

        Schema::dropIfExists('google_integrations');
    }
};
