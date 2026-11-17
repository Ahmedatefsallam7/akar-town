<?php

namespace App\Support\Images;

use App\Models\ImageSize;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Once;
use InvalidArgumentException;

/**
 * Every image of the website (AKAR-50): the admin upload fields and the places on the website that show them, with
 * the sizes they need. The defaults below match the design; an admin can change them on Settings → Image Sizes
 * within each kind's safe limits (and go back to the default), and the change applies everywhere at once: the hint
 * and the rules of the upload field, the size the photo is saved at, and, for the places that can change shape,
 * how the photo is shown on the website (only from a list of shapes the layout is tested with).
 *
 * The changed values live in the image_sizes table (only what differs from the default), read once and cached.
 */
class ImageSizes
{
    /** The formats an image can have, as the upload field checks them. */
    public const FORMATS = ['jpg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];

    private const CACHE_KEY = 'image_sizes.changed';

    /**
     * The most pixels an uploaded image may have (50 megapixels, e.g. 8660 × 5773): a bigger one, even a small file,
     * takes too much memory to open and make its smaller copies.
     */
    public const MAX_PIXELS = 50_000_000;

    /**
     * The safe limits of each kind of image: the recommended size (longest side, px), the minimum size (each side,
     * px; 0 = any) and the file size (MB). The upper file size stays under the server's upload limit (20 MB).
     */
    public const KINDS = [
        'photo' => ['size' => [800, 4000], 'min' => [0, 3000], 'mb' => [1, 10]],
        'banner' => ['size' => [1200, 4000], 'min' => [600, 3000], 'mb' => [1, 10]],
        'panorama' => ['size' => [2000, 8000], 'min' => [1000, 6000], 'mb' => [5, 20]],
        'plan' => ['size' => [800, 6000], 'min' => [0, 4000], 'mb' => [1, 20]],
        'logo' => ['size' => [200, 2000], 'min' => [0, 1500], 'mb' => [1, 5]],
        'icon' => ['size' => [64, 1024], 'min' => [0, 512], 'mb' => [1, 2]],
        'avatar' => ['size' => [200, 2000], 'min' => [100, 1500], 'mb' => [1, 5]],
        'share' => ['size' => [600, 2400], 'min' => [300, 1800], 'mb' => [1, 5]],
    ];

    /**
     * The upload fields. fit: how a new upload is prepared — "contain" (made at most the recommended size, its
     * shape kept), "crop" (cut to `crop` in the image editor, then saved at the recommended size) or "none" (kept as
     * uploaded: 360° images need every pixel).
     */
    private const FIELDS = [
        // Properties
        'unit_featured' => ['group' => 'properties', 'kind' => 'photo', 'recommended' => [1920, 1440], 'min' => [1200, 800], 'mb' => 5, 'formats' => ['jpg', 'png', 'webp'], 'fit' => 'contain'],
        'unit_360' => ['group' => 'properties', 'kind' => 'panorama', 'recommended' => [6000, 3000], 'min' => [4000, 2000], 'mb' => 20, 'formats' => ['jpg', 'png', 'webp'], 'fit' => 'none'],
        'unit_floor_plan' => ['group' => 'properties', 'kind' => 'plan', 'recommended' => [3000, 3000], 'min' => [1200, 0], 'mb' => 10, 'formats' => ['jpg', 'png', 'webp'], 'fit' => 'contain'],
        'unit_master_plan' => ['group' => 'properties', 'kind' => 'plan', 'recommended' => [3000, 3000], 'min' => [1200, 0], 'mb' => 10, 'formats' => ['jpg', 'png', 'webp'], 'fit' => 'contain'],
        // Galleries (a property's photos, plans and 360° images)
        'gallery_featured' => ['group' => 'galleries', 'kind' => 'photo', 'recommended' => [1920, 1440], 'min' => [1200, 800], 'mb' => 5, 'formats' => ['jpg', 'png', 'webp'], 'fit' => 'contain'],
        'gallery_photos' => ['group' => 'galleries', 'kind' => 'photo', 'recommended' => [1920, 1280], 'min' => [1200, 800], 'mb' => 10, 'formats' => ['jpg', 'png', 'webp'], 'fit' => 'contain'],
        'gallery_360' => ['group' => 'galleries', 'kind' => 'panorama', 'recommended' => [6000, 3000], 'min' => [4000, 2000], 'mb' => 20, 'formats' => ['jpg', 'png', 'webp'], 'fit' => 'none'],
        'gallery_floor_plans' => ['group' => 'galleries', 'kind' => 'plan', 'recommended' => [3000, 3000], 'min' => [1200, 0], 'mb' => 10, 'formats' => ['jpg', 'png', 'webp'], 'fit' => 'contain'],
        // Projects
        'project_featured' => ['group' => 'projects', 'kind' => 'photo', 'recommended' => [1920, 1080], 'min' => [1280, 720], 'mb' => 5, 'formats' => ['jpg', 'png', 'webp'], 'fit' => 'contain'],
        'project_logo' => ['group' => 'projects', 'kind' => 'logo', 'recommended' => [800, 800], 'min' => [200, 0], 'mb' => 2, 'formats' => ['png', 'webp', 'jpg'], 'fit' => 'contain'],
        'project_gallery' => ['group' => 'projects', 'kind' => 'photo', 'recommended' => [1920, 1280], 'min' => [1200, 800], 'mb' => 5, 'formats' => ['jpg', 'png', 'webp'], 'fit' => 'contain'],
        'project_about' => ['group' => 'projects', 'kind' => 'photo', 'recommended' => [1920, 1280], 'min' => [1200, 800], 'mb' => 5, 'formats' => ['jpg', 'png', 'webp'], 'fit' => 'contain'],
        'project_master_plan' => ['group' => 'projects', 'kind' => 'plan', 'recommended' => [3000, 3000], 'min' => [1200, 0], 'mb' => 10, 'formats' => ['jpg', 'png', 'webp'], 'fit' => 'contain'],
        // Developers
        'developer_logo' => ['group' => 'developers', 'kind' => 'logo', 'recommended' => [800, 800], 'min' => [200, 0], 'mb' => 2, 'formats' => ['png', 'webp', 'jpg'], 'fit' => 'contain'],
        'developer_featured' => ['group' => 'developers', 'kind' => 'photo', 'recommended' => [1920, 1080], 'min' => [1200, 675], 'mb' => 5, 'formats' => ['jpg', 'png', 'webp'], 'fit' => 'contain'],
        // Locations
        'location_featured' => ['group' => 'locations', 'kind' => 'photo', 'recommended' => [1920, 1280], 'min' => [1200, 800], 'mb' => 5, 'formats' => ['jpg', 'png', 'webp'], 'fit' => 'contain'],
        // Website images (Settings)
        'site_logo' => ['group' => 'website', 'kind' => 'logo', 'recommended' => [800, 200], 'min' => [300, 0], 'mb' => 1, 'formats' => ['png', 'webp'], 'fit' => 'contain'],
        'page_header' => ['group' => 'website', 'kind' => 'banner', 'recommended' => [2400, 800], 'min' => [1200, 400], 'mb' => 5, 'formats' => ['jpg', 'png', 'webp'], 'fit' => 'crop', 'crop' => '3:1'],
        'contact_hero' => ['group' => 'website', 'kind' => 'banner', 'recommended' => [2400, 1350], 'min' => [1200, 675], 'mb' => 5, 'formats' => ['jpg', 'png', 'webp'], 'fit' => 'crop', 'crop' => '16:9'],
        'careers_hero' => ['group' => 'website', 'kind' => 'banner', 'recommended' => [2400, 1350], 'min' => [1200, 675], 'mb' => 5, 'formats' => ['jpg', 'png', 'webp'], 'fit' => 'crop', 'crop' => '16:9'],
        'about_hero' => ['group' => 'website', 'kind' => 'banner', 'recommended' => [2400, 1350], 'min' => [1200, 675], 'mb' => 5, 'formats' => ['jpg', 'png', 'webp'], 'fit' => 'crop', 'crop' => '16:9'],
        'why_background' => ['group' => 'website', 'kind' => 'banner', 'recommended' => [2400, 1350], 'min' => [1200, 675], 'mb' => 5, 'formats' => ['jpg', 'png', 'webp'], 'fit' => 'crop', 'crop' => '16:9'],
        'footer_background' => ['group' => 'website', 'kind' => 'banner', 'recommended' => [2400, 1350], 'min' => [1200, 675], 'mb' => 5, 'formats' => ['jpg', 'png', 'webp'], 'fit' => 'crop', 'crop' => '16:9'],
        'share_image' => ['group' => 'website', 'kind' => 'share', 'recommended' => [1200, 630], 'min' => [600, 315], 'mb' => 5, 'formats' => ['jpg', 'png', 'webp'], 'fit' => 'crop', 'crop' => '40:21'],
        // About us
        'about_story' => ['group' => 'about', 'kind' => 'photo', 'recommended' => [1600, 1200], 'min' => [800, 600], 'mb' => 2, 'formats' => ['jpg', 'png', 'webp'], 'fit' => 'contain'],
        'card_icon' => ['group' => 'about', 'kind' => 'icon', 'recommended' => [256, 256], 'min' => [64, 64], 'mb' => 1, 'formats' => ['png', 'webp', 'jpg'], 'fit' => 'contain'],
        // Team
        'user_avatar' => ['group' => 'team', 'kind' => 'avatar', 'recommended' => [512, 512], 'min' => [200, 200], 'mb' => 2, 'formats' => ['jpg', 'png', 'webp'], 'fit' => 'crop', 'crop' => '1:1'],
        // Sell requests (sent from the website's "Submit Property" form)
        'sell_request_featured' => ['group' => 'sell_requests', 'kind' => 'photo', 'recommended' => [1920, 1440], 'min' => [800, 600], 'mb' => 5, 'formats' => ['jpg', 'png', 'webp'], 'fit' => 'contain'],
        'sell_request_photos' => ['group' => 'sell_requests', 'kind' => 'photo', 'recommended' => [1920, 1440], 'min' => [800, 600], 'mb' => 5, 'formats' => ['jpg', 'png', 'webp'], 'fit' => 'contain'],
    ];

    /**
     * The shapes a place can be switched to: each one the layout is tested with (the photo fills it, cropped at the
     * edges, never stretched). Written out so Tailwind makes every class.
     */
    private const SHAPES = [
        '1:1' => 'aspect-square',
        '5:4' => 'aspect-[5/4]',
        '4:3' => 'aspect-[4/3]',
        '3:2' => 'aspect-[3/2]',
        '16:10' => 'aspect-[16/10]',
        '16:9' => 'aspect-video',
    ];

    /** The phone shapes, per breakpoint a place uses (also written out for Tailwind). */
    private const PHONE_SHAPES = [
        'max-[700px]' => ['1:1' => 'max-[700px]:aspect-square', '4:3' => 'max-[700px]:aspect-[4/3]', '3:2' => 'max-[700px]:aspect-[3/2]', '16:10' => 'max-[700px]:aspect-[16/10]', '16:9' => 'max-[700px]:aspect-video'],
        'max-[560px]' => ['1:1' => 'max-[560px]:aspect-square', '4:3' => 'max-[560px]:aspect-[4/3]', '3:2' => 'max-[560px]:aspect-[3/2]', '16:10' => 'max-[560px]:aspect-[16/10]', '16:9' => 'max-[560px]:aspect-video'],
    ];

    /**
     * The places on the website (and in the admin) that show the images. width: the widest the photo is shown on a
     * computer (CSS px), so a sharp screen needs twice that. display: "shape" (a shape that can be changed, from
     * `shapes`, and on phones from `phone_shapes` at `phone`), "screen" (fills the screen: its shape follows the
     * visitor's screen), "strip" (a band of a fixed height, cropped), "whole" (shown whole, never cropped) or
     * "admin" (only seen in the admin).
     */
    private const PLACES = [
        // Properties
        'property_card' => ['fields' => ['unit_featured'], 'page' => 'properties', 'display' => 'shape', 'shape' => '4:3', 'shapes' => ['4:3', '3:2', '16:10', '5:4', '1:1'], 'width' => 420],
        'property_page_photo' => ['fields' => ['unit_featured', 'gallery_photos'], 'page' => 'property', 'display' => 'shape', 'shape' => '16:10', 'shapes' => ['16:10', '16:9', '3:2', '4:3'], 'width' => 820],
        'home_featured' => ['fields' => ['unit_featured'], 'page' => 'home', 'display' => 'strip', 'width' => 860],
        'wishlist_card' => ['fields' => ['unit_featured', 'project_featured'], 'page' => 'wishlist', 'display' => 'shape', 'shape' => '16:10', 'shapes' => ['16:10', '16:9', '3:2', '4:3'], 'width' => 420],
        'compare_card' => ['fields' => ['unit_featured'], 'page' => 'compare', 'display' => 'strip', 'width' => 320],
        'search_result' => ['fields' => ['unit_featured', 'project_featured', 'developer_logo', 'location_featured'], 'page' => 'search', 'display' => 'strip', 'width' => 72],
        'property_360' => ['fields' => ['unit_360', 'gallery_360'], 'page' => 'property', 'display' => 'screen', 'width' => 1920],
        'property_plans' => ['fields' => ['unit_floor_plan', 'unit_master_plan', 'gallery_floor_plans'], 'page' => 'property', 'display' => 'whole', 'width' => 820],
        'photo_viewer' => ['fields' => ['gallery_photos', 'project_gallery'], 'page' => 'viewer', 'display' => 'whole', 'width' => 1600],
        'property_pdf' => ['fields' => ['unit_featured', 'gallery_photos', 'project_featured'], 'page' => 'pdf', 'display' => 'whole', 'width' => 800],
        'gallery_cover' => ['fields' => ['gallery_featured'], 'page' => 'admin', 'display' => 'admin', 'width' => 160],
        // Projects
        'project_card' => ['fields' => ['project_featured'], 'page' => 'projects', 'display' => 'shape', 'shape' => '16:10', 'shapes' => ['16:10', '16:9', '3:2', '4:3'], 'width' => 420],
        'home_hero' => ['fields' => ['project_featured'], 'page' => 'home', 'display' => 'screen', 'width' => 1920],
        'project_hero' => ['fields' => ['project_featured'], 'page' => 'project', 'display' => 'screen', 'width' => 1920],
        'project_logo' => ['fields' => ['project_logo'], 'page' => 'project', 'display' => 'whole', 'width' => 200],
        'project_gallery' => ['fields' => ['project_gallery'], 'page' => 'project', 'display' => 'shape', 'shape' => '4:3', 'shapes' => ['4:3', '3:2', '16:10', '1:1'], 'phone' => 'max-[560px]', 'phone_shape' => '3:2', 'phone_shapes' => ['3:2', '4:3', '16:9', '1:1'], 'width' => 640],
        'project_location' => ['fields' => ['project_gallery', 'project_featured'], 'page' => 'project', 'display' => 'shape', 'shape' => '4:3', 'shapes' => ['4:3', '3:2', '16:10', '1:1'], 'width' => 560],
        'project_master_plan' => ['fields' => ['project_master_plan'], 'page' => 'project', 'display' => 'whole', 'width' => 1200],
        'project_about' => ['fields' => ['project_about'], 'page' => 'admin', 'display' => 'admin', 'width' => 160],
        // Developers
        'developer_card' => ['fields' => ['developer_featured', 'developer_logo'], 'page' => 'developers', 'display' => 'strip', 'width' => 420],
        'developer_photo' => ['fields' => ['developer_featured'], 'page' => 'developer', 'display' => 'shape', 'shape' => '4:3', 'shapes' => ['4:3', '3:2', '16:10', '5:4'], 'width' => 620],
        'developer_logo' => ['fields' => ['developer_logo'], 'page' => 'developer', 'display' => 'whole', 'width' => 220],
        // Locations
        'location_card' => ['fields' => ['location_featured'], 'page' => 'locations', 'display' => 'shape', 'shape' => '1:1', 'shapes' => ['1:1', '4:3', '3:2', '5:4'], 'phone' => 'max-[700px]', 'phone_shape' => '16:9', 'phone_shapes' => ['16:9', '4:3', '3:2', '1:1'], 'width' => 320],
        'home_locations' => ['fields' => ['location_featured'], 'page' => 'home', 'display' => 'strip', 'width' => 460],
        'location_header' => ['fields' => ['location_featured'], 'page' => 'location', 'display' => 'screen', 'width' => 1920],
        // Website
        'site_logo' => ['fields' => ['site_logo'], 'page' => 'everywhere', 'display' => 'whole', 'width' => 230],
        'page_header' => ['fields' => ['page_header'], 'page' => 'listings', 'display' => 'screen', 'width' => 1920],
        'contact_hero' => ['fields' => ['contact_hero'], 'page' => 'contact', 'display' => 'screen', 'width' => 1920],
        'careers_hero' => ['fields' => ['careers_hero'], 'page' => 'careers', 'display' => 'screen', 'width' => 1920],
        'about_hero' => ['fields' => ['about_hero'], 'page' => 'about', 'display' => 'screen', 'width' => 1920],
        'why_background' => ['fields' => ['why_background'], 'page' => 'home', 'display' => 'screen', 'width' => 1920],
        'footer_background' => ['fields' => ['footer_background'], 'page' => 'everywhere', 'display' => 'screen', 'width' => 1920],
        'share_preview' => ['fields' => ['share_image', 'unit_featured', 'project_featured'], 'page' => 'share', 'display' => 'whole', 'width' => 600],
        // About us
        'about_story' => ['fields' => ['about_story'], 'page' => 'about', 'display' => 'strip', 'width' => 640],
        'card_icons' => ['fields' => ['card_icon'], 'page' => 'about', 'display' => 'whole', 'width' => 48],
        // Team
        'agent_card' => ['fields' => ['user_avatar'], 'page' => 'property', 'display' => 'strip', 'width' => 64],
        // Sell requests
        'sell_request' => ['fields' => ['sell_request_featured', 'sell_request_photos'], 'page' => 'admin', 'display' => 'admin', 'width' => 160],
    ];

    /**
     * Every upload field with its values now (the default, or what an admin changed).
     *
     * @return array<string, array{key: string, group: string, kind: string, recommended: array{int, int}, min: array{int, int}, mb: int, formats: list<string>, fit: string, crop: ?string, changed: bool}>
     */
    public static function fields(): array
    {
        return once(function () {
            $changed = static::changed();

            return collect(self::FIELDS)->map(function (array $field, string $key) use ($changed) {
                $values = $changed["field:{$key}"] ?? [];
                // Made safe again when read: a value changed outside this page, or limits made tighter in the code
                // since, can never get through.
                $safe = $values ? static::safe("field:{$key}", [...static::defaults("field:{$key}"), ...$values]) : [];

                return ['key' => $key, 'crop' => null, ...$field, ...$safe, 'changed' => $values !== []];
            })->all();
        });
    }

    /**
     * @return array{key: string, group: string, kind: string, recommended: array{int, int}, min: array{int, int}, mb: int, formats: list<string>, fit: string, crop: ?string, changed: bool}
     */
    public static function field(string $key): array
    {
        return static::fields()[$key] ?? throw new InvalidArgumentException("Unknown image field [{$key}].");
    }

    /**
     * Every place with its shape now.
     *
     * @return array<string, array{key: string, fields: list<string>, page: string, display: string, width: int, shape: ?string, shapes: list<string>, phone: ?string, phone_shape: ?string, phone_shapes: list<string>, changed: bool}>
     */
    public static function places(): array
    {
        return once(function () {
            $changed = static::changed();

            return collect(self::PLACES)->map(function (array $place, string $key) use ($changed) {
                $values = $changed["place:{$key}"] ?? [];
                $place = ['key' => $key, 'shape' => null, 'shapes' => [], 'phone' => null, 'phone_shape' => null, 'phone_shapes' => [], ...$place];
                // Only a shape the layout is tested with: one changed in the code since is dropped.
                if (in_array($values['shape'] ?? null, $place['shapes'], true)) {
                    $place['shape'] = $values['shape'];
                }
                if (in_array($values['phone_shape'] ?? null, $place['phone_shapes'], true)) {
                    $place['phone_shape'] = $values['phone_shape'];
                }

                return [...$place, 'changed' => $values !== []];
            })->all();
        });
    }

    /**
     * @return array{key: string, fields: list<string>, page: string, display: string, width: int, shape: ?string, shapes: list<string>, phone: ?string, phone_shape: ?string, phone_shapes: list<string>, changed: bool}
     */
    public static function place(string $key): array
    {
        return static::places()[$key] ?? throw new InvalidArgumentException("Unknown image place [{$key}].");
    }

    /**
     * The places a field's images are shown in.
     *
     * @return list<string>
     */
    public static function placesOf(string $field): array
    {
        return array_keys(array_filter(self::PLACES, fn (array $place) => in_array($field, $place['fields'], true)));
    }

    /**
     * The Tailwind classes of a place's shape (with its phone shape), e.g. "aspect-square max-[700px]:aspect-video".
     */
    public static function shapeClass(string $place): string
    {
        $place = static::place($place);

        return trim(self::SHAPES[$place['shape']].' '.($place['phone'] ? self::PHONE_SHAPES[$place['phone']][$place['phone_shape']] : ''));
    }

    /**
     * The limits an admin can change a field within (its kind's).
     *
     * @return array{size: array{int, int}, min: array{int, int}, mb: array{int, int}}
     */
    public static function limits(string $field): array
    {
        return self::KINDS[static::field($field)['kind']];
    }

    /**
     * The default values of a field or a place ("field:unit_featured", "place:property_card").
     *
     * @return array<string, mixed>
     */
    public static function defaults(string $key): array
    {
        [$type, $name] = explode(':', $key, 2);

        return $type === 'field'
            ? array_intersect_key(self::FIELDS[$name], array_flip(['recommended', 'min', 'mb', 'formats']))
            : array_intersect_key(self::PLACES[$name], array_flip(['shape', 'phone_shape']));
    }

    /**
     * Saves an admin's change, kept within the safe limits (and back to nothing when it equals the default).
     *
     * @param  array<string, mixed>  $values
     */
    public static function save(string $key, array $values, ?int $userId = null): void
    {
        $values = static::safe($key, $values);
        $changed = array_filter($values, fn ($value, string $name) => $value !== (static::defaults($key)[$name] ?? null), ARRAY_FILTER_USE_BOTH);

        $changed === []
            ? ImageSize::query()->where('key', $key)->delete()
            : ImageSize::query()->updateOrCreate(['key' => $key], ['values' => $changed, 'updated_by' => $userId]);

        static::forget();
    }

    /**
     * "Back to default".
     */
    public static function reset(string $key): void
    {
        ImageSize::query()->where('key', $key)->delete();
        static::forget();
    }

    /**
     * The values made safe: sizes within the kind's limits, the minimum never above the recommended size, the file
     * size within its limits, formats among the known ones (at least one), shapes among the place's tested ones.
     *
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    public static function safe(string $key, array $values): array
    {
        [$type, $name] = explode(':', $key, 2);
        $defaults = static::defaults($key);

        if ($type === 'place') {
            $place = self::PLACES[$name] ?? throw new InvalidArgumentException("Unknown image place [{$name}].");

            return array_filter([
                'shape' => in_array($values['shape'] ?? null, $place['shapes'] ?? [], true) ? $values['shape'] : ($defaults['shape'] ?? null),
                'phone_shape' => in_array($values['phone_shape'] ?? null, $place['phone_shapes'] ?? [], true) ? $values['phone_shape'] : ($defaults['phone_shape'] ?? null),
            ], fn ($value) => $value !== null);
        }

        $field = self::FIELDS[$name] ?? throw new InvalidArgumentException("Unknown image field [{$name}].");
        $limits = self::KINDS[$field['kind']];
        $clamp = fn ($value, array $range, int $fallback) => is_numeric($value) ? max($range[0], min($range[1], (int) $value)) : $fallback;
        $recommended = [
            $clamp($values['recommended'][0] ?? null, $limits['size'], $defaults['recommended'][0]),
            $clamp($values['recommended'][1] ?? null, $limits['size'], $defaults['recommended'][1]),
        ];
        $min = [
            min($recommended[0], $clamp($values['min'][0] ?? null, $limits['min'], $defaults['min'][0])),
            min($recommended[1], $clamp($values['min'][1] ?? null, $limits['min'], $defaults['min'][1])),
        ];

        // A cropped image keeps its place's shape: the height follows the width (the place is built for that shape).
        if (isset($field['crop'])) {
            [$w, $h] = array_map('intval', explode(':', $field['crop']));
            $recommended[1] = (int) round($recommended[0] * $h / $w);
            $min[0] = min($min[0], $recommended[0]);
            $min[1] = (int) round($min[0] * $h / $w);
        }

        $formats = array_values(array_intersect(array_keys(self::FORMATS), (array) ($values['formats'] ?? [])));
        // The same formats in another order are no change.
        $sameAsDefault = $formats !== [] && array_diff($formats, $defaults['formats']) === [] && array_diff($defaults['formats'], $formats) === [];

        return [
            'recommended' => $recommended,
            'min' => $min,
            'mb' => $clamp($values['mb'] ?? null, $limits['mb'], $defaults['mb']),
            'formats' => $formats === [] || $sameAsDefault ? $defaults['formats'] : $formats,
        ];
    }

    /**
     * The changed values, by key: one cached read, whatever the number of fields on a page.
     *
     * @return array<string, array<string, mixed>>
     */
    private static function changed(): array
    {
        // Once per request (the fields and the places both need it), from the cache shared by every request.
        return once(function () {
            try {
                return Cache::rememberForever(self::CACHE_KEY, fn () => ImageSize::query()->pluck('values', 'key')->all());
            } catch (QueryException $e) {
                // While the site is being updated (the table not made yet): the design's sizes instead of an error
                // page; nothing is cached, so the table is read as soon as it exists.
                report($e);

                return [];
            }
        });
    }

    /**
     * After a change: the cache and this request's copies, so the page shows the new values at once.
     */
    private static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
        Once::flush();
    }
}
