<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Indexes for the Users list: default sort (newest first, without deleted rows) and the status filter.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->index(['deleted_at', 'created_at'], 'users_deleted_at_created_at_index');
            $table->index('is_suspended');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_deleted_at_created_at_index');
            $table->dropIndex(['is_suspended']);
        });
    }
};
