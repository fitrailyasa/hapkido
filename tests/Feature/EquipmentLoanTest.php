<?php

namespace Tests\Feature;

use App\Models\EquipmentLoan;
use Database\Factories\AthleteFactory;
use Database\Factories\EquipmentFactory;
use Database\Factories\EquipmentLoanFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class EquipmentLoanTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    public function test_equipment_loan_index_can_be_rendered(): void
    {
        $user = $this->userWithPermissions(['equipment-loans.view']);
        $loan = EquipmentLoanFactory::new()->create();

        $response = $this->actingAs($user)->get('/admin/equipment-loans');

        $response->assertOk();
        $response->assertSee($loan->equipment->code);
    }

    public function test_equipment_can_borrowed(): void
    {
        $user = $this->userWithPermissions(['equipment-loans.borrow']);
        $equipment = EquipmentFactory::new()->create(['total_qty' => 5, 'available_qty' => 5, 'status' => 'available']);
        $athlete = AthleteFactory::new()->create();

        $response = $this->actingAs($user)->post('/admin/equipment-loans', [
            'athlete_id' => $athlete->id,
            'equipment_id' => $equipment->id,
            'qty' => 2,
            'notes' => 'Dipakai latihan',
        ]);

        $response->assertRedirect(route('admin.equipment-loans.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('equipment_loans', [
            'equipment_id' => $equipment->id,
            'athlete_id' => $athlete->id,
            'qty' => 2,
            'status' => 'loaned',
            'loaned_by' => $user->id,
            'notes' => 'Dipakai latihan',
        ]);
        $this->assertDatabaseHas('equipments', [
            'id' => $equipment->id,
            'available_qty' => 3,
            'status' => 'available',
        ]);
    }

    public function test_equipment_status_becomes_loaned_when_stock_is_empty(): void
    {
        $user = $this->userWithPermissions(['equipment-loans.borrow']);
        $equipment = EquipmentFactory::new()->create(['total_qty' => 2, 'available_qty' => 2]);
        $athlete = AthleteFactory::new()->create();

        $this->actingAs($user)->post('/admin/equipment-loans', [
            'athlete_id' => $athlete->id,
            'equipment_id' => $equipment->id,
            'qty' => 2,
        ]);

        $this->assertDatabaseHas('equipments', [
            'id' => $equipment->id,
            'available_qty' => 0,
            'status' => 'loaned',
        ]);
    }

    public function test_borrowing_more_than_stock_is_rejected(): void
    {
        $user = $this->userWithPermissions(['equipment-loans.borrow']);
        $equipment = EquipmentFactory::new()->create(['total_qty' => 2, 'available_qty' => 2]);
        $athlete = AthleteFactory::new()->create();

        $response = $this->actingAs($user)
            ->from('/admin/equipment-loans')
            ->post('/admin/equipment-loans', [
                'athlete_id' => $athlete->id,
                'equipment_id' => $equipment->id,
                'qty' => 5,
            ]);

        $response->assertSessionHas(
            'error',
            "Stok {$equipment->code} tidak mencukupi: tersedia 2 dari total 2."
        );
        $this->assertSame(0, EquipmentLoan::count());
        $this->assertDatabaseHas('equipments', ['id' => $equipment->id, 'available_qty' => 2]);
    }

    public function test_borrowing_maintenance_equipment_is_rejected(): void
    {
        $user = $this->userWithPermissions(['equipment-loans.borrow']);
        $equipment = EquipmentFactory::new()->create(['status' => 'maintenance', 'available_qty' => 3]);
        $athlete = AthleteFactory::new()->create();

        $response = $this->actingAs($user)
            ->from('/admin/equipment-loans')
            ->post('/admin/equipment-loans', [
                'athlete_id' => $athlete->id,
                'equipment_id' => $equipment->id,
                'qty' => 1,
            ]);

        $response->assertSessionHas(
            'error',
            "Perlengkapan {$equipment->code} sedang dalam perawatan."
        );
        $this->assertSame(0, EquipmentLoan::count());
    }

    public function test_borrow_requires_valid_quantity(): void
    {
        $user = $this->userWithPermissions(['equipment-loans.borrow']);
        $equipment = EquipmentFactory::new()->create();
        $athlete = AthleteFactory::new()->create();

        $response = $this->actingAs($user)
            ->from('/admin/equipment-loans')
            ->post('/admin/equipment-loans', [
                'athlete_id' => $athlete->id,
                'equipment_id' => $equipment->id,
                'qty' => 0,
            ]);

        $response->assertSessionHasErrors('qty', 'Jumlah pinjam minimal 1.');
    }

    public function test_equipment_can_be_returned(): void
    {
        $user = $this->userWithPermissions(['equipment-loans.return']);
        $equipment = EquipmentFactory::new()->create([
            'total_qty' => 5,
            'available_qty' => 4,
            'status' => 'loaned',
        ]);
        $loan = EquipmentLoanFactory::new()->create([
            'equipment_id' => $equipment->id,
            'qty' => 1,
            'status' => 'loaned',
        ]);

        $response = $this->actingAs($user)
            ->from('/admin/equipment-loans')
            ->post("/admin/equipment-loans/{$loan->id}/return", [
                'return_condition' => 'Rusak ringan',
            ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('equipment_loans', [
            'id' => $loan->id,
            'status' => 'returned',
            'returned_by' => $user->id,
            'return_condition' => 'Rusak ringan',
        ]);
        $this->assertDatabaseHas('equipments', [
            'id' => $equipment->id,
            'available_qty' => 5,
            'status' => 'available',
        ]);
        $this->assertNotNull($loan->fresh()->returned_at);
    }

    public function test_equipment_cannot_be_returned_twice(): void
    {
        $user = $this->userWithPermissions(['equipment-loans.return']);
        $loan = EquipmentLoanFactory::new()->returned()->create();

        $response = $this->actingAs($user)
            ->from('/admin/equipment-loans')
            ->post("/admin/equipment-loans/{$loan->id}/return");

        $response->assertSessionHas('error', 'Peminjaman ini sudah dikembalikan sebelumnya.');
    }

    public function test_loan_module_requires_permissions(): void
    {
        $viewUser = $this->userWithPermissions(['equipment-loans.view']);
        $athlete = AthleteFactory::new()->create();
        $equipment = EquipmentFactory::new()->create();

        $this->actingAs($viewUser)->get('/admin/equipment-loans')->assertOk();
        $this->actingAs($viewUser)->post('/admin/equipment-loans', [
            'athlete_id' => $athlete->id,
            'equipment_id' => $equipment->id,
            'qty' => 1,
        ])->assertForbidden();
    }
}
