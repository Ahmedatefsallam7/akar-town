<?php

namespace Modules\PossibleLeads\Http\Controllers;

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
            'page_url' => ['nullable', 'url:http,https', 'max:2048'],
            'unit_id' => ['nullable', 'integer', Rule::exists('units', 'id')->whereNull('deleted_at')],
            'project_id' => ['nullable', 'integer', Rule::exists('projects', 'id')->whereNull('deleted_at')],
        ]);

        $visitor = Visitor::id($request) ?? Visitor::newId();

        // No session here (kept cheap), so the account isn't known yet: it's linked when the visitor sends a lead.
        PossibleLead::create($data + ['visitor_id' => $visitor]);

        return response()->json(status: 201)->withCookie(Visitor::cookie($visitor));
    }
}
