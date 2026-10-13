<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sell requests go to the CRM as leads too (website form "Submit Your Property").
        Schema::table('unit_requests', function (Blueprint $table) {
            $table->string('crm_lead_id', 191)->nullable()->after('unit_id');
            $table->timestamp('crm_synced_at')->nullable()->after('crm_lead_id');
            $table->index('crm_lead_id');
        });

        Schema::table('meetings', fn (Blueprint $table) => $table->index('crm_lead_id'));
    }

    public function down(): void
    {
        Schema::table('meetings', fn (Blueprint $table) => $table->dropIndex(['crm_lead_id']));

        Schema::table('unit_requests', function (Blueprint $table) {
            $table->dropIndex(['crm_lead_id']);
            $table->dropColumn(['crm_lead_id', 'crm_synced_at']);
        });
    }
};
