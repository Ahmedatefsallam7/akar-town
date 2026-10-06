<?php

namespace Modules\GeneralSettings\Filament\Clusters\GeneralSettings\Pages;

use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Modules\GeneralSettings\Filament\Support\GeneralSettingsPage;
use Modules\GeneralSettings\Settings\DeveloperDetailsSettings;

class DeveloperDetailsSettingsPage extends GeneralSettingsPage
{
    protected static string $settings = DeveloperDetailsSettings::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-briefcase';

    protected static ?int $navigationSort = 6;

    protected static function getTranslationKey(): string
    {
        return 'developer_details';
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('admin.settings.sections.what_to_expect'))
                ->schema([static::cards('what_to_expect_cards', 'what_to_expect')->hiddenLabel()])
                ->columnSpanFull(),
        ]);
    }
}
