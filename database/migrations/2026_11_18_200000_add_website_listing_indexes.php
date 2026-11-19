<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indexes for the website's heaviest reads (performance review, 2026-11-18), measured with 50,000 properties and
 * 10,000 projects:
 * - the compounds that have published properties / projects (the "Compound" filter): read from the index alone;
 * - a developer's published projects count (developers page, search);
 * - the listings' text search (the name, any part of it): checked inside the index in the "newest first" order, so
 *   the count and the page's ids need no row reads (a search went from ~300 ms to ~130 ms); with "featured first" and
 *   the compound in it too, the website search (GlobalSearch) finds its first ids the same way;
 * - the listing's "lowest price first" (no price last): the same expression as the sort, so the page is read in order;
 * - the listing's type filter: its count read from the index alone (~150 ms → 1 ms). Price ranges use the sort index
 *   above, bedrooms their own index; composite ones for the sizes were tried and MySQL didn't use them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->index(['is_published', 'deleted_at', 'sub_area_id'], 'units_published_compound_index');
            $table->index(['is_published', 'deleted_at', 'created_at', 'search_name', 'is_featured', 'sub_area_id'], 'units_published_search_index');
            $table->rawIndex('is_published, deleted_at, (price is null), price', 'units_published_price_sort_index');
            $table->index(['is_published', 'deleted_at', 'property_type_id'], 'units_published_type_index');
        });
        Schema::table('projects', function (Blueprint $table) {
            $table->index(['is_published', 'deleted_at', 'sub_area_id'], 'projects_published_compound_index');
            $table->index(['is_published', 'deleted_at', 'created_at', 'search_name', 'is_featured', 'sub_area_id'], 'projects_published_search_index');
            // developer_id last: an index starting with it would replace the foreign key's own index (MySQL drops
            // that one), and then couldn't be dropped on a rollback. The count compares all three, so it's as fast.
            $table->index(['is_published', 'deleted_at', 'developer_id'], 'projects_developer_published_index');
        });
    }

    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->dropIndex('units_published_compound_index');
            $table->dropIndex('units_published_search_index');
            $table->dropIndex('units_published_price_sort_index');
            $table->dropIndex('units_published_type_index');
        });
        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex('projects_published_compound_index');
            $table->dropIndex('projects_published_search_index');
            $table->dropIndex('projects_developer_published_index');
        });
    }
};
