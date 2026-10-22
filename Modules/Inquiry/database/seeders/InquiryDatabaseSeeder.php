<?php

namespace Modules\Inquiry\Database\Seeders;

use Database\Seeders\Concerns\SeedsDemoData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Inquiry\Models\Inquiry;
use Modules\Inquiry\Models\Meeting;
use Modules\WebsiteUsers\Database\Seeders\WebsiteUsersDatabaseSeeder;
use Modules\WebsiteUsers\Models\WebsiteUser;

/**
 * Demo: Contact Us messages (two per form) and meetings (one per status) with every field filled. Saved quietly: NOT
 * sent to the CRM (half show a demo CRM lead number). Local only, see SeedsDemoData. With SEED_SCALE, many more
 * messages (a fifth of it) and meetings (a tenth).
 */
class InquiryDatabaseSeeder extends Seeder
{
    use SeedsDemoData;

    private const PEOPLE = [['Sara Ali', 'sara.lead@example.com', '01001234561'], ['Ahmed Nabil', 'ahmed.lead@example.com', '01001234562'], ['Mariam Hany', 'mariam.lead@example.com', '01001234563'], ['Hassan Zaki', 'hassan.lead@example.com', '01001234564']];

    public function run(): void
    {
        if (! $this->demo()) {
            return;
        }

        $this->needs(WebsiteUsersDatabaseSeeder::class);

        $users = WebsiteUser::query()->where('email', 'not like', 'bulk-%')->pluck('id')->all();
        $this->inquiries($users);
        $this->meetings($users);
        $this->bulkLeads(WebsiteUser::query()->orderBy('id')->limit(5000)->pluck('id')->all() ?: [null]);
        $this->stamps('inquiries', 'meetings');
    }

    /**
     * @param  list<int>  $users
     */
    private function inquiries(array $users): void
    {
        foreach (Inquiry::FORMS as $i => $form) {
            foreach ([0, 1] as $n) {
                [$name, $email, $phone] = self::PEOPLE[($i + $n) % 4];
                $email = str_replace('@', "+{$form}{$n}@", $email);
                if (Inquiry::query()->where('email', $email)->exists()) {
                    continue;
                }
                Inquiry::query()->createQuietly([
                    'website_user_id' => $n && $users ? $users[$i % count($users)] : null,
                    'visitor_id' => (string) Str::uuid(),
                    'full_name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'message' => "Hello, I'd like more details and the payment plan, please.",
                    'form' => $form,
                    'source' => 'Website',
                    'url' => url('/'),
                    'crm_lead_id' => $n ? (string) (90000 + $i) : null,
                    'crm_synced_at' => $n ? now()->subDay() : null,
                ]);
            }
        }
    }

    /**
     * @param  list<int>  $users
     */
    private function meetings(array $users): void
    {
        foreach (Meeting::STATUSES as $i => $status) {
            [$name, $email, $phone] = self::PEOPLE[$i % 4];
            $email = str_replace('@', "+meeting{$i}@", $email);
            if (Meeting::query()->where('email', $email)->exists()) {
                continue;
            }
            Meeting::query()->createQuietly([
                'website_user_id' => $users ? $users[$i % count($users)] : null,
                'visitor_id' => (string) Str::uuid(),
                'name' => $name,
                'phone' => $phone,
                'email' => $email,
                'meeting_at' => now()->addDays($i - 2)->setTime(11 + $i, 0),
                'type' => Meeting::TYPES[$i % count(Meeting::TYPES)],
                'status' => $status,
                'source' => 'Property page',
                'url' => url('/properties'),
                'notes_en' => 'Prefers a morning visit.',
                'notes_ar' => 'يفضل الزيارة صباحاً.',
                'admin_notes_en' => 'Call one day before to confirm.',
                'admin_notes_ar' => 'الاتصال قبلها بيوم للتأكيد.',
                'crm_lead_id' => $i % 2 ? (string) (91000 + $i) : null,
                'crm_synced_at' => $i % 2 ? now()->subDay() : null,
            ]);
        }
    }

    /**
     * SEED_SCALE: many more messages and meetings, spread over the forms, statuses and the last year.
     *
     * @param  list<int|null>  $users
     */
    private function bulkLeads(array $users): void
    {
        $now = now();
        $this->bulk('inquiries', 'email', $this->volume(0.2), fn (int $n) => [
            'website_user_id' => $n % 3 ? null : $users[$n % count($users)],
            'visitor_id' => $this->bulkUuid($n),
            'full_name' => "Lead {$n}",
            'email' => "bulk-lead-{$n}@example.test",
            'phone' => '0105'.str_pad((string) $n, 7, '0', STR_PAD_LEFT),
            'message' => "Hello, I'd like more details and the payment plan, please.",
            'form' => Inquiry::FORMS[$n % count(Inquiry::FORMS)],
            'source' => 'Website',
            'url' => url('/'),
            'crm_lead_id' => $n % 2 ? (string) (100000 + $n) : null,
            'crm_synced_at' => $n % 2 ? $now : null,
            'created_at' => $now->copy()->subMinutes($n * 5),
            'updated_at' => $now,
        ]);

        $this->bulk('meetings', 'email', $this->volume(0.1), fn (int $n) => [
            'website_user_id' => $n % 3 ? null : $users[$n % count($users)],
            'visitor_id' => $this->bulkUuid($n),
            'name' => "Lead {$n}",
            'phone' => '0104'.str_pad((string) $n, 7, '0', STR_PAD_LEFT),
            'email' => "bulk-meeting-{$n}@example.test",
            'meeting_at' => $now->copy()->addHours(($n % 720) - 360),
            'type' => Meeting::TYPES[$n % count(Meeting::TYPES)],
            'status' => Meeting::STATUSES[$n % count(Meeting::STATUSES)],
            'source' => 'Property page',
            'url' => url('/properties'),
            'notes_en' => 'Prefers a morning visit.',
            'notes_ar' => 'يفضل الزيارة صباحاً.',
            'admin_notes_en' => 'Call one day before to confirm.',
            'admin_notes_ar' => 'الاتصال قبلها بيوم للتأكيد.',
            'crm_lead_id' => $n % 2 ? (string) (200000 + $n) : null,
            'crm_synced_at' => $n % 2 ? $now : null,
            'created_at' => $now->copy()->subMinutes($n * 7),
            'updated_at' => $now,
        ]);
    }
}
