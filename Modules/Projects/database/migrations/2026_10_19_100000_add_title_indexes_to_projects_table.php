<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Measured on 10,000 projects. Titles sorted and searched by how they start (the admin's project pickers, the website
 * search), like units' names: 25 ms without. The website listing: published projects counted from the index and
 * ordered "featured first, newest" without sorting them all (5,000 sorted in memory without).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->index('title_en');
            $table->index('title_ar');
            $table->index(['is_published', 'deleted_at', 'is_featured', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex(['title_en']);
            $table->dropIndex(['title_ar']);
            $table->dropIndex(['is_published', 'deleted_at', 'is_featured', 'created_at']);
        });
    }
};
