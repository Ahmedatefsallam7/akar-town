<?php

namespace Modules\PossibleLeads\Support;

use Illuminate\Database\Eloquent\Model;
use Modules\PossibleLeads\Models\PossibleLead;

/**
 * When a website lead is sent (contact message, meeting, sell request), the same visitor's earlier clicks — matched by
 * visitor id, or by account when logged in — become "converted" and point at that lead.
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
                'converted_at' => $lead->created_at,
                'converted_lead_type' => $lead->getMorphClass(),
                'converted_lead_id' => $lead->getKey(),
            ]);
    }
}
