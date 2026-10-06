<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class RoleTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    public function test_role_index_can_be_rendered(): void
    {
        $this->seedRoles();
        $user = $this->userWithPermissions(['roles.view']);

        $response = $this->actingAs($user)->get('/admin/roles');

        $response->assertOk();
        $response->assertSee('Administrator');
    }

    public function test_role_can_be_created(): void
    {
        $this->seedRoles();
        $user = $this->userWithPermissions(['roles.create']);

        $response = $this->actingAs($user)
            ->from('/admin/roles')
            ->post('/admin/roles', ['name' => 'Juri']);

        $response->assertRedirect(route('admin.roles.index'));
        $response->assertSessionHas('success', 'Role Juri berhasil ditambahkan.');
        $this->assertDatabaseHas('roles', ['name' => 'Juri']);
    }

    public function test_role_name_must_be_unique(): void
    {
        $this->seedRoles();
        $user = $this->userWithPermissions(['roles.create']);

        $response = $this->actingAs($user)
            ->from('/admin/roles')
            ->post('/admin/roles', ['name' => 'Operator']);

        $response->assertSessionHasErrors('name');
        $this->assertSame(1, Role::where('name', 'Operator')->count());
    }

    public function test_role_name_is_required(): void
    {
        $this->seedRoles();
        $user = $this->userWithPermissions(['roles.create']);

        $response = $this->actingAs($user)
            ->from('/admin/roles')
            ->post('/admin/roles', []);

        $response->assertSessionHasErrors('name');
    }

    public function test_role_can_be_updated(): void
    {
        $this->seedRoles();
        $user = $this->userWithPermissions(['roles.update']);
        $role = Role::create(['name' => 'Petugas']);

        $response = $this->actingAs($user)
            ->from('/admin/roles')
            ->put("/admin/roles/{$role->id}", ['name' => 'Petugas Arena']);

        $response->assertRedirect(route('admin.roles.index'));
        $response->assertSessionHas('success', 'Role berhasil diperbarui.');
        $this->assertDatabaseHas('roles', ['id' => $role->id, 'name' => 'Petugas Arena']);
    }

    public function test_administrator_role_cannot_be_deleted(): void
    {
        $this->seedRoles();
        $user = $this->userWithPermissions(['roles.delete']);
        $role = Role::findByName('Administrator');

        $response = $this->actingAs($user)
            ->from('/admin/roles')
            ->delete("/admin/roles/{$role->id}");

        $response->assertSessionHas('error', 'Role Administrator tidak bisa dihapus.');
        $this->assertDatabaseHas('roles', ['id' => $role->id]);
    }

    public function test_role_in_use_cannot_be_deleted(): void
    {
        $this->seedRoles();
        $user = $this->userWithPermissions(['roles.delete']);
        $role = Role::create(['name' => 'Petugas']);
        User::factory()->create()->syncRoles(['Petugas']);

        $response = $this->actingAs($user)
            ->from('/admin/roles')
            ->delete("/admin/roles/{$role->id}");

        $response->assertSessionHas('error', 'Role masih digunakan oleh user dan tidak bisa dihapus.');
        $this->assertDatabaseHas('roles', ['id' => $role->id]);
    }

    public function test_unused_role_can_be_deleted(): void
    {
        $this->seedRoles();
        $user = $this->userWithPermissions(['roles.delete']);
        $role = Role::create(['name' => 'Petugas']);

        $response = $this->actingAs($user)
            ->from('/admin/roles')
            ->delete("/admin/roles/{$role->id}");

        $response->assertRedirect(route('admin.roles.index'));
        $response->assertSessionHas('success', 'Role berhasil dihapus.');
        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    public function test_role_permissions_can_be_read(): void
    {
        $this->seedRoles();
        $user = $this->userWithPermissions(['roles.permissions']);
        $role = Role::findByName('Coach');
        $role->syncPermissions(['matches.view']);

        $response = $this->actingAs($user)->getJson("/admin/roles/{$role->id}/permissions");

        $response->assertOk();
        $response->assertJson([
            'id' => $role->id,
            'name' => 'Coach',
        ]);
        $this->assertContains('matches.view', $response->json('permissions'));
    }

    public function test_role_permissions_can_be_updated(): void
    {
        $this->seedRoles();
        $user = $this->userWithPermissions(['roles.permissions']);
        $role = Role::create(['name' => 'Juri']);

        $response = $this->actingAs($user)
            ->from('/admin/roles')
            ->put("/admin/roles/{$role->id}/permissions", [
                'permissions' => ['matches.view', 'results.view'],
            ]);

        $response->assertSessionHas(
            'success',
            'Permission untuk role Juri berhasil diperbarui.'
        );
        $this->assertEqualsCanonicalizing(
            ['matches.view', 'results.view'],
            $role->fresh()->permissions()->pluck('name')->all()
        );
    }

    public function test_role_permissions_reject_unknown_permission(): void
    {
        $this->seedRoles();
        $user = $this->userWithPermissions(['roles.permissions']);
        $role = Role::create(['name' => 'Juri']);

        $response = $this->actingAs($user)
            ->from('/admin/roles')
            ->put("/admin/roles/{$role->id}/permissions", [
                'permissions' => ['permission.tidak.ada'],
            ]);

        $response->assertSessionHasErrors('permissions.0');
    }

    public function test_configured_permissions_missing_in_db_are_synced(): void
    {
        $this->seedRoles();
        $user = $this->userWithPermissions(['roles.view', 'roles.permissions']);
        $role = Role::create(['name' => 'Juri']);

        // Permission baru di config belum ada di tabel permissions.
        Permission::where('name', 'settings.view')->delete();

        $this->actingAs($user)->get('/admin/roles')->assertOk();
        $this->assertDatabaseHas('permissions', ['name' => 'settings.view']);

        // Submit tetap sukses walau permission hilang lagi dari tabel.
        Permission::where('name', 'settings.view')->delete();

        $response = $this->actingAs($user)
            ->from('/admin/roles')
            ->put("/admin/roles/{$role->id}/permissions", [
                'permissions' => ['settings.view', 'matches.view'],
            ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('permissions', ['name' => 'settings.view']);
        $this->assertEqualsCanonicalizing(
            ['settings.view', 'matches.view'],
            $role->fresh()->permissions()->pluck('name')->all()
        );
    }

    public function test_role_module_requires_permission(): void
    {
        $this->seedRoles();
        $viewer = $this->userWithPermissions(['roles.view']);
        $role = Role::create(['name' => 'Petugas']);

        $this->actingAs($viewer)->get('/admin/roles')->assertOk();
        $this->actingAs($viewer)
            ->post('/admin/roles', ['name' => 'Baru'])
            ->assertForbidden();
        $this->actingAs($viewer)
            ->put("/admin/roles/{$role->id}/permissions", ['permissions' => []])
            ->assertForbidden();
    }
}
