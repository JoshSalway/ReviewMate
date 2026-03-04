<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('review_requests', function (Blueprint $table) {
            // possible values: manual, servicem8, xero, cliniko, timely
            $table->string('source')->default('manual')->after('channel');
        });
    }

    public function down(): void
    {
        Schema::table('review_requests', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
