<?php

namespace App\Filament\Forms;

use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Tabs\Tab;

/**
 * The "SEO & Metadata" tab for any module with website pages: meta title, description and keywords in English
 * and Arabic (columns meta_title_en/ar, meta_description_en/ar, meta_keywords_en/ar as JSON).
 */
class SeoFields
{
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
