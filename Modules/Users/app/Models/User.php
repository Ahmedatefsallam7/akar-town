<?php

namespace Modules\Users\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Mattiverse\Userstamps\Traits\Userstamps;
use Modules\Lookups\Models\Lookup;
use Modules\Users\Enums\PermissionAction;
use Modules\Users\Support\PermissionModules;

class User extends Authenticatable implements FilamentUser, HasAvatar, HasName
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes, Userstamps;

    /**
     * Slug of the seeded role that always has full access.
     */
    public const ADMIN_ROLE = Lookup::ADMIN_ROLE_SLUG;

    /**
     * Modules the Admin role can never lose, so nobody can lock themselves out of managing access.
     */
    public const ADMIN_LOCKED_MODULES = ['users', 'roles'];

    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'avatar',
        'username',
        'phone',
        'job_title_en',
        'job_title_ar',
        'whatsapp',
        'rating',
        'reviews_count',
        'email',
        'password',
        'is_suspended',
        'role_id',
        'custom_permissions',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'is_suspended' => 'boolean',
            'password' => 'hashed',
            'custom_permissions' => 'array',
            'rating' => 'float',
            'reviews_count' => 'integer',
        ];
    }

    /**
     * Remove avatar files that are no longer used, so replaced photos don't pile up on the disk.
     * A soft-deleted user keeps the photo (they can be restored); a permanent delete removes it.
     */
    protected static function booted(): void
    {
        static::updated(function (User $user) {
            if ($user->wasChanged('avatar')) {
                static::deleteAvatarFile($user->getOriginal('avatar'));
            }
        });

        static::forceDeleted(fn (User $user) => static::deleteAvatarFile($user->avatar));
    }

    /**
     * Deletes an avatar file once the database change is committed (a rolled-back save keeps the photo), and only
     * if no other user still points to the same file.
     */
    protected static function deleteAvatarFile(?string $path): void
    {
        if (blank($path)) {
            return;
        }

        DB::afterCommit(function () use ($path) {
            if (! static::withTrashed()->where('avatar', $path)->exists()) {
                Storage::disk(self::AVATAR_DISK)->delete($path);
            }
        });
    }

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

    /**
     * Full name, built from first and last name.
     */
    public function getNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * Accept a full name (e.g. from the register page) and split it.
     */
    public function setNameAttribute($value): void
    {
        $parts = explode(' ', trim((string) $value), 2);
        $this->first_name = $parts[0];
        $this->last_name = $parts[1] ?? '';
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Lookup::class, 'role_id');
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return ! $this->is_suspended;
    }

    /**
     * Disk and folder for avatars (public, so the browser can load them directly).
     */
    public const AVATAR_DISK = 'public';

    public const AVATAR_DIRECTORY = 'avatars';

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->avatar ? Storage::disk(self::AVATAR_DISK)->url($this->avatar) : null;
    }

    public function getFilamentName(): string
    {
        return $this->name ?: (string) $this->username ?: $this->email;
    }

    /**
     * Badge color for the user's role, used in the users table and the user menu.
     * Read from the role's attributes, because every role except Admin can change its slug.
     */
    public function roleColor(): string
    {
        if ($this->isAdmin()) {
            return 'danger';
        }

        return $this->role?->getAttribute('attributes')['color'] ?? 'gray';
    }

    public function isAdmin(): bool
    {
        return $this->role?->slug === self::ADMIN_ROLE;
    }

    /**
     * Deny by default: an action the user doesn't effectively have is denied. Every action also needs "view".
     * The Admin role always has every action on the locked modules, so admins can never lock themselves out.
     */
    public function hasPermission(string $module, PermissionAction $action = PermissionAction::View): bool
    {
        if ($this->role?->slug === self::ADMIN_ROLE && in_array($module, self::ADMIN_LOCKED_MODULES, true)) {
            return true;
        }

        $actions = $this->effectiveActions($module);

        return in_array(PermissionAction::View->value, $actions, true)
            && in_array($action->value, $actions, true);
    }

    /**
     * What the user really has on a module: their custom permissions if set, otherwise their role's (if active).
     * Only actions the module offers count.
     *
     * @return array<string> e.g. ["view", "edit"]
     */
    public function effectiveActions(string $module): array
    {
        if ($this->role?->slug === self::ADMIN_ROLE && in_array($module, self::ADMIN_LOCKED_MODULES, true)) {
            return PermissionModules::actionsFor($module);
        }

        $actions = $this->usesCustomPermissions()
            ? (array) ($this->custom_permissions[$module] ?? [])
            : ($this->role?->is_active ? (array) ($this->role->getAttribute('attributes')['permissions'][$module] ?? []) : []);

        return array_values(array_intersect(PermissionModules::actionsFor($module), $actions));
    }

    /**
     * Every module with what the user really has on it.
     *
     * @return array<string, array<string>>
     */
    public function effectivePermissions(): array
    {
        return collect(PermissionModules::all())->mapWithKeys(fn (array $module, string $key) => [$key => $this->effectiveActions($key)])->all();
    }

    /**
     * True when this user has every action in $permissions ({module: [actions]}). Admins cover everything.
     * Used so nobody can assign, edit or take over a role or user with more access than themselves.
     */
    public function covers(array $permissions): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        foreach (PermissionModules::all() as $key => $module) {
            $needed = array_intersect($module['actions'], (array) ($permissions[$key] ?? []));

            if (array_diff($needed, $this->effectiveActions($key)) !== []) {
                return false;
            }
        }

        return true;
    }

    public function usesCustomPermissions(): bool
    {
        return $this->custom_permissions !== null;
    }
}
