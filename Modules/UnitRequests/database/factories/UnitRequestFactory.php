<?php

namespace Modules\UnitRequests\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\UnitRequests\Models\UnitRequest;

/**
 * @extends Factory<UnitRequest>
 */
class UnitRequestFactory extends Factory
{
    protected $model = UnitRequest::class;

    public function definition(): array
    {
        return [
            'seller_name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => '+2010'.fake()->numerify('########'),
            'name_en' => fake()->numberBetween(2, 4).'-Bedroom Apartment '.fake()->unique()->numberBetween(1, 99999),
            'price' => fake()->numberBetween(2, 30) * 500_000,
            'area' => fake()->numberBetween(80, 400),
            'bedrooms' => fake()->numberBetween(1, 5),
            'bathrooms' => fake()->numberBetween(1, 4),
        ];
    }
}
