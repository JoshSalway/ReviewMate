<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->string('timezone')->default('Australia/Sydney')->after('auto_reply_custom_instructions');
            $table->timestamp('auto_reply_last_run_at')->nullable()->after('timezone');
            $table->unsignedInteger('auto_reply_last_reply_count')->default(0)->after('auto_reply_last_run_at');
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn(['timezone', 'auto_reply_last_run_at', 'auto_reply_last_reply_count']);
        });
    }
};
