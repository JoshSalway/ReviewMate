<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->boolean('auto_reply_enabled')->default(false)->after('widget_theme');
            $table->tinyInteger('auto_reply_min_rating')->default(4)->after('auto_reply_enabled');
            $table->string('auto_reply_tone')->default('friendly')->after('auto_reply_min_rating');
            $table->string('auto_reply_length')->default('medium')->after('auto_reply_tone');
            $table->string('auto_reply_signature')->nullable()->after('auto_reply_length');
            $table->text('auto_reply_custom_instructions')->nullable()->after('auto_reply_signature');
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn([
                'auto_reply_enabled',
                'auto_reply_min_rating',
                'auto_reply_tone',
                'auto_reply_length',
                'auto_reply_signature',
                'auto_reply_custom_instructions',
            ]);
        });
    }
};
