<?php

namespace Tests\Feature;

use Database\Factories\ArenaFactory;
use Database\Factories\AthleteFactory;
use Database\Factories\CategoryFactory;
use Database\Factories\ContingentFactory;
use Database\Factories\MatchupFactory;
use Database\Factories\ScheduleFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

/**
 * Rules that keep the arena and the athletes free of conflicts.
 */
class ScheduleGuardTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    public function test_schedule_cannot_be_created_when_the_arena_is_still_busy(): void
    {
        $user = $this->userWithPermissions(['schedules.create']);
        $category = CategoryFactory::new()->daeryun()->create();
        $arena = ArenaFactory::new()->create();
        $busy = ScheduleFactory::new()->running()->create(['arena_id' => $arena->id]);

        $response = $this->actingAs($user)->from('/admin/schedules')->post('/admin/schedules', [
            'match_no' => 'X-101',
            'category_id' => $category->id,
            'arena_id' => $arena->id,
            'type' => 'daeryun',
            'match_date' => now()->toDateString(),
            'start_time' => '13:00',
            'end_time' => '14:00',
            'status' => 'pending',
        ]);

        $response->assertSessionHas('error', fn ($message) => str_contains($message, 'sedang digunakan oleh'));
        $this->assertDatabaseMissing('schedules', ['match_no' => 'X-101']);
    }

    public function test_schedule_cannot_be_created_when_the_time_overlaps(): void
    {
        $user = $this->userWithPermissions(['schedules.create']);
        $category = CategoryFactory::new()->daeryun()->create();
        $arena = ArenaFactory::new()->create();
        $existing = ScheduleFactory::new()->create([
            'arena_id' => $arena->id,
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'match_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($user)->from('/admin/schedules')->post('/admin/schedules', [
            'match_no' => 'X-102',
            'category_id' => $category->id,
            'arena_id' => $arena->id,
            'type' => 'daeryun',
            'match_date' => now()->toDateString(),
            'start_time' => '09:30',
            'end_time' => '10:30',
            'status' => 'pending',
        ]);

        $response->assertSessionHas('error', fn ($message) => str_contains($message, 'bentrok dengan'));
        $this->assertDatabaseMissing('schedules', ['match_no' => 'X-102']);
    }

    public function test_schedule_rejects_an_end_time_before_the_start_time(): void
    {
        $user = $this->userWithPermissions(['schedules.create']);
        $category = CategoryFactory::new()->daeryun()->create();
        $arena = ArenaFactory::new()->create();

        $response = $this->actingAs($user)->from('/admin/schedules')->post('/admin/schedules', [
            'match_no' => 'X-103',
            'category_id' => $category->id,
            'arena_id' => $arena->id,
            'type' => 'daeryun',
            'match_date' => now()->toDateString(),
            'start_time' => '14:00',
            'end_time' => '13:00',
            'status' => 'pending',
        ]);

        $response->assertSessionHas('error', 'Jam selesai harus lebih besar dari jam mulai.');
        $this->assertDatabaseMissing('schedules', ['match_no' => 'X-103']);
    }

    public function test_schedule_can_be_created_when_the_arena_is_free(): void
    {
        $user = $this->userWithPermissions(['schedules.create']);
        $category = CategoryFactory::new()->daeryun()->create();
        $arena = ArenaFactory::new()->create();
        ScheduleFactory::new()->create([
            'arena_id' => $arena->id,
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'match_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($user)->post('/admin/schedules', [
            'match_no' => 'X-104',
            'category_id' => $category->id,
            'arena_id' => $arena->id,
            'type' => 'daeryun',
            'match_date' => now()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '11:00',
            'status' => 'pending',
        ]);

        $response->assertSessionHas('success', 'Jadwal berhasil ditambahkan.');
        $this->assertDatabaseHas('schedules', ['match_no' => 'X-104']);
    }

    public function test_running_schedule_cannot_be_deleted(): void
    {
        $user = $this->userWithPermissions(['schedules.delete']);
        $schedule = ScheduleFactory::new()->running()->create();

        $response = $this->actingAs($user)
            ->from('/admin/schedules')
            ->delete("/admin/schedules/{$schedule->id}");

        $response->assertSessionHas('error', 'Jadwal tidak bisa dihapus saat pertandingan sedang berlangsung.');
        $this->assertDatabaseHas('schedules', ['id' => $schedule->id]);
    }

    public function test_match_cannot_start_when_an_athlete_competes_in_another_arena(): void
    {
        $user = $this->userWithPermissions(['matches.start']);
        $category = CategoryFactory::new()->daeryun()->create();
        $contingent = ContingentFactory::new()->create();
        $athlete = AthleteFactory::new()->create([
            'contingent_id' => $contingent->id,
            'category_id' => $category->id,
        ]);

        // The same athlete is already on stage in another arena.
        $otherArena = ArenaFactory::new()->create();
        $otherSchedule = ScheduleFactory::new()->running()->create(['arena_id' => $otherArena->id]);
        MatchupFactory::new()->create([
            'schedule_id' => $otherSchedule->id,
            'athlete_a_id' => $athlete->id,
            'athlete_b_id' => AthleteFactory::new()->create([
                'contingent_id' => $contingent->id,
                'category_id' => $category->id,
            ])->id,
            'status' => 'running',
            'started_at' => now(),
        ]);

        $arena = ArenaFactory::new()->create();
        $schedule = ScheduleFactory::new()->create(['category_id' => $category->id, 'arena_id' => $arena->id]);
        $match = MatchupFactory::new()->create([
            'schedule_id' => $schedule->id,
            'athlete_a_id' => $athlete->id,
            'athlete_b_id' => AthleteFactory::new()->create([
                'contingent_id' => $contingent->id,
                'category_id' => $category->id,
            ])->id,
        ]);

        $response = $this->actingAs($user)
            ->from('/admin/matches')
            ->post("/admin/matches/{$match->id}/start");

        $response->assertSessionHas('error', fn ($message) => str_contains($message, 'sedang tampil pada'));
        $this->assertDatabaseHas('matches', ['id' => $match->id, 'status' => 'pending']);
    }
}
