<?php

namespace Modules\GeneralSettings\Filament\Clusters\GeneralSettings\Pages;

use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Modules\GeneralSettings\Filament\Support\GeneralSettingsPage;
use Modules\GeneralSettings\Settings\SubmitPropertySettings;

class SubmitPropertySettingsPage extends GeneralSettingsPage
{
    protected static string $settings = SubmitPropertySettings::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-paper-airplane';

    protected static ?int $navigationSort = 5;

    protected static function getTranslationKey(): string
    {
        return 'submit_property';
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('admin.settings.sections.why_list'))
                ->schema([
                    static::textareaEn('why_list_mini_description', 'mini_description'),
                    static::textareaAr('why_list_mini_description', 'mini_description'),
                    static::cards('points', 'points'),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }
}
