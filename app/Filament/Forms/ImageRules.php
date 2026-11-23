<?php

namespace App\Filament\Forms;

use App\Rules\NotTooBigImage;
use App\Support\Images\ImageSizes;
use App\Support\Images\ImageText;
use App\Support\Images\ImageValidation;
use Filament\Forms\Components\FileUpload;

/**
 * An admin upload field set from its sizes (AKAR-50, App\Support\Images\ImageSizes): the checks it makes
 * (ImageValidation: formats, file size, smallest image, not too big), how a new image is prepared (made at most the
 * recommended size, or cropped to the place's shape in the editor) and one line under it saying all that and where
 * the image is shown. Changing the sizes on Settings → Image Sizes changes every field using them.
 */
class ImageRules
{
    /**
     * @template T of FileUpload
     *
     * @param  T  $field
     * @return T
     */
    public static function apply(FileUpload $field, string $key, ?string $note = null): FileUpload
    {
        $image = ImageSizes::field($key);
        [$width, $height] = $image['recommended'];

        $field->image()
            ->acceptedFileTypes(ImageValidation::mimeTypes($key))
            ->maxSize($image['mb'] * 1024)
            ->helperText(trim(static::hint($key).' '.$note));

        // A rule object: Filament would run a closure passed to rule() to get the rule.
        if (ImageValidation::checksPixels($key)) {
            $field->rule(new NotTooBigImage);
        }

        if ($rule = ImageValidation::minimumRule($key)) {
            $field->rules([$rule])->validationMessages(['dimensions' => ImageText::tooSmall($key)]);
        }

        return match ($image['fit']) {
            // Cut to the place's shape (the editor lets the admin pick the part to keep), then saved at its size.
            'crop' => $field->imageAspectRatio($image['crop'])->automaticallyCropImagesToAspectRatio()
                ->imageEditor()->imageEditorAspectRatioOptions([$image['crop']])
                ->automaticallyResizeImagesMode('cover')->automaticallyResizeImagesToWidth((string) $width)->automaticallyResizeImagesToHeight((string) $height),
            // Made at most the recommended size on its longer side, its shape kept (a smaller image is never enlarged).
            // Not into the recommended box: a portrait photo was shrunk below the smallest size accepted in the browser,
            // then refused as "too small" (a 1600 × 2133 gallery photo became 1080 × 1440 for a 1200 × 800 minimum).
            'contain' => $field->automaticallyResizeImagesMode('contain')
                ->automaticallyResizeImagesToWidth((string) max($width, $height))->automaticallyResizeImagesToHeight((string) max($width, $height))
                ->automaticallyUpscaleImagesWhenResizing(false),
            default => $field,
        };
    }

    /**
     * The line under the field, e.g. "Recommended 1920 × 1440 px (4:3) · at least 1200 × 800 px · JPG, PNG, WebP ·
     * up to 5 MB · Shown on: Property card, Property page".
     */
    public static function hint(string $key): string
    {
        $image = ImageSizes::field($key);
        $parts = [
            __('admin.image_sizes.hint.recommended', ['size' => ImageText::size($image['recommended']), 'ratio' => ImageText::ratio($image['recommended'])]),
            $image['min'] === [0, 0] ? '' : __('admin.image_sizes.hint.at_least', ['size' => ImageText::minimum($image['min'])]),
            ImageText::formats($image['formats']),
            __('admin.image_sizes.hint.up_to', ['mb' => $image['mb']]),
        ];
        $places = array_map(fn (string $place) => __("admin.image_sizes.places.{$place}"), ImageSizes::placesOf($key));

        $shownOn = $places ? __('admin.image_sizes.hint.shown_on', ['places' => implode(__('admin.image_sizes.list_separator'), $places)]) : '';

        return implode(' · ', array_filter([...$parts, $shownOn]));
    }
}
