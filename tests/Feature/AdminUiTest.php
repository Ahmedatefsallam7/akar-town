<?php

namespace Tests\Feature;

use Filament\Facades\Filament;
use Filament\Pages\BasePage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Lookups\Filament\Resources\LookupResource;
use Modules\Users\Filament\Resources\UserResource;
use Modules\Users\Models\User;
use Tests\TestCase;

/**
 * The simplified admin UI: visible row buttons, status badge, empty states, user form tabs.
 */
class AdminUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_view_and_edit_are_direct_buttons_in_tables(): void
    {
        $this->actingAs($this->createAdmin());
        $user = User::factory()->create();

        $this->get(UserResource::getUrl('index'))
            ->assertOk()
            ->assertSee(UserResource::getUrl('view', ['record' => $user]), false)
            ->assertSee(UserResource::getUrl('edit', ['record' => $user]), false)
            ->assertSee(__('admin.actions.more'));
    }

    public function test_users_table_shows_a_clear_status_badge(): void
    {
        $this->actingAs($this->createAdmin());
        User::factory()->create(['is_suspended' => true]);

        $this->get(UserResource::getUrl('index'))
            ->assertSee(__('admin.table.status'))
            ->assertSee(__('admin.lookups.active'))
            ->assertSee(__('admin.users.suspended'));
    }

    public function test_empty_table_shows_a_friendly_message_with_an_add_button(): void
    {
        $this->actingAs($this->createAdmin());

        $this->get(LookupResource::getUrl('index'))
            ->assertSee(__('admin.table.empty_heading', ['name' => LookupResource::getPluralModelLabel()]))
            ->assertSee(__('admin.table.empty_description'))
            ->assertSee(LookupResource::getUrl('create'), false);
    }

    public function test_user_form_is_split_into_tabs(): void
    {
        $this->actingAs($this->createAdmin());
        $user = User::factory()->create();

        $this->get(UserResource::getUrl('edit', ['record' => $user]))
            ->assertSee(__('admin.tabs.details'))
            ->assertSee(__('admin.users.password'))
            ->assertSee(__('admin.users.permissions'));

        $this->get(UserResource::getUrl('view', ['record' => $user]))
            ->assertSee(__('admin.users.effective_permissions'));
    }

    public function test_sidebar_sections_have_a_fixed_order_with_translated_labels(): void
    {
        $this->actingAs($this->createAdmin());
        app()->setLocale('ar');

        $labels = collect(Filament::getNavigationGroups())->map->getLabel()->values()->all();

        $this->assertSame([
            __('admin.navigation.property_management'),
            __('admin.navigation.marketing_content'),
            __('admin.navigation.inquiries_crm'),
            __('admin.navigation.system_configuration'),
        ], $labels);
    }

    public function test_save_buttons_stay_visible_on_long_forms(): void
    {
        $this->assertTrue((new \ReflectionProperty(BasePage::class, 'formActionsAreSticky'))->getValue());
    }

    public function test_links_prefetch_on_hover_but_never_the_language_switch(): void
    {
        $this->actingAs($this->createAdmin());

        $html = $this->get(UserResource::getUrl('index'))->assertOk()->getContent();

        $this->assertStringContainsString('wire:navigate.hover', $html);

        // Opening /locale/{x} changes the language cookie, so it must never load just because the mouse passed over it.
        preg_match_all('/<a\b[^>]*?>/s', $html, $links);
        $localeLinks = array_filter($links[0], fn (string $tag) => str_contains($tag, '/locale/'));

        $this->assertNotEmpty($localeLinks);
        foreach ($localeLinks as $tag) {
            $this->assertStringNotContainsString('wire:navigate', $tag);
        }
    }
}
