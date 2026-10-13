<?php

namespace Modules\Users\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Modules\Users\Filament\Resources\UserResource;
use Modules\Users\Filament\Resources\UserResource\Pages\ListUsers;
use Modules\Users\Models\User;
use Tests\TestCase;

/**
 * Users search matches values that start with the term (fast with an index on big tables).
 */
class UserSearchTest extends TestCase
{
    use RefreshDatabase;

    private User $sara;

    private User $omar;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->createAdmin(['name' => 'Admin Person', 'email' => 'admin@site.test', 'username' => 'admin', 'phone' => '+201000000000']));
        $this->sara = User::factory()->create(['name' => 'Sara Ali', 'email' => 'sara@mail.test', 'username' => 'sara.ali', 'phone' => '+201011111111']);
        $this->omar = User::factory()->create(['name' => 'Omar Hassan', 'email' => 'omar@mail.test', 'username' => 'omar.h', 'phone' => '+201022222222']);
    }

    private function search(string $term): Testable
    {
        return Livewire::test(ListUsers::class)->searchTable($term);
    }

    public function test_finds_by_the_start_of_any_searched_field(): void
    {
        $this->search('Sar')->assertCanSeeTableRecords([$this->sara])->assertCanNotSeeTableRecords([$this->omar]);
        $this->search('Hass')->assertCanSeeTableRecords([$this->omar])->assertCanNotSeeTableRecords([$this->sara]);
        $this->search('omar@')->assertCanSeeTableRecords([$this->omar])->assertCanNotSeeTableRecords([$this->sara]);
        $this->search('sara.')->assertCanSeeTableRecords([$this->sara])->assertCanNotSeeTableRecords([$this->omar]);
        $this->search('+2010222')->assertCanSeeTableRecords([$this->omar])->assertCanNotSeeTableRecords([$this->sara]);
    }

    public function test_finds_a_full_name(): void
    {
        $this->search('Sara Al')->assertCanSeeTableRecords([$this->sara])->assertCanNotSeeTableRecords([$this->omar]);
    }

    public function test_does_not_match_the_middle_of_a_value(): void
    {
        $this->search('mail.test')->assertCanNotSeeTableRecords([$this->sara, $this->omar]);
    }

    public function test_search_never_starts_with_a_wildcard(): void
    {
        DB::enableQueryLog();
        $this->search('sa%');

        $bindings = collect(DB::getQueryLog())->pluck('bindings')->flatten()->filter(fn ($value) => is_string($value) && str_contains($value, 'sa'));

        $this->assertNotEmpty($bindings);
        foreach ($bindings as $value) {
            $this->assertStringStartsNotWith('%', $value, 'A leading % would scan the whole table.');
            $this->assertStringContainsString('sa\\%', $value, 'A typed % must be escaped.');
        }
    }

    public function test_global_search_uses_the_same_prefix_search(): void
    {
        $titles = UserResource::getGlobalSearchResults('Oma')->map(fn ($result) => (string) $result->title)->all();

        $this->assertSame([$this->omar->username], $titles);
        $this->assertEmpty(UserResource::getGlobalSearchResults('mail.test')->all());
    }
}
