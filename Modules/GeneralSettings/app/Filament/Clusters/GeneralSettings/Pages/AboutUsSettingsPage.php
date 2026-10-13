<?php

namespace Modules\GeneralSettings\Filament\Clusters\GeneralSettings\Pages;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Modules\GeneralSettings\Filament\Support\GeneralSettingsPage;
use Modules\GeneralSettings\Settings\AboutUsSettings;

class AboutUsSettingsPage extends GeneralSettingsPage
{
    protected static string $settings = AboutUsSettings::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-information-circle';

    protected static ?int $navigationSort = 4;

    protected static function getTranslationKey(): string
    {
        return 'about_us';
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('admin.settings.sections.hero'))
                ->schema([
                    static::textEn('hero_header', 'header'),
                    static::textAr('hero_header', 'header'),
                    static::textareaEn('hero_body', 'body'),
                    static::textareaAr('hero_body', 'body'),
                ])
                ->columns(2)
                ->columnSpanFull(),
            Section::make(__('admin.settings.sections.story'))
                ->schema([
                    static::textEn('story_header', 'header'),
                    static::textAr('story_header', 'header'),
                    static::textareaEn('story_body', 'story', 10000)->rows(8),
                    static::textareaAr('story_body', 'story', 10000)->rows(8),
                ])
                ->columns(2)
                ->columnSpanFull(),
            Section::make(__('admin.settings.sections.stats'))
                ->schema([
                    ...collect(['years_of_excellence', 'stats_happy_clients', 'stats_properties_sold', 'stats_awards_won'])
                        ->map(fn (string $stat) => TextInput::make($stat)
                            ->label(__("admin.settings.fields.{$stat}"))
                            ->required()
                            ->integer()
                            ->minValue(0)
                            ->maxValue(999_999_999))
                        ->all(),
                    TextInput::make('stats_commitment')
                        ->label(__('admin.settings.fields.stats_commitment'))
                        ->required()
                        ->integer()
                        ->minValue(0)
                        ->maxValue(100)
                        ->suffix('%'),
                ])
                ->columns(3)
                ->columnSpanFull(),
            Section::make(__('admin.settings.sections.core_values'))
                ->schema([static::cards('core_values', 'core_values')->hiddenLabel()])
                ->columnSpanFull(),
        ]);
    }
}
