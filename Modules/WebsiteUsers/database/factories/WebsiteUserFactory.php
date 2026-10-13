<?php

namespace Modules\WebsiteUsers\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\WebsiteUsers\Models\WebsiteUser;

/**
 * @extends Factory<WebsiteUser>
 */
class WebsiteUserFactory extends Factory
{
    protected $model = WebsiteUser::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '+2010'.fake()->unique()->numerify('########'),
            'email_verified_at' => now(),
            'password' => 'password',
        ];
    }

    public function blocked(): static
    {
        return $this->state(['status' => WebsiteUser::BLOCKED]);
    }
}
