<?php

namespace Modules\Users\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Mattiverse\Userstamps\Traits\Userstamps;
use Modules\Lookups\Models\Lookup;

class User extends Authenticatable implements FilamentUser, HasName
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes, Userstamps;

    /**
     * Slug of the seeded role that always has full access.
     */
    public const ADMIN_ROLE = 'user_roles_admin';

    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'username',
        'phone',
        'email',
        'password',
        'is_suspended',
        'role_id',
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
        ];
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

    public function getFilamentName(): string
    {
        return $this->name ?: (string) $this->username ?: $this->email;
    }

    public function isAdmin(): bool
    {
        return $this->role?->slug === self::ADMIN_ROLE;
    }

    /**
     * Deny by default: no role, an inactive role, or no permissions stored on the role means no access.
     */
    public function hasModulePermission(string $module): bool
    {
        $role = $this->role;

        if (! $role) {
            return false;
        }

        // Checked before is_active, so admins can never lock themselves out.
        if ($role->slug === self::ADMIN_ROLE) {
            return true;
        }

        if (! $role->is_active) {
            return false;
        }

        $permissions = $role->getAttribute('attributes')['permissions'] ?? null;

        return (bool) ($permissions[$module] ?? false);
    }
}
