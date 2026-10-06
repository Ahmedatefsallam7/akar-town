<?php

namespace Modules\Inquiry\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Inquiry\Models\Meeting;

/**
 * @extends Factory<Meeting>
 */
class MeetingFactory extends Factory
{
    protected $model = Meeting::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'phone' => '+2010'.fake()->numerify('########'),
            'email' => fake()->safeEmail(),
            'meeting_at' => now()->addDays(fake()->numberBetween(1, 30))->setTime(fake()->numberBetween(9, 17), 0),
            'type' => fake()->randomElement(Meeting::TYPES),
            'status' => Meeting::PENDING,
        ];
    }
}
