<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * AKAR-52: a sell request has the same two sizes as a property: the old "area" becomes "land_area" (values kept), and
 * "built_up_area" (BUA) is added.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unit_requests', function (Blueprint $table) {
            $table->renameColumn('area', 'land_area');
        });
        Schema::table('unit_requests', function (Blueprint $table) {
            $table->unsignedInteger('built_up_area')->nullable()->after('land_area');
        });
    }

    public function down(): void
    {
        Schema::table('unit_requests', function (Blueprint $table) {
            $table->dropColumn('built_up_area');
        });
        Schema::table('unit_requests', function (Blueprint $table) {
            $table->renameColumn('land_area', 'area');
        });
    }
};
