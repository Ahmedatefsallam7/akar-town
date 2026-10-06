<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Meeting / viewing bookings from the website's forms (or added by staff), each sent to the CRM as a fresh lead.
        Schema::create('meetings', function (Blueprint $table) {
            $table->id();
            $table->string('name', 191);
            $table->string('phone', 30);
            $table->string('email', 191)->nullable();
            $table->dateTime('meeting_at');
            $table->string('type', 20);
            $table->string('status', 20)->default('pending');
            // Where it came from: the form / context and the page URL.
            $table->string('source', 255)->nullable();
            $table->string('url', 2048)->nullable();
            // The client's words, and the agents' internal notes.
            $table->text('notes_en')->nullable();
            $table->text('notes_ar')->nullable();
            $table->text('admin_notes_en')->nullable();
            $table->text('admin_notes_ar')->nullable();
            $table->string('crm_lead_id', 191)->nullable();
            $table->timestamp('crm_synced_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            // The list is newest first; the calendar reads a date range; search by how name / email / phone start.
            $table->index(['deleted_at', 'created_at']);
            $table->index(['deleted_at', 'meeting_at']);
            $table->index('status');
            $table->index('type');
            $table->index('name');
            $table->index('email');
            $table->index('phone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meetings');
    }
};
