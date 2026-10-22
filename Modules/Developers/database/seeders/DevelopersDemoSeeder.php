<?php

namespace Modules\Developers\Database\Seeders;

use Database\Seeders\Concerns\SeedsDemoData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Developers\Models\Developer;
use Modules\Lookups\Database\Seeders\LookupsDatabaseSeeder;

/**
 * Demo: every developer's empty fields filled (tagline, descriptions, contacts, place, SEO, logo, photo); every third
 * one featured. Local only, see SeedsDemoData. With SEED_SCALE, many more developers (1% of it).
 */
class DevelopersDemoSeeder extends Seeder
{
    use SeedsDemoData;

    public function run(): void
    {
        if (! $this->demo()) {
            return;
        }

        $this->needs(LookupsDatabaseSeeder::class);

        $place = $this->location('New Cairo');
        foreach (Developer::query()->where('slug', 'not like', 'bulk-%')->get() as $i => $developer) {
            $ar = $developer->name_ar ?: $developer->name_en;
            $this->fillEmpty($developer, [
                'tagline_en' => 'Building communities that last',
                'tagline_ar' => 'نبني مجتمعات تدوم',
                'short_description_en' => "{$developer->name_en} delivers premium residential and commercial communities across Egypt.",
                'short_description_ar' => "{$ar} تقدم مجتمعات سكنية وتجارية راقية في أنحاء مصر.",
                'description_en' => "<p>{$developer->name_en} has delivered more than 20 projects since 2004, known for on-time handover and high finishing standards.</p><p>Its portfolio covers apartments, villas and mixed-use towns in New Cairo, Sheikh Zayed and the North Coast.</p>",
                'description_ar' => "<p>سلمت {$ar} أكثر من 20 مشروعاً منذ 2004، وتُعرف بالالتزام بمواعيد التسليم وجودة التشطيب.</p><p>تضم مشروعاتها شققاً وفللاً ومدناً متكاملة في القاهرة الجديدة والشيخ زايد والساحل الشمالي.</p>",
                'email' => 'contact@'.(Str::slug($developer->name_en) ?: 'developer'.$developer->id).'.com',
                'phone' => '0120'.str_pad((string) (1000000 + $developer->id), 7, '0', STR_PAD_LEFT),
                'neighbourhood' => 'Fifth Settlement',
                ...$this->socials($developer->slug),
                ...($developer->country_id ? [] : ($place ?: $this->place('', $i))),
                ...$this->meta($developer->name_en, $ar),
            ]);
            if ($i % 3 === 0 && ! $developer->is_featured) {
                $developer->update(['is_featured' => true]);
            }
            $this->fullChain($developer);
            $this->mediaIfEmpty($developer, Developer::LOGO, [], $this->logo($developer->name_en));
            $this->mediaIfEmpty($developer, Developer::FEATURED_IMAGE, [self::PHOTOS[$i % count(self::PHOTOS)]]);
        }

        $chains = $this->chains();
        $now = now();
        $this->bulk('developers', 'slug', $this->volume(0.01), fn (int $n) => $chains[$n % count($chains)] + [
            'name_en' => "Bulk Developer {$n}",
            'name_ar' => "مطور {$n}",
            'slug' => "bulk-developer-{$n}",
            'is_published' => $n % 10 !== 0,
            'is_featured' => $n % 7 === 0,
            'tagline_en' => 'Building communities that last',
            'tagline_ar' => 'نبني مجتمعات تدوم',
            'short_description_en' => "Bulk Developer {$n} delivers residential communities across Egypt.",
            'short_description_ar' => "مطور {$n} يقدم مجتمعات سكنية في أنحاء مصر.",
            'description_en' => "<p>Bulk Developer {$n} has delivered many projects, known for on-time handover.</p>",
            'description_ar' => "<p>سلم مطور {$n} مشروعات كثيرة، ويُعرف بالالتزام بمواعيد التسليم.</p>",
            'email' => "contact@bulk-developer-{$n}.test",
            'phone' => '0128'.str_pad((string) $n, 7, '0', STR_PAD_LEFT),
            'neighbourhood' => 'Fifth Settlement',
            ...$this->socials("bulk-developer-{$n}"),
            ...$this->bulkMeta("Bulk Developer {$n}", "مطور {$n}"),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->stamps('developers');
    }

    /**
     * @return array<string, string> the developer page's social links
     */
    private function socials(string $handle): array
    {
        return collect(['facebook' => 'https://www.facebook.com/', 'instagram' => 'https://www.instagram.com/', 'linkedin' => 'https://www.linkedin.com/company/', 'youtube' => 'https://www.youtube.com/@'])
            ->mapWithKeys(fn (string $url, string $network) => ["social_{$network}" => $url.$handle])
            ->all();
    }
}
