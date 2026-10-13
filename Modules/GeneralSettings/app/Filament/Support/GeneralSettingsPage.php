<?php

namespace Modules\GeneralSettings\Filament\Support;

use App\Filament\Concerns\InteractsWithPermissionModule;
use App\Filament\Contracts\HasPermissionModule;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Pages\SettingsPage;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Modules\GeneralSettings\Filament\Clusters\GeneralSettingsCluster;
use Modules\Users\Enums\PermissionAction;

/**
 * One General Settings page: a single form with Save Changes, no create or delete. All eight pages share one
 * permission ("General Settings": view, edit); without edit the form is read-only and there is no Save button.
 */
abstract class GeneralSettingsPage extends SettingsPage implements HasPermissionModule
{
    use InteractsWithPermissionModule;

    public const ICONS_DIRECTORY = 'settings/icons';

    protected static ?string $cluster = GeneralSettingsCluster::class;

    protected static ?string $permissionKey = 'general_settings';

    protected static ?string $permissionGroup = 'system_configuration';

    /** @var array<PermissionAction> */
    protected static array $permissionActions = [PermissionAction::View, PermissionAction::Edit];

    /** Icon files in use before the save, to delete the ones the admin removed or replaced. */
    private array $iconsBeforeSave = [];

    abstract protected static function getTranslationKey(): string;

    public static function getNavigationLabel(): string
    {
        return __('admin.settings.pages.'.static::getTranslationKey());
    }

    public function getTitle(): string
    {
        return static::getNavigationLabel();
    }

    public static function getPermissionLabel(): string
    {
        return __('admin.navigation.settings');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasPermission(static::getPermissionKey(), PermissionAction::View) ?? false;
    }

    public function canEdit(): bool
    {
        return auth()->user()?->hasPermission(static::getPermissionKey(), PermissionAction::Edit) ?? false;
    }

    protected function beforeSave(): void
    {
        $this->iconsBeforeSave = static::iconPaths(app(static::getSettings())->toArray());
    }

    protected function afterSave(): void
    {
        $removed = array_diff($this->iconsBeforeSave, static::iconPaths(app(static::getSettings())->toArray()));

        Storage::disk('public')->delete(array_values($removed));
    }

    /**
     * Uploaded icon paths in the settings' repeaters.
     *
     * @return array<string>
     */
    protected static function iconPaths(array $settings): array
    {
        return collect($settings)
            ->filter(fn ($value) => is_array($value))
            ->flatMap(fn (array $items) => Arr::pluck($items, 'icon'))
            ->filter(fn ($path) => is_string($path) && str_starts_with($path, self::ICONS_DIRECTORY.'/'))
            ->values()
            ->all();
    }

    protected static function textEn(string $name, string $label): TextInput
    {
        return TextInput::make("{$name}_en")->label(__("admin.settings.fields.{$label}_en"))->required()->maxLength(255);
    }

    protected static function textAr(string $name, string $label): TextInput
    {
        return TextInput::make("{$name}_ar")->label(__("admin.settings.fields.{$label}_ar"))->required()->maxLength(255);
    }

    protected static function textareaEn(string $name, string $label, int $maxLength = 5000): Textarea
    {
        return Textarea::make("{$name}_en")->label(__("admin.settings.fields.{$label}_en"))->required()->rows(4)->maxLength($maxLength);
    }

    protected static function textareaAr(string $name, string $label, int $maxLength = 5000): Textarea
    {
        return Textarea::make("{$name}_ar")->label(__("admin.settings.fields.{$label}_ar"))->required()->rows(4)->maxLength($maxLength);
    }

    /**
     * Card-style items (header and body in both languages, optionally an icon image): add, remove, drag to
     * re-order, collapse / expand.
     */
    protected static function cards(string $name, string $label, bool $withIcon = true, int $bodyMaxLength = 5000): Repeater
    {
        return Repeater::make($name)
            ->label(__("admin.settings.sections.{$label}"))
            ->schema([
                ...($withIcon ? [
                    FileUpload::make('icon')
                        ->label(__('admin.settings.fields.icon'))
                        ->helperText(__('admin.settings.fields.icon_help'))
                        ->image()
                        ->disk('public')
                        ->directory(self::ICONS_DIRECTORY)
                        ->visibility('public')
                        // No SVG: an uploaded SVG can carry scripts.
                        ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                        ->maxSize(1024)
                        ->columnSpanFull(),
                ] : []),
                static::textEn('header', 'header'),
                static::textAr('header', 'header'),
                static::textareaEn('body', 'body', $bodyMaxLength),
                static::textareaAr('body', 'body', $bodyMaxLength),
            ])
            ->columns(2)
            ->addActionLabel(__('admin.settings.add_item'))
            ->reorderableWithButtons()
            ->collapsible()
            ->collapsed()
            ->cloneable(false)
            ->itemLabel(fn (array $state): ?string => $state['header_'.(app()->getLocale() === 'ar' ? 'ar' : 'en')] ?? $state['header_en'] ?? null)
            ->columnSpanFull();
    }
}
