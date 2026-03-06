<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_integrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('provider');
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->text('api_key')->nullable();
            $table->boolean('auto_send_reviews')->default(true);
            $table->timestamp('last_polled_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->unique(['business_id', 'provider']);
        });

        // Migrate data from each dedicated table into the unified table
        $migrate = function (string $provider, string $fromTable, callable $mapper): void {
            if (! Schema::hasTable($fromTable)) {
                return;
            }
            DB::table($fromTable)->orderBy('id')->each(function ($row) use ($provider, $mapper): void {
                DB::table('business_integrations')->insertOrIgnore(
                    array_merge($mapper($row), [
                        'business_id' => $row->business_id,
                        'provider' => $provider,
                        'created_at' => $row->created_at,
                        'updated_at' => $row->updated_at,
                    ])
                );
            });
        };

        $migrate('servicem8', 'servicem8_integrations', fn ($r) => [
            'access_token' => $r->access_token,
            'refresh_token' => $r->refresh_token,
            'token_expires_at' => $r->token_expires_at,
            'auto_send_reviews' => $r->auto_send_reviews,
        ]);

        $migrate('xero', 'xero_integrations', fn ($r) => [
            'access_token' => $r->access_token,
            'refresh_token' => $r->refresh_token,
            'token_expires_at' => $r->token_expires_at,
            'auto_send_reviews' => $r->auto_send_reviews,
            'meta' => json_encode(['tenant_id' => $r->tenant_id ?? null]),
        ]);

        $migrate('cliniko', 'cliniko_integrations', fn ($r) => [
            'api_key' => $r->api_key,
            'auto_send_reviews' => $r->auto_send_reviews,
            'last_polled_at' => $r->last_polled_at,
            'meta' => json_encode(['shard' => $r->shard ?? 'au1']),
        ]);

        $migrate('timely', 'timely_integrations', fn ($r) => [
            'access_token' => $r->access_token,
            'refresh_token' => $r->refresh_token,
            'token_expires_at' => $r->token_expires_at,
            'auto_send_reviews' => $r->auto_send_reviews,
            'meta' => json_encode(['account_id' => $r->account_id ?? null]),
        ]);

        $migrate('simpro', 'simpro_integrations', fn ($r) => [
            'access_token' => $r->access_token,
            'refresh_token' => $r->refresh_token,
            'token_expires_at' => $r->token_expires_at,
            'auto_send_reviews' => $r->auto_send_reviews,
            'meta' => json_encode(['company_url' => $r->company_url ?? null]),
        ]);

        $migrate('halaxy', 'halaxy_integrations', fn ($r) => [
            'api_key' => $r->api_key,
            'auto_send_reviews' => $r->auto_send_reviews,
            'last_polled_at' => $r->last_polled_at,
        ]);

        $migrate('google', 'google_integrations', fn ($r) => [
            'access_token' => $r->access_token,
            'refresh_token' => $r->refresh_token,
            'token_expires_at' => $r->token_expires_at,
            'meta' => json_encode([
                'account_id' => $r->account_id ?? null,
                'location_id' => $r->location_id ?? null,
                'rating' => $r->rating ?? null,
                'review_count' => $r->review_count ?? null,
                'stats_updated_at' => $r->stats_updated_at ?? null,
            ]),
        ]);

        $migrate('jobber', 'jobber_integrations', fn ($r) => [
            'access_token' => $r->access_token,
            'refresh_token' => $r->refresh_token,
            'token_expires_at' => $r->token_expires_at,
            'auto_send_reviews' => $r->auto_send_reviews,
        ]);

        $migrate('housecallpro', 'housecallpro_integrations', fn ($r) => [
            'access_token' => $r->access_token,
            'refresh_token' => $r->refresh_token,
            'token_expires_at' => $r->token_expires_at,
            'auto_send_reviews' => $r->auto_send_reviews,
        ]);

        // Drop the old dedicated tables
        Schema::dropIfExists('servicem8_integrations');
        Schema::dropIfExists('xero_integrations');
        Schema::dropIfExists('cliniko_integrations');
        Schema::dropIfExists('timely_integrations');
        Schema::dropIfExists('simpro_integrations');
        Schema::dropIfExists('halaxy_integrations');
        Schema::dropIfExists('google_integrations');
        Schema::dropIfExists('jobber_integrations');
        Schema::dropIfExists('housecallpro_integrations');
    }

    public function down(): void
    {
        Schema::dropIfExists('business_integrations');
    }
};
