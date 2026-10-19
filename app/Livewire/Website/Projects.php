<?php

namespace App\Livewire\Website;

use App\Support\Website\ProjectCards;
use App\Support\Website\ProjectSearch;
use App\Support\Website\SearchOptions;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * The projects listing (the design's projects.html): header with the number of matching projects, filters that apply
 * at once (as in the design) and are kept in the URL, the cards and "Load More". The home quick search's
 * "New Projects" tab lands here (its lists and its "Off-Plan" tab are read on the way in).
 */
class Projects extends Component
{
    private const BATCH = 6;

    /** Most cards one page shows ("Load More" stops here), so one request stays light. */
    private const MAX = 96;

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

    /** Changed only by the server (loadMore / filters), never by the browser. */
    #[Locked]
    public int $shown = self::BATCH;

    public function mount(): void
    {
        // The quick search sends lists (type[]=…): keep the first plain value.
        $first = fn (mixed $value) => is_array($value) ? (array_values($value)[0] ?? '') : $value;
        $this->type = is_scalar($first($this->type)) ? (string) $first($this->type) : '';
        $this->status = is_scalar($first($this->status)) ? (string) $first($this->status) : '';

        // The quick search's "New Projects → Off-Plan" tab.
        if (request()->query('category') === ProjectSearch::OFF_PLAN && $this->status === '' && isset(SearchOptions::values('projectstatus')[ProjectSearch::OFF_PLAN])) {
            $this->status = ProjectSearch::OFF_PLAN;
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
        $this->shown = min(self::MAX, $this->shown + self::BATCH);
    }

    public function clear(): void
    {
        $this->reset('q', 'location', 'type', 'status', 'developer', 'sort', 'shown');
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
            'more' => $total > count($projects) && $this->shown < self::MAX,
            'cards' => ProjectCards::from($projects),
            'locations' => SearchOptions::locations(),
            'types' => SearchOptions::values('unittype'),
            'statuses' => SearchOptions::values('projectstatus'),
            'developers' => SearchOptions::developers(),
        ]);
    }
}
