<?php

namespace Tests\Concerns;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

trait CreatesUsers
{
    protected bool $rolesSeeded = false;

    protected function seedRoles(): void
    {
        if ($this->rolesSeeded) {
            return;
        }

        $this->seed(RoleSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->rolesSeeded = true;
    }

    protected function userWithPermissions(array $permissions, array $attributes = []): User
    {
        $this->seedRoles();

        $user = User::factory()->create($attributes);
        $user->givePermissionTo($permissions);

        return $user;
    }

    protected function userWithAllPermissions(array $attributes = []): User
    {
        $this->seedRoles();

        $user = User::factory()->create($attributes);
        $user->givePermissionTo(Permission::pluck('name')->all());

        return $user;
    }

    protected function userWithRole(string $role, array $attributes = []): User
    {
        $this->seedRoles();

        $user = User::factory()->create($attributes);
        $user->assignRole($role);

        return $user;
    }
}
