<?php

namespace App\Rules;

use App\Support\Images\ImageSizes;
use App\Support\Images\ImageText;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/**
 * Refuses an image with too many pixels (ImageSizes::MAX_PIXELS, AKAR-50): a small file can still be a huge image
 * (a "pixel bomb"), too big to open and make its smaller copies. Only the file's header is read.
 */
class NotTooBigImage implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $path = $value instanceof UploadedFile ? $value->getRealPath() : null;
        [$width, $height] = ($path ? @getimagesize($path) : false) ?: [0, 0];

        if ($width * $height > ImageSizes::MAX_PIXELS) {
            $fail(ImageText::tooBig());
        }
    }
}
