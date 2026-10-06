<?php

namespace Modules\Sliders\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Sliders\Models\Slider;

/**
 * @extends Factory<Slider>
 */
class SliderFactory extends Factory
{
    protected $model = Slider::class;

    public function definition(): array
    {
        return [
            'title_en' => ucwords(fake()->unique()->words(2, true)),
            'title_ar' => 'قسم '.fake()->unique()->numberBetween(1, 99999),
            'placement' => Slider::HOME_BODY,
            'content_type' => fake()->randomElement(Slider::CONTENT_TYPES),
            'order' => fake()->unique()->numberBetween(1, 60000),
        ];
    }

    public function hero(): static
    {
        return $this->state(['placement' => Slider::HOME_HERO, 'content_type' => 'projects']);
    }
}
