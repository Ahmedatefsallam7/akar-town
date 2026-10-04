<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The actions that existed when permissions moved from on/off per module to a list of actions.
     * Kept here (not read from the enum) so this migration never changes meaning.
     */
    private const ACTIONS = ['view', 'create', 'edit', 'delete', 'bulk'];

    /**
     * {"lookups": true} becomes {"lookups": ["view", "create", "edit", "delete", "bulk"]}; false becomes [].
     */
    public function up(): void
    {
        $this->convert(fn ($allowed) => is_bool($allowed) ? ($allowed ? self::ACTIONS : []) : $allowed);
    }

    /**
     * A module with "view" becomes true, anything else false.
     */
    public function down(): void
    {
        $this->convert(fn ($allowed) => is_array($allowed) ? in_array('view', $allowed, true) : $allowed);
    }

    private function convert(Closure $map): void
    {
        $rolesParentId = DB::table('lookups')->where('slug', 'user_roles')->whereNull('parent_id')->value('id');

        if (! $rolesParentId) {
            return;
        }

        DB::table('lookups')->where('parent_id', $rolesParentId)->orderBy('id')->each(function ($role) use ($map) {
            $attributes = json_decode($role->attributes ?? '', true);

            if (! is_array($attributes['permissions'] ?? null)) {
                return;
            }

            $attributes['permissions'] = array_map($map, $attributes['permissions']);

            DB::table('lookups')->where('id', $role->id)->update(['attributes' => json_encode($attributes)]);
        });
    }
};
