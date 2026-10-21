<?php

namespace Tests\Feature;

use App\Jobs\SendLeadToCrm;
use App\Livewire\Website\Projects;
use App\Livewire\Website\Properties;
use App\Livewire\Website\Wishlist;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Modules\Developers\Models\Developer;
use Modules\GeneralSettings\Settings\SiteSettings;
use Modules\Inquiry\Models\Inquiry;
use Modules\PossibleLeads\Models\PossibleLead;
use Modules\PossibleLeads\Support\Visitor;
use Modules\Projects\Models\Project;
use Modules\Units\Models\Unit;
use Modules\Users\Filament\Auth\Login;
use Modules\Users\Filament\Resources\UserResource\Pages\EditUser;
use Tests\TestCase;

/**
 * The fixes of the security review (2026-10-19).
 */
class SecurityTest extends TestCase
{
    use RefreshDatabase;

    private function live(bool $live = true): void
    {
        $site = app(SiteSettings::class);
        $site->is_live = $live;
        $site->save();
    }

    public function test_an_upload_is_saved_with_the_extension_of_what_it_really_is(): void
    {
        // A real picture renamed to a web page (Livewire keeps uploads in its temporary folder first).
        Storage::fake(FileUploadConfiguration::disk());
        $picture = UploadedFile::fake()->image('x.png', 10, 10);
        FileUploadConfiguration::storage()->put(FileUploadConfiguration::path('evil.html'), file_get_contents($picture->getPathname()));
        $file = TemporaryUploadedFile::createFromLivewire('evil.html');

        // Saved as a picture, so it can never open as a web page on this site (any upload field, media ones too).
        $this->assertStringEndsWith('.png', FileUpload::make('avatar')->getUploadedFileNameForStorage($file));
        $this->assertStringEndsWith('.png', SpatieMediaLibraryFileUpload::make('photo')->getUploadedFileNameForStorage($file));
    }

    public function test_pages_send_the_browser_protection_headers(): void
    {
        $this->live();

        foreach ([route('home'), '/admin/login'] as $url) {
            $this->get($url)
                ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
                ->assertHeader('X-Content-Type-Options', 'nosniff')
                ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        }
    }

    public function test_crafted_filter_urls_are_ignored_not_errors(): void
    {
        $this->live();

        foreach (['beds[0][]=1', 'price[0][]=1', 'status[0][]=x', 'area[0][]=x', 'type[0][]=x'] as $query) {
            $this->get('/properties?'.$query)->assertOk();
        }
        foreach (['type[0][]=x', 'status[0][]=x'] as $query) {
            $this->get('/projects?'.$query)->assertOk();
        }
    }

    public function test_paging_and_the_wishlist_ids_cant_be_set_from_the_browser(): void
    {
        foreach ([[Properties::class, 'perPage', 1000000], [Projects::class, 'shown', 1000000], [Wishlist::class, 'unitIds', range(1, 1000)]] as [$component, $property, $value]) {
            try {
                Livewire::test($component)->set($property, $value);
                $this->fail("{$component}::\${$property} could be set from the browser.");
            } catch (CannotUpdateLockedPropertyException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_click_tracking_takes_only_this_sites_pages_and_counts_a_repeat_once(): void
    {
        $this->live();
        $visitor = Visitor::newId();
        $click = fn (array $data) => $this->withCredentials()->withUnencryptedCookie(Visitor::COOKIE, $visitor)->postJson(route('api.possible-leads.track'), $data + ['click_type' => 'phone']);

        $click(['page_url' => 'https://evil.example/login'])->assertUnprocessable();
        $click(['page_url' => url('/properties')])->assertCreated()->assertCookie(Visitor::COOKIE);
        $click(['page_url' => url('/properties')])->assertCreated();

        $this->assertSame(1, PossibleLead::count());

        // The visitor cookie can't be read by scripts on the page.
        $cookie = $click(['click_type' => 'whatsapp'])->getCookie(Visitor::COOKIE, false);
        $this->assertTrue($cookie->isHttpOnly());
    }

    public function test_offline_site_closes_click_tracking_and_public_livewire_calls(): void
    {
        $this->live(false);

        $this->postJson(route('api.possible-leads.track'), ['click_type' => 'phone'])->assertStatus(503);
        // A public form can't pass as an admin page by faking the Referer header.
        $this->withHeader('Referer', url('/admin/units'))->post(route('default-livewire.update'))->assertStatus(503);
        $this->withHeader('Referer', url('/admin/login'))->post(route('default-livewire.update'), $this->livewireCall('website.property-matching'))->assertStatus(503);
    }

    public function test_offline_site_still_lets_admins_sign_in(): void
    {
        $this->live(false);

        // Only the panel's sign-in pages pass before signing in (Livewire then checks the snapshot's signature).
        $response = $this->withHeader('Referer', url('/admin/login'))->post(route('default-livewire.update'), $this->livewireCall(Login::class));
        $this->assertNotSame(503, $response->getStatusCode());
    }

    private function livewireCall(string $component): array
    {
        return ['components' => [['snapshot' => json_encode(['data' => [], 'memo' => ['name' => $component], 'checksum' => 'x']), 'updates' => [], 'calls' => []]]];
    }

    public function test_hidden_projects_and_developers_are_not_named_on_published_pages(): void
    {
        $this->live();
        $developer = Developer::factory()->create(['name_en' => 'Secret Developer', 'is_published' => false]);
        $hiddenProject = Project::factory()->create(['title_en' => 'Secret Compound', 'is_published' => false, 'developer_id' => $developer->id]);
        $project = Project::factory()->create(['developer_id' => $developer->id]);
        $unit = Unit::factory()->create(['project_id' => $hiddenProject->id]);

        $this->get(route('properties.show', $unit->slug))->assertOk()->assertDontSee(['Secret Compound', 'Secret Developer']);
        $this->get(route('projects.show', $project->slug))->assertOk()->assertDontSee('Secret Developer');
        $this->get(route('projects'))->assertOk()->assertDontSee('Secret Developer');
        // Filters don't take hidden ones either.
        $this->get(route('properties', ['project' => $hiddenProject->slug]))->assertOk();
    }

    public function test_your_own_email_changes_only_from_the_profile_page(): void
    {
        $this->actingAs($me = $this->createAdmin());

        Livewire::test(EditUser::class, ['record' => $me->getRouteKey()])
            ->assertFormFieldIsDisabled('email')
            ->fillForm(['email' => 'new@example.com'])
            ->call('save');

        $this->assertNotSame('new@example.com', $me->fresh()->email);
    }

    public function test_a_refused_crm_send_keeps_only_the_status_in_its_error(): void
    {
        config(['crm.token' => 'secret', 'crm.base_url' => 'https://akartown.8xcrm.com', 'crm.forms.contact_us' => '1']);
        Http::fake(['*' => Http::response(['message' => 'Lead Sara Ali 01001234567 rejected'], 422)]);
        $inquiry = Inquiry::factory()->createQuietly(['full_name' => 'Sara Ali', 'phone' => '01001234567']);

        try {
            (new SendLeadToCrm($inquiry))->handle();
            $this->fail('The send should fail.');
        } catch (RequestException $exception) {
            $this->assertStringContainsString('422', $exception->getMessage());
            $this->assertStringNotContainsString('Sara', $exception->getMessage());
        }
    }
}
