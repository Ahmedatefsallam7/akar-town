<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Building details the website's property page shows: parking spaces, the building's floors, ownership type and
 * building year.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->unsignedTinyInteger('parking_spaces')->nullable()->after('floor');
            // Floors above the ground floor (shown as "G+2").
            $table->unsignedSmallInteger('building_floors')->nullable()->after('parking_spaces');
            $table->unsignedSmallInteger('building_year')->nullable()->after('building_floors');
            $table->foreignId('ownership_type_id')->nullable()->after('unit_status_id')->constrained('lookups')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ownership_type_id');
            $table->dropColumn(['parking_spaces', 'building_floors', 'building_year']);
        });
    }
};
