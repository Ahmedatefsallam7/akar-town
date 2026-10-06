<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lookup values picked on any record (a project's amenities, views…). "type" is the lookup type's slug, so each
     * checkbox list saves only its own values and the website can filter on them.
     */
    public function up(): void
    {
        Schema::create('lookupables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lookup_id')->constrained('lookups')->cascadeOnDelete();
            $table->morphs('lookupable');
            $table->string('type', 50);

            $table->unique(['lookupable_type', 'lookupable_id', 'lookup_id'], 'lookupables_record_lookup_unique');
            // "Projects with amenity X" (website filters).
            $table->index(['lookup_id', 'lookupable_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lookupables');
    }
};
