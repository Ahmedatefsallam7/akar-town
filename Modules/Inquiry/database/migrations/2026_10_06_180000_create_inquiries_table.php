<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Contact messages from the website's forms (or added by hand), each sent to the CRM as a fresh lead.
        Schema::create('inquiries', function (Blueprint $table) {
            $table->id();
            $table->string('full_name', 191);
            $table->string('email', 191)->nullable();
            $table->string('phone', 30);
            $table->text('message');
            // Where it came from: the form / context (e.g. "Project: Mountain Park") and the page URL.
            $table->string('source', 255)->nullable();
            $table->string('url', 2048)->nullable();
            $table->string('crm_lead_id', 191)->nullable();
            $table->timestamp('crm_synced_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            // The admin list is newest first, can be sorted by name, and searches names, emails and phones by how
            // they start (PrefixSearch), which these indexes make fast with many messages.
            $table->index(['deleted_at', 'created_at']);
            // Single-column indexes: the search is name OR email OR phone, and only these let MySQL use one per part.
            $table->index('full_name');
            $table->index('email');
            $table->index('phone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inquiries');
    }
};
