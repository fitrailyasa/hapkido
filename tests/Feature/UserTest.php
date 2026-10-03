<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class UserTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    public function test_user_index_can_be_rendered(): void
    {
        $this->seedRoles();
        $user = $this->userWithPermissions(['users.view']);

        $response = $this->actingAs($user)->get('/admin/users');

        $response->assertOk();
        $response->assertSee($user->name);
    }

    public function test_user_can_be_searched(): void
    {
        $this->seedRoles();
        $user = $this->userWithPermissions(['users.view']);
        $other = User::factory()->create(['name' => 'Peserta Khusus']);

        $response = $this->actingAs($user)->get('/admin/users?q=Peserta Khusus');

        $response->assertOk();
        $response->assertSee('Peserta Khusus');
    }

    public function test_user_can_be_created_with_role(): void
    {
        $this->seedRoles();
        $admin = $this->userWithPermissions(['users.create', 'users.view']);

        $response = $this->actingAs($admin)
            ->from('/admin/users')
            ->post('/admin/users', [
                'name' => 'User Baru',
                'email' => 'baru@example.test',
                'password' => 'rahasia123',
                'role' => 'Operator',
                'is_active' => 1,
            ]);

        $response->assertRedirect('/admin/users');
        $response->assertSessionHas('success', 'User berhasil ditambahkan.');
        $this->assertDatabaseHas('users', [
            'name' => 'User Baru',
            'email' => 'baru@example.test',
            'is_active' => true,
        ]);
        $created = User::where('email', 'baru@example.test')->first();
        $this->assertTrue($created->hasRole('Operator'));
        $this->assertTrue(\Hash::check('rahasia123', $created->password));
    }

    public function test_user_creation_validates_email_uniqueness(): void
    {
        $this->seedRoles();
        $admin = $this->userWithPermissions(['users.create']);
        $existing = User::factory()->create();

        $response = $this->actingAs($admin)
            ->from('/admin/users')
            ->post('/admin/users', [
                'name' => 'Duplikat',
                'email' => $existing->email,
                'password' => 'rahasia123',
            ]);

        $response->assertSessionHasErrors('email');
        $this->assertSame(1, User::where('email', $existing->email)->count());
    }

    public function test_user_creation_validates_password_length(): void
    {
        $this->seedRoles();
        $admin = $this->userWithPermissions(['users.create']);

        $response = $this->actingAs($admin)
            ->from('/admin/users')
            ->post('/admin/users', [
                'name' => 'Pendek',
                'email' => 'pendek@example.test',
                'password' => 'abc',
            ]);

        $response->assertSessionHasErrors('password');
        $this->assertDatabaseMissing('users', ['email' => 'pendek@example.test']);
    }

    public function test_user_creation_validates_role(): void
    {
        $this->seedRoles();
        $admin = $this->userWithPermissions(['users.create']);

        $response = $this->actingAs($admin)
            ->from('/admin/users')
            ->post('/admin/users', [
                'name' => 'Tanpa Role',
                'email' => 'tanparole@example.test',
                'password' => 'rahasia123',
                'role' => 'RoleTidakAda',
            ]);

        $response->assertSessionHasErrors('role');
    }

    public function test_user_can_be_updated(): void
    {
        $this->seedRoles();
        $admin = $this->userWithPermissions(['users.update', 'users.view']);
        $target = User::factory()->create(['is_active' => false]);

        $response = $this->actingAs($admin)
            ->from('/admin/users')
            ->put("/admin/users/{$target->id}", [
                'name' => 'Nama Baru',
                'email' => $target->email,
                'role' => 'Coach',
                'is_active' => 1,
            ]);

        $response->assertRedirect('/admin/users');
        $response->assertSessionHas('success', 'User berhasil diperbarui.');
        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'name' => 'Nama Baru',
            'is_active' => true,
        ]);
        $this->assertTrue($target->fresh()->hasRole('Coach'));
    }

    public function test_user_password_can_be_changed_on_update(): void
    {
        $this->seedRoles();
        $admin = $this->userWithPermissions(['users.update']);
        $target = User::factory()->create();

        $this->actingAs($admin)
            ->put("/admin/users/{$target->id}", [
                'name' => $target->name,
                'email' => $target->email,
                'password' => 'barusangat123',
            ]);

        $this->assertTrue(\Hash::check('barusangat123', $target->fresh()->password));
    }

    public function test_current_user_cannot_deactivate_self(): void
    {
        $this->seedRoles();
        $admin = $this->userWithPermissions(['users.update']);

        $response = $this->actingAs($admin)
            ->from('/admin/users')
            ->put("/admin/users/{$admin->id}", [
                'name' => $admin->name,
                'email' => $admin->email,
            ]);

        $response->assertSessionHas('error', 'Tidak bisa menonaktifkan akun yang sedang login.');
        $this->assertTrue((bool) $admin->fresh()->is_active);
    }

    public function test_other_user_can_be_deactivated(): void
    {
        $this->seedRoles();
        $admin = $this->userWithPermissions(['users.update']);
        $target = User::factory()->create(['is_active' => true]);

        $this->actingAs($admin)
            ->put("/admin/users/{$target->id}", [
                'name' => $target->name,
                'email' => $target->email,
            ]);

        $this->assertFalse((bool) $target->fresh()->is_active);
    }

    public function test_user_can_be_deleted(): void
    {
        $this->seedRoles();
        $admin = $this->userWithPermissions(['users.delete']);
        $target = User::factory()->create();

        $response = $this->actingAs($admin)
            ->from('/admin/users')
            ->delete("/admin/users/{$target->id}");

        $response->assertRedirect('/admin/users');
        $response->assertSessionHas('success', 'User berhasil dihapus.');
        $this->assertDatabaseMissing('users', ['id' => $target->id]);
    }

    public function test_current_user_cannot_delete_self(): void
    {
        $this->seedRoles();
        $admin = $this->userWithPermissions(['users.delete']);

        $response = $this->actingAs($admin)
            ->from('/admin/users')
            ->delete("/admin/users/{$admin->id}");

        $response->assertSessionHas('error', 'Tidak bisa menghapus akun yang sedang login.');
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_user_detail_is_returned_as_json(): void
    {
        $this->seedRoles();
        $admin = $this->userWithPermissions(['users.view']);
        $target = User::factory()->create();

        $response = $this->actingAs($admin)->getJson("/admin/users/{$target->id}");

        $response->assertOk();
        $response->assertJson([
            'id' => $target->id,
            'name' => $target->name,
            'email' => $target->email,
        ]);
    }

    public function test_user_module_requires_permission(): void
    {
        $this->seedRoles();
        $viewer = $this->userWithPermissions(['users.view']);

        $this->actingAs($viewer)->get('/admin/users')->assertOk();
        $this->actingAs($viewer)->post('/admin/users', [
            'name' => 'X',
            'email' => 'x@example.test',
            'password' => 'rahasia123',
        ])->assertForbidden();
    }
}
