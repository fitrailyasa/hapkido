<?php

namespace Tests\Feature;

use Database\Factories\EquipmentFactory;
use Database\Factories\EquipmentLoanFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class EquipmentTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    public function test_equipment_index_can_be_rendered(): void
    {
        $user = $this->userWithPermissions(['equipments.view']);
        EquipmentFactory::new()->create(['code' => 'HG-001', 'type' => 'head_guard']);

        $response = $this->actingAs($user)->get('/admin/equipments');

        $response->assertOk();
        $response->assertSee('HG-001');
    }

    public function test_equipment_can_be_created(): void
    {
        $user = $this->userWithPermissions(['equipments.create']);

        $response = $this->actingAs($user)->post('/admin/equipments', [
            'type' => 'body_protector',
            'color' => 'Biru',
            'size' => 'L',
            'code' => 'BP-001',
            'status' => 'available',
            'total_qty' => 4,
        ]);

        $response->assertRedirect(route('admin.equipments.index'));
        $response->assertSessionHas('success', 'Perlengkapan BP-001 berhasil ditambahkan.');
        $this->assertDatabaseHas('equipments', [
            'code' => 'BP-001',
            'type' => 'body_protector',
            'total_qty' => 4,
            'available_qty' => 4,
        ]);
    }

    public function test_equipment_rejects_invalid_type(): void
    {
        $user = $this->userWithPermissions(['equipments.create']);

        $response = $this->actingAs($user)->from('/admin/equipments')->post('/admin/equipments', [
            'type' => 'sarung_tangan',
            'color' => 'Merah',
            'size' => 'M',
            'code' => 'XX-1',
            'status' => 'available',
            'total_qty' => 1,
        ]);

        $response->assertSessionHasErrors('type', 'Jenis perlengkapan tidak valid.');
        $this->assertDatabaseCount('equipments', 0);
    }

    public function test_duplicate_equipment_code_is_rejected(): void
    {
        $user = $this->userWithPermissions(['equipments.create']);
        EquipmentFactory::new()->create(['code' => 'DUP-EQ']);

        $response = $this->actingAs($user)->from('/admin/equipments')->post('/admin/equipments', [
            'type' => 'head_guard',
            'color' => 'Putih',
            'size' => 'S',
            'code' => 'DUP-EQ',
            'status' => 'available',
            'total_qty' => 1,
        ]);

        $response->assertSessionHasErrors('code', 'Kode perlengkapan sudah digunakan.');
    }

    public function test_equipment_can_be_updated(): void
    {
        $user = $this->userWithPermissions(['equipments.update']);
        $equipment = EquipmentFactory::new()->create(['total_qty' => 5, 'available_qty' => 5]);

        $response = $this->actingAs($user)->put("/admin/equipments/{$equipment->id}", [
            'type' => $equipment->type,
            'color' => 'Hitam',
            'size' => 'XL',
            'code' => $equipment->code,
            'status' => 'available',
            'total_qty' => 8,
        ]);

        $response->assertRedirect(route('admin.equipments.index'));
        $response->assertSessionHas('success', "Perlengkapan {$equipment->code} berhasil diperbarui.");
        $this->assertDatabaseHas('equipments', [
            'id' => $equipment->id,
            'color' => 'Hitam',
            'total_qty' => 8,
            'available_qty' => 8,
        ]);
    }

    public function test_total_below_loaned_quantity_is_rejected(): void
    {
        $user = $this->userWithPermissions(['equipments.update']);
        $equipment = EquipmentFactory::new()->create(['total_qty' => 5, 'available_qty' => 3]);
        EquipmentLoanFactory::new()->create([
            'equipment_id' => $equipment->id,
            'qty' => 2,
            'status' => 'loaned',
        ]);

        $response = $this->actingAs($user)
            ->from('/admin/equipments')
            ->put("/admin/equipments/{$equipment->id}", [
                'type' => $equipment->type,
                'color' => $equipment->color,
                'size' => $equipment->size,
                'code' => $equipment->code,
                'status' => 'available',
                'total_qty' => 1,
            ]);

        $response->assertSessionHas(
            'error',
            'Total stok tidak boleh kurang dari jumlah yang sedang dipinjam (2).'
        );
        $this->assertDatabaseHas('equipments', ['id' => $equipment->id, 'total_qty' => 5]);
    }

    public function test_equipment_with_active_loan_cannot_be_deleted(): void
    {
        $user = $this->userWithPermissions(['equipments.delete']);
        $equipment = EquipmentFactory::new()->create();
        EquipmentLoanFactory::new()->create(['equipment_id' => $equipment->id, 'status' => 'loaned']);

        $response = $this->actingAs($user)->delete("/admin/equipments/{$equipment->id}");

        $response->assertSessionHas('error', "Tidak bisa menghapus {$equipment->code}, masih ada 1 peminjaman aktif.");
        $this->assertDatabaseHas('equipments', ['id' => $equipment->id]);
    }

    public function test_equipment_can_be_deleted(): void
    {
        $user = $this->userWithPermissions(['equipments.delete']);
        $equipment = EquipmentFactory::new()->create();

        $response = $this->actingAs($user)->delete("/admin/equipments/{$equipment->id}");

        $response->assertRedirect(route('admin.equipments.index'));
        $response->assertSessionHas('success', "Perlengkapan {$equipment->code} berhasil dihapus.");
        $this->assertDatabaseMissing('equipments', ['id' => $equipment->id]);
    }

    public function test_equipment_module_requires_permission(): void
    {
        $user = $this->userWithPermissions(['equipments.view']);
        $equipment = EquipmentFactory::new()->create();

        $this->actingAs($user)->get('/admin/equipments')->assertOk();
        $this->actingAs($user)->delete("/admin/equipments/{$equipment->id}")->assertForbidden();
    }
}
