<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Full-text indexes on the people's names of the admin's leads lists, so a search finds any word of the name ("Ali"
 * finds "Sara Ali") and stays fast with big tables: "%ali%" reads every row, the full-text index doesn't
 * (App\Filament\Support\PrefixSearch). MySQL / MariaDB only; the tests' SQLite searches the words with LIKE.
 */
return new class extends Migration
{
    private const NAMES = [
        'inquiries' => 'full_name',
        'meetings' => 'name',
        'applicants' => 'name',
        'website_users' => 'name',
        'unit_requests' => 'seller_name',
    ];

    public function up(): void
    {
        if (! in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        foreach (self::NAMES as $table => $column) {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->fullText($column, "{$table}_{$column}_fulltext"));
        }
    }

    public function down(): void
    {
        if (! in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        foreach (self::NAMES as $table => $column) {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropFullText("{$table}_{$column}_fulltext"));
        }
    }
};
