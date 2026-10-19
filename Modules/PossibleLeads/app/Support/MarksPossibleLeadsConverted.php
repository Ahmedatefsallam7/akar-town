<?php

namespace Modules\PossibleLeads\Support;

use Illuminate\Database\Eloquent\Model;
use Modules\PossibleLeads\Models\PossibleLead;

/**
 * When a website lead is sent (contact message, meeting, sell request), the same visitor's earlier clicks (same browser
 * cookie) become "converted", point at that lead, and get the account when the visitor was logged in.
 */
class MarksPossibleLeadsConverted
{
    public static function handle(Model $lead): void
    {
        // if thire is no visitor_id or website_user_id then we can't link the possible leads to the lead
        if (blank($lead->visitor_id) && blank($lead->website_user_id)) {
            return;
        }

        // Update all possible leads that have the same visitor_id or website_user_id and were created before the lead was created
        PossibleLead::query()
            ->whereNull('converted_at') // only unconverted possible leads
            ->where('created_at', '<=', $lead->created_at) // only possible leads created before the lead was created
            ->where(fn ($query) => $query
                // if the lead has a visitor_id, link possible leads with the same visitor_id
                ->when($lead->visitor_id, fn ($query, $visitor) => $query->orWhere('visitor_id', $visitor))
                // if the lead has a website_user_id, link possible leads with the same website_user_id
                ->when($lead->website_user_id, fn ($query, $user) => $query->orWhere('website_user_id', $user)))
            ->update(array_filter([
                // A lead sent logged out keeps the account the clicks already have.
                'website_user_id' => $lead->website_user_id,
                'converted_at' => $lead->created_at, // the lead's creation time is when the possible lead was converted
                'converted_lead_type' => $lead->getMorphClass(),
                'converted_lead_id' => $lead->getKey(),
            ], fn ($value) => $value !== null));
    }
}
