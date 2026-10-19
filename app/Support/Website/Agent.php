<?php

namespace App\Support\Website;

use Illuminate\Support\Facades\Storage;
use Modules\Users\Models\User;

/**
 * An admin user shaped for the website's agent cards (property page) and sales advisor card (project page).
 */
class Agent
{
    /**
     * Eager-load constraint: suspended users (e.g. people who left) aren't shown as agents.
     */
    public static function scope(): \Closure
    {
        return fn ($query) => $query->where('is_suspended', false);
    }

    /**
     * @return array{name: string, initials: string, role: ?string, photo: ?string, phone: ?string, whatsapp: ?string, email: ?string, rating: ?float, stars: int, reviews: ?int}|null
     */
    public static function from(?User $user): ?array
    {
        if (! $user) {
            return null;
        }

        $name = $user->getFilamentName();
        $rating = $user->rating !== null ? (float) $user->rating : null;

        return [
            'name' => $name,
            'initials' => collect(preg_split('/\s+/u', trim($name)))->filter()->take(2)->map(fn (string $word) => mb_strtoupper(mb_substr($word, 0, 1)))->implode(''),
            'role' => $user->{'job_title_'.app()->getLocale()} ?: ($user->job_title_en ?: $user->job_title_ar),
            'photo' => $user->avatar ? Storage::disk(User::AVATAR_DISK)->url($user->avatar) : null,
            'phone' => $user->phone,
            'whatsapp' => $user->whatsapp ? 'https://wa.me/'.preg_replace('/\D/', '', $user->whatsapp) : null,
            'email' => $user->email,
            'rating' => $rating,
            'stars' => $rating !== null ? (int) round($rating) : 0,
            'reviews' => $user->reviews_count,
        ];
    }
}
