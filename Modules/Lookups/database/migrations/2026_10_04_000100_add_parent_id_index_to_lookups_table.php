<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The nested-set index starts with _lft, so lookups by parent_id (children counts, roles) scanned the table.
     */
    public function up(): void
    {
        Schema::table('lookups', function (Blueprint $table) {
            $table->index('parent_id');
        });
    }

    public function down(): void
    {
        Schema::table('lookups', function (Blueprint $table) {
            $table->dropIndex(['parent_id']);
        });
    }
};
