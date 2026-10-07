<?php

namespace Modules\Careers\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Careers\Models\Applicant;
use Modules\Careers\Models\Career;

/**
 * @extends Factory<Applicant>
 */
class ApplicantFactory extends Factory
{
    protected $model = Applicant::class;

    public function definition(): array
    {
        return [
            'career_id' => Career::factory(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '+2010'.fake()->numerify('########'),
        ];
    }
}
