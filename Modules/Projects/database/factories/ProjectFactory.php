<?php

namespace Modules\Projects\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Developers\Models\Developer;
use Modules\Projects\Models\Project;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        $title = fake()->unique()->words(2, true).' Compound';

        return [
            'title_en' => ucwords($title),
            'title_ar' => "ع {$title}",
            'developer_id' => Developer::factory(),
            'is_published' => true,
        ];
    }
}
