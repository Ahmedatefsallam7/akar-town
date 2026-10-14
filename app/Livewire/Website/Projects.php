<?php

namespace App\Livewire\Website;

use App\Support\Website\ProjectCards;
use App\Support\Website\ProjectSearch;
use App\Support\Website\SearchOptions;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Modules\Developers\Models\Developer;
use Modules\Locations\Models\Location;
use Modules\Lookups\Models\Lookup;

/**
 * The projects listing (the design's projects.html): header with the number of matching projects, filters that apply
 * at once (as in the design) and are kept in the URL, the cards and "Load More". The home quick search's
 * "New Projects" tab lands here (its lists and its "Off-Plan" tab are read on the way in).
 */
class Projects extends Component
{
    private const BATCH = 6;

    #[Url(except: '')]
    public string $q = '';

    #[Url(except: '')]
    public string $location = '';

    /** Untyped: the quick search sends lists (type[]=…); mount() keeps the first value. */
    #[Url(except: '')]
    public $type = '';

    #[Url(except: '')]
    public $status = '';

    #[Url(except: '')]
    public string $developer = '';

    /** Empty: "Featured First" (the sort list's first choice). */
    #[Url(except: '')]
    public string $sort = '';

    public int $shown = self::BATCH;

    public function mount(): void
    {
        $this->type = (string) (is_array($this->type) ? (array_values($this->type)[0] ?? '') : $this->type);
        $this->status = (string) (is_array($this->status) ? (array_values($this->status)[0] ?? '') : $this->status);

        // The quick search's "New Projects → Off-Plan" tab.
        if (request()->query('category') === ProjectSearch::OFF_PLAN && $this->status === '') {
            $this->status = (string) (array_search(ProjectSearch::OFF_PLAN, $this->statusSlugs(), true) ?: '');
        }

        if (! in_array($this->sort, ProjectSearch::SORTS, true)) {
            $this->sort = '';
        }
    }

    /** Any filter change starts from the first batch again. */
    public function updated(): void
    {
        $this->shown = self::BATCH;
    }

    public function loadMore(): void
    {
        $this->shown += self::BATCH;
    }

    public function clear(): void
    {
        $this->reset('q', 'location', 'type', 'status', 'developer', 'sort', 'shown');
    }

    /**
     * @return array<int, string> status lookup id => slug
     */
    private function statusSlugs(): array
    {
        return Lookup::query()
            ->whereIn('parent_id', Lookup::query()->select('id')->whereNull('parent_id')->where('slug', 'projectstatus'))
            ->pluck('slug', 'id')
            ->all();
    }

    public function render(): View
    {
        $query = ProjectSearch::query([
            'q' => $this->q,
            'location' => $this->location,
            'type' => $this->type,
            'status' => $this->status,
            'developer' => $this->developer,
            'sort' => $this->sort,
        ]);

        $total = (clone $query)->count();
        $projects = ProjectCards::eagerLoad($query)->limit($this->shown)->get();

        return view('livewire.website.projects', [
            'total' => $total,
            'cards' => ProjectCards::from($projects),
            'locations' => Location::query()->where('is_active', true)->orderBy('order')->orderBy('name_en')->get(['id', 'slug', 'name_en', 'name_ar'])
                ->mapWithKeys(fn (Location $location) => [$location->slug => $location->getLocalized('name')])->all(),
            'types' => SearchOptions::values('unittype'),
            'statuses' => SearchOptions::values('projectstatus'),
            'developers' => Developer::query()->where('is_published', true)->orderBy('name_en')->get(['id', 'name_en', 'name_ar'])
                ->mapWithKeys(fn (Developer $developer) => [$developer->id => $developer->getLocalized('name')])->all(),
        ]);
    }
}
