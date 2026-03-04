<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->text('timely_access_token')->nullable();
            $table->text('timely_refresh_token')->nullable();
            $table->timestamp('timely_token_expires_at')->nullable();
            $table->string('timely_account_id')->nullable();
            $table->boolean('timely_auto_send_reviews')->default(true);
        });
    }

    public function down(): void
    {
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
};
