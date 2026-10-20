<?php

namespace App\Support\Website;

use Modules\Projects\Models\Project;
use Modules\Units\Models\Unit;
use Modules\WebsiteUsers\Models\WebsiteUser;
use Modules\WebsiteUsers\Models\WebsiteUserFavourite;

/**
 * The wishlist ids as the browser keeps them ($store.favourites): unit ids, and projects as "project-<id>".
 * A guest's list stays in the browser; a signed-in user's is saved to the account.
 */
class Favourites
{
    /** Most items read from one browser or saved to one account. */
    public const MAX = 200;

    /**
     * Splits the browser's ids into unit and project ids (anything else is ignored), in the same order, capped.
     *
     * @param  array<int, mixed>  $ids
     * @return array{units: list<int>, projects: list<int>}
     */
    public static function split(array $ids): array
    {
        $ids = array_slice(array_values(array_unique(array_map('strval', array_filter($ids, 'is_scalar')))), 0, self::MAX);

        return [
            'units' => array_values(array_map('intval', array_filter($ids, fn (string $id) => ctype_digit($id)))),
            'projects' => array_values(array_map(fn (string $id) => (int) substr($id, 8), array_filter($ids, fn (string $id) => (bool) preg_match('/^project-\d+$/', $id)))),
        ];
    }

    /**
     * The account's saved ids in the browser's format, oldest first (as the browser adds them).
     *
     * @return list<int|string>
     */
    public static function of(WebsiteUser $user): array
    {
        return $user->favourites()->orderBy('id')->get(['unit_id', 'project_id'])
            ->map(fn (WebsiteUserFavourite $fav) => $fav->unit_id ?? 'project-'.$fav->project_id)
            ->all();
    }

    /**
     * Saves and removes ids on the account. Unknown units/projects and ones already saved are skipped.
     *
     * @param  array<int, mixed>  $add
     * @param  array<int, mixed>  $remove
     */
    public static function update(WebsiteUser $user, array $add, array $remove): void
    {
        $remove = self::split($remove);
        if ($remove['units'] || $remove['projects']) {
            $user->favourites()->where(fn ($query) => $query->whereIn('unit_id', $remove['units'])->orWhereIn('project_id', $remove['projects']))->delete();
        }

        $add = self::split($add);
        $room = self::MAX - $user->favourites()->count();
        $rows = [
            ...Unit::query()->whereIn('id', $add['units'])->pluck('id')->map(fn (int $id) => ['unit_id' => $id, 'project_id' => null]),
            ...Project::query()->whereIn('id', $add['projects'])->pluck('id')->map(fn (int $id) => ['unit_id' => null, 'project_id' => $id]),
        ];

        if ($rows && $room > 0) {
            WebsiteUserFavourite::query()->insertOrIgnore(array_map(
                fn (array $row) => [...$row, 'website_user_id' => $user->id, 'created_at' => now()],
                array_slice($rows, 0, $room),
            ));
        }
    }
}
