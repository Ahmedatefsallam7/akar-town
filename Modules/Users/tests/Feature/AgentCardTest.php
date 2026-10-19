<?php

namespace Modules\Users\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Projects\Filament\Resources\ProjectResource\Pages\EditProject;
use Modules\Projects\Models\Project;
use Modules\Units\Filament\Resources\UnitResource\Pages\EditUnit;
use Modules\Units\Models\Unit;
use Modules\Users\Filament\Resources\UserResource\Pages\EditUser;
use Tests\TestCase;

/**
 * Agents are admin users: their card fields on the user form, and the "Agent" picker of units and projects.
 */
class AgentCardTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_agent_card_fields_are_saved_on_the_user(): void
    {
        $this->actingAs($admin = $this->createAdmin());
        $agent = $this->createAdmin(['email' => 'agent@example.com', 'username' => 'agent', 'phone' => '+201000000001']);

        Livewire::test(EditUser::class, ['record' => $agent->getRouteKey()])
            ->fillForm(['job_title_en' => 'Senior Property Consultant', 'job_title_ar' => 'مستشار عقاري أول', 'whatsapp' => '+201001234567', 'rating' => 4.8, 'reviews_count' => 48])
            ->call('save')
            ->assertHasNoFormErrors();

        $agent->refresh();
        $this->assertSame(['Senior Property Consultant', 'مستشار عقاري أول', '+201001234567', 4.8, 48], [$agent->job_title_en, $agent->job_title_ar, $agent->whatsapp, $agent->rating, $agent->reviews_count]);

        Livewire::test(EditUser::class, ['record' => $agent->getRouteKey()])
            ->fillForm(['whatsapp' => 'call me', 'rating' => 6, 'reviews_count' => -1])
            ->call('save')
            ->assertHasFormErrors(['whatsapp' => 'regex', 'rating' => 'max', 'reviews_count' => 'min']);
    }

    public function test_a_unit_and_a_project_pick_an_active_admin_user_as_their_agent(): void
    {
        $this->actingAs($this->createAdmin());
        $agent = $this->createAdmin(['email' => 'agent@example.com', 'username' => 'agent', 'phone' => '+201000000001']);
        $suspended = $this->createAdmin(['email' => 'left@example.com', 'username' => 'left', 'phone' => '+201000000002', 'is_suspended' => true]);
        $unit = Unit::factory()->create();

        Livewire::test(EditUnit::class, ['record' => $unit->getRouteKey()])
            ->fillForm(['agent_id' => $agent->id])
            ->call('save')
            ->assertHasNoFormErrors();
        $this->assertTrue($unit->refresh()->agent->is($agent));

        // People who left (suspended) can't be picked.
        Livewire::test(EditUnit::class, ['record' => $unit->getRouteKey()])
            ->fillForm(['agent_id' => $suspended->id])
            ->call('save')
            ->assertHasFormErrors(['agent_id']);
        Livewire::test(EditProject::class, ['record' => Project::factory()->create()->getRouteKey()])
            ->fillForm(['agent_id' => $suspended->id])
            ->call('save')
            ->assertHasFormErrors(['agent_id']);
    }
}
