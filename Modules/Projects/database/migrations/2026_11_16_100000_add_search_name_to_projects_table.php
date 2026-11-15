<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Lookups\Support\SearchText;

/**
 * The names as searches compare them (AKAR-48, App\Models\Concerns\HasSearchName): any part of a name, any case,
 * Arabic letters written either way, in one column. Filled for the rows already there.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('search_name', 500)->nullable()->after('title_ar');
        });

        DB::table('projects')->select(['id', 'title_en', 'title_ar'])->orderBy('id')->chunkById(1000, function ($rows) {
            foreach ($rows as $row) {
                $names = array_filter(array_map(fn ($name) => SearchText::normalize($name), [$row->title_en, $row->title_ar]), fn (string $name) => $name !== '');
                DB::table('projects')->where('id', $row->id)->update(['search_name' => implode("\n", $names)]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('search_name');
        });
    }
};
