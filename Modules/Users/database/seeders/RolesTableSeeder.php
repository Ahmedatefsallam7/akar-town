<?php

namespace Modules\Users\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Lookups\Models\Lookup;
use Modules\Users\Models\User;
use Modules\Users\Support\PermissionModules;

class RolesTableSeeder extends Seeder
{
    /**
     * Roles are lookups under the "user_roles" category. Saved through the model so the nested set stays valid.
     */
    public function run(): void
    {
        $parent = Lookup::updateOrCreate(
            ['slug' => 'user_roles', 'parent_id' => null],
            [
                'name_en' => 'User Roles',
                'name_ar' => 'صلاحيات المستخدمين',
                'order' => 1,
                'description_en' => 'Roles for the users',
                'description_ar' => 'صلاحيات المستخدمين',
                'is_active' => true,
                'is_deletable' => false,
            ]
        );

        $roles = [
            [
                'slug' => 'user_roles_admin',
                'name_en' => 'Admin',
                'name_ar' => 'المدير',
                'description_en' => 'Administrator role with full permissions',
                'description_ar' => 'صلاحية المدير مع جميع الأذونات',
            ],
            [
                'slug' => 'user_roles_editor',
                'name_en' => 'Editor',
                'name_ar' => 'المحرر',
                'description_en' => 'Editor role with limited permissions',
                'description_ar' => 'صلاحية المحرر مع أذونات محدودة',
                'attributes' => ['color' => 'info'],
            ],
            [
                'slug' => 'user_roles_client',
                'name_en' => 'Client',
                'name_ar' => 'العميل',
                'description_en' => 'Client role with basic permissions',
                'description_ar' => 'صلاحية العميل مع أذونات أساسية',
                'attributes' => ['color' => 'success'],
            ],
        ];

        foreach ($roles as $index => $role) {
            // Admin is always ensured. Editor and Client can be renamed (and their slug with them), so they are
            // only added to a new roles category; otherwise a second run would create duplicates.
            if ($role['slug'] !== User::ADMIN_ROLE && ! $parent->wasRecentlyCreated) {
                continue;
            }

            // firstOrCreate: names and settings edited from the admin are never overwritten.
            $record = Lookup::firstOrCreate(
                ['slug' => $role['slug'], 'parent_id' => $parent->id],
                $role + ['order' => $index + 1, 'is_active' => true, 'is_deletable' => false]
            );

            // A new Admin role starts with every module allowed. Saved permissions are never overwritten.
            if ($record->slug === User::ADMIN_ROLE && empty($record->getAttribute('attributes')['permissions'])) {
                $record->update(['attributes' => ['permissions' => PermissionModules::everything()]]);
            }
        }
    }
}
