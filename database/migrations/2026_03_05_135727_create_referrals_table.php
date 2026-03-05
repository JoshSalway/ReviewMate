<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignId('referred_customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('referral_token')->unique();
            $table->string('referral_type'); // 'customer', 'business'
            $table->string('status')->default('pending'); // 'pending', 'signed_up', 'converted'
            $table->foreignId('referred_business_id')->nullable()->constrained('businesses')->nullOnDelete();
            $table->timestamp('signed_up_at')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->timestamp('reward_issued_at')->nullable();
            $table->timestamps();
        });

        // Add referral_token to users for tracking which referral brought them in
        Schema::table('users', function (Blueprint $table) {
            $table->string('referral_token')->nullable()->index();
            $table->foreignId('referral_id')->nullable()->constrained('referrals')->nullOnDelete();
        });

        // Add referral_token (shareable link token) to businesses
        Schema::table('businesses', function (Blueprint $table) {
            $table->string('referral_token')->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['referral_token']);
            $table->dropColumn(['referral_token', 'referral_id']);
        });
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn('referral_token');
        });
        Schema::dropIfExists('referrals');
    }
};
