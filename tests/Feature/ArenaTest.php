<?php

namespace Tests\Feature;

use Database\Factories\ArenaFactory;
use Database\Factories\CategoryFactory;
use Database\Factories\ScheduleFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class ArenaTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    public function test_arena_index_can_be_rendered(): void
    {
        $user = $this->userWithPermissions(['arenas.view']);
        ArenaFactory::new()->create(['name' => 'Arena Cabor 1', 'label' => 'C1']);

        $response = $this->actingAs($user)->get('/admin/arenas');

        $response->assertOk();
        $response->assertSee('Arena Cabor 1');
    }

    public function test_arena_can_be_created(): void
    {
        $user = $this->userWithPermissions(['arenas.create']);
        $category = CategoryFactory::new()->create();

        $response = $this->actingAs($user)->post('/admin/arenas', [
            'name' => 'Arena Baru',
            'label' => 'B1',
            'category_id' => $category->id,
            'match_type' => 'daeryun',
            'status' => 'idle',
        ]);

        $response->assertRedirect(route('admin.arenas.index'));
        $response->assertSessionHas('success', 'Arena Arena Baru berhasil ditambahkan.');
        $this->assertDatabaseHas('arenas', [
            'name' => 'Arena Baru',
            'label' => 'B1',
            'match_type' => 'daeryun',
        ]);
    }

    public function test_duplicate_arena_name_is_rejected(): void
    {
        $user = $this->userWithPermissions(['arenas.create']);
        ArenaFactory::new()->create(['name' => 'Arena Sama']);

        $response = $this->actingAs($user)->from('/admin/arenas')->post('/admin/arenas', [
            'name' => 'Arena Sama',
            'label' => 'X1',
            'match_type' => 'art',
            'status' => 'idle',
        ]);

        $response->assertSessionHasErrors('name', 'Nama arena sudah digunakan.');
    }

    public function test_arena_status_validation_is_enforced(): void
    {
        $user = $this->userWithPermissions(['arenas.create']);

        $response = $this->actingAs($user)->from('/admin/arenas')->post('/admin/arenas', [
            'name' => 'Arena Validasi',
            'label' => 'V1',
            'match_type' => 'daeryun',
            'status' => 'siap',
        ]);

        $response->assertSessionHasErrors('status');
        $this->assertDatabaseCount('arenas', 0);
    }

    public function test_arena_can_be_updated(): void
    {
        $user = $this->userWithPermissions(['arenas.update']);
        $arena = ArenaFactory::new()->create();

        $response = $this->actingAs($user)->put("/admin/arenas/{$arena->id}", [
            'name' => 'Arena Diubah',
            'label' => 'ZZ',
            'category_id' => '',
            'match_type' => 'art',
            'status' => 'preparation',
        ]);

        $response->assertSessionHas('success', 'Arena berhasil diperbarui.');
        $this->assertDatabaseHas('arenas', [
            'id' => $arena->id,
            'name' => 'Arena Diubah',
            'status' => 'preparation',
            'category_id' => null,
        ]);
    }

    public function test_arena_used_by_schedule_cannot_be_deleted(): void
    {
        $user = $this->userWithPermissions(['arenas.delete']);
        $arena = ArenaFactory::new()->create();
        ScheduleFactory::new()->create(['arena_id' => $arena->id]);

        $response = $this->actingAs($user)->delete("/admin/arenas/{$arena->id}");

        $response->assertSessionHas('error', "Arena {$arena->name} masih digunakan jadwal dan tidak bisa dihapus.");
        $this->assertDatabaseHas('arenas', ['id' => $arena->id]);
    }

    public function test_unused_arena_can_be_deleted(): void
    {
        $user = $this->userWithPermissions(['arenas.delete']);
        $arena = ArenaFactory::new()->create();

        $response = $this->actingAs($user)->delete("/admin/arenas/{$arena->id}");

        $response->assertRedirect(route('admin.arenas.index'));
        $response->assertSessionHas('success', "Arena {$arena->name} berhasil dihapus.");
        $this->assertDatabaseMissing('arenas', ['id' => $arena->id]);
    }
}
