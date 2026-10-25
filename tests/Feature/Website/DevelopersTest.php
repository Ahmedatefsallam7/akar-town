<?php

namespace Tests\Feature\Website;

use App\Livewire\Website\Developers;
use App\Support\Website\DeveloperCards;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Modules\Developers\Models\Developer;
use Modules\GeneralSettings\Settings\DeveloperDetailsSettings;
use Modules\GeneralSettings\Settings\SiteSettings;
use Modules\Projects\Models\Project;
use Tests\TestCase;

/**
 * The developers page (the design's developers.html) and one developer's page (developer-details.html).
 */
class DevelopersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $site = app(SiteSettings::class);
        $site->is_live = true;
        $site->save();
    }

    private function developer(array $attributes = []): Developer
    {
        return Developer::factory()->create($attributes + ['name_en' => 'Mountain View', 'slug' => 'mountain-view', 'is_published' => true]);
    }

    public function test_the_page_lists_published_developers_featured_first_then_by_projects(): void
    {
        $few = $this->developer(['name_en' => 'Few Projects', 'slug' => 'few', 'short_description_en' => 'Coastal homes.', 'phone' => '+20 16166']);
        $many = $this->developer(['name_en' => 'Many Projects', 'slug' => 'many']);
        $this->developer(['name_en' => 'Star Developer', 'slug' => 'star', 'is_featured' => true]);
        $this->developer(['name_en' => 'Hidden Developer', 'slug' => 'hidden', 'is_published' => false]);
        Project::factory()->count(3)->create(['developer_id' => $many->id]);
        Project::factory()->create(['developer_id' => $few->id]);
        Project::factory()->create(['developer_id' => $few->id, 'is_published' => false]);

        $this->get(route('developers'))
            ->assertOk()
            ->assertSeeLivewire(Developers::class)
            ->assertSee(['Explore <em>Trusted</em> Developers', '>3</div>', 'Trusted Partners'], false)
            ->assertSeeInOrder(['Star Developer', 'Many Projects', '3 Projects', 'Few Projects', '1 Project', 'Coastal homes.'])
            ->assertSee(['href="tel:+2016166"', 'href="'.route('developers.show', 'few').'"'], false)
            ->assertDontSee('Hidden Developer');
    }

    public function test_a_developer_without_a_photo_shows_its_initials(): void
    {
        $this->assertSame('TMG', DeveloperCards::initials('Talaat Moustafa Group Holding'));

        $this->developer(['name_en' => 'Palm Hills']);

        $this->get(route('developers'))->assertSee('>PH</span>', false);
    }

    public function test_load_more_shows_six_more_and_cant_be_changed_by_the_browser(): void
    {
        Developer::factory()->count(14)->create(['is_published' => true]);

        Livewire::test(Developers::class)
            ->assertViewHas('cards', fn (array $cards) => count($cards) === 6)
            ->call('loadMore')
            ->assertViewHas('cards', fn (array $cards) => count($cards) === 12)
            ->call('loadMore')
            ->assertViewHas('cards', fn (array $cards) => count($cards) === 14)
            ->assertDontSee('Load More Developers');

        $this->expectException(CannotUpdateLockedPropertyException::class);
        Livewire::test(Developers::class)->set('shown', 5000);
    }

    public function test_the_list_keeps_the_same_queries_with_more_developers(): void
    {
        Developer::factory()->count(2)->create(['is_published' => true]);
        $this->get(route('developers'));
        DB::enableQueryLog();
        $this->get(route('developers'))->assertOk();
        $few = count(DB::getQueryLog());

        foreach (Developer::factory()->count(4)->create(['is_published' => true]) as $developer) {
            Project::factory()->count(2)->create(['developer_id' => $developer->id]);
        }
        DB::flushQueryLog();
        $this->get(route('developers'))->assertOk();

        $this->assertSame($few, count(DB::getQueryLog()));
    }

    public function test_no_developers_shows_the_empty_message(): void
    {
        $this->get(route('developers'))->assertOk()->assertSee('No developers found');
    }

    public function test_a_developers_page_shows_its_profile_story_and_projects(): void
    {
        $developer = $this->developer([
            'tagline_en' => 'Building happiness',
            'phone' => '+20 16166',
            'email' => 'info@mountainview.test',
            'neighbourhood' => 'Fifth Settlement',
            'description_en' => '<p>Founded in 2005.</p><script>alert(1)</script>',
            'social_instagram' => 'https://www.instagram.com/mountainview',
            'meta_title_en' => 'Mountain View Projects',
            'meta_description_en' => 'Homes by Mountain View.',
        ]);
        Project::factory()->create(['title_en' => 'iCity', 'developer_id' => $developer->id]);
        Project::factory()->create(['title_en' => 'Draft Project', 'developer_id' => $developer->id, 'is_published' => false]);
        Project::factory()->create(['title_en' => 'Someone Else']);

        $html = $this->get(route('developers.show', 'mountain-view'))
            ->assertOk()
            ->assertSee(['<title>Mountain View Projects', 'content="Homes by Mountain View."', 'Building happiness', 'href="tel:+2016166"', 'mailto:info@mountainview.test', 'Fifth Settlement'], false)
            ->assertSee(['Follow us on', 'href="https://www.instagram.com/mountainview"', 'About <em>Mountain View</em>', '<p>Founded in 2005.</p>'], false)
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee(['Projects by <em>Mountain View</em>', 'iCity'], false)
            ->assertDontSee(['Draft Project', 'Someone Else'])
            ->getContent();

        // Only the filled social links are shown (the footer has the company's own).
        $card = str($html)->between('Follow us on', 'Our Story');
        $this->assertTrue($card->contains('Instagram'));
        $this->assertFalse($card->contains('Facebook'));
    }

    public function test_more_than_six_projects_link_to_the_filtered_projects_list(): void
    {
        $developer = $this->developer();
        Project::factory()->count(7)->create(['developer_id' => $developer->id]);

        $this->get(route('developers.show', 'mountain-view'))
            ->assertSee(['View All Projects (7)', 'href="'.route('projects', ['developer' => 'mountain-view']).'"'], false);
    }

    public function test_without_social_links_or_projects_those_parts_are_hidden(): void
    {
        $this->developer();

        $this->get(route('developers.show', 'mountain-view'))
            ->assertOk()
            ->assertDontSee(['Follow us on', 'Projects by']);
    }

    public function test_what_to_expect_shows_the_developer_details_cards(): void
    {
        $settings = app(DeveloperDetailsSettings::class);
        $settings->what_to_expect_cards = [
            ['icon' => null, 'header_en' => 'Clear Terms', 'header_ar' => 'شروط واضحة', 'body_en' => 'Transparent payment plans.', 'body_ar' => 'خطط سداد شفافة.'],
            ['icon' => null, 'header_en' => '', 'header_ar' => '', 'body_en' => 'Empty card', 'body_ar' => ''],
        ];
        $settings->save();
        $this->developer();

        $this->get(route('developers.show', 'mountain-view'))
            ->assertOk()
            ->assertSee(['What to <em>Expect</em>'], false)
            ->assertSee(['Clear Terms', 'Transparent payment plans.'])
            ->assertDontSee('Empty card');

        $settings->what_to_expect_cards = [];
        $settings->save();

        $this->get(route('developers.show', 'mountain-view'))->assertOk()->assertDontSee('What to');
    }

    public function test_unpublished_or_unknown_developers_are_not_found(): void
    {
        $this->developer(['is_published' => false]);

        $this->get(route('developers.show', 'mountain-view'))->assertNotFound();
        $this->get(route('developers.show', 'nobody'))->assertNotFound();
    }

    public function test_a_developer_name_is_shown_as_text_not_html(): void
    {
        $this->developer(['name_en' => '<b>Bold</b> Homes']);

        $this->get(route('developers.show', 'mountain-view'))
            ->assertOk()
            ->assertDontSee('<b>Bold</b>', false)
            ->assertSee('About <em>&lt;b&gt;Bold&lt;/b&gt; Homes</em>', false);
    }

    public function test_arabic(): void
    {
        $this->developer(['name_ar' => 'ماونتن فيو']);

        $this->withCookie('locale', 'ar')->get(route('developers'))->assertOk()->assertSee(['اكتشف', 'ماونتن فيو', 'عرض المطور']);
        $this->withCookie('locale', 'ar')->get(route('developers.show', 'mountain-view'))->assertOk()->assertSee(['قصتنا', 'عن <em>ماونتن فيو</em>'], false);
    }
}
