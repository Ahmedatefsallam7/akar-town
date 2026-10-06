<?php

namespace Modules\Galleries\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Galleries\Models\Gallery;

/**
 * @extends Factory<Gallery>
 */
class GalleryFactory extends Factory
{
    protected $model = Gallery::class;

    public function definition(): array
    {
        return [
            'name_en' => ucwords(fake()->unique()->words(2, true)).' Apartments',
        ];
    }
}
