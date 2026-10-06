<?php

namespace Modules\GeneralSettings\Filament\Clusters\GeneralSettings\Pages;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Modules\GeneralSettings\Filament\Support\GeneralSettingsPage;
use Modules\GeneralSettings\Settings\BusinessInfoSettings;

class BusinessInfoSettingsPage extends GeneralSettingsPage
{
    protected static string $settings = BusinessInfoSettings::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office';

    protected static ?int $navigationSort = 3;

    protected static function getTranslationKey(): string
    {
        return 'business_info';
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('admin.settings.sections.footer'))
                ->schema([
                    static::textareaEn('mini_description', 'mini_description'),
                    static::textareaAr('mini_description', 'mini_description'),
                ])
                ->columns(2)
                ->columnSpanFull(),
            Section::make(__('admin.settings.sections.social_media'))
                ->schema(collect(['facebook', 'instagram', 'linkedin', 'youtube', 'tiktok'])
                    ->map(fn (string $network) => TextInput::make("social_{$network}")
                        ->label(__("admin.settings.fields.{$network}"))
                        ->url()
                        ->rules(['url:http,https'])
                        ->maxLength(255))
                    ->all())
                ->columns(2)
                ->columnSpanFull(),
            Section::make(__('admin.settings.sections.offices'))
                ->schema([
                    static::textareaEn('headquarters', 'headquarters', 1000)->rows(2),
                    static::textareaAr('headquarters', 'headquarters', 1000)->rows(2),
                    ...collect(range(1, 4))->flatMap(fn (int $branch) => [
                        Textarea::make("branch_{$branch}_en")->label(__('admin.settings.fields.branch_en', ['number' => $branch]))->rows(2)->maxLength(1000),
                        Textarea::make("branch_{$branch}_ar")->label(__('admin.settings.fields.branch_ar', ['number' => $branch]))->rows(2)->maxLength(1000),
                    ])->all(),
                ])
                ->columns(2)
                ->columnSpanFull(),
            Section::make(__('admin.settings.sections.contact'))
                ->schema([
                    ...collect(['hotline', 'phone_primary', 'phone_secondary', 'phone_tertiary', 'whatsapp'])
                        ->map(fn (string $phone) => TextInput::make($phone)
                            ->label(__("admin.settings.fields.{$phone}"))
                            ->tel()
                            ->maxLength(30))
                        ->all(),
                    TextInput::make('email')->label(__('admin.settings.fields.email'))->email()->maxLength(255),
                    TextInput::make('tax_number')->label(__('admin.settings.fields.tax_number'))->maxLength(50),
                ])
                ->columns(2)
                ->columnSpanFull(),
            Section::make(__('admin.settings.sections.working_hours'))
                ->schema([
                    Select::make('working_days_from')->label(__('admin.settings.fields.working_days_from'))->options(static::days())->required()->native(false),
                    Select::make('working_days_to')->label(__('admin.settings.fields.working_days_to'))->options(static::days())->required()->native(false),
                    TimePicker::make('working_hours_from')->label(__('admin.settings.fields.working_hours_from'))->seconds(false)->format('H:i')->required(),
                    TimePicker::make('working_hours_to')->label(__('admin.settings.fields.working_hours_to'))->seconds(false)->format('H:i')->required(),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }

    /**
     * @return array<string, string>
     */
    protected static function days(): array
    {
        return collect(['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday'])
            ->mapWithKeys(fn (string $day) => [$day => __("admin.settings.days.{$day}")])
            ->all();
    }
}
