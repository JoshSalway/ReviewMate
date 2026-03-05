<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->boolean('widget_enabled')->default(true);
            $table->unsignedTinyInteger('widget_min_rating')->default(4);
            $table->unsignedTinyInteger('widget_max_reviews')->default(6);
            $table->string('widget_theme')->default('light'); // 'light', 'dark'
            $table->string('slug')->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn(['widget_enabled', 'widget_min_rating', 'widget_max_reviews', 'widget_theme', 'slug']);
        });
    }
};
