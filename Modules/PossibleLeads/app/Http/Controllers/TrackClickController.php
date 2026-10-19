<?php

namespace Modules\PossibleLeads\Http\Controllers;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\PossibleLeads\Models\PossibleLead;
use Modules\PossibleLeads\Support\Visitor;

/**
 * Records a click from the website (form, WhatsApp, phone number) as a possible lead. Stateless (no session), rate
 * limited, and it gives the browser a visitor id cookie the first time.
 */
class TrackClickController
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'click_type' => ['required', Rule::in(PossibleLead::TYPES)],
            // Only pages of this site (staff open it from the admin: no links to other sites).
            'page_url' => ['nullable', 'url:http,https', 'max:2048', function (string $attribute, string $value, Closure $fail) use ($request) {
                if (parse_url($value, PHP_URL_HOST) !== $request->getHost()) {
                    $fail(__('validation.url', ['attribute' => $attribute]));
                }
            }],
            'unit_id' => ['nullable', 'integer', Rule::exists('units', 'id')->whereNull('deleted_at')],
            'project_id' => ['nullable', 'integer', Rule::exists('projects', 'id')->whereNull('deleted_at')],
        ]);

        // return the visitor id from the cookie or create a new id if not exists
        // the visitor id is used to link the possible lead to the visitor when the lead is sent
        $visitor = Visitor::id($request) ?? Visitor::newId();

        // A repeated click (same visitor, type and unit / project within 10 minutes) is counted once.
        $repeated = PossibleLead::query()
            ->where('visitor_id', $visitor)
            ->where('click_type', $data['click_type'])
            ->where('unit_id', $data['unit_id'] ?? null)
            ->where('project_id', $data['project_id'] ?? null)
            ->where('created_at', '>=', now()->subMinutes(10))
            ->exists();

        // no session here so thire is why not use website_user_id , so the account isn't known yet
        if (! $repeated) {
            PossibleLead::create($data + ['visitor_id' => $visitor]);
        }

        return response()->json(status: 201)->withCookie(Visitor::cookie($visitor));
    }
}
