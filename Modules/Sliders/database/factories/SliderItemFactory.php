<?php

namespace Modules\Sliders\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Projects\Models\Project;
use Modules\Sliders\Models\Slider;
use Modules\Sliders\Models\SliderItem;

/**
 * @extends Factory<SliderItem>
 */
class SliderItemFactory extends Factory
{
    protected $model = SliderItem::class;

    public function definition(): array
    {
        return [
            'slider_id' => Slider::factory()->hero(),
            'project_id' => Project::factory(),
            'order' => fake()->numberBetween(1, 1000),
        ];
    }
}
