<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    /**
     * Seed seluruh permission + role beserta hak aksesnya.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (config('permissions') as $group => $permissions) {
            foreach ($permissions as $permission => $label) {
                Permission::firstOrCreate([
                    'name' => $permission,
                    'guard_name' => 'web',
                ]);
            }
        }

        $all = Permission::pluck('name');

        $map = [
            'Administrator' => $all->all(),

            'Operator' => $all->filter(fn ($name) => ! str_starts_with($name, 'roles.')
                && ! str_starts_with($name, 'users.'))->values()->all(),

            'Coach' => [
                'dashboard.view', 'athletes.view', 'contingents.view', 'categories.view',
                'schedules.view', 'arenas.view', 'callings.view', 'verifications.view',
                'readiness.view', 'equipments.view', 'equipment-loans.view',
                'matches.view', 'performances.view', 'scores.view',
                'brackets.view', 'results.view', 'display.view', 'history.view',
            ],

            'Manajer Tim' => [
                'dashboard.view', 'athletes.view', 'contingents.view', 'categories.view',
                'schedules.view', 'arenas.view', 'callings.view', 'results.view',
                'display.view', 'history.view',
            ],

            'Public Display' => ['display.view'],
        ];

        foreach ($map as $roleName => $permissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($permissions);
        }
    }
}
