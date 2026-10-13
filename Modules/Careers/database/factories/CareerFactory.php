<?php

namespace Modules\Careers\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Careers\Models\Career;

/**
 * @extends Factory<Career>
 */
class CareerFactory extends Factory
{
    protected $model = Career::class;

    public function definition(): array
    {
        $title = fake()->randomElement(['Sales Consultant', 'Property Advisor', 'Marketing Specialist', 'Accountant']).' '.fake()->unique()->numberBetween(1, 99999);

        return [
            'title_en' => $title,
            'title_ar' => 'وظيفة '.$title,
            'type' => 'full_time',
            'vacancies' => fake()->numberBetween(1, 5),
        ];
    }
}
