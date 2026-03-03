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
        Schema::table('reviews', function (Blueprint $table) {
            $table->string('google_review_id')->nullable()->unique();
            $table->string('google_review_name')->nullable();
            $table->text('google_reply')->nullable();
            $table->timestamp('google_reply_posted_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn([
                'google_review_id',
                'google_review_name',
                'google_reply',
                'google_reply_posted_at',
            ]);
        });
    }
};
