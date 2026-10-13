<?php

namespace Modules\PossibleLeads\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\PossibleLeads\Models\PossibleLead;

/**
 * @extends Factory<PossibleLead>
 */
class PossibleLeadFactory extends Factory
{
    protected $model = PossibleLead::class;

    public function definition(): array
    {
        return [
            'click_type' => fake()->randomElement(PossibleLead::TYPES),
            'page_url' => 'https://akartown.com/'.fake()->slug(2),
            'visitor_id' => (string) Str::uuid(),
        ];
    }
}
