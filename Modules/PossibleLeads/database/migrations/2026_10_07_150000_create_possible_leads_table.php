<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Engagement clicks on the website (form, WhatsApp, phone), recorded for sales to follow up. Can grow large,
        // so it's lean: no userstamps or soft deletes.
        Schema::create('possible_leads', function (Blueprint $table) {
            $table->id();
            $table->string('click_type', 20);
            $table->string('page_url', 2048)->nullable();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            // An anonymous id from a cookie (or the account when logged in), so one visitor counts once.
            $table->uuid('visitor_id');
            $table->foreignId('website_user_id')->nullable()->constrained('website_users')->nullOnDelete();
            // Set when the same visitor later sends a form: which lead it became.
            $table->nullableMorphs('converted_lead');
            $table->timestamp('converted_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            // Newest first, and this month's unique visitors read from the index alone.
            $table->index(['created_at', 'visitor_id']);
            $table->index(['click_type', 'created_at']);
            $table->index(['visitor_id', 'converted_at']);
            $table->index(['converted_at', 'created_at']);
        });

        // Leads sent from the website keep the visitor id, to mark that visitor's clicks as converted.
        foreach (['inquiries', 'meetings', 'unit_requests'] as $leads) {
            Schema::table($leads, fn (Blueprint $table) => $table->uuid('visitor_id')->nullable()->after('website_user_id'));
        }
    }

    public function down(): void
    {
        foreach (['inquiries', 'meetings', 'unit_requests'] as $leads) {
            Schema::table($leads, fn (Blueprint $table) => $table->dropColumn('visitor_id'));
        }

        Schema::dropIfExists('possible_leads');
    }
};
