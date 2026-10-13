<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The list sorts newest first, then by id: an index on created_at alone (InnoDB adds id) serves that order, so
        // the page doesn't sort the whole table. The (created_at, visitor_id) index stays for the unique-visitor count.
        Schema::table('possible_leads', fn (Blueprint $table) => $table->index('created_at'));
    }

    public function down(): void
    {
        Schema::table('possible_leads', fn (Blueprint $table) => $table->dropIndex(['created_at']));
    }
};
