<?php

namespace Modules\Lookups\Support;

use Illuminate\Support\Collection;
use Modules\Lookups\Models\Lookup;

/**
 * Values of lookup types for selects and checkbox lists. Fields say which types they need while the form is built
 * (need()), then the first lookup reads ALL of those types in ONE query. The state lives for one request (a scoped
 * binding in LookupsServiceProvider), so nothing carries over between requests or tests.
 */
class LookupOptions
{
    /**
     * Types the fields of this request need.
     *
     * @var array<string, true>
     */
    private array $needed = [];

    /**
     * Values loaded so far, by type slug.
     *
     * @var array<string, Collection<int, Lookup>>
     */
    private array $loaded = [];

    public static function need(string $type): void
    {
        static::current()->needed[$type] = true;
    }

    /**
     * Active values of a type, in display order, plus the ids in $keep so a saved value that was later switched
     * off still shows.
     *
     * @param  array<int>  $keep
     * @return array<int, string>
     */
    public static function for(string $type, array $keep = []): array
    {
        return static::values($type)
            ->filter(fn (Lookup $value) => $value->is_active || in_array($value->id, $keep, true))
            ->mapWithKeys(fn (Lookup $value) => [$value->id => $value->getLocalized('name')])
            ->all();
    }

    /**
     * One value of a type by id, e.g. to show a project's currency in a list.
     */
    public static function find(string $type, ?int $id): ?Lookup
    {
        return $id ? static::values($type)->firstWhere('id', $id) : null;
    }

    private static function current(): self
    {
        return app(self::class);
    }

    /**
     * @return Collection<int, Lookup>
     */
    private static function values(string $type): Collection
    {
        $state = static::current();

        if (! array_key_exists($type, $state->loaded)) {
            $types = array_keys($state->needed + [$type => true]);
            $types = array_values(array_diff($types, array_keys($state->loaded)));

            $values = Lookup::query()
                ->join('lookups as type', 'type.id', '=', 'lookups.parent_id')
                ->whereNull('type.parent_id')
                ->whereNull('type.deleted_at')
                ->whereIn('type.slug', $types)
                ->orderBy('lookups.order')
                ->orderBy('lookups.name_en')
                ->get(['lookups.id', 'lookups.parent_id', 'lookups.slug', 'lookups.name_en', 'lookups.name_ar', 'lookups.is_active', 'type.slug as type_slug'])
                ->groupBy('type_slug');

            foreach ($types as $loadedType) {
                $state->loaded[$loadedType] = $values->get($loadedType, collect());
            }
        }

        return $state->loaded[$type];
    }
}
