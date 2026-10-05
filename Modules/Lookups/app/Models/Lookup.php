<?php

namespace Modules\Lookups\Models;

use App\Traits\HasLocalization;
use Askedio\SoftCascade\Traits\SoftCascadeTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Kalnoy\Nestedset\NodeTrait;
use Mattiverse\Userstamps\Traits\Userstamps;
use Modules\Lookups\Support\LookupUsage;

/**
 * A lookup is either a root category (e.g. "Property Types") or a value inside one (e.g. "Villa").
 */
class Lookup extends Model
{
    use HasLocalization, NodeTrait, SoftCascadeTrait, SoftDeletes, Userstamps;

    /**
     * Slug of the category that holds the user roles.
     */
    public const ROLES_SLUG = 'user_roles';

    /**
     * Slug of the Admin role (same as User::ADMIN_ROLE).
     */
    public const ADMIN_ROLE_SLUG = 'user_roles_admin';

    protected $fillable = [
        'parent_id',
        'name_en',
        'name_ar',
        'order',
        'slug',
        'description_en',
        'description_ar',
        'attributes',
        'is_active',
        'is_deletable',
        'is_default',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_deletable' => 'boolean',
        'is_default' => 'boolean',
        'attributes' => 'array',
    ];

    protected $softCascade = ['children'];

    protected $appends = ['default_name', 'default_description'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Lookup::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Lookup::class, 'parent_id');
    }

    /**
     * Roles: the lookups inside the "user_roles" category.
     */
    public function scopeRoles(Builder $query): void
    {
        $query->whereIn('parent_id', static::rolesCategoryIdQuery());
    }

    /**
     * Everything except the "user_roles" category and its roles.
     */
    public function scopeWithoutRoles(Builder $query): void
    {
        $query->where('slug', '!=', self::ROLES_SLUG)
            ->where(fn (Builder $query) => $query
                ->whereNull('parent_id')
                ->orWhereNotIn('parent_id', static::rolesCategoryIdQuery()));
    }

    /**
     * The "user_roles" category id as a subquery. It doesn't depend on the outer row, so the database runs it once
     * (a per-row "parent has slug" check was the slowest part of the Lookups list with thousands of rows).
     */
    protected static function rolesCategoryIdQuery(): \Closure
    {
        return fn (\Illuminate\Database\Query\Builder $query) => $query
            ->select('id')
            ->from((new static)->getTable())
            ->where('slug', self::ROLES_SLUG)
            ->whereNull('parent_id');
    }

    /**
     * Adds "has_protected_descendants" (one subquery on the nested-set columns), so list pages don't need a
     * query per row to know whether a category can be deleted.
     */
    public function scopeWithProtectedDescendantsFlag(Builder $query): void
    {
        $table = $this->getTable();

        $query
            ->when($query->getQuery()->columns === null, fn (Builder $query) => $query->select("{$table}.*"))
            ->selectSub(
                static::withTrashed()
                    ->from("{$table} as descendant")
                    ->selectRaw('1')
                    ->whereColumn('descendant._lft', '>', "{$table}._lft")
                    ->whereColumn('descendant._rgt', '<', "{$table}._rgt")
                    ->where('descendant.is_deletable', false)
                    ->limit(1),
                'has_protected_descendants',
            );
    }

    /**
     * Whether any item inside this lookup (at any depth, deleted or not) is protected.
     */
    public function hasProtectedDescendants(): bool
    {
        if (array_key_exists('has_protected_descendants', $this->attributes)) {
            return (bool) $this->attributes['has_protected_descendants'];
        }

        return $this->descendants()->withTrashed()->where('is_deletable', false)->exists();
    }

    /**
     * Adds "is_used": whether another module (e.g. a location) uses this lookup or a value inside it.
     */
    public function scopeWithUsageFlag(Builder $query): void
    {
        LookupUsage::addFlag($query);
    }

    /**
     * Whether another module uses this lookup or a value inside it (see LookupUsage).
     */
    public function isUsed(): bool
    {
        if (array_key_exists('is_used', $this->attributes)) {
            return (bool) $this->attributes['is_used'];
        }

        return LookupUsage::isUsed($this);
    }

    public function getDefaultNameAttribute(): ?string
    {
        return $this->getLocalized('name');
    }

    public function getDefaultDescriptionAttribute(): ?string
    {
        return $this->getLocalized('description');
    }

    protected static function booted(): void
    {
        static::saving(function (Lookup $lookup) {
            // The Admin role (Users module) is always active, so admins can never lose access by accident.
            if ($lookup->slug === self::ADMIN_ROLE_SLUG) {
                $lookup->is_active = true;
            }

            if (empty($lookup->slug) && ! empty($lookup->name_en)) {
                $lookup->slug = Str::slug($lookup->name_en);
            }
        });
    }
}
