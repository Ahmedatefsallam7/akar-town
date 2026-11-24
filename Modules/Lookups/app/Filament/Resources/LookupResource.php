<?php

namespace Modules\Lookups\Filament\Resources;

use App\Filament\Concerns\HasResourceTranslations;
use App\Filament\Contracts\HasPermissionModule;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\GlobalSearch\GlobalSearchResult;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;
use Livewire\Component as Livewire;
use Modules\Lookups\Filament\Resources\LookupResource\Pages;
use Modules\Lookups\Models\Lookup;
use Modules\Lookups\Support\LocationTree;
use Modules\Lookups\Support\LookupUsage;

/**
 * Lookups: one card per lookup type (the top level), and a page per type to manage its values. The Locations type
 * is a tree (country › city › region › area › compound) browsed level by level. Roles live under "user_roles" but
 * are managed on the Roles page, so they never show up here.
 */
class LookupResource extends Resource implements HasPermissionModule
{
    use HasResourceTranslations;

    /**
     * How many levels of values a type has below it. Locations: country, city, region, area, compound.
     */
    public const DEPTHS = ['locations' => 5];

    protected static ?string $permissionGroup = 'system_configuration';

    protected static ?string $model = Lookup::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-list-bullet';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name_en';

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation.system_configuration');
    }

    public static function getRecordTitleAttribute(): string
    {
        return getLocalizedFieldName('name');
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name_en', 'name_ar'];
    }

    /**
     * The ancestors come with the results (one query), for the line under each result.
     */
    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with('ancestors');
    }

    /**
     * The line under a result: "Lookup type" for a type; for a value, where it is inside its type ("Egypt › Giza ›
     * Sheikh Zayed City"; nothing for a flat type, its name is the group's title, see getGlobalSearchCategories()).
     */
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        if (! $record->parent_id) {
            return [__('admin.lookups.lookup_type')];
        }

        $path = $record->ancestors->sortBy('_lft')->slice(1)->map(fn (Lookup $ancestor) => $ancestor->default_name)->implode(' › ');

        return $path === '' ? [] : [$path];
    }

    /**
     * The admin's global search (App\Filament\Support\AdminGlobalSearchProvider) shows lookups grouped: the
     * matching types under "Lookups", then each type's matching values under "Lookups › <type>".
     *
     * @return array<string, Collection<int, GlobalSearchResult>>
     */
    public static function getGlobalSearchCategories(string $search): array
    {
        $query = static::getGlobalSearchEloquentQuery();
        static::applyGlobalSearchAttributeConstraints($query, $search);
        static::modifyGlobalSearchQuery($query, $search);

        $records = $query->limit(static::getGlobalSearchResultsLimit())->get();
        $result = fn (Lookup $record) => new GlobalSearchResult(
            title: static::getGlobalSearchResultTitle($record),
            url: static::getGlobalSearchResultUrl($record),
            details: static::getGlobalSearchResultDetails($record),
        );

        [$types, $values] = $records->partition(fn (Lookup $record) => ! $record->parent_id);
        $categories = $types->isEmpty() ? [] : [static::getPluralModelLabel() => $types->map($result)->values()];

        $values
            ->groupBy(fn (Lookup $record) => $record->ancestors->sortBy('_lft')->first()?->default_name)
            ->each(function (Collection $group, string $type) use (&$categories, $result) {
                $categories[static::getPluralModelLabel().' › '.$type] = $group->map($result)->values();
            });

        return $categories;
    }

    /**
     * A search result opens the page of the type (or tree level) the value belongs to.
     */
    public static function getGlobalSearchResultUrl(Model $record): ?string
    {
        return $record->parent_id ? static::getUrl('values', ['record' => $record->parent_id]) : static::getUrl('values', ['record' => $record]);
    }

    public static function getNavigationBadgeCount(): int
    {
        return Lookup::withoutRoles()->count();
    }

    public static function getModelLabel(): string
    {
        return __('admin.lookups.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.lookups.plural');
    }

    /**
     * Lookup types (seeded, protected) can't be deleted, nor anything that contains a protected item, nor a value
     * that another module uses (e.g. a city a location points at).
     */
    protected static function canPerformOnRecord(string $ability, Model $record): bool
    {
        return static::recordDenialMessage($ability, $record) === null;
    }

    protected static function recordDenialMessage(string $ability, Model $record): ?string
    {
        if (! in_array($ability, ['delete', 'forceDelete'], true)) {
            return null;
        }

        return match (true) {
            ! $record->is_deletable || $record->hasProtectedDescendants() => __('admin.lookups.cannot_delete_protected'),
            // A place (e.g. a compound) linked to properties says how many (AKAR-46).
            $record->isUsed() && LocationTree::levelOf($record->getKey()) && ($properties = LookupUsage::countIn('units', $record)) > 0 => trans_choice('admin.location_chain.used_by_properties', $properties, ['count' => $properties]),
            $record->isUsed() => __('admin.lookups.cannot_delete_used'),
            default => null,
        };
    }

    /**
     * The add/edit form for a value. Its parent is the page's type or tree level, so the slug only has to be unique
     * there (the same slug, e.g. "studio", can exist in two types).
     */
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name_en')
                    ->label(__('admin.lookups.name_en'))
                    ->required()
                    ->maxLength(191)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Set $set, Get $get, ?string $state, string $operation) {
                        if ($operation === 'create' || blank($get('slug'))) {
                            $set('slug', Str::slug((string) $state, '_'));
                        }
                    }),
                TextInput::make('name_ar')
                    ->label(__('admin.lookups.name_ar'))
                    ->required()
                    ->maxLength(191),
                TextInput::make('slug')
                    ->label(__('admin.table.slug'))
                    ->required()
                    ->maxLength(191)
                    ->alphaDash()
                    ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule, Livewire $livewire) => $rule
                        ->where('parent_id', $livewire->getOwnerRecord()->getKey()))
                    ->helperText(__('admin.lookups.slug_helper')),
                Toggle::make('is_active')
                    ->label(__('admin.lookups.active'))
                    ->default(true)
                    ->inline(false),
            ])
            ->columns(2);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            // Roles are managed only from the Roles resource.
            ->withoutRoles()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLookupTypes::route('/'),
            'values' => Pages\ManageLookupValues::route('/{record}'),
        ];
    }

    /**
     * How many value levels a type has (1 for a flat list).
     */
    public static function depthOf(Lookup $type): int
    {
        return self::DEPTHS[$type->slug] ?? 1;
    }
}
