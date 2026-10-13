<?php

namespace Modules\GeneralSettings\Filament\Clusters\GeneralSettings\Pages;

use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Modules\GeneralSettings\Filament\Support\GeneralSettingsPage;
use Modules\GeneralSettings\Settings\CtaRibbonSettings;

class CtaRibbonSettingsPage extends GeneralSettingsPage
{
    protected static string $settings = CtaRibbonSettings::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-megaphone';

    protected static ?int $navigationSort = 2;

    protected static function getTranslationKey(): string
    {
        return 'cta_ribbon';
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('admin.settings.pages.cta_ribbon'))
                ->schema([
                    static::textEn('header', 'header'),
                    static::textAr('header', 'header'),
                    static::textareaEn('body', 'body'),
                    static::textareaAr('body', 'body'),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }
}
