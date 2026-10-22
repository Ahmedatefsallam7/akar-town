<?php

namespace Modules\PossibleLeads\Database\Seeders;

use Database\Seeders\Concerns\SeedsDemoData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Inquiry\Database\Seeders\InquiryDatabaseSeeder;
use Modules\Inquiry\Models\Inquiry;
use Modules\PossibleLeads\Models\PossibleLead;
use Modules\Projects\Models\Project;
use Modules\Units\Database\Seeders\UnitsDatabaseSeeder;
use Modules\Units\Models\Unit;

/**
 * Demo: ten call / WhatsApp / form clicks on properties and projects over the last days, the first three turned into
 * leads. Saved quietly. Local only, see SeedsDemoData. With SEED_SCALE, that many more clicks (a fifth of them turned
 * into the bulk Contact Us messages of the same visitor).
 */
class PossibleLeadsDatabaseSeeder extends Seeder
{
    use SeedsDemoData;

    public function run(): void
    {
        if (! $this->demo()) {
            return;
        }

        $this->needs(UnitsDatabaseSeeder::class, InquiryDatabaseSeeder::class);

        if (! PossibleLead::query()->where('page_url', 'not like', '%/bulk-%')->exists()) {
            $this->demoClicks();
        }

        $this->bulkClicks();
    }

    private function demoClicks(): void
    {
        $units = Unit::query()->where('slug', 'not like', 'bulk-%')->limit(4)->get(['id', 'slug']);
        $projects = Project::query()->where('slug', 'not like', 'bulk-%')->limit(4)->get(['id', 'slug']);
        $inquiries = Inquiry::query()->where('email', 'not like', 'bulk-%')->limit(3)->get();
        foreach (range(0, 9) as $i) {
            $unit = $i % 2 ? $units[$i % max(1, $units->count())] ?? null : null;
            $project = $i % 2 ? null : $projects[$i % max(1, $projects->count())] ?? null;
            $converted = $i < 3 ? ($inquiries[$i] ?? null) : null;
            $lead = new PossibleLead([
                'click_type' => PossibleLead::TYPES[$i % 3],
                'page_url' => $unit ? route('properties.show', $unit->slug) : ($project ? route('projects.show', $project->slug) : url('/')),
                'unit_id' => $unit?->id,
                'project_id' => $project?->id,
                'visitor_id' => $converted?->visitor_id ?? (string) Str::uuid(),
                'website_user_id' => $converted?->website_user_id,
            ]);
            $lead->forceFill([
                'converted_lead_type' => $converted?->getMorphClass(),
                'converted_lead_id' => $converted?->id,
                'converted_at' => $converted ? now()->subHours($i) : null,
                'created_at' => now()->subDays($i)->subHours(2),
            ])->saveQuietly();
        }
    }

    /**
     * SEED_SCALE: clicks on the bulk properties' pages over the last year.
     */
    private function bulkClicks(): void
    {
        $target = $this->volume(1);
        if (! $target) {
            return;
        }

        $units = DB::table('units')->where('slug', 'like', 'bulk-%')->orderBy('id')->limit(20_000)->get(['id', 'slug'])->all();
        $leads = DB::table('inquiries')->where('email', 'like', 'bulk-%')->pluck('id', 'visitor_id')->all();
        $now = now();
        $this->bulk('possible_leads', 'page_url', $target, function (int $n) use ($units, $leads, $now) {
            $unit = $units ? $units[$n % count($units)] : null;
            $visitor = $this->bulkUuid($n);
            $lead = $n % 5 === 0 ? ($leads[$visitor] ?? null) : null;

            return [
                'click_type' => PossibleLead::TYPES[$n % 3],
                'page_url' => url('/properties/'.($unit->slug ?? "bulk-unit-{$n}")),
                'unit_id' => $unit?->id,
                'project_id' => null,
                'visitor_id' => $visitor,
                'website_user_id' => null,
                'converted_lead_type' => $lead ? (new Inquiry)->getMorphClass() : null,
                'converted_lead_id' => $lead,
                'converted_at' => $lead ? $now : null,
                'created_at' => $now->copy()->subMinutes($n * 3),
            ];
        }, '%/properties/bulk-%');
    }
}
