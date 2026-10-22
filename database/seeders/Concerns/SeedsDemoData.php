<?php

namespace Database\Seeders\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Modules\Lookups\Models\Lookup;
use Modules\Users\Models\User;

/**
 * Shared helpers of the demo seeders (every admin field filled with sample data, to test by hand).
 *
 * Demo data is made in the local environment only (never on the live site). Each seeder runs what it needs first
 * (needs()), once per run, so a module's seeder also works alone: `php artisan module:seed Projects`.
 * Only empty values are written, so running again never overwrites what was entered in the admin.
 * Photos come from Unsplash (the design's) and are kept in storage/app/demo after the first download.
 *
 * Big data for speed tests: SEED_SCALE in .env (extra properties; the other modules in proportion). Those rows are
 * inserted in chunks with every field filled but no photos, marked "bulk-" (slug / e-mail), and topped up to the
 * number on each run (never doubled). Start again with `php artisan migrate:fresh --seed`.
 *
 * @mixin Seeder
 */
trait SeedsDemoData
{
    /** Unsplash photos of the design, used for every image field. */
    protected const PHOTOS = [
        'photo-1545324418-cc1a3fa10c00', 'photo-1512917774080-9991f1c4c750', 'photo-1600596542815-ffad4c1539a9',
        'photo-1600607687939-ce8a6c25118c', 'photo-1600585154340-be6161a56a0c', 'photo-1499793983690-e29da59ef1c2',
        'photo-1613490493576-7fde63acd811', 'photo-1600566753190-17f0baa2a6c3', 'photo-1600210492493-0946911123ea',
        'photo-1580587771525-78b9dba3b914', 'photo-1600585154526-990dced4db0d', 'photo-1522708323590-d24dbb6b0267',
    ];

    /** Portraits for the user and agent photos (the first one is the design's agent). */
    protected const PORTRAITS = ['photo-1507003211169-0a1dd7228f2d', 'photo-1494790108377-be9c29b29330', 'photo-1500648767791-00dcc994a43e', 'photo-1438761681033-6461ffad8d80'];

    protected const PLAN = 'photo-1503387762-592deb58ef4e';

    protected const VIDEO = 'https://www.youtube.com/watch?v=ScMzIvxBSi4';

    /** @var array<string, array<string, int>> lookup type => child name_en => id */
    private array $lookupIds = [];

    /**
     * Demo data is for the local environment only.
     */
    protected function demo(): bool
    {
        if (app()->isLocal()) {
            return true;
        }

        $this->command?->warn(static::class.' skipped: demo data is made in the local environment only.');

        return false;
    }

    /**
     * Runs other seeders first, each once per run (db:seed runs every module; a module seeder alone runs what it
     * needs).
     *
     * @param  class-string  ...$seeders
     */
    protected function needs(string ...$seeders): void
    {
        foreach ($seeders as $seeder) {
            if (! app()->bound("seeded.{$seeder}")) {
                app()->instance("seeded.{$seeder}", true);
                $this->call($seeder);
            }
        }
    }

    /**
     * How many bulk rows to add for a module: SEED_SCALE × its share (at least $min when the scale is on).
     */
    protected function volume(float $share, int $min = 1): int
    {
        $scale = max(0, (int) config('app.seed_scale'));

        return $scale ? max($min, (int) round($scale * $share)) : 0;
    }

    /**
     * Inserts bulk rows numbered after the ones already there (rows whose $column matches $pattern: starts with
     * "bulk-"), up to $target, 1,000 per insert.
     *
     * @param  \Closure(int): array<string, mixed>  $row  the row for number n
     */
    protected function bulk(string $table, string $column, int $target, \Closure $row, string $pattern = 'bulk-%'): void
    {
        $have = $target > 0 ? DB::table($table)->where($column, 'like', $pattern)->count() : 0;
        if ($target <= $have) {
            return;
        }

        $this->command?->info('  '.number_format($target - $have)." more {$table} (SEED_SCALE)");
        foreach (array_chunk(range($have + 1, $target), 1000) as $numbers) {
            DB::table($table)->insert(array_map($row, $numbers));
        }
    }

    /**
     * A fixed UUID for bulk row n (visitor ids), so bulk leads and clicks can share visitors.
     */
    protected function bulkUuid(int $n): string
    {
        return sprintf('00000000-0000-4000-8000-%012d', $n);
    }

    /**
     * Picked lookup values (amenities, views…) for the bulk rows that have none yet.
     *
     * @param  class-string<Model>  $model
     * @param  array<string, int>  $types  lookup type => how many each
     */
    protected function bulkLookups(string $model, string $table, string $column, array $types): void
    {
        $ids = array_filter(array_map(fn (string $type) => array_values($this->children($type)), array_combine(array_keys($types), array_keys($types))));
        if ($ids === [] || ! DB::table($table)->where($column, 'like', 'bulk-%')->exists()) {
            return;
        }

        DB::table($table)->where($column, 'like', 'bulk-%')
            ->whereNotExists(fn ($query) => $query->from('lookupables')->whereColumn('lookupable_id', "{$table}.id")->where('lookupable_type', $model))
            ->select('id')->orderBy('id')
            ->chunk(1000, function ($records) use ($model, $types, $ids) {
                $rows = [];
                foreach ($records as $record) {
                    foreach ($ids as $type => $ofType) {
                        for ($n = 0; $n < min($types[$type], count($ofType)); $n++) {
                            $id = $ofType[($record->id + $n) % count($ofType)];
                            $rows["{$record->id}-{$id}"] = ['lookup_id' => $id, 'lookupable_type' => $model, 'lookupable_id' => $record->id, 'type' => $type];
                        }
                    }
                }
                DB::table('lookupables')->insert(array_values($rows));
            });
    }

    /**
     * Real place chains (country_id … sub_area_id) to spread bulk rows over: the locations tree's deepest places.
     *
     * @return list<array<string, int|null>>
     */
    protected function chains(): array
    {
        $root = Lookup::query()->whereNull('parent_id')->where('slug', 'locations')->first();
        if (! $root) {
            return [['country_id' => null, 'city_id' => null, 'region_id' => null, 'area_id' => null, 'sub_area_id' => null]];
        }

        $nodes = Lookup::query()->whereDescendantOf($root)->get(['id', 'parent_id', '_lft', '_rgt'])->keyBy('id');
        $leaves = $nodes->filter(fn (Lookup $node) => $node->_rgt - $node->_lft === 1)->take(50);
        $columns = ['country_id', 'city_id', 'region_id', 'area_id', 'sub_area_id'];

        return $leaves->map(function (Lookup $leaf) use ($nodes, $columns) {
            $path = [];
            for ($node = $leaf; $node && $nodes->has($node->id); $node = $nodes->get($node->parent_id)) {
                array_unshift($path, $node->id);
            }

            return array_combine($columns, array_pad(array_slice($path, 0, 5), 5, null));
        })->values()->all() ?: [array_fill_keys($columns, null)];
    }

    /**
     * SEO fields for a bulk row (insert-ready: the keywords as JSON).
     *
     * @return array<string, string>
     */
    protected function bulkMeta(string $en, string $ar): array
    {
        $meta = $this->meta($en, $ar);
        $meta['meta_keywords_en'] = json_encode($meta['meta_keywords_en'], JSON_UNESCAPED_UNICODE);
        $meta['meta_keywords_ar'] = json_encode($meta['meta_keywords_ar'], JSON_UNESCAPED_UNICODE);

        return $meta;
    }

    /**
     * Sets the given values only where the record's field is still empty, then saves.
     *
     * @param  array<string, mixed>  $values
     */
    protected function fillEmpty(Model $model, array $values): void
    {
        foreach ($values as $key => $value) {
            $current = $model->getAttribute($key);
            if (($current === null || $current === '' || $current === []) && $value !== null) {
                $model->setAttribute($key, $value);
            }
        }
        $model->save();
    }

    /**
     * Picks $count values of a lookup type for the record, unless it has some already.
     */
    protected function lookupsIfEmpty(Model $model, string $type, int $count, int $offset): void
    {
        if ($model->lookupsOfType($type)->exists() || ! ($ids = array_values($this->children($type)))) {
            return;
        }

        $picked = [];
        for ($n = 0; $n < min($count, count($ids)); $n++) {
            $picked[] = $ids[($offset + $n) % count($ids)];
        }
        $model->lookupsOfType($type)->sync($picked);
    }

    /**
     * Adds photos (or one file) to an empty media collection.
     *
     * @param  list<string>  $photos  Unsplash ids
     */
    protected function mediaIfEmpty(Model $model, string $collection, array $photos, ?string $file = null): void
    {
        if ($model->hasMedia($collection)) {
            return;
        }

        foreach ($file ? [$file] : array_map(fn (string $photo) => $this->photo($photo), $photos) as $path) {
            $model->addMedia($path)->preservingOriginal()->toMediaCollection($collection);
        }
    }

    /**
     * A photo kept in storage/app/demo after its first download, so runs after the first are offline and fast.
     */
    protected function photo(string $id): string
    {
        $path = storage_path("app/demo/{$id}.jpg");
        if (! is_file($path)) {
            @mkdir(dirname($path), 0775, true);
            file_put_contents($path, Http::timeout(60)->get("https://images.unsplash.com/{$id}?w=1600&auto=format&fit=crop&q=85&fm=jpg")->throw()->body());
        }

        return $path;
    }

    /**
     * A square logo with the name's initials (white on the brand red).
     */
    protected function logo(string $name): string
    {
        $letters = collect(preg_split('/\s+/', trim($name)))->filter()->take(2)->map(fn (string $word) => strtoupper($word[0]))->implode('');
        $path = storage_path("app/demo/logo-{$letters}.png");
        if (! is_file($path)) {
            @mkdir(dirname($path), 0775, true);
            $image = imagecreatetruecolor(400, 400);
            imagefill($image, 0, 0, imagecolorallocate($image, 200, 16, 46));
            $tile = imagecreatetruecolor(20, 16);
            imagefill($tile, 0, 0, imagecolorallocate($tile, 200, 16, 46));
            imagestring($tile, 5, 1, 0, $letters, imagecolorallocate($tile, 255, 255, 255));
            imagecopyresized($image, $tile, 50, 90, 0, 0, 300, 240, 20, 16);
            imagepng($image, $path);
        }

        return $path;
    }

    /**
     * A one-page PDF (resumes, brochures and gallery attachments).
     */
    protected function pdf(): string
    {
        $path = storage_path('app/demo/demo.pdf');
        if (! is_file($path)) {
            @mkdir(dirname($path), 0775, true);
            $text = 'BT /F1 24 Tf 72 720 Td (Akar Town demo document) Tj ET';
            $objects = ['<< /Type /Catalog /Pages 2 0 R >>', '<< /Type /Pages /Kids [3 0 R] /Count 1 >>', '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>', '<< /Length '.strlen($text)." >>\nstream\n{$text}\nendstream", '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>'];
            $pdf = "%PDF-1.4\n";
            $offsets = [];
            foreach ($objects as $n => $object) {
                $offsets[] = strlen($pdf);
                $pdf .= ($n + 1)." 0 obj\n{$object}\nendobj\n";
            }
            $xref = strlen($pdf);
            $pdf .= 'xref
0 '.(count($objects) + 1)."\n0000000000 65535 f \n".implode('', array_map(fn ($offset) => sprintf("%010d 00000 n \n", $offset), $offsets));
            $pdf .= 'trailer << /Size '.(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";
            file_put_contents($path, $pdf);
        }

        return $path;
    }

    /**
     * A portrait copied to the public disk as a 512×512 square (as the admin's avatar field saves it); returns its
     * path there.
     */
    protected function avatar(string $directory, int $i): string
    {
        $id = self::PORTRAITS[$i % count(self::PORTRAITS)];
        $target = "{$directory}/demo-{$id}.jpg";
        if (! Storage::disk('public')->exists($target)) {
            $photo = imagecreatefromjpeg($this->photo($id));
            $side = min(imagesx($photo), imagesy($photo));
            $square = imagecreatetruecolor(512, 512);
            imagecopyresampled($square, $photo, 0, 0, intdiv(imagesx($photo) - $side, 2), intdiv(imagesy($photo) - $side, 2), 512, 512, $side, $side);
            ob_start();
            imagejpeg($square, null, 82);
            Storage::disk('public')->put($target, ob_get_clean());
        }

        return $target;
    }

    /**
     * @return array<string, mixed> SEO fields in both languages
     */
    protected function meta(string $en, string $ar): array
    {
        return [
            'meta_title_en' => "{$en} | Akar Town",
            'meta_title_ar' => "{$ar} | عقار تاون",
            'meta_description_en' => "Discover {$en} with Akar Town: prices, photos, payment plans and location.",
            'meta_description_ar' => "اكتشف {$ar} مع عقار تاون: الأسعار والصور وخطط السداد والموقع.",
            'meta_keywords_en' => ['real estate', 'Egypt', $en],
            'meta_keywords_ar' => ['عقارات', 'مصر', $ar],
        ];
    }

    /**
     * @return list<string> $count photos starting at a different one for each record
     */
    protected function rotate(int $offset, int $count): array
    {
        return array_map(fn (int $n) => self::PHOTOS[($offset + $n) % count(self::PHOTOS)], range(0, $count - 1));
    }

    /**
     * @return array<string, int> child name_en => id of a lookup type
     */
    protected function children(string $type): array
    {
        return $this->lookupIds[$type] ??= Lookup::query()
            ->whereIn('parent_id', Lookup::query()->select('id')->whereNull('parent_id')->where('slug', $type))
            ->orderBy('order')->orderBy('id')
            ->pluck('id', 'name_en')
            ->all();
    }

    /**
     * One value of a lookup type by its English name or slug.
     */
    protected function lookup(string $type, string $name): ?int
    {
        return $this->children($type)[$name] ?? Lookup::query()
            ->whereIn('parent_id', Lookup::query()->select('id')->whereNull('parent_id')->where('slug', $type))
            ->where('slug', $name)
            ->value('id');
    }

    /**
     * The location chain (country_id …) of a place name in the locations tree; empty when it isn't there.
     *
     * @return array<string, int>
     */
    protected function location(string $name): array
    {
        $root = Lookup::query()->whereNull('parent_id')->where('slug', 'locations')->first();
        $node = $root ? Lookup::query()->whereDescendantOf($root)->where(fn ($query) => $query->where('name_en', $name)->orWhere('slug', $name))->first() : null;
        $chain = [];
        while ($node && $node->parent_id) {
            array_unshift($chain, $node->id);
            $node = Lookup::query()->find($node->parent_id);
        }
        $columns = ['country_id', 'city_id', 'region_id', 'area_id', 'sub_area_id'];

        return array_combine(array_slice($columns, 0, count($chain)), $chain) ?: [];
    }

    /**
     * A record placed only down to a city or region gets the next levels too (the first child each time), so the
     * area and sub-area fields have data.
     */
    protected function fullChain(Model $record): void
    {
        $columns = ['country_id', 'city_id', 'region_id', 'area_id', 'sub_area_id'];
        if ($record->sub_area_id !== null || $record->country_id === null) {
            return;
        }

        $changes = [];
        $parent = collect($columns)->map(fn (string $column) => $record->{$column})->filter()->last();
        foreach ($columns as $column) {
            if ($record->{$column} !== null) {
                continue;
            }
            $parent = Lookup::query()->where('parent_id', $parent)->orderBy('order')->orderBy('id')->value('id');
            if (! $parent) {
                break;
            }
            $changes[$column] = $parent;
        }
        $changes && $record->updateQuietly($changes);
    }

    /**
     * "Created by" / "Updated by" (empty when seeding: nobody is signed in) set to the first admin user.
     */
    protected function stamps(string ...$tables): void
    {
        if (! $admin = User::query()->orderBy('id')->value('id')) {
            return;
        }

        foreach ($tables as $table) {
            DB::table($table)->whereNull('created_by')->update(['created_by' => $admin]);
            DB::table($table)->whereNull('updated_by')->update(['updated_by' => $admin]);
        }
    }

    /**
     * The place chain of a name, or (when it isn't in the tree) one of the tree's full chains.
     *
     * @return array<string, int|null>
     */
    protected function place(string $name, int $i = 0): array
    {
        $chains = $this->chains();

        return $this->location($name) ?: $chains[$i % count($chains)];
    }

    /**
     * @return list<int> ids of the admin users that can be agents (not suspended)
     */
    protected function agents(): array
    {
        return User::query()->where('is_suspended', false)->orderBy('id')->pluck('id')->all();
    }
}
