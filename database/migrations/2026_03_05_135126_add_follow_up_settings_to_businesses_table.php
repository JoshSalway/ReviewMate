<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->boolean('follow_up_enabled')->default(true)->after('onboarding_completed_at');
            $table->unsignedTinyInteger('follow_up_days')->default(3)->after('follow_up_enabled');
            $table->string('follow_up_channel')->default('same')->after('follow_up_days'); // 'same', 'sms', 'email'
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn(['follow_up_enabled', 'follow_up_days', 'follow_up_channel']);
        });
    }
};
