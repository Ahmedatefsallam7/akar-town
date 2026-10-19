<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * What the website's agent card shows when a user is picked as a unit's or project's agent: job title, WhatsApp
     * number, and the star rating with its number of reviews (typed by the admin).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('job_title_en')->nullable()->after('phone');
            $table->string('job_title_ar')->nullable()->after('job_title_en');
            $table->string('whatsapp', 30)->nullable()->after('job_title_ar');
            $table->decimal('rating', 2, 1)->nullable()->after('whatsapp');
            $table->unsignedInteger('reviews_count')->nullable()->after('rating');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['job_title_en', 'job_title_ar', 'whatsapp', 'rating', 'reviews_count']);
        });
    }
};
