<?php

namespace Modules\PossibleLeads\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * An anonymous id per browser (a cookie, no session), so a visitor's clicks count once and link to the form they
 * send later. The website's forms read it with Visitor::id() and save it as visitor_id on the lead.
 */
class Visitor
{
    public const COOKIE = 'akar_visitor';

    private const DAYS = 365;

    /**
     * The visitor's id, or null when there's none (or it isn't one of ours).
     */
    public static function id(Request $request): ?string
    {
        $id = $request->cookie(self::COOKIE);

        return is_string($id) && Str::isUuid($id) ? $id : null;
    }

    public static function newId(): string
    {
        return (string) Str::uuid();
    }

    public static function cookie(string $id): Cookie
    {
        return cookie(self::COOKIE, $id, self::DAYS * 24 * 60, httpOnly: true, sameSite: 'lax');
    }
}
