<?php

namespace Modules\Lookups\Database\Seeders;

use Database\Seeders\Concerns\SeedsDemoData;
use Illuminate\Database\Seeder;
use Modules\Lookups\Models\Lookup;

/**
 * Demo: every lookup value gets a short description in both languages (local only, see SeedsDemoData).
 */
class LookupsDemoSeeder extends Seeder
{
    use SeedsDemoData;

    public function run(): void
    {
        if (! $this->demo()) {
            return;
        }

        foreach (Lookup::query()->whereNotNull('parent_id')->whereNull('description_en')->get() as $lookup) {
            $lookup->update([
                'description_en' => "{$lookup->name_en} — shown in the website filters and on the property pages.",
                'description_ar' => ($lookup->name_ar ?: $lookup->name_en).' — يظهر في فلاتر الموقع وصفحات العقارات.',
            ]);
        }

        $this->stamps('lookups');
    }
}
