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
        if (blank($lead->visitor_id) && blank($lead->website_user_id)) {
            return;
        }

        PossibleLead::query()
            ->whereNull('converted_at')
            ->where('created_at', '<=', $lead->created_at)
            ->where(fn ($query) => $query
                ->when($lead->visitor_id, fn ($query, $visitor) => $query->orWhere('visitor_id', $visitor))
                ->when($lead->website_user_id, fn ($query, $user) => $query->orWhere('website_user_id', $user)))
            ->update([
                'website_user_id' => $lead->website_user_id,
                'converted_at' => $lead->created_at,
                'converted_lead_type' => $lead->getMorphClass(),
                'converted_lead_id' => $lead->getKey(),
            ]);
    }
}
