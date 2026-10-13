<?php

namespace Modules\Inquiry\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Inquiry\Models\Inquiry;

/**
 * @extends Factory<Inquiry>
 */
class InquiryFactory extends Factory
{
    protected $model = Inquiry::class;

    public function definition(): array
    {
        return [
            'full_name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => '+2010'.fake()->numerify('########'),
            'message' => fake()->sentence(12),
        ];
    }
}
