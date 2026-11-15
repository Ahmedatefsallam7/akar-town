<?php

namespace App\Livewire\Website;

use App\Support\Website\GlobalSearch;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * The search page (AKAR-48): every result for the text in the URL (?q=), grouped by type, more a group than the
 * navbar's window, each group with a link to its full listing. The same search as the window (GlobalSearch).
 */
class Search extends Component
{
    public const PER_GROUP = 12;

    #[Url(except: '')]
    public string $q = '';

    public function render(): View
    {
        $term = trim(mb_substr($this->q, 0, 80));
        ['groups' => $results, 'near' => $near] = GlobalSearch::search($term, self::PER_GROUP);

        return view('livewire.website.search', [
            'term' => $term,
            'results' => $results,
            'near' => $near,
            'total' => collect($results)->sum(fn (array $group) => count($group)),
        ]);
    }
}
