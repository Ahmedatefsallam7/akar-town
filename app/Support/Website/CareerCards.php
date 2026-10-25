<?php

namespace App\Support\Website;

use Illuminate\Support\Str;
use Modules\Careers\Models\Career;
use Modules\Lookups\Models\Lookup;

/**
 * The careers page's job cards (the design's career.html .job-card) and its department filter: open jobs, newest first,
 * with their department, type, place (the most precise one set) and the start of the description. Two queries
 * whatever the number of jobs: the jobs, then the department and place names.
 */
class CareerCards
{
    /** A department's icon (the design's ones), by its lookup slug; other departments get the briefcase. */
    private const ICONS = [
        'sales' => '<path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z" /><polyline points="9 22 9 12 15 12 15 22" />',
        'marketing' => '<path d="M22 12h-4l-3 9L9 3l-3 9H2" />',
        'design' => '<rect x="3" y="3" width="18" height="18" rx="2" /><path d="M3 9h18M9 21V9" />',
        'customer-support' => '<path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2" /><circle cx="9" cy="7" r="4" /><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75" />',
        'human-resources' => '<path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2" /><circle cx="9" cy="7" r="4" /><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75" />',
    ];

    private const BRIEFCASE = '<rect x="2" y="7" width="20" height="14" rx="2" /><path d="M16 21V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v16" />';

    /** The place columns, most precise first: the card shows the first one set. */
    private const PLACES = ['sub_area_id', 'area_id', 'region_id', 'city_id', 'country_id'];

    /**
     * @return array{jobs: list<array{id: int, slug: string, title: string, department: ?string, departmentName: ?string, type: string, place: ?string, summary: ?string, icon: string}>, departments: array<string, string>}
     */
    public static function open(): array
    {
        $careers = Career::query()
            ->open()
            ->select(['id', 'slug', 'title_en', 'title_ar', 'type', 'department_id', 'description_en', 'description_ar', ...self::PLACES])
            ->latest()
            ->get();

        $placeIds = $careers->map(fn (Career $career) => collect(self::PLACES)->map(fn (string $column) => $career->{$column})->first(fn ($id) => $id !== null));
        $lookups = Lookup::query()
            ->whereIn('id', $careers->pluck('department_id')->merge($placeIds)->filter()->unique())
            ->get(['id', 'slug', 'name_en', 'name_ar', 'order'])
            ->keyBy('id');

        $types = Career::typeOptions();
        $jobs = $careers->values()->map(function (Career $career, int $i) use ($lookups, $placeIds, $types) {
            $department = $lookups->get($career->department_id);

            return [
                'id' => $career->id,
                'slug' => $career->slug,
                'title' => $career->getLocalized('title'),
                'department' => $department?->slug,
                'departmentName' => $department?->getLocalized('name'),
                'type' => $types[$career->type] ?? $career->type,
                'place' => $lookups->get($placeIds[$i])?->getLocalized('name'),
                'summary' => Str::limit(trim(html_entity_decode(strip_tags((string) $career->getLocalized('description')))), 220) ?: null,
                'icon' => self::ICONS[$department?->slug] ?? self::BRIEFCASE,
            ];
        })->all();

        // The filter lists only departments that have open jobs, in the lookups' order.
        $departments = $lookups->only($careers->pluck('department_id')->filter()->unique()->all())
            ->sortBy('order')
            ->mapWithKeys(fn (Lookup $department) => [$department->slug => $department->getLocalized('name')])
            ->all();

        return ['jobs' => $jobs, 'departments' => $departments];
    }
}
