<?php

namespace Tests\Feature;

use Database\Factories\AthleteFactory;
use Database\Factories\CategoryFactory;
use Database\Factories\ContingentFactory;
use Database\Factories\PerformanceFactory;
use Database\Factories\ScheduleFactory;
use Database\Factories\ScoreFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class PerformanceTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    private function artScheduleWithAthlete(array $scheduleAttributes = []): array
    {
        $category = CategoryFactory::new()->art()->create();
        $contingent = ContingentFactory::new()->create();
        $athlete = AthleteFactory::new()->create([
            'contingent_id' => $contingent->id,
            'category_id' => $category->id,
        ]);
        $schedule = ScheduleFactory::new()->art()->create(array_merge(
            ['category_id' => $category->id],
            $scheduleAttributes
        ));

        return [$schedule, $athlete, $category];
    }

    public function test_performance_index_can_be_rendered(): void
    {
        $user = $this->userWithPermissions(['performances.view']);
        [$schedule, $athlete] = $this->artScheduleWithAthlete();
        PerformanceFactory::new()->create([
            'schedule_id' => $schedule->id,
            'athlete_id' => $athlete->id,
            'order_no' => 1,
        ]);

        $response = $this->actingAs($user)->get('/admin/performances');

        $response->assertOk();
        $response->assertSee($schedule->match_no);
        $response->assertSee($athlete->name);
    }

    public function test_athlete_can_be_added_to_art_schedule(): void
    {
        $user = $this->userWithPermissions(['performances.create']);
        [$schedule, $athlete] = $this->artScheduleWithAthlete();

        $response = $this->actingAs($user)
            ->from('/admin/performances')
            ->post('/admin/performances', [
                'schedule_id' => $schedule->id,
                'athlete_id' => $athlete->id,
                'order_no' => 2,
            ]);

        $response->assertSessionHas('success', "Penampil {$athlete->name} berhasil ditambahkan.");
        $this->assertDatabaseHas('performances', [
            'schedule_id' => $schedule->id,
            'athlete_id' => $athlete->id,
            'order_no' => 2,
            'status' => 'waiting',
        ]);
    }

    public function test_athlete_from_other_category_is_rejected(): void
    {
        $user = $this->userWithPermissions(['performances.create']);
        [$schedule] = $this->artScheduleWithAthlete();
        $otherAthlete = AthleteFactory::new()->create();

        $response = $this->actingAs($user)
            ->from('/admin/performances')
            ->post('/admin/performances', [
                'schedule_id' => $schedule->id,
                'athlete_id' => $otherAthlete->id,
                'order_no' => 1,
            ]);

        $response->assertSessionHas('error', 'Atlet yang dipilih bukan berasal dari kategori jadwal tersebut.');
        $this->assertSame(0, \App\Models\Performance::count());
    }

    public function test_duplicate_athlete_on_schedule_is_rejected(): void
    {
        $user = $this->userWithPermissions(['performances.create']);
        [$schedule, $athlete] = $this->artScheduleWithAthlete();
        PerformanceFactory::new()->create([
            'schedule_id' => $schedule->id,
            'athlete_id' => $athlete->id,
        ]);

        $response = $this->actingAs($user)
            ->from('/admin/performances')
            ->post('/admin/performances', [
                'schedule_id' => $schedule->id,
                'athlete_id' => $athlete->id,
                'order_no' => 3,
            ]);

        $response->assertSessionHas('error', 'Atlet tersebut sudah terdaftar pada jadwal ini.');
        $this->assertSame(1, \App\Models\Performance::count());
    }

    public function test_daeryun_schedule_is_not_accepted(): void
    {
        $user = $this->userWithPermissions(['performances.create']);
        $daeryun = CategoryFactory::new()->daeryun()->create();
        $schedule = ScheduleFactory::new()->create(['category_id' => $daeryun->id, 'type' => 'daeryun']);
        $athlete = AthleteFactory::new()->create(['category_id' => $daeryun->id]);

        $response = $this->actingAs($user)
            ->from('/admin/performances')
            ->post('/admin/performances', [
                'schedule_id' => $schedule->id,
                'athlete_id' => $athlete->id,
                'order_no' => 1,
            ]);

        $response->assertSessionHasErrors('schedule_id', 'Jadwal seni yang dipilih tidak ditemukan.');
    }

    public function test_performance_status_can_be_updated_to_performing(): void
    {
        $user = $this->userWithPermissions(['performances.update']);
        $performance = PerformanceFactory::new()->create(['status' => 'waiting']);

        $response = $this->actingAs($user)
            ->from('/admin/performances')
            ->post("/admin/performances/{$performance->id}/status", ['status' => 'performing']);

        $response->assertSessionHas('success', 'Status tampil diperbarui menjadi "Tampil".');
        $this->assertDatabaseHas('performances', ['id' => $performance->id, 'status' => 'performing']);
        $this->assertNotNull($performance->fresh()->performed_at);
        $this->assertDatabaseHas('schedules', [
            'id' => $performance->schedule_id,
            'status' => 'running',
        ]);
    }

    public function test_performance_status_can_be_finished(): void
    {
        $user = $this->userWithPermissions(['performances.update']);
        $performance = PerformanceFactory::new()->create(['status' => 'performing']);

        $response = $this->actingAs($user)
            ->from('/admin/performances')
            ->post("/admin/performances/{$performance->id}/status", ['status' => 'finished']);

        $response->assertSessionHas('success', 'Status tampil diperbarui menjadi "Selesai".');
        $this->assertDatabaseHas('performances', ['id' => $performance->id, 'status' => 'finished']);
    }

    public function test_finishing_last_performance_finishes_schedule(): void
    {
        $user = $this->userWithPermissions(['performances.update']);
        $performance = PerformanceFactory::new()->create(['status' => 'performing']);

        $this->actingAs($user)
            ->from('/admin/performances')
            ->post("/admin/performances/{$performance->id}/status", ['status' => 'finished']);

        $this->assertDatabaseHas('schedules', [
            'id' => $performance->schedule_id,
            'status' => 'finished',
        ]);
        $this->assertNotNull(\App\Models\Schedule::find($performance->schedule_id)->end_time);
    }

    public function test_same_status_returns_info_message(): void
    {
        $user = $this->userWithPermissions(['performances.update']);
        $performance = PerformanceFactory::new()->create(['status' => 'waiting']);

        $response = $this->actingAs($user)
            ->from('/admin/performances')
            ->post("/admin/performances/{$performance->id}/status", ['status' => 'waiting']);

        $response->assertSessionHas('info', 'Status penampil sudah seperti itu.');
    }

    public function test_performance_status_validation(): void
    {
        $user = $this->userWithPermissions(['performances.update']);
        $performance = PerformanceFactory::new()->create();

        $response = $this->actingAs($user)
            ->from('/admin/performances')
            ->post("/admin/performances/{$performance->id}/status", ['status' => 'salah']);

        $response->assertSessionHasErrors('status', 'Status penampil tidak valid.');
    }

    public function test_performance_module_requires_permission(): void
    {
        $user = $this->userWithPermissions(['performances.view']);
        $performance = PerformanceFactory::new()->create();

        $this->actingAs($user)->get('/admin/performances')->assertOk();
        $this->actingAs($user)
            ->post("/admin/performances/{$performance->id}/status", ['status' => 'finished'])
            ->assertForbidden();
    }
}
