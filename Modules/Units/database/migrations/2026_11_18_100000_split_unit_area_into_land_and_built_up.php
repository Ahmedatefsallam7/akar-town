<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * AKAR-52: a property has two sizes. The old "area" becomes "land_area" (its values kept), and "built_up_area" (BUA)
 * is added, empty until filled in. Both in m², indexed for the website's filters.
 *
 * "shown_area" is the size a card shows (the BUA, else the land area), kept by the database itself and indexed, so the
 * quick size ranges and "largest first" stay fast with many properties.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->renameColumn('area', 'land_area');
        });
        Schema::table('units', function (Blueprint $table) {
            $table->renameIndex('units_area_index', 'units_land_area_index');
            $table->unsignedInteger('built_up_area')->nullable()->after('land_area')->index();
        });
        Schema::table('units', function (Blueprint $table) {
            $table->unsignedInteger('shown_area')->nullable()->virtualAs('COALESCE(built_up_area, land_area)')->after('built_up_area')->index();
        });
    }

    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->dropIndex(['shown_area']);
            $table->dropColumn('shown_area');
        });
        Schema::table('units', function (Blueprint $table) {
            $table->dropIndex(['built_up_area']);
            $table->dropColumn('built_up_area');
            $table->renameIndex('units_land_area_index', 'units_area_index');
        });
        Schema::table('units', function (Blueprint $table) {
            $table->renameColumn('land_area', 'area');
        });
    }
};
