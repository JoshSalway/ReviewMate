<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timely_integrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->string('account_id')->nullable();
            $table->boolean('auto_send_reviews')->default(true);
            $table->timestamps();
        });

        // Migrate existing data from businesses table
        DB::table('businesses')
            ->whereNotNull('timely_access_token')
            ->get()
            ->each(function ($business) {
                DB::table('timely_integrations')->insert([
                    'business_id'       => $business->id,
                    'access_token'      => $business->timely_access_token,
                    'refresh_token'     => $business->timely_refresh_token,
                    'token_expires_at'  => $business->timely_token_expires_at,
                    'account_id'        => $business->timely_account_id,
                    'auto_send_reviews' => $business->timely_auto_send_reviews ?? true,
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]);
            });

        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn([
                'timely_access_token',
                'timely_refresh_token',
                'timely_token_expires_at',
                'timely_account_id',
                'timely_auto_send_reviews',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->text('timely_access_token')->nullable();
            $table->text('timely_refresh_token')->nullable();
            $table->timestamp('timely_token_expires_at')->nullable();
            $table->string('timely_account_id')->nullable();
            $table->boolean('timely_auto_send_reviews')->default(true);
        });

        Schema::dropIfExists('timely_integrations');
    }
};
