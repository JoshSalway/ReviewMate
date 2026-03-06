<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('xero_integrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->string('tenant_id')->nullable();
            $table->boolean('auto_send_reviews')->default(true);
            $table->timestamps();
        });

        // Migrate existing data from businesses table
        DB::table('businesses')
            ->whereNotNull('xero_access_token')
            ->get()
            ->each(function ($business) {
                DB::table('xero_integrations')->insert([
                    'business_id' => $business->id,
                    'access_token' => $business->xero_access_token,
                    'refresh_token' => $business->xero_refresh_token,
                    'token_expires_at' => $business->xero_token_expires_at,
                    'tenant_id' => $business->xero_tenant_id,
                    'auto_send_reviews' => $business->xero_auto_send_reviews ?? true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });

        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn([
                'xero_access_token',
                'xero_refresh_token',
                'xero_token_expires_at',
                'xero_tenant_id',
                'xero_auto_send_reviews',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->text('xero_access_token')->nullable();
            $table->text('xero_refresh_token')->nullable();
            $table->timestamp('xero_token_expires_at')->nullable();
            $table->string('xero_tenant_id')->nullable();
            $table->boolean('xero_auto_send_reviews')->default(true);
        });

        Schema::dropIfExists('xero_integrations');
    }
};
