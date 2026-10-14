<?php

namespace App\Support\Crm;

/**
 * Phone and email checks for every form whose record goes to the CRM (website and admin), as strict as the CRM's
 * own: it refuses a number that isn't valid for the lead's country ("Number does not match the provided country"),
 * so a lead that passes here isn't refused there.
 */
class LeadContact
{
    /**
     * A number valid for the CRM's country (e.g. 01001234567 or +201001234567 for Egypt).
     *
     * @return list<string>
     */
    public static function phoneRules(): array
    {
        return ['phone:'.config('crm.default_country_code')];
    }

    /**
     * A real address with a domain (the plain "email" rule lets "name@host" through).
     *
     * @return list<string>
     */
    public static function emailRules(): array
    {
        return ['email:rfc,filter'];
    }
}
