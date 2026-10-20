<?php

namespace Tests\Feature\Website;

use App\Support\Website\Favourites;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\GeneralSettings\Settings\SiteSettings;
use Modules\Projects\Models\Project;
use Modules\Units\Models\Unit;
use Modules\WebsiteUsers\Models\WebsiteUser;
use Tests\TestCase;

/**
 * A signed-in user's wishlist is saved to the account (a guest's stays in the browser).
 */
class AccountFavouritesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $site = app(SiteSettings::class);
        $site->is_live = true;
        $site->save();
    }

    public function test_signed_in_pages_carry_the_saved_list_and_guest_pages_dont(): void
    {
        $user = WebsiteUser::factory()->create();
        $unit = Unit::factory()->create();
        $project = Project::factory()->create();
        Favourites::update($user, [$unit->id, "project-{$project->id}"], []);

        $this->get(route('home'))->assertOk()->assertDontSee('name="account-favourites"', false);

        $this->actingAs($user, 'website')->get(route('home'))
            ->assertOk()
            ->assertSee('name="account-favourites" content="'.e(json_encode([$unit->id, "project-{$project->id}"])).'"', false);
    }

    public function test_clicks_save_and_remove_on_the_account_only(): void
    {
        $user = WebsiteUser::factory()->create();
        $other = WebsiteUser::factory()->create();
        [$a, $b] = Unit::factory()->count(2)->create();
        $project = Project::factory()->create();
        Favourites::update($other, [$a->id], []);

        $this->actingAs($user, 'website')
            ->postJson(route('account.favourites'), ['add' => [$a->id, $b->id, "project-{$project->id}", $a->id, 999999, 'project-999999', 'junk', ['x']]])
            ->assertNoContent();
        $this->assertSame([$a->id, $b->id, "project-{$project->id}"], Favourites::of($user)); // unknown ids and repeats skipped

        $this->postJson(route('account.favourites'), ['add' => [$a->id]])->assertNoContent(); // already saved
        $this->postJson(route('account.favourites'), ['remove' => [$a->id, "project-{$project->id}"]])->assertNoContent();

        $this->assertSame([$b->id], Favourites::of($user));
        $this->assertSame([$a->id], Favourites::of($other)); // someone else's list is never touched
    }

    public function test_guests_cant_save_and_lists_are_capped(): void
    {
        $this->postJson(route('account.favourites'), ['add' => [1]])->assertUnauthorized();

        $user = WebsiteUser::factory()->create();
        $this->actingAs($user, 'website')
            ->postJson(route('account.favourites'), ['add' => range(1, Favourites::MAX + 1)])
            ->assertUnprocessable();
    }

    public function test_deleting_a_unit_or_project_removes_it_from_lists(): void
    {
        $user = WebsiteUser::factory()->create();
        $unit = Unit::factory()->create();
        $project = Project::factory()->create();
        Favourites::update($user, [$unit->id, "project-{$project->id}"], []);

        $unit->forceDelete();
        $project->forceDelete();

        $this->assertSame([], Favourites::of($user));
    }
}
