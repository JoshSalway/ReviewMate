<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->text('xero_access_token')->nullable();
            $table->text('xero_refresh_token')->nullable();
            $table->timestamp('xero_token_expires_at')->nullable();
            $table->string('xero_tenant_id')->nullable();
            $table->boolean('xero_auto_send_reviews')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
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
};
