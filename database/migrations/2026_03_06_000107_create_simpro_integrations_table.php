<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('simpro_integrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->string('company_url')->nullable();
            $table->boolean('auto_send_reviews')->default(true);
            $table->timestamps();
        });

        // Migrate existing data from businesses table
        DB::table('businesses')
            ->whereNotNull('simpro_access_token')
            ->get()
            ->each(function ($business) {
                DB::table('simpro_integrations')->insert([
                    'business_id'       => $business->id,
                    'access_token'      => $business->simpro_access_token,
                    'refresh_token'     => $business->simpro_refresh_token,
                    'token_expires_at'  => $business->simpro_token_expires_at,
                    'company_url'       => $business->simpro_company_url,
                    'auto_send_reviews' => $business->simpro_auto_send_reviews ?? true,
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]);
            });

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

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->text('simpro_access_token')->nullable();
            $table->text('simpro_refresh_token')->nullable();
            $table->timestamp('simpro_token_expires_at')->nullable();
            $table->string('simpro_company_url')->nullable();
            $table->boolean('simpro_auto_send_reviews')->default(true);
        });

        Schema::dropIfExists('simpro_integrations');
    }
};
