<?php

namespace Modules\GeneralSettings\Filament\Clusters\GeneralSettings\Pages;

use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Modules\GeneralSettings\Filament\Support\GeneralSettingsPage;
use Modules\GeneralSettings\Settings\PrivacyPolicySettings;

class PrivacyPolicySettingsPage extends GeneralSettingsPage
{
    protected static string $settings = PrivacyPolicySettings::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static ?int $navigationSort = 7;

    protected static function getTranslationKey(): string
    {
        return 'privacy_policy';
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
