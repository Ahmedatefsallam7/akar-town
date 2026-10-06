<?php

namespace Modules\GeneralSettings\Filament\Clusters\GeneralSettings\Pages;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Modules\GeneralSettings\Filament\Support\GeneralSettingsPage;
use Modules\GeneralSettings\Settings\HomeSettings;

class HomeSettingsPage extends GeneralSettingsPage
{
    protected static string $settings = HomeSettings::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-home';

    protected static ?int $navigationSort = 1;

    protected static function getTranslationKey(): string
    {
        return 'home';
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('admin.settings.sections.why_choose'))
                ->schema([
                    static::textEn('why_choose_subtitle', 'subtitle'),
                    static::textAr('why_choose_subtitle', 'subtitle'),
                    static::textEn('why_choose_header', 'header'),
                    static::textAr('why_choose_header', 'header'),
                    static::textareaEn('why_choose_body', 'body'),
                    static::textareaAr('why_choose_body', 'body'),
                    static::textEn('why_choose_cta_text', 'cta_text'),
                    static::textAr('why_choose_cta_text', 'cta_text'),
                    TextInput::make('why_choose_cta_url')
                        ->label(__('admin.settings.fields.cta_url'))
                        ->url()
                        ->rules(['url:http,https'])
                        ->maxLength(255)
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->columnSpanFull(),
            Section::make(__('admin.settings.sections.why_choose_cards'))
                ->schema([static::cards('why_choose_cards', 'why_choose_cards')->hiddenLabel()])
                ->columnSpanFull(),
        ]);
    }
}
