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
            $table->decimal('google_rating', 3, 2)->nullable()->after('google_place_id');
            $table->unsignedInteger('google_review_count')->nullable()->after('google_rating');
            $table->timestamp('google_stats_updated_at')->nullable()->after('google_review_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn(['google_rating', 'google_review_count', 'google_stats_updated_at']);
        });
    }
};
