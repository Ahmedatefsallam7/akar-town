<?php

namespace App\Support\Website;

use Propaganistas\LaravelPhone\PhoneNumber;
use Throwable;

/**
 * "Call" links that work from any country: a local number ("0100 123 4567") becomes international ("+201001234567"),
 * so a buyer abroad (e.g. in the Gulf) can call it. A short number such as the hotline 19000 stays as it is.
 */
class Phone
{
    public static function tel(?string $number): string
    {
        $digits = preg_replace('/[^\d+]/', '', (string) $number);

        try {
            $phone = new PhoneNumber($digits, config('crm.default_country_code'));

            return 'tel:'.($phone->isValid() ? $phone->formatE164() : $digits);
        } catch (Throwable) {
            return 'tel:'.$digits;
        }
    }
}
