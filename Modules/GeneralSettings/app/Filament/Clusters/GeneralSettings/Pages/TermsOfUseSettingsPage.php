<?php

namespace Modules\GeneralSettings\Filament\Clusters\GeneralSettings\Pages;

use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Modules\GeneralSettings\Filament\Support\GeneralSettingsPage;
use Modules\GeneralSettings\Settings\TermsOfUseSettings;

class TermsOfUseSettingsPage extends GeneralSettingsPage
{
    protected static string $settings = TermsOfUseSettings::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 8;

    protected static function getTranslationKey(): string
    {
        return 'terms_of_use';
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('admin.settings.sections.intro'))
                ->schema([
                    static::textareaEn('description', 'intro_description', 10000),
                    static::textareaAr('description', 'intro_description', 10000),
                ])
                ->columns(2)
                ->columnSpanFull(),
            Section::make(__('admin.settings.sections.entries'))
                ->schema([static::cards('entries', 'entries', withIcon: false, bodyMaxLength: 20000)->hiddenLabel()])
                ->columnSpanFull(),
        ]);
    }
}
