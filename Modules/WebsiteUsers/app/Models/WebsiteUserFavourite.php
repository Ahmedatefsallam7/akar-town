<?php

namespace Modules\WebsiteUsers\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One unit or project in a signed-in user's wishlist (a guest's stays in the browser).
 */
class WebsiteUserFavourite extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['website_user_id', 'unit_id', 'project_id'];
}
