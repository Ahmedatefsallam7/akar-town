<?php

namespace Modules\Units\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Units\Models\Unit;

/**
 * @extends Factory<Unit>
 */
class UnitFactory extends Factory
{
    protected $model = Unit::class;

    public function definition(): array
    {
        $name = 'Apartment '.fake()->unique()->numberBetween(100, 99999);

        return [
            'name_en' => $name,
            'name_ar' => "شقة {$name}",
            'is_published' => true,
        ];
    }
}
