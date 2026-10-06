<?php

namespace Modules\GeneralSettings\Tests\Feature;

use Filament\Forms\Components\Repeater;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Modules\GeneralSettings\Filament\Clusters\GeneralSettings\Pages\AboutUsSettingsPage;
use Modules\GeneralSettings\Filament\Clusters\GeneralSettings\Pages\BusinessInfoSettingsPage;
use Modules\GeneralSettings\Filament\Clusters\GeneralSettings\Pages\CtaRibbonSettingsPage;
use Modules\GeneralSettings\Filament\Clusters\GeneralSettings\Pages\DeveloperDetailsSettingsPage;
use Modules\GeneralSettings\Filament\Clusters\GeneralSettings\Pages\HomeSettingsPage;
use Modules\GeneralSettings\Filament\Clusters\GeneralSettings\Pages\PrivacyPolicySettingsPage;
use Modules\GeneralSettings\Filament\Clusters\GeneralSettings\Pages\SubmitPropertySettingsPage;
use Modules\GeneralSettings\Filament\Clusters\GeneralSettings\Pages\TermsOfUseSettingsPage;
use Modules\GeneralSettings\Filament\Clusters\GeneralSettingsCluster;
use Modules\GeneralSettings\Settings\AboutUsSettings;
use Modules\GeneralSettings\Settings\BusinessInfoSettings;
use Modules\GeneralSettings\Settings\CtaRibbonSettings;
use Modules\GeneralSettings\Settings\HomeSettings;
use Modules\GeneralSettings\Settings\TermsOfUseSettings;
use Modules\Users\Support\PermissionModules;
use Tests\TestCase;

class GeneralSettingsTest extends TestCase
{
    use RefreshDatabase;

    private const PAGES = [
        HomeSettingsPage::class,
        CtaRibbonSettingsPage::class,
        BusinessInfoSettingsPage::class,
        AboutUsSettingsPage::class,
        SubmitPropertySettingsPage::class,
        DeveloperDetailsSettingsPage::class,
        PrivacyPolicySettingsPage::class,
        TermsOfUseSettingsPage::class,
    ];

    public function test_users_without_the_permission_are_forbidden(): void
    {
        $this->actingAs($this->createUserWithPermissions(['projects' => true]));

        foreach (self::PAGES as $page) {
            $this->get($page::getUrl())->assertForbidden();
        }
    }

    public function test_admin_opens_every_page_in_both_languages_and_the_menu_opens_the_first(): void
    {
        $this->actingAs($this->createAdmin());

        $this->get(GeneralSettingsCluster::getUrl())->assertRedirect(HomeSettingsPage::getUrl());

        foreach (['en', 'ar'] as $locale) {
            app()->setLocale($locale);

            foreach (self::PAGES as $page) {
                $this->withCookie('locale', $locale)->get($page::getUrl())
                    ->assertOk()
                    ->assertSee($page::getNavigationLabel())
                    ->assertDontSee('admin.settings.');
            }
        }
    }

    public function test_one_permission_with_view_and_edit_only(): void
    {
        $module = PermissionModules::all()['general_settings'];

        $this->assertSame('system_configuration', $module['group']);
        $this->assertSame(['view', 'edit'], $module['actions']);
    }

    public function test_view_only_users_see_a_read_only_form_without_save(): void
    {
        $this->actingAs($this->createUserWithPermissions(['general_settings' => ['view']]));

        Livewire::test(CtaRibbonSettingsPage::class)
            ->assertSchemaStateSet(['header_en' => app(CtaRibbonSettings::class)->header_en])
            ->assertDontSee(__('filament-spatie-laravel-settings-plugin::pages/settings-page.form.actions.save.label'))
            ->fillForm(['header_en' => 'Changed'])
            ->call('save');

        $this->assertNotSame('Changed', app(CtaRibbonSettings::class)->header_en);
    }

    public function test_saving_updates_the_settings(): void
    {
        $this->actingAs($this->createAdmin());

        Livewire::test(CtaRibbonSettingsPage::class)
            ->assertSee(__('filament-spatie-laravel-settings-plugin::pages/settings-page.form.actions.save.label'))
            ->fillForm(['header_en' => 'Find your home', 'header_ar' => 'اعثر على منزلك', 'body_en' => 'Talk to us.', 'body_ar' => 'تحدث معنا.'])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $settings = app(CtaRibbonSettings::class)->refresh();
        $this->assertSame(['Find your home', 'اعثر على منزلك', 'Talk to us.', 'تحدث معنا.'], [$settings->header_en, $settings->header_ar, $settings->body_en, $settings->body_ar]);
    }

    public function test_texts_are_required_in_both_languages(): void
    {
        $this->actingAs($this->createAdmin());

        Livewire::test(HomeSettingsPage::class)
            ->fillForm(['why_choose_header_en' => '', 'why_choose_header_ar' => '', 'why_choose_cta_url' => null])
            ->call('save')
            ->assertHasFormErrors(['why_choose_header_en' => 'required', 'why_choose_header_ar' => 'required'])
            ->assertHasNoFormErrors(['why_choose_cta_url']);
    }

    public function test_links_must_be_http_or_https(): void
    {
        $this->actingAs($this->createAdmin());

        Livewire::test(HomeSettingsPage::class)
            ->fillForm(['why_choose_cta_url' => 'javascript:alert(1)'])
            ->call('save')
            ->assertHasFormErrors(['why_choose_cta_url']);

        Livewire::test(BusinessInfoSettingsPage::class)
            ->fillForm(['headquarters_en' => 'Cairo', 'headquarters_ar' => 'القاهرة', 'social_facebook' => 'ftp://facebook.com/x', 'email' => 'not-an-email'])
            ->call('save')
            ->assertHasFormErrors(['social_facebook', 'email']);
    }

    public function test_business_info_saves_contacts_and_working_hours(): void
    {
        $this->actingAs($this->createAdmin());

        Livewire::test(BusinessInfoSettingsPage::class)
            ->fillForm([
                'headquarters_en' => 'Fifth Settlement, New Cairo',
                'headquarters_ar' => 'التجمع الخامس، القاهرة الجديدة',
                'branch_2_en' => 'Sheikh Zayed',
                'hotline' => '19000',
                'email' => 'info@akartown.com',
                'social_instagram' => 'https://instagram.com/akartown',
                'working_days_from' => 'sunday',
                'working_days_to' => 'thursday',
                'working_hours_from' => '10:00',
                'working_hours_to' => '19:30',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $settings = app(BusinessInfoSettings::class)->refresh();
        $this->assertSame(['Sheikh Zayed', '19000', 'info@akartown.com', 'sunday', '10:00', '19:30'], [
            $settings->branch_2_en, $settings->hotline, $settings->email, $settings->working_days_from, $settings->working_hours_from, $settings->working_hours_to,
        ]);
    }

    public function test_about_us_stats_are_numbers_and_commitment_is_a_percentage(): void
    {
        $this->actingAs($this->createAdmin());

        Livewire::test(AboutUsSettingsPage::class)
            ->fillForm(['stats_commitment' => 101, 'stats_happy_clients' => -1])
            ->call('save')
            ->assertHasFormErrors(['stats_commitment', 'stats_happy_clients']);

        Livewire::test(AboutUsSettingsPage::class)
            ->fillForm(['years_of_excellence' => 12, 'stats_happy_clients' => 4500, 'stats_commitment' => 100])
            ->call('save')
            ->assertHasNoFormErrors();

        $settings = app(AboutUsSettings::class)->refresh();
        $this->assertSame([12, 4500, 100], [$settings->years_of_excellence, $settings->stats_happy_clients, $settings->stats_commitment]);
    }

    public function test_cards_can_be_added_removed_and_reordered(): void
    {
        $this->actingAs($this->createAdmin());
        $undoRepeaterFake = Repeater::fake();

        $entry = fn (string $header) => ['header_en' => $header, 'header_ar' => "بند {$header}", 'body_en' => "{$header} body", 'body_ar' => 'نص'];

        Livewire::test(TermsOfUseSettingsPage::class)
            ->fillForm(['entries' => [$entry('Second'), $entry('First'), $entry('Third')]])
            ->call('save')
            ->assertHasNoFormErrors();

        $undoRepeaterFake();

        $this->assertSame(['Second', 'First', 'Third'], array_column(app(TermsOfUseSettings::class)->refresh()->entries, 'header_en'));
    }

    public function test_card_texts_are_required(): void
    {
        $this->actingAs($this->createAdmin());
        $undoRepeaterFake = Repeater::fake();

        Livewire::test(PrivacyPolicySettingsPage::class)
            ->fillForm(['entries' => [['header_en' => '', 'header_ar' => 'x', 'body_en' => 'x', 'body_ar' => 'x']]])
            ->call('save')
            ->assertHasFormErrors(['entries.0.header_en' => 'required']);

        $undoRepeaterFake();
    }

    public function test_a_card_icon_is_an_uploaded_image_and_a_replaced_one_is_deleted(): void
    {
        Storage::fake('public');
        $this->actingAs($this->createAdmin());
        $undoRepeaterFake = Repeater::fake();
        $card = fn ($icon) => ['icon' => $icon, 'header_en' => 'Expert Guidance', 'header_ar' => 'إرشاد الخبراء', 'body_en' => 'We guide you.', 'body_ar' => 'نرشدك.'];

        Livewire::test(HomeSettingsPage::class)
            ->fillForm(['why_choose_cards' => [$card([UploadedFile::fake()->image('first.png', 64, 64)])]])
            ->call('save')
            ->assertHasNoFormErrors();
        $first = app(HomeSettings::class)->refresh()->why_choose_cards[0]['icon'];
        Storage::disk('public')->assertExists($first);

        Livewire::test(HomeSettingsPage::class)
            ->fillForm(['why_choose_cards' => [$card([UploadedFile::fake()->image('second.webp', 64, 64)])]])
            ->call('save')
            ->assertHasNoFormErrors();
        $second = app(HomeSettings::class)->refresh()->why_choose_cards[0]['icon'];

        Storage::disk('public')->assertExists($second);
        Storage::disk('public')->assertMissing($first);

        Livewire::test(HomeSettingsPage::class)
            ->fillForm(['why_choose_cards' => [$card([UploadedFile::fake()->create('icon.svg', 5, 'image/svg+xml')])]])
            ->call('save')
            ->assertHasFormErrors(['why_choose_cards.0.icon']);

        $undoRepeaterFake();
    }

    public function test_default_content_has_no_dastan_branding_or_contacts(): void
    {
        $payloads = DB::table('settings')->pluck('payload')->implode(' ');

        $this->assertDoesNotMatchRegularExpression('/dastan|داستان/iu', $payloads);
        $this->assertSame('Why Choose Akar Town', app(HomeSettings::class)->why_choose_subtitle_en);
        $this->assertNull(app(BusinessInfoSettings::class)->email);
        $this->assertNull(app(BusinessInfoSettings::class)->phone_primary);
    }
}
