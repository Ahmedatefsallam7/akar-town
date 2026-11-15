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
        Schema::table('developers', function (Blueprint $table) {
            $table->string('search_name', 500)->nullable()->after('name_ar');
        });

        DB::table('developers')->select(['id', 'name_en', 'name_ar'])->orderBy('id')->chunkById(1000, function ($rows) {
            foreach ($rows as $row) {
                $names = array_filter(array_map(fn ($name) => SearchText::normalize($name), [$row->name_en, $row->name_ar]), fn (string $name) => $name !== '');
                DB::table('developers')->where('id', $row->id)->update(['search_name' => implode("\n", $names)]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('developers', function (Blueprint $table) {
            $table->dropColumn('search_name');
        });
    }
};
