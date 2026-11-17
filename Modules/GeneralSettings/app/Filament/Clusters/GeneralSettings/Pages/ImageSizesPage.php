<?php

namespace Modules\GeneralSettings\Filament\Clusters\GeneralSettings\Pages;

use App\Filament\Concerns\InteractsWithPermissionModule;
use App\Filament\Contracts\HasPermissionModule;
use App\Support\Images\ImageSizes;
use App\Support\Images\ImageSizesExport;
use App\Support\Images\ImageText;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View;
use Filament\Support\Icons\Heroicon;
use Modules\GeneralSettings\Filament\Clusters\GeneralSettingsCluster;
use Modules\Users\Enums\PermissionAction;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Settings → Image Sizes (AKAR-50): every image of the website as a card, grouped by section. Each card draws the
 * image's shape with its size inside, then three short lines: the smallest image accepted, the files it takes, and
 * where it is shown. "Edit" changes the sizes (within the safe limits) and the shape of the places that can change;
 * "Back to default" restores the design's values; Export Excel downloads the sheet. Its own permission: View sees and
 * exports, Edit edits.
 */
class ImageSizesPage extends Page implements HasPermissionModule
{
    use InteractsWithPermissionModule;

    protected static ?string $cluster = GeneralSettingsCluster::class;

    protected static ?string $slug = 'image-sizes';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrows-pointing-out';

    protected static ?int $navigationSort = 10;

    protected static ?string $permissionKey = 'image_sizes';

    protected static ?string $permissionGroup = 'system_configuration';

    /** @var array<PermissionAction> */
    protected static array $permissionActions = [PermissionAction::View, PermissionAction::Edit];

    protected string $view = 'generalsettings::image-sizes.page';

    /**
     * The shapes the cards can draw (written out so Tailwind makes them); an image is drawn in the closest one.
     */
    private const DRAWINGS = [
        '4:1' => 'aspect-[4/1]',
        '3:1' => 'aspect-[3/1]',
        '2:1' => 'aspect-[2/1]',
        '16:9' => 'aspect-video',
        '40:21' => 'aspect-[40/21]',
        '16:10' => 'aspect-[16/10]',
        '3:2' => 'aspect-[3/2]',
        '4:3' => 'aspect-[4/3]',
        '5:4' => 'aspect-[5/4]',
        '1:1' => 'aspect-square',
        '4:5' => 'aspect-[4/5]',
        '3:4' => 'aspect-[3/4]',
    ];

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.image_sizes');
    }

    public function getTitle(): string
    {
        return static::getNavigationLabel();
    }

    public function getSubheading(): ?string
    {
        return __('admin.image_sizes.subheading');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasPermission(static::getPermissionKey(), PermissionAction::View) ?? false;
    }

    public static function canEdit(): bool
    {
        return auth()->user()?->hasPermission(static::getPermissionKey(), PermissionAction::Edit) ?? false;
    }

    /**
     * The cards by section: each image with what its card shows.
     *
     * @return array<string, array{name: string, cards: list<array<string, mixed>>}>
     */
    public function getSections(): array
    {
        $places = ImageSizes::places();

        return collect(ImageSizes::fields())
            ->map(function (array $field) use ($places) {
                $shownOn = array_map(fn (string $place) => $places[$place], ImageSizes::placesOf($field['key']));
                $names = array_map(fn (array $place) => __("admin.image_sizes.places.{$place['key']}"), $shownOn);

                return [
                    'key' => $field['key'],
                    'group' => $field['group'],
                    'name' => __("admin.image_sizes.fields.{$field['key']}"),
                    'size' => "{$field['recommended'][0]} × {$field['recommended'][1]}",
                    'ratio' => ImageText::ratio($field['recommended']),
                    ...static::drawingOf($field['recommended']),
                    // What the search looks in: the name in both languages and the places.
                    'search' => mb_strtolower(implode(' ', [__("admin.image_sizes.fields.{$field['key']}", [], 'en'), __("admin.image_sizes.fields.{$field['key']}", [], 'ar'), ...$names])),
                    'smallest' => $field['min'] === [0, 0] ? __('admin.image_sizes.card.any_size') : __('admin.image_sizes.card.smallest', ['size' => ImageText::minimum($field['min'])]),
                    'file' => __('admin.image_sizes.card.file', ['formats' => ImageText::formats($field['formats']), 'mb' => $field['mb']]),
                    'shown_on' => static::shortList($names),
                    'shown_on_all' => implode(__('admin.image_sizes.list_separator'), $names),
                    'changed' => static::isChanged($field['key']),
                ];
            })
            ->groupBy('group')
            ->map(fn ($cards, string $group) => ['name' => __("admin.image_sizes.groups.{$group}"), 'cards' => $cards->values()->all()])
            ->all();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label(__('admin.image_sizes.actions.export'))
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                // The admin picks the sheet's language (the admin's own one is picked first).
                ->modalHeading(__('admin.image_sizes.export.heading'))
                ->modalWidth('sm')
                ->modalSubmitActionLabel(__('admin.image_sizes.export.download'))
                ->fillForm(fn () => ['locale' => app()->getLocale() === 'ar' ? 'ar' : 'en'])
                ->schema([
                    ToggleButtons::make('locale')
                        ->label(__('admin.image_sizes.export.language'))
                        // Each language in its own name, so it's found whatever the admin's language.
                        ->options(['ar' => 'العربية', 'en' => 'English'])
                        ->icons(['ar' => Heroicon::OutlinedLanguage, 'en' => Heroicon::OutlinedLanguage])
                        ->inline()
                        ->required()
                        ->in(ImageSizesExport::LOCALES),
                ])
                ->action(fn (array $data): StreamedResponse => ImageSizesExport::download($data['locale'])),
        ];
    }

    /**
     * "Edit" on a card: the sizes, the file, and the shape of each place of this image that can change.
     */
    public function editAction(): Action
    {
        return Action::make('edit')
            ->label(__('admin.image_sizes.actions.edit'))
            ->icon(Heroicon::OutlinedPencilSquare)
            ->button()
            ->size('sm')
            ->visible(fn () => static::canEdit())
            ->disabled(fn (array $arguments) => ! static::isKnown($arguments))
            // Every closure checks the image exists first: the browser can send any name.
            ->modalHeading(fn (array $arguments) => static::isKnown($arguments) ? __("admin.image_sizes.fields.{$arguments['field']}") : null)
            ->modalWidth('2xl')
            ->modalSubmitActionLabel(__('admin.image_sizes.actions.save'))
            ->fillForm(function (array $arguments) {
                if (! static::isKnown($arguments)) {
                    return [];
                }
                $field = ImageSizes::field($arguments['field']);
                $shapes = collect(static::shapePlaces($field['key']))->flatMap(fn (array $place) => [
                    "shape_{$place['key']}" => $place['shape'],
                    "phone_{$place['key']}" => $place['phone_shape'],
                ])->all();

                return [
                    'width' => $field['recommended'][0],
                    'height' => $field['recommended'][1],
                    'min_width' => $field['min'][0],
                    'min_height' => $field['min'][1],
                    'mb' => $field['mb'],
                    'formats' => $field['formats'],
                    ...$shapes,
                ];
            })
            ->schema(fn (array $arguments) => static::isKnown($arguments) ? static::editForm($arguments['field']) : [])
            ->action(function (array $arguments, array $data) {
                $key = $arguments['field'];
                ImageSizes::save("field:{$key}", [
                    'recommended' => [$data['width'], $data['height'] ?? null],
                    'min' => [$data['min_width'], $data['min_height'] ?? null],
                    'mb' => $data['mb'],
                    'formats' => $data['formats'],
                ], auth()->id());

                foreach (static::shapePlaces($key) as $place) {
                    ImageSizes::save("place:{$place['key']}", [
                        'shape' => $data["shape_{$place['key']}"] ?? null,
                        'phone_shape' => $data["phone_{$place['key']}"] ?? null,
                    ], auth()->id());
                }

                Notification::make()->success()->title(__('admin.image_sizes.saved'))->send();
            });
    }

    /**
     * "Back to default" on a changed card: its sizes and the shapes of its places.
     */
    public function resetAction(): Action
    {
        return Action::make('reset')
            ->label(__('admin.image_sizes.actions.reset'))
            ->icon(Heroicon::OutlinedArrowUturnLeft)
            ->link()
            ->color('gray')
            ->size('sm')
            ->visible(fn () => static::canEdit())
            // Always shown; it only works on a changed image (an unchanged one says it's already the default).
            ->disabled(fn (array $arguments) => ! static::isKnown($arguments) || ! static::isChanged($arguments['field']))
            ->tooltip(fn (array $arguments) => static::isKnown($arguments) && ! static::isChanged($arguments['field']) ? __('admin.image_sizes.already_default') : null)
            ->requiresConfirmation()
            ->modalHeading(fn (array $arguments) => static::isKnown($arguments) ? __('admin.image_sizes.reset_heading', ['name' => __("admin.image_sizes.fields.{$arguments['field']}")]) : null)
            ->modalDescription(__('admin.image_sizes.reset_help'))
            ->action(function (array $arguments) {
                ImageSizes::reset("field:{$arguments['field']}");
                foreach (static::shapePlaces($arguments['field']) as $place) {
                    ImageSizes::reset("place:{$place['key']}");
                }

                Notification::make()->success()->title(__('admin.image_sizes.reset_done'))->send();
            });
    }

    /**
     * @return array<Component>
     */
    private static function editForm(string $key): array
    {
        $field = ImageSizes::field($key);
        $limits = ImageSizes::limits($key);
        $crop = $field['crop'];
        $number = fn (string $name, array $range, string $suffix = 'px') => TextInput::make($name)->integer()->required()
            ->minValue($range[0])->maxValue($range[1])->suffix($suffix)
            ->helperText(__('admin.image_sizes.between', ['min' => $range[0], 'max' => $range[1]]));
        $options = fn (array $shapes) => array_combine($shapes, array_map(fn (string $shape) => __("admin.image_sizes.shapes.{$shape}"), $shapes));

        $shapes = collect(static::shapePlaces($key))->flatMap(fn (array $place) => [
            Select::make("shape_{$place['key']}")->label(__("admin.image_sizes.places.{$place['key']}"))
                ->options($options($place['shapes']))->required()->native(false)->selectablePlaceholder(false),
            ...($place['phone'] ? [
                Select::make("phone_{$place['key']}")->label(__('admin.image_sizes.form.on_phone', ['place' => __("admin.image_sizes.places.{$place['key']}")]))
                    ->options($options($place['phone_shapes']))->required()->native(false)->selectablePlaceholder(false),
            ] : []),
        ])->all();

        return [
            // The best size drawn, redrawn as the admin types it.
            View::make('generalsettings::image-sizes.drawing')
                ->viewData(fn (Get $get) => static::previewData((int) $get('width'), (int) $get('height'), $crop)),
            Section::make(__('admin.image_sizes.form.recommended'))
                ->description($crop ? __('admin.image_sizes.form.keeps_shape', ['ratio' => $crop]) : __('admin.image_sizes.form.recommended_help'))
                ->icon(Heroicon::OutlinedPhoto)
                ->schema([
                    Grid::make(2)->schema([
                        $number('width', $limits['size'])->label(__('admin.image_sizes.form.width'))->live(onBlur: true),
                        $crop
                            ? Text::make(fn (Get $get) => __('admin.image_sizes.form.height_follows', ['height' => static::followingHeight((int) $get('width'), $crop)]))
                            : $number('height', $limits['size'])->label(__('admin.image_sizes.form.height'))->live(onBlur: true),
                    ]),
                ]),
            Section::make(__('admin.image_sizes.form.minimum'))
                ->description(__('admin.image_sizes.form.minimum_help'))
                ->icon(Heroicon::OutlinedArrowsPointingIn)
                ->schema([
                    Grid::make(2)->schema([
                        $number('min_width', $limits['min'])->label(__('admin.image_sizes.form.width'))->lte('width'),
                        $crop
                            ? Text::make(__('admin.image_sizes.form.min_height_follows'))
                            : $number('min_height', $limits['min'])->label(__('admin.image_sizes.form.height'))->lte('height'),
                    ]),
                ]),
            Section::make(__('admin.image_sizes.form.file'))
                ->icon(Heroicon::OutlinedDocument)
                ->schema([
                    Grid::make(2)->schema([
                        CheckboxList::make('formats')->label(__('admin.image_sizes.form.formats'))->required()
                            ->options(collect(ImageSizes::FORMATS)->keys()->mapWithKeys(fn (string $format) => [$format => ImageText::formats([$format])])->all())
                            ->columns(3),
                        $number('mb', $limits['mb'], 'MB')->label(__('admin.image_sizes.form.mb')),
                    ]),
                ]),
            ...($shapes ? [
                Section::make(__('admin.image_sizes.form.shapes'))
                    ->description(__('admin.image_sizes.form.shape_help'))
                    ->icon(Heroicon::OutlinedComputerDesktop)
                    ->schema([Grid::make(2)->schema($shapes)]),
            ] : []),
        ];
    }

    /**
     * Whether the image a card's button was sent for exists (the browser can send anything).
     *
     * @param  array<string, mixed>  $arguments
     */
    private static function isKnown(array $arguments): bool
    {
        return is_string($arguments['field'] ?? null) && isset(ImageSizes::fields()[$arguments['field']]);
    }

    /**
     * Whether an image was changed: its sizes, or the shape of one of its places.
     */
    private static function isChanged(string $field): bool
    {
        return ImageSizes::field($field)['changed']
            || collect(static::shapePlaces($field))->contains(fn (array $place) => $place['changed']);
    }

    /**
     * The places showing this image whose shape can change.
     *
     * @return list<array<string, mixed>>
     */
    private static function shapePlaces(string $field): array
    {
        return array_values(array_filter(
            array_map(fn (string $place) => ImageSizes::place($place), ImageSizes::placesOf($field)),
            fn (array $place) => $place['display'] === 'shape',
        ));
    }

    /**
     * How a size is drawn: the closest shape, its width or height filling the box (wide shapes take the width, the
     * others the height, so every drawing fits the same box), and whether it's too low for the icon.
     *
     * @param  array{int, int}  $size
     * @return array{drawing: string, thin: bool}
     */
    private static function drawingOf(array $size): array
    {
        $ratio = $size[0] / max(1, $size[1]);

        return ['drawing' => static::drawing($size).' '.($ratio >= 1.6 ? 'w-full max-w-[220px]' : 'h-full'), 'thin' => $ratio >= 3];
    }

    /**
     * The drawing closest to a size's shape.
     *
     * @param  array{int, int}  $size
     */
    private static function drawing(array $size): string
    {
        $ratio = $size[0] / max(1, $size[1]);

        return collect(self::DRAWINGS)
            ->sortBy(function (string $class, string $shape) use ($ratio) {
                [$w, $h] = explode(':', $shape);

                return abs($w / $h - $ratio);
            })
            ->first();
    }

    /**
     * "Property card, Property page +6".
     *
     * @param  list<string>  $names
     */
    private static function shortList(array $names): string
    {
        $more = count($names) - 2;

        return implode(__('admin.image_sizes.list_separator'), array_slice($names, 0, 2)).($more > 0 ? " +{$more}" : '');
    }

    /**
     * The edit window's drawing of the size being typed (a cropped image keeps its shape).
     *
     * @return array{size: string, ratio: string, drawing: string, thin: bool}
     */
    private static function previewData(int $width, int $height, ?string $crop): array
    {
        $width = max(1, $width);
        $height = max(1, $crop ? static::followingHeight($width, $crop) : $height);

        return ['size' => "{$width} × {$height}", 'ratio' => ImageText::ratio([$width, $height]), ...static::drawingOf([$width, $height])];
    }

    /**
     * The height that keeps a cropped image's shape, e.g. 1200 wide at 40:21 → 630.
     */
    private static function followingHeight(int $width, string $crop): int
    {
        [$w, $h] = array_map('intval', explode(':', $crop));

        return (int) round($width * $h / $w);
    }
}
