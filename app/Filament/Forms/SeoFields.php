<?php

namespace App\Filament\Forms;

use App\Filament\Schemas\Tab;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * The "SEO & Metadata" tab for any module with website pages: meta title, description and keywords in English
 * and Arabic (columns meta_title_en/ar, meta_description_en/ar, meta_keywords_en/ar as JSON).
 */
class SeoFields
{
    /**
     * The page's URL slug (the model uses HasUniqueSlug). Filled from the English name, see slugFrom().
     */
    public static function slug(): TextInput
    {
        return TextInput::make('slug')
            ->label(__('admin.seo.slug'))
            ->helperText(__('admin.seo.slug_help'))
            ->alphaDash()
            ->maxLength(191)
            ->unique(ignoreRecord: true)
            ->columnSpanFull();
    }

    /**
     * Makes the English name/title field fill the slug while a record is being created: the slug follows it until
     * someone writes their own. After creation the slug stays put when the name changes, so published links (Google,
     * social posts) keep working; it can still be edited by hand, or refilled by clearing it.
     *
     * @param  class-string<Model>  $model  uses HasUniqueSlug
     */
    public static function slugFrom(TextInput $field, string $model): TextInput
    {
        return $field
            ->live(onBlur: true)
            ->afterStateUpdated(function (Set $set, Get $get, ?string $old, ?string $state, ?Model $record, string $operation) use ($model) {
                $slug = (string) $get('slug');
                $followsName = blank($slug)
                    || ($operation === 'create' && preg_match('/^'.preg_quote(Str::slug((string) $old), '/').'(-\d+)?$/', $slug));

                if (filled($state) && $followsName) {
                    $set('slug', $model::uniqueSlug($state, $record?->getKey()));
                }
            });
    }

    /**
     * @param  array  $before  module fields shown first (e.g. the page's URL slug)
     */
    public static function tab(array $before = []): Tab
    {
        return Tab::make(__('admin.tabs.seo'))
            ->icon('heroicon-o-magnifying-glass')
            ->schema([
                ...$before,
                TextInput::make('meta_title_en')
                    ->label(__('admin.seo.meta_title_en'))
                    ->maxLength(191),
                TextInput::make('meta_title_ar')
                    ->label(__('admin.seo.meta_title_ar'))
                    ->maxLength(191),
                Textarea::make('meta_description_en')
                    ->label(__('admin.seo.meta_description_en'))
                    ->rows(3)
                    ->maxLength(500),
                Textarea::make('meta_description_ar')
                    ->label(__('admin.seo.meta_description_ar'))
                    ->rows(3)
                    ->maxLength(500),
                TagsInput::make('meta_keywords_en')
                    ->label(__('admin.seo.meta_keywords_en')),
                TagsInput::make('meta_keywords_ar')
                    ->label(__('admin.seo.meta_keywords_ar')),
            ])
            ->columns(2);
    }
}
