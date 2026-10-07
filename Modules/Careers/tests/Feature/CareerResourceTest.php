<?php

namespace Modules\Careers\Tests\Feature;

use Filament\Forms\Components\Repeater;
use Filament\Tables\Filters\QueryBuilder\Constraints\NumberConstraint\Operators\IsMinOperator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Careers\Filament\Resources\CareerResource;
use Modules\Careers\Filament\Resources\CareerResource\Pages\CreateCareer;
use Modules\Careers\Filament\Resources\CareerResource\Pages\EditCareer;
use Modules\Careers\Filament\Resources\CareerResource\Pages\ListCareers;
use Modules\Careers\Models\Career;
use Modules\Lookups\Models\Lookup;
use Modules\Lookups\Support\LookupUsage;
use Modules\Users\Support\PermissionModules;
use Tests\TestCase;

class CareerResourceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, Lookup>
     */
    private function lookupValues(string $type, array $slugs): array
    {
        $parent = Lookup::firstOrCreate(['slug' => $type], ['name_en' => ucfirst($type)]);

        return collect($slugs)->mapWithKeys(fn (string $slug) => [$slug => $parent->children()->create(['slug' => $slug, 'name_en' => ucfirst($slug)])])->all();
    }

    public function test_users_without_the_permission_are_forbidden(): void
    {
        $this->actingAs($this->createUserWithPermissions(['contact_us' => true]));

        $this->get(CareerResource::getUrl('index'))->assertForbidden();
    }

    public function test_admin_can_open_every_page(): void
    {
        $this->actingAs($this->createAdmin());
        $career = Career::factory()->create();

        $this->get(CareerResource::getUrl('index'))->assertOk()->assertSee($career->title_en);
        $this->get(CareerResource::getUrl('create'))->assertOk();
        $this->get(CareerResource::getUrl('view', ['record' => $career]))->assertOk();
        $this->get(CareerResource::getUrl('edit', ['record' => $career]))->assertOk();
    }

    public function test_it_is_an_inquiries_and_crm_permission_module(): void
    {
        $this->assertSame('inquiries_crm', PermissionModules::all()['careers']['group'] ?? null);
    }

    public function test_required_fields_and_checked_values(): void
    {
        $this->actingAs($this->createAdmin());

        Livewire::test(CreateCareer::class)
            ->fillForm(['title_en' => '', 'title_ar' => '', 'type' => null, 'vacancies' => null])
            ->call('create')
            ->assertHasFormErrors(['title_en' => 'required', 'title_ar' => 'required', 'type' => 'required', 'vacancies' => 'required'])
            ->assertHasNoFormErrors(['department_id', 'closing_date', 'salary_from', 'salary_currency']);

        Livewire::test(CreateCareer::class)
            ->fillForm(['title_en' => 'Sales', 'title_ar' => 'مبيعات', 'type' => 'freelance', 'vacancies' => 0, 'salary_from' => 20000, 'salary_to' => 10000, 'salary_currency' => 'BTC'])
            ->call('create')
            ->assertHasFormErrors(['type', 'vacancies', 'salary_to', 'salary_currency']);
    }

    public function test_a_salary_needs_its_currency(): void
    {
        $this->actingAs($this->createAdmin());

        Livewire::test(CreateCareer::class)
            ->fillForm(['title_en' => 'Sales', 'title_ar' => 'مبيعات', 'type' => 'full_time', 'vacancies' => 2, 'salary_from' => 15000, 'salary_currency' => null])
            ->call('create')
            ->assertHasFormErrors(['salary_currency']);
    }

    public function test_admin_creates_a_job_with_lists_department_benefits_and_salary(): void
    {
        $this->actingAs($this->createAdmin());
        $undoRepeaterFake = Repeater::fake();
        $sales = $this->lookupValues('department', ['sales'])['sales'];
        $benefits = $this->lookupValues('benefits', ['health-insurance', 'bonus']);

        Livewire::test(CreateCareer::class)
            ->assertSchemaStateSet(['is_active' => true])
            ->fillForm([
                'title_en' => 'Senior Sales Consultant',
                'title_ar' => 'مستشار مبيعات أول',
                'type' => 'full_time',
                'vacancies' => 3,
                'department_id' => $sales->id,
                'closing_date' => '2026-12-31',
                'responsibilities' => [['en' => 'Meet clients', 'ar' => 'مقابلة العملاء'], ['en' => 'Close deals', 'ar' => 'إتمام الصفقات']],
                'requirements' => [['en' => '3+ years in real estate', 'ar' => 'خبرة 3 سنوات']],
                'benefits' => [$benefits['health-insurance']->id, $benefits['bonus']->id],
                'show_salary' => true,
                'salary_from' => 15000,
                'salary_to' => 25000,
                'salary_currency' => 'EGP',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $undoRepeaterFake();

        $career = Career::sole();
        $this->assertSame('senior-sales-consultant', $career->slug);
        $this->assertSame([3, $sales->id, '2026-12-31', true, 15000, 25000, 'EGP'], [
            $career->vacancies, $career->department_id, $career->closing_date->toDateString(), $career->show_salary, $career->salary_from, $career->salary_to, $career->salary_currency,
        ]);
        $this->assertSame(['Meet clients', 'Close deals'], array_column($career->responsibilities, 'en'));
        $this->assertSame([['en' => '3+ years in real estate', 'ar' => 'خبرة 3 سنوات']], $career->requirements);
        $this->assertEqualsCanonicalizing([$benefits['health-insurance']->id, $benefits['bonus']->id], $career->lookupIds('benefits'));
    }

    public function test_every_list_item_needs_both_languages(): void
    {
        $this->actingAs($this->createAdmin());
        $undoRepeaterFake = Repeater::fake();

        Livewire::test(CreateCareer::class)
            ->fillForm(['title_en' => 'Sales', 'title_ar' => 'مبيعات', 'type' => 'full_time', 'vacancies' => 1, 'requirements' => [['en' => 'Driving licence', 'ar' => '']]])
            ->call('create')
            ->assertHasFormErrors(['requirements.0.ar' => 'required']);

        $undoRepeaterFake();
    }

    public function test_a_new_closing_date_cannot_be_in_the_past(): void
    {
        $this->actingAs($this->createAdmin());
        $fields = ['title_en' => 'Sales', 'title_ar' => 'مبيعات', 'type' => 'full_time', 'vacancies' => 1];

        Livewire::test(CreateCareer::class)
            ->fillForm([...$fields, 'closing_date' => '1985-01-18'])
            ->call('create')
            ->assertHasFormErrors(['closing_date']);

        Livewire::test(CreateCareer::class)
            ->fillForm([...$fields, 'closing_date' => today()->toDateString()])
            ->call('create')
            ->assertHasNoFormErrors();

        // An expired job can still be edited without touching its date.
        $expired = Career::factory()->create(['closing_date' => now()->subMonth()]);
        Livewire::test(EditCareer::class, ['record' => $expired->getRouteKey()])
            ->fillForm(['vacancies' => 4])
            ->call('save')
            ->assertHasNoFormErrors();
        $this->assertSame(4, $expired->fresh()->vacancies);
    }

    public function test_a_job_shows_while_published_and_until_its_closing_day(): void
    {
        $this->travelTo('2026-10-07 15:00');
        $open = Career::factory()->create(['closing_date' => null]);
        $closesToday = Career::factory()->create(['closing_date' => '2026-10-07']);
        $expired = Career::factory()->create(['closing_date' => '2026-10-06']);
        $draft = Career::factory()->create(['is_active' => false]);

        $this->assertEqualsCanonicalizing([$open->id, $closesToday->id], Career::open()->pluck('id')->all());
        $this->assertSame([true, true, false, false], [$open->isOpen(), $closesToday->isOpen(), $expired->isOpen(), $draft->isOpen()]);

        // The next day it hides by itself, with no one changing anything.
        $this->travelTo('2026-10-08 00:01');
        $this->assertSame([$open->id], Career::open()->pluck('id')->all());
    }

    public function test_filters_query_builder_bulk_status_and_trash(): void
    {
        $this->actingAs($this->createAdmin());
        $full = Career::factory()->create(['type' => 'full_time', 'vacancies' => 1]);
        $part = Career::factory()->create(['type' => 'part_time', 'vacancies' => 6]);

        Livewire::test(ListCareers::class)
            ->filterTable('type', 'part_time')
            ->assertCanSeeTableRecords([$part])->assertCanNotSeeTableRecords([$full]);

        Livewire::test(ListCareers::class)
            ->filterTable('queryBuilder', ['rules' => [
                ['type' => 'vacancies', 'data' => ['operator' => IsMinOperator::make()->getName(), 'settings' => ['number' => 5]]],
            ]])
            ->assertCanSeeTableRecords([$part])->assertCanNotSeeTableRecords([$full]);

        Livewire::test(ListCareers::class)->callTableBulkAction('deactivate', [$full, $part]);
        $this->assertSame(0, Career::where('is_active', true)->count());
        Livewire::test(ListCareers::class)->callTableBulkAction('activate', [$full]);
        $this->assertTrue($full->fresh()->is_active);

        $part->delete();
        Livewire::test(ListCareers::class)
            ->assertCanNotSeeTableRecords([$part])
            ->filterTable('trashed', true)
            ->assertCanSeeTableRecords([$part]);
    }

    public function test_editing_keeps_the_slug_and_updates_benefits(): void
    {
        $this->actingAs($this->createAdmin());
        $benefits = $this->lookupValues('benefits', ['bonus', 'remote']);
        $career = Career::factory()->create(['title_en' => 'Accountant']);
        $career->lookupsOfType('benefits')->sync([$benefits['bonus']->id]);

        Livewire::test(EditCareer::class, ['record' => $career->getRouteKey()])
            ->assertSchemaStateSet(['benefits' => [$benefits['bonus']->id]])
            ->fillForm(['title_en' => 'Senior Accountant', 'benefits' => [$benefits['remote']->id]])
            ->call('save')
            ->assertHasNoFormErrors();

        $career->refresh();
        $this->assertSame(['Senior Accountant', 'accountant'], [$career->title_en, $career->slug]);
        $this->assertSame([$benefits['remote']->id], $career->lookupIds('benefits'));
    }

    public function test_a_department_or_location_in_use_cannot_be_deleted(): void
    {
        $sales = $this->lookupValues('department', ['sales'])['sales'];
        Career::factory()->create(['department_id' => $sales->id]);

        $this->assertTrue(LookupUsage::isUsed($sales));
    }
}
