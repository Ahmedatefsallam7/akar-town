<?php

namespace App\Support\Crm;

/**
 * A record that becomes a fresh lead in the CRM (a contact message, later viewings and sell requests). It needs the
 * columns crm_lead_id and crm_synced_at, filled once the CRM has the lead.
 */
interface CrmLead
{
    /**
     * Which CRM form the lead goes to (a key of config('crm.forms')).
     */
    public function crmForm(): string;

    /**
     * @return array{full_name: string, phone: ?string, email: ?string, description: string}
     */
    public function toCrmLead(): array;
}
