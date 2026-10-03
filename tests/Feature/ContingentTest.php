<?php

namespace Tests\Feature;

use Database\Factories\AthleteFactory;
use Database\Factories\ContingentFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class ContingentTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    public function test_contingent_index_can_be_rendered(): void
    {
        $user = $this->userWithPermissions(['contingents.view']);
        ContingentFactory::new()->create(['name' => 'Tim Merdeka']);

        $response = $this->actingAs($user)->get('/admin/contingents');

        $response->assertOk();
        $response->assertSee('Tim Merdeka');
    }

    public function test_contingent_index_requires_permission(): void
    {
        $user = $this->userWithPermissions([]);

        $this->actingAs($user)->get('/admin/contingents')->assertForbidden();
    }

    public function test_contingent_can_be_searched(): void
    {
        $user = $this->userWithPermissions(['contingents.view']);
        ContingentFactory::new()->create(['name' => 'Tim Alpha', 'code' => 'ALP1']);
        ContingentFactory::new()->create(['name' => 'Tim Beta', 'code' => 'BET2']);

        $response = $this->actingAs($user)->get('/admin/contingents?q=Beta');

        $response->assertOk();
        $response->assertSee('Tim Beta');
        $response->assertDontSee('Tim Alpha');
    }

    public function test_contingent_can_be_created(): void
    {
        $user = $this->userWithPermissions(['contingents.create']);

        $response = $this->actingAs($user)->post('/admin/contingents', [
            'name' => 'Tim Baru',
            'code' => 'BARU1',
            'region' => 'Bandung',
            'coach_name' => 'Coach Baru',
        ]);

        $response->assertRedirect(route('admin.contingents.index'));
        $response->assertSessionHas('success', 'Kontingen berhasil ditambahkan.');
        $this->assertDatabaseHas('contingents', [
            'name' => 'Tim Baru',
            'code' => 'BARU1',
            'region' => 'Bandung',
        ]);
    }

    public function test_duplicate_contingent_code_is_rejected(): void
    {
        $user = $this->userWithPermissions(['contingents.create']);
        ContingentFactory::new()->create(['code' => 'DUP99']);

        $response = $this->actingAs($user)->from('/admin/contingents')->post('/admin/contingents', [
            'name' => 'Tim Duplikat',
            'code' => 'DUP99',
        ]);

        $response->assertSessionHasErrors('code', 'Kode kontingen sudah digunakan.');
        $this->assertDatabaseCount('contingents', 1);
    }

    public function test_contingent_can_be_updated(): void
    {
        $user = $this->userWithPermissions(['contingents.update']);
        $contingent = ContingentFactory::new()->create();

        $response = $this->actingAs($user)->put("/admin/contingents/{$contingent->id}", [
            'name' => 'Tim Hasil Edit',
            'code' => $contingent->code,
            'region' => 'Surabaya',
            'coach_name' => 'Coach Edit',
        ]);

        $response->assertRedirect(route('admin.contingents.index'));
        $response->assertSessionHas('success', 'Kontingen berhasil diperbarui.');
        $this->assertDatabaseHas('contingents', [
            'id' => $contingent->id,
            'name' => 'Tim Hasil Edit',
            'region' => 'Surabaya',
        ]);
    }

    public function test_contingent_with_athletes_cannot_be_deleted(): void
    {
        $user = $this->userWithPermissions(['contingents.delete']);
        $contingent = ContingentFactory::new()->create();
        AthleteFactory::new()->create(['contingent_id' => $contingent->id]);

        $response = $this->actingAs($user)->delete("/admin/contingents/{$contingent->id}");

        $response->assertSessionHas('error', 'Kontingen tidak bisa dihapus karena masih memiliki atlet.');
        $this->assertDatabaseHas('contingents', ['id' => $contingent->id]);
    }

    public function test_empty_contingent_can_be_deleted(): void
    {
        $user = $this->userWithPermissions(['contingents.delete']);
        $contingent = ContingentFactory::new()->create();

        $response = $this->actingAs($user)->delete("/admin/contingents/{$contingent->id}");

        $response->assertRedirect(route('admin.contingents.index'));
        $response->assertSessionHas('success', 'Kontingen berhasil dihapus.');
        $this->assertDatabaseMissing('contingents', ['id' => $contingent->id]);
    }

    public function test_contingent_creation_requires_permission(): void
    {
        $user = $this->userWithPermissions(['contingents.view']);

        $response = $this->actingAs($user)->post('/admin/contingents', [
            'name' => 'Tanpa Hak',
            'code' => 'TANPA1',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('contingents', 0);
    }
}
