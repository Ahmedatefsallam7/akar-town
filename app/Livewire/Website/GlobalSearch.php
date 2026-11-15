<?php

namespace App\Livewire\Website;

use App\Support\Website\GlobalSearch as Search;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * The navbar's search window (AKAR-47): results grouped by type as the visitor types (see App\Support\Website\GlobalSearch).
 * Opened by the window event "open-search".
 */
class GlobalSearch extends Component
{
    public string $q = '';

    /** Set when the window first opens: only then are the popular compounds read (not on every page load). */
    public bool $opened = false;

    public function render(): View
    {
        $term = trim(mb_substr($this->q, 0, 80));
        ['groups' => $results, 'near' => $near] = Search::search($term);

        return view('livewire.website.global-search', [
            'term' => $term,
            'results' => $results,
            'near' => $near,
            'searched' => $searched = mb_strlen($term) >= 2,
            'popular' => $this->opened && ! $searched ? Search::popularCompounds() : [],
            'found' => collect($results)->flatten(1)->isNotEmpty(),
        ]);
    }
}
