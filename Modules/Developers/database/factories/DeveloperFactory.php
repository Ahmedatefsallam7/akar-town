<?php

namespace Modules\Developers\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Developers\Models\Developer;

/**
 * @extends Factory<Developer>
 */
class DeveloperFactory extends Factory
{
    protected $model = Developer::class;

    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name_en' => $name,
            'name_ar' => "ع {$name}",
            'is_published' => true,
        ];
    }
}
