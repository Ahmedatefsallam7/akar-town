<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Which website form sent it (contact page, a property's or a project's enquiry form): routes it to its CRM form.
        Schema::table('inquiries', function (Blueprint $table) {
            $table->string('form', 30)->default('contact_us')->after('message');
            $table->index('crm_lead_id');
        });
    }

    public function down(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->dropIndex(['crm_lead_id']);
            $table->dropColumn('form');
        });
    }
};
