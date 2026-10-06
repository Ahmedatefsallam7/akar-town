<?php

namespace Modules\Units\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Users\Models\User;

/**
 * One price a unit had, and who set it. Written by Unit when the price is set or changed.
 */
class UnitPriceHistory extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['unit_id', 'price', 'changed_by'];

    protected $casts = ['price' => 'integer'];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
