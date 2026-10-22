<?php

namespace Modules\Developers\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Developers\Models\Developer;

/**
 * Well-known Egyptian developers (names from Dastan), names only: logos, descriptions and contacts are added in the
 * admin. Safe to run again: creates what's missing and never overwrites developers edited in the admin.
 * Locally it then adds the demo data (DevelopersDemoSeeder: logos, texts, contacts…).
 */
class DevelopersDatabaseSeeder extends Seeder
{
    private const DEVELOPERS = [
        ['Emaar Misr', 'إعمار مصر'],
        ['Sodic', 'سوديك'],
        ['Palm Hills Developments', 'بالم هيلز للتطوير العقاري'],
        ['Mountain View', 'ماونتن فيو'],
        ['Talaat Moustafa Group', 'مجموعة طلعت مصطفى'],
        ['Ora Developers', 'أورا للتطوير العقاري'],
        ['New Giza', 'نيو جيزة'],
        ['Tatweer Misr', 'تطوير مصر'],
        ['Misr Italia Properties', 'مصر إيطاليا'],
        ['Hassan Allam Properties', 'حسن علام العقارية'],
        ['Al Ahly Sabbour', 'الأهلي صبور'],
        ['Hyde Park Developments', 'هايد بارك للتطوير'],
        ['City Edge Developments', 'سيتي إيدج للتطوير'],
        ['La Vista Developments', 'لا فيستا للتطوير'],
        ['Rooya Group', 'مجموعة رؤية'],
        ['Madinet Masr', 'مدينة مصر'],
        ['Dorra Developments', 'درة للتطوير'],
        ['ARDIC Development', 'أرديك للتطوير'],
    ];

    public function run(): void
    {
        foreach (self::DEVELOPERS as [$nameEn, $nameAr]) {
            Developer::firstOrCreate(['slug' => Str::slug($nameEn)], [
                'name_en' => $nameEn,
                'name_ar' => $nameAr,
                'is_published' => true,
            ]);
        }

        if (app()->isLocal()) {
            $this->call(DevelopersDemoSeeder::class);
        }
    }
}
