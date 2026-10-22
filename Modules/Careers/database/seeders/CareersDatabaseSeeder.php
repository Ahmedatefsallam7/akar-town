<?php

namespace Modules\Careers\Database\Seeders;

use Database\Seeders\Concerns\SeedsDemoData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Careers\Models\Applicant;
use Modules\Careers\Models\Career;
use Modules\Lookups\Database\Seeders\LookupsDatabaseSeeder;

/**
 * Demo: four job openings with every field filled (department, place, texts, lists, salary, benefits, SEO) and two
 * applicants each (with a résumé). Local only, see SeedsDemoData. With SEED_SCALE, many more applicants (a tenth of it,
 * no résumé files).
 */
class CareersDatabaseSeeder extends Seeder
{
    use SeedsDemoData;

    private const JOBS = [
        ['Senior Sales Consultant', 'مستشار مبيعات أول', 'full_time', 3],
        ['Marketing Specialist', 'أخصائي تسويق', 'hybrid', 1],
        ['Customer Support Agent', 'موظف خدمة عملاء', 'part_time', 2],
        ['Frontend Developer', 'مطور واجهات أمامية', 'online', 1],
    ];

    private const PEOPLE = ['Sara Ali', 'Youssef Adel', 'Nour Mahmoud', 'Karim Samir'];

    public function run(): void
    {
        if (! $this->demo()) {
            return;
        }

        $this->needs(LookupsDatabaseSeeder::class);

        $departments = array_values($this->children('department'));
        $place = $this->location('New Cairo');
        foreach (self::JOBS as $i => [$en, $ar, $type, $vacancies]) {
            $career = Career::query()->firstOrCreate(['title_en' => $en], [
                'title_ar' => $ar,
                'type' => $type,
                'vacancies' => $vacancies,
                'department_id' => $departments ? $departments[$i % count($departments)] : null,
                'closing_date' => now()->addMonths(2)->toDateString(),
                'is_active' => true,
                ...$place,
                'description_en' => "<p>We are looking for a {$en} to join our growing team in New Cairo.</p><p>You will work with a friendly team and a strong portfolio of projects.</p>",
                'description_ar' => "<p>نبحث عن {$ar} للانضمام إلى فريقنا في القاهرة الجديدة.</p><p>ستعمل مع فريق متعاون ومحفظة قوية من المشروعات.</p>",
                'responsibilities' => [['en' => 'Meet clients and understand their needs', 'ar' => 'مقابلة العملاء وفهم احتياجاتهم'], ['en' => 'Follow up leads until closing', 'ar' => 'متابعة العملاء المحتملين حتى إتمام البيع']],
                'requirements' => [['en' => '2+ years of experience', 'ar' => 'خبرة سنتين على الأقل'], ['en' => 'Good English and Arabic', 'ar' => 'إجادة الإنجليزية والعربية']],
                'show_salary' => true,
                'salary_from' => 15000 + $i * 5000,
                'salary_to' => 25000 + $i * 5000,
                'salary_currency' => 'EGP',
                ...$this->meta($en, $ar),
            ]);
            // Openings made before (or in the admin) get their empty fields too.
            $this->fillEmpty($career, [
                'department_id' => $departments ? $departments[$i % count($departments)] : null,
                ...($career->country_id ? [] : ($place ?: $this->place('', $i))),
            ]);
            $this->fullChain($career);
            $this->lookupsIfEmpty($career, 'benefits', 3, $i);

            foreach (array_slice(Applicant::STATUSES, $i % 2, 2) as $n => $status) {
                $email = Str::slug($en)."-{$n}@example.com";
                if (Applicant::query()->where('email', $email)->exists()) {
                    continue;
                }
                // Quietly: no "application received" e-mail.
                $applicant = Applicant::query()->createQuietly([
                    'career_id' => $career->id,
                    'name' => self::PEOPLE[($i + $n) % 4],
                    'email' => $email,
                    'phone' => '0101'.str_pad((string) ($i * 10 + $n), 7, '0', STR_PAD_LEFT),
                    'status' => $status,
                    'gender' => Applicant::GENDERS[$n % 2],
                    'linkedin_profile' => 'https://www.linkedin.com/in/demo-applicant-'.($i * 10 + $n),
                    'cover_letter' => "I'd love to join Akar Town as a {$en}. I have relevant experience and I'm ready to start soon.",
                ]);
                $applicant->addMedia($this->pdf())->preservingOriginal()->usingFileName('resume.pdf')->toMediaCollection(Applicant::RESUME, Applicant::RESUME_DISK);
            }
        }

        $careers = Career::query()->orderBy('id')->pluck('id')->all();
        $now = now();
        $this->bulk('applicants', 'email', $this->volume(0.1), fn (int $n) => [
            'career_id' => $careers[$n % count($careers)],
            'name' => self::PEOPLE[$n % 4]." {$n}",
            'email' => "bulk-applicant-{$n}@example.test",
            'phone' => '0107'.str_pad((string) $n, 7, '0', STR_PAD_LEFT),
            'status' => Applicant::STATUSES[$n % count(Applicant::STATUSES)],
            'gender' => Applicant::GENDERS[$n % 2],
            'linkedin_profile' => "https://www.linkedin.com/in/bulk-applicant-{$n}",
            'cover_letter' => 'I have relevant experience and I am ready to start soon.',
            'created_at' => $now->copy()->subMinutes($n),
            'updated_at' => $now,
        ]);

        $this->stamps('careers', 'applicants');
    }
}
