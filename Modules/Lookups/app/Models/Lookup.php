<?php

namespace Modules\Lookups\Models;

use App\Traits\HasLocalization;
use Askedio\SoftCascade\Traits\SoftCascadeTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Kalnoy\Nestedset\NodeTrait;
use Mattiverse\Userstamps\Traits\Userstamps;

/**
 * A lookup is either a root category (e.g. "Property Types") or a value inside one (e.g. "Villa").
 */
class Lookup extends Model
{
    use HasLocalization, NodeTrait, SoftCascadeTrait, SoftDeletes, Userstamps;

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
            if (empty($lookup->slug) && ! empty($lookup->name_en)) {
                $lookup->slug = Str::slug($lookup->name_en);
            }
        });
    }
}
