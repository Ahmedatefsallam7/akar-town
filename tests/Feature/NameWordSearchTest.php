<?php

namespace Tests\Feature;

use App\Filament\Support\PrefixSearch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Careers\Filament\Resources\ApplicantResource;
use Modules\Careers\Filament\Resources\ApplicantResource\Pages\ListApplicants;
use Modules\Careers\Models\Applicant;
use Modules\Inquiry\Filament\Resources\InquiryResource\Pages\ListInquiries;
use Modules\Inquiry\Filament\Resources\MeetingResource\Pages\ListMeetings;
use Modules\Inquiry\Models\Inquiry;
use Modules\Inquiry\Models\Meeting;
use Modules\UnitRequests\Filament\Resources\UnitRequestResource\Pages\ListUnitRequests;
use Modules\UnitRequests\Models\UnitRequest;
use Modules\WebsiteUsers\Filament\Resources\WebsiteUserResource\Pages\ListWebsiteUsers;
use Modules\WebsiteUsers\Models\WebsiteUser;
use Tests\TestCase;

/**
 * The leads lists find a person by any word of the name ("Ali" finds "Sara Ali"), not only its start; e-mail and
 * phone stay a start match (PrefixSearch, anyWord).
 */
class NameWordSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_leads_list_finds_any_word_of_the_name(): void
    {
        $this->actingAs($this->createAdmin());

        $lists = [
            [ListApplicants::class, Applicant::class, 'name'],
            [ListInquiries::class, Inquiry::class, 'full_name'],
            [ListMeetings::class, Meeting::class, 'name'],
            [ListWebsiteUsers::class, WebsiteUser::class, 'name'],
            [ListUnitRequests::class, UnitRequest::class, 'seller_name'],
        ];

        foreach ($lists as [$page, $model, $column]) {
            $sara = $model::factory()->create([$column => 'Sara Ali Hassan']);
            $omar = $model::factory()->create([$column => 'Omar Khaled']);

            foreach (['Sara', 'Ali', 'hassan', 'ali sara', 'Sara Ali'] as $search) {
                Livewire::test($page)->searchTable($search)
                    ->assertCanSeeTableRecords([$sara])->assertCanNotSeeTableRecords([$omar]);
            }

            // Inside a word doesn't count: "li" isn't the start of any word.
            Livewire::test($page)->searchTable('li')->assertCanNotSeeTableRecords([$sara, $omar]);
        }
    }

    public function test_the_global_search_finds_any_word_too_and_typed_wildcards_are_literal(): void
    {
        $this->actingAs($this->createAdmin());
        Applicant::factory()->create(['name' => 'Sara Ali']);
        Applicant::factory()->create(['name' => 'Omar 100% Khaled']);

        $this->assertSame(['Sara Ali'], ApplicantResource::getGlobalSearchResults('Ali')->pluck('title')->all());
        $this->assertSame([], ApplicantResource::getGlobalSearchResults('%')->pluck('title')->all());

        $query = Applicant::query();
        PrefixSearch::apply($query, '  ', ['name'], anyWord: ['name']);
        $this->assertSame(2, $query->count()); // nothing typed: no filter
    }
}
