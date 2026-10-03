<?php

namespace Tests\Feature;

use Database\Factories\ArenaFactory;
use Database\Factories\AthleteFactory;
use Database\Factories\CategoryFactory;
use Database\Factories\ContingentFactory;
use Database\Factories\MatchupFactory;
use Database\Factories\PerformanceFactory;
use Database\Factories\ScheduleFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class AthleteTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    public function test_athlete_index_can_be_rendered(): void
    {
        $user = $this->userWithPermissions(['athletes.view']);
        AthleteFactory::new()->create(['name' => 'Atlet Andalan']);

        $response = $this->actingAs($user)->get('/admin/athletes');

        $response->assertOk();
        $response->assertSee('Atlet Andalan');
    }

    public function test_athlete_index_can_be_filtered(): void
    {
        $user = $this->userWithPermissions(['athletes.view']);
        $contingent = ContingentFactory::new()->create(['name' => 'Tim Utara']);
        $contingent2 = ContingentFactory::new()->create(['name' => 'Tim Selatan']);
        AthleteFactory::new()->create(['name' => 'Atlet Utara', 'contingent_id' => $contingent->id]);
        AthleteFactory::new()->create(['name' => 'Atlet Selatan', 'contingent_id' => $contingent2->id]);

        $response = $this->actingAs($user)->get("/admin/athletes?contingent={$contingent->id}");

        $response->assertOk();
        $response->assertSee('Atlet Utara');
        $response->assertDontSee('Atlet Selatan');
    }

    public function test_athlete_labels_page_can_be_rendered(): void
    {
        $user = $this->userWithPermissions(['athletes.view']);
        AthleteFactory::new()->create(['name' => 'Atlet Label']);

        $response = $this->actingAs($user)->get('/admin/athletes-labels');

        $response->assertOk();
        $response->assertSee('Atlet Label');
    }

    public function test_athlete_can_be_created(): void
    {
        $user = $this->userWithPermissions(['athletes.create']);
        $contingent = ContingentFactory::new()->create();
        $category = CategoryFactory::new()->daeryun()->create();

        $response = $this->actingAs($user)->post('/admin/athletes', [
            'name' => 'Atlet Baru',
            'gender' => 'male',
            'birth_date' => '2005-04-12',
            'id_number' => '1234567890',
            'contingent_id' => $contingent->id,
            'category_id' => $category->id,
            'participant_number' => 'P-0001',
            'qr_code' => '',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('admin.athletes.index'));
        $response->assertSessionHas('success', 'Atlet berhasil ditambahkan.');
        $this->assertDatabaseHas('athletes', [
            'name' => 'Atlet Baru',
            'participant_number' => 'P-0001',
            'qr_code' => 'P-0001',
        ]);
    }

    public function test_duplicate_participant_number_is_rejected(): void
    {
        $user = $this->userWithPermissions(['athletes.create']);
        $athlete = AthleteFactory::new()->create(['participant_number' => 'P-0001']);

        $response = $this->actingAs($user)->from('/admin/athletes')->post('/admin/athletes', [
            'name' => 'Atlet Duplikat',
            'gender' => 'female',
            'contingent_id' => $athlete->contingent_id,
            'category_id' => $athlete->category_id,
            'participant_number' => 'P-0001',
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors('participant_number', 'Nomor peserta sudah digunakan atlet lain.');
    }

    public function test_athlete_detail_is_returned_as_json(): void
    {
        $user = $this->userWithPermissions(['athletes.view']);
        $athlete = AthleteFactory::new()->create(['name' => 'Atlet Detail']);

        $response = $this->actingAs($user)->getJson("/admin/athletes/{$athlete->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'id', 'name', 'gender', 'gender_label', 'birth_date', 'age',
            'contingent', 'region', 'category', 'category_type',
            'participant_number', 'qr_code', 'status', 'status_label', 'photo_url',
        ]);
        $response->assertJsonPath('name', 'Atlet Detail');
    }

    public function test_athlete_can_be_updated(): void
    {
        $user = $this->userWithPermissions(['athletes.update']);
        $athlete = AthleteFactory::new()->create();
        $newCategory = CategoryFactory::new()->art()->create();

        $response = $this->actingAs($user)->put("/admin/athletes/{$athlete->id}", [
            'name' => 'Atlet Diedit',
            'gender' => 'female',
            'birth_date' => '2004-01-01',
            'id_number' => '0987654321',
            'contingent_id' => $athlete->contingent_id,
            'category_id' => $newCategory->id,
            'participant_number' => $athlete->participant_number,
            'qr_code' => $athlete->qr_code,
            'status' => 'inactive',
        ]);

        $response->assertRedirect(route('admin.athletes.index'));
        $response->assertSessionHas('success', 'Data atlet berhasil diperbarui.');
        $this->assertDatabaseHas('athletes', [
            'id' => $athlete->id,
            'name' => 'Atlet Diedit',
            'status' => 'inactive',
            'category_id' => $newCategory->id,
        ]);
    }

    public function test_athlete_in_matchup_cannot_be_deleted(): void
    {
        $user = $this->userWithPermissions(['athletes.delete']);
        $athlete = AthleteFactory::new()->create();
        MatchupFactory::new()->create(['athlete_a_id' => $athlete->id]);

        $response = $this->actingAs($user)->delete("/admin/athletes/{$athlete->id}");

        $response->assertSessionHas('error', 'Atlet tidak bisa dihapus karena sudah terdaftar pada pertandingan.');
        $this->assertDatabaseHas('athletes', ['id' => $athlete->id]);
    }

    public function test_athlete_in_performance_cannot_be_deleted(): void
    {
        $user = $this->userWithPermissions(['athletes.delete']);
        $athlete = AthleteFactory::new()->create();
        PerformanceFactory::new()->create(['athlete_id' => $athlete->id]);

        $response = $this->actingAs($user)->delete("/admin/athletes/{$athlete->id}");

        $response->assertSessionHas('error', 'Atlet tidak bisa dihapus karena sudah terdaftar pada pertandingan.');
        $this->assertDatabaseHas('athletes', ['id' => $athlete->id]);
    }

    public function test_unused_athlete_can_be_deleted(): void
    {
        $user = $this->userWithPermissions(['athletes.delete']);
        $athlete = AthleteFactory::new()->create();

        $response = $this->actingAs($user)->delete("/admin/athletes/{$athlete->id}");

        $response->assertRedirect(route('admin.athletes.index'));
        $response->assertSessionHas('success', 'Atlet berhasil dihapus.');
        $this->assertDatabaseMissing('athletes', ['id' => $athlete->id]);
    }

    public function test_athlete_module_requires_permission(): void
    {
        $user = $this->userWithPermissions(['contingents.view']);

        $this->actingAs($user)->get('/admin/athletes')->assertForbidden();
        $this->actingAs($user)->get('/admin/athletes-labels')->assertForbidden();
    }
}
