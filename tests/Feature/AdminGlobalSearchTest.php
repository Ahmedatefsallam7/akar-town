<?php

namespace Tests\Feature;

use App\Filament\Support\AdminGlobalSearchProvider;
use Filament\Livewire\GlobalSearch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\HtmlString;
use Livewire\Livewire;
use Modules\Lookups\Models\Lookup;
use Tests\TestCase;

/**
 * The admin's global search window (resources/views/vendor/filament-panels/livewire/global-search.blade.php).
 */
class AdminGlobalSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_searched_words_are_marked_and_the_title_is_escaped(): void
    {
        $this->assertSame('Beverly <mark>Hi</mark>lls', AdminGlobalSearchProvider::highlight('Beverly Hills', 'hi')->toHtml());
        $this->assertSame('<mark>Sea</mark> <mark>View</mark>', AdminGlobalSearchProvider::highlight('Sea View', 'view sea')->toHtml());
        $this->assertSame('&lt;b&gt;<mark>Tom</mark> &amp; Co&lt;/b&gt;', AdminGlobalSearchProvider::highlight('<b>Tom & Co</b>', 'tom')->toHtml());
        $this->assertSame('Tom &amp; Co', AdminGlobalSearchProvider::highlight('Tom & Co', 'amp')->toHtml()); // never inside an escaped sign
        $this->assertSame('حي <mark>الشيخ</mark> زايد', AdminGlobalSearchProvider::highlight('حي الشيخ زايد', 'الشيخ')->toHtml());
        $this->assertSame('Plain', AdminGlobalSearchProvider::highlight('Plain', '  ')->toHtml());

        $html = new HtmlString('<b>kept</b>');
        $this->assertSame($html, AdminGlobalSearchProvider::highlight($html, 'kept'));
    }

    public function test_the_window_shows_the_count_the_groups_and_the_keys(): void
    {
        $this->actingAs($this->createAdmin());
        Lookup::create(['slug' => 'views', 'name_en' => 'Views', 'name_ar' => 'الإطلالات', 'is_deletable' => false])
            ->children()->create(['slug' => 'sea_view', 'name_en' => 'Sea View', 'name_ar' => 'إطلالة بحرية']);

        Livewire::test(GlobalSearch::class)
            ->set('search', 'View')
            ->assertSee(__('admin.global_search.results_for'))
            ->assertSee(trans_choice('admin.global_search.count', 2, ['count' => 2]))
            ->assertSee(__('admin.lookups.plural').' › Views')
            ->assertSeeHtml('Sea <mark>View</mark>')
            ->assertSee([__('admin.global_search.navigate'), __('admin.global_search.open'), __('admin.global_search.close')])
            ->assertSeeHtml('placeholder="'.__('admin.global_search.placeholder').'"');

        Livewire::test(GlobalSearch::class)
            ->set('search', 'zzzq')
            ->assertSee(__('admin.global_search.no_results', ['search' => 'zzzq']))
            ->assertDontSee(__('admin.global_search.results_for'));
    }
}
