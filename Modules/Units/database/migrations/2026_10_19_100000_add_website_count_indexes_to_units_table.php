<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Website counts read from the index alone (measured on 50,000 units): all published units (the listing's total, 70 ms
 * without), and published units per project (the projects listing's "Units").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->index(['is_published', 'deleted_at']);
            $table->index(['project_id', 'is_published', 'deleted_at']);
        });
    }

    public function down(): void
    {
        // MySQL drops the project foreign key's own index once the wider one covers it: put it back (when gone) first.
        if (! Schema::hasIndex('units', 'units_project_id_foreign')) {
            Schema::table('units', fn (Blueprint $table) => $table->index('project_id', 'units_project_id_foreign'));
        }

        Schema::table('units', function (Blueprint $table) {
            $table->dropIndex(['is_published', 'deleted_at']);
            $table->dropIndex(['project_id', 'is_published', 'deleted_at']);
        });
    }
};
