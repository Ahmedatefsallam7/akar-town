<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * New projects and developers start unpublished (as in Dastan), so nothing reaches the website before its data is
     * complete. Existing rows keep their value.
     */
    public function up(): void
    {
        foreach (['projects', 'developers'] as $table) {
            Schema::table($table, fn (Blueprint $table) => $table->boolean('is_published')->default(false)->change());
        }
    }

    public function down(): void
    {
        foreach (['projects', 'developers'] as $table) {
            Schema::table($table, fn (Blueprint $table) => $table->boolean('is_published')->default(true)->change());
        }
    }
};
