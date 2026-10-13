<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Job applications from the careers page. The CV is a media file on the private disk.
        Schema::create('applicants', function (Blueprint $table) {
            $table->id();
            // Restrict: a job with applications can't be removed for good, so no application is lost.
            $table->foreignId('career_id')->constrained('careers')->restrictOnDelete();
            $table->string('name', 191);
            $table->string('email', 191);
            $table->string('phone', 30);
            $table->string('status', 20)->default('pending');
            $table->string('gender', 10)->nullable();
            $table->string('linkedin_profile', 500)->nullable();
            $table->text('cover_letter')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            // The list is newest first, filtered by status; search by how name / email / phone start.
            $table->index(['deleted_at', 'created_at']);
            $table->index('status');
            $table->index('name');
            $table->index('email');
            $table->index('phone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applicants');
    }
};
