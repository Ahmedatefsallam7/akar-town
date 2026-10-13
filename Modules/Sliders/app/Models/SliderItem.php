<?php

namespace Modules\Sliders\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Mattiverse\Userstamps\Traits\Userstamps;
use Modules\Projects\Models\Project;
use Modules\Sliders\Database\Factories\SliderItemFactory;

/**
 * A hero slide: one project, shown with its own title, description and image (so a renamed project or a new
 * photo shows up on the slide by itself).
 */
class SliderItem extends Model
{
    use HasFactory, Userstamps;

    protected $fillable = [
        'slider_id',
        'project_id',
        'order',
        'is_active',
    ];

    protected $casts = [
        'order' => 'integer',
        'is_active' => 'boolean',
    ];

    protected static function newFactory(): SliderItemFactory
    {
        return SliderItemFactory::new();
    }

    public function slider(): BelongsTo
    {
        return $this->belongsTo(Slider::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class)->withTrashed();
    }
}
