<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('review_requests', function (Blueprint $table) {
            $table->tinyInteger('private_rating')->nullable()->after('tracking_token');
            $table->text('private_feedback')->nullable()->after('private_rating');
            $table->timestamp('feedback_received_at')->nullable()->after('private_feedback');
        });
    }

    public function down(): void
    {
        Schema::table('review_requests', function (Blueprint $table) {
            $table->dropColumn(['private_rating', 'private_feedback', 'feedback_received_at']);
        });
    }
};
