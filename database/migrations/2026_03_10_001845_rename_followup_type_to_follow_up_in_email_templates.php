<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Rename any existing 'followup' type records to 'follow_up' in email_templates.
     *
     * DefaultTemplateService previously created templates with type 'followup' (no underscore)
     * but the UI and E2eSeeder both use 'follow_up'. This one-time migration standardises
     * all existing records to use the correct key.
     */
    public function up(): void
    {
        DB::table('email_templates')
            ->where('type', 'followup')
            ->update(['type' => 'follow_up']);
    }

    public function down(): void
    {
        DB::table('email_templates')
            ->where('type', 'follow_up')
            ->update(['type' => 'followup']);
    }
};
