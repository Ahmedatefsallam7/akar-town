<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * An admin's change to an image's sizes (AKAR-50, see App\Support\Images\ImageSizes): only the values that differ
 * from the design's default, for a field ("field:unit_featured") or a place ("place:property_card").
 */
class ImageSize extends Model
{
    protected $fillable = ['key', 'values', 'updated_by'];

    protected $casts = ['values' => 'array'];
}
