<?php

namespace Modules\GeneralSettings\Database\Seeders;

use Database\Seeders\Concerns\SeedsDemoData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Modules\GeneralSettings\Filament\Support\GeneralSettingsPage;
use Modules\GeneralSettings\Settings\AboutUsSettings;
use Modules\GeneralSettings\Settings\BusinessInfoSettings;
use Modules\GeneralSettings\Settings\DeveloperDetailsSettings;
use Modules\GeneralSettings\Settings\HomeSettings;
use Modules\GeneralSettings\Settings\SubmitPropertySettings;

/**
 * Demo: the settings' empty fields (contacts, social links, branches, numbers, the cards' icons). The texts come with
 * the settings migrations. Local only, see SeedsDemoData. The site's Online / Offline switch is left as it is.
 */
class GeneralSettingsDatabaseSeeder extends Seeder
{
    use SeedsDemoData;

    public function run(): void
    {
        if (! $this->demo()) {
            return;
        }

        $business = app(BusinessInfoSettings::class);
        foreach ([
            'social_facebook' => 'https://www.facebook.com/AkarTownEG',
            'social_instagram' => 'https://www.instagram.com/akar.town.real.estate/',
            'social_linkedin' => 'https://www.linkedin.com/company/akar-town/',
            'social_youtube' => 'https://youtube.com/@mohamedelhemaly.akrealestate',
            'social_tiktok' => 'https://www.tiktok.com/@akartown',
            'headquarters_en' => '90th Street, Fifth Settlement, New Cairo, Egypt',
            'headquarters_ar' => 'شارع التسعين، التجمع الخامس، القاهرة الجديدة، مصر',
            'branch_1_en' => 'Mall of Arabia, 6th of October', 'branch_1_ar' => 'مول العرب، السادس من أكتوبر',
            'branch_2_en' => 'Arkan Plaza, Sheikh Zayed', 'branch_2_ar' => 'أركان بلازا، الشيخ زايد',
            'branch_3_en' => 'Marassi, North Coast', 'branch_3_ar' => 'مراسي، الساحل الشمالي',
            'branch_4_en' => 'Porto Sokhna, Ain Sokhna', 'branch_4_ar' => 'بورتو السخنة، العين السخنة',
            'hotline' => '19000',
            'phone_primary' => '01001234567', 'phone_secondary' => '01112345678', 'phone_tertiary' => '01223456789',
            'whatsapp' => '201001234567',
            'email' => 'info@akartown.com',
            'tax_number' => '123-456-789',
        ] as $key => $value) {
            if (blank($business->{$key})) {
                $business->{$key} = $value;
            }
        }
        $business->save();

        // Numbers left at 0 (e.g. the footer's "Years").
        $about = app(AboutUsSettings::class);
        foreach (['years_of_excellence' => 20, 'stats_happy_clients' => 5000, 'stats_properties_sold' => 1200, 'stats_awards_won' => 15, 'stats_commitment' => 100] as $key => $value) {
            $about->{$key} = $about->{$key} ?: $value;
        }
        $about->core_values = $this->withIcons($about->core_values);
        $about->save();

        $home = app(HomeSettings::class);
        $home->why_choose_cta_url = $home->why_choose_cta_url ?: '/projects';
        $home->why_choose_cards = $this->withIcons($home->why_choose_cards);
        $home->save();

        $developer = app(DeveloperDetailsSettings::class);
        $developer->what_to_expect_cards = $this->withIcons($developer->what_to_expect_cards);
        $developer->save();

        $submit = app(SubmitPropertySettings::class);
        $submit->points = $this->withIcons($submit->points);
        $submit->save();
    }

    /**
     * Cards without an icon get a demo one.
     *
     * @param  list<array<string, mixed>>  $cards
     * @return list<array<string, mixed>>
     */
    private function withIcons(array $cards): array
    {
        return array_map(fn (array $card, int $i) => [...$card, 'icon' => $card['icon'] ?? $this->icon($i)], $cards, array_keys($cards));
    }

    /**
     * A simple icon on the public disk (a brand-red ring and dot on a light red disc), as the admin saves
     * an uploaded one; a different shade for each card.
     */
    private function icon(int $i): string
    {
        $path = GeneralSettingsPage::ICONS_DIRECTORY."/demo-icon-{$i}.png";
        if (! Storage::disk('public')->exists($path)) {
            $image = imagecreatetruecolor(240, 200);
            imagesavealpha($image, true);
            imagefill($image, 0, 0, imagecolorallocatealpha($image, 0, 0, 0, 127));
            imagefilledellipse($image, 120, 100, 180, 180, imagecolorallocatealpha($image, 200, 16, 46, 100 - $i * 10));
            imagesetthickness($image, 10);
            imageellipse($image, 120, 100, 120, 120, imagecolorallocate($image, 200, 16, 46));
            imagefilledellipse($image, 120, 100, 40, 40, imagecolorallocate($image, 200, 16, 46));
            ob_start();
            imagepng($image);
            Storage::disk('public')->put($path, ob_get_clean());
        }

        return $path;
    }
}
