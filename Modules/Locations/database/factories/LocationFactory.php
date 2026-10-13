<?php

namespace Modules\Locations\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Locations\Models\Location;

/**
 * @extends Factory<Location>
 */
class LocationFactory extends Factory
{
    protected $model = Location::class;

    public function definition(): array
    {
        $name = fake()->unique()->city();

        return [
            'name_en' => $name,
            'name_ar' => "ع {$name}",
            'is_active' => true,
        ];
    }
}
