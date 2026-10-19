<?php

namespace App\Livewire\Website;

use App\Support\Website\ProjectCards;
use App\Support\Website\PropertyCards;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Modules\Projects\Models\Project;
use Modules\Units\Models\Unit;

/**
 * The wishlist page (the design's wishlist.html). The saved ids live in the visitor's browser ($store.favourites:
 * unit ids and "project-<id>"); the page sends them once and shows the current data of the ones still published,
 * newest saved first. Removing happens in the browser.
 */
class Wishlist extends Component
{
    /** Most items read from one browser. */
    private const MAX = 200;

    /** @var list<int> Set only by load() (capped), never by the browser. */
    #[Locked]
    public array $unitIds = [];

    /** @var list<int> */
    #[Locked]
    public array $projectIds = [];

    public bool $loaded = false;

    /**
     * @param  array<int, mixed>  $ids  the browser's saved ids
     */
    public function load(array $ids): void
    {
        $ids = array_slice(array_reverse(array_unique(array_map('strval', array_filter($ids, 'is_scalar')))), 0, self::MAX);

        $this->unitIds = array_values(array_map('intval', array_filter($ids, fn (string $id) => ctype_digit($id))));
        $this->projectIds = array_values(array_map(fn (string $id) => (int) substr($id, 8), array_filter($ids, fn (string $id) => (bool) preg_match('/^project-\d+$/', $id))));
        $this->loaded = true;
    }

    public function render(): View
    {
        $order = fn (array $ids) => fn ($model) => array_search($model->id, $ids, true);

        $units = $this->unitIds ? PropertyCards::eagerLoad(Unit::query())->where('is_published', true)->whereIn('id', $this->unitIds)->get()->sortBy($order($this->unitIds))->values() : collect();
        $projects = $this->projectIds ? ProjectCards::eagerLoad(Project::query())->where('is_published', true)->whereIn('id', $this->projectIds)->get()->sortBy($order($this->projectIds))->values() : collect();

        return view('livewire.website.wishlist', [
            'units' => PropertyCards::from($units),
            'projects' => ProjectCards::from($projects),
        ]);
    }
}
