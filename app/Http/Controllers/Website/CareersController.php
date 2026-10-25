<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Modules\Careers\Models\Career;
use Modules\Lookups\Models\Lookup;
use Modules\Lookups\Support\LocationTree;

class CareersController extends Controller
{
    /**
     * One open job's page (the design has none: built from the site's own pieces): its description, responsibilities
     * and requirements, a side card with the job's facts and "Apply Now" (the careers page's apply window).
     */
    public function show(string $slug): View
    {
        $career = Career::query()->open()->where('slug', $slug)->firstOrFail();

        $title = $career->getLocalized('title');
        $locale = app()->getLocale();
        // The admin's bullet lists keep both languages per item: [{en, ar}, …].
        $bullets = fn (?array $items) => collect($items ?? [])->map(fn (array $item) => $item[$locale] ?? $item['en'] ?? null)->filter()->values()->all();
        $salary = $career->show_salary && ($career->salary_from || $career->salary_to)
            ? collect([$career->salary_from, $career->salary_to])->filter()->map(fn (int $amount) => Number::format($amount))->implode(' – ').' '.__($career->salary_currency ?? 'EGP')
            : null;

        return view('website.career', [
            'id' => $career->id,
            'title' => $title,
            'department' => $career->department_id ? Lookup::query()->whereKey($career->department_id)->first(['id', 'name_en', 'name_ar'])?->getLocalized('name') : null,
            'type' => Career::typeOptions()[$career->type] ?? $career->type,
            'place' => LocationTree::place($career->only(LocationTree::LEVELS)) ?: null,
            'vacancies' => $career->vacancies,
            'closes' => $career->closing_date?->locale($locale)->translatedFormat('j F Y'),
            'salary' => $salary,
            'description' => (string) str((string) $career->getLocalized('description'))->sanitizeHtml(),
            'responsibilities' => $bullets($career->responsibilities),
            'requirements' => $bullets($career->requirements),
            'metaTitle' => $career->getLocalized('meta_title') ?: $title,
            'metaDescription' => $career->getLocalized('meta_description') ?: (Str::limit(trim(html_entity_decode(strip_tags((string) $career->getLocalized('description')))), 160) ?: null),
        ]);
    }
}
