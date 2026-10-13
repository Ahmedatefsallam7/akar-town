<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A slug is unique inside its category (it can repeat across categories, e.g. "studio").
     * parent_key turns a NULL parent into 0, so root categories are covered too (NULLs never clash in a unique index).
     */
    public function up(): void
    {
        Schema::table('lookups', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_key')->virtualAs('coalesce(parent_id, 0)');
            $table->unique(['parent_key', 'slug'], 'lookups_parent_key_slug_unique');
        });
    }

    public function down(): void
    {
        Schema::table('lookups', function (Blueprint $table) {
            $table->dropUnique('lookups_parent_key_slug_unique');
            $table->dropColumn('parent_key');
        });
    }
};
