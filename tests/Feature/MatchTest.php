<?php

namespace Tests\Feature;

use Database\Factories\AthleteFactory;
use Database\Factories\CategoryFactory;
use Database\Factories\ContingentFactory;
use Database\Factories\MatchupFactory;
use Database\Factories\ScheduleFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class MatchTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    private function matchupWithAthletes(array $scheduleAttributes = [], array $matchAttributes = []): array
    {
        $category = CategoryFactory::new()->daeryun()->create();
        $contingent = ContingentFactory::new()->create();
        $athleteA = AthleteFactory::new()->create([
            'contingent_id' => $contingent->id,
            'category_id' => $category->id,
        ]);
        $athleteB = AthleteFactory::new()->create([
            'contingent_id' => $contingent->id,
            'category_id' => $category->id,
        ]);
        $schedule = ScheduleFactory::new()->create(array_merge(
            ['category_id' => $category->id],
            $scheduleAttributes
        ));
        $match = MatchupFactory::new()->create(array_merge([
            'schedule_id' => $schedule->id,
            'athlete_a_id' => $athleteA->id,
            'athlete_b_id' => $athleteB->id,
        ], $matchAttributes));

        return [$match, $schedule, $athleteA, $athleteB];
    }

    public function test_match_index_can_be_rendered(): void
    {
        $user = $this->userWithPermissions(['matches.view']);
        [$match] = $this->matchupWithAthletes();

        $response = $this->actingAs($user)->get('/admin/matches');

        $response->assertOk();
        $response->assertSee($match->schedule->match_no);
    }

    public function test_match_detail_can_be_rendered(): void
    {
        $user = $this->userWithPermissions(['matches.view']);
        [$match] = $this->matchupWithAthletes();

        $response = $this->actingAs($user)->get("/admin/matches/{$match->id}");

        $response->assertOk();
        $response->assertSee($match->athleteA->name);
    }

    public function test_match_can_be_started(): void
    {
        $user = $this->userWithPermissions(['matches.start']);
        [$match, $schedule] = $this->matchupWithAthletes(['start_time' => null]);

        $response = $this->actingAs($user)
            ->from('/admin/matches')
            ->post("/admin/matches/{$match->id}/start");

        $response->assertSessionHas('success', 'Pertandingan dimulai.');
        $this->assertDatabaseHas('matches', ['id' => $match->id, 'status' => 'running']);
        $this->assertNotNull($match->fresh()->started_at);
        $this->assertDatabaseHas('schedules', ['id' => $schedule->id, 'status' => 'running']);
        $this->assertNotNull($schedule->fresh()->start_time);
    }

    public function test_start_rejects_art_schedule(): void
    {
        $user = $this->userWithPermissions(['matches.start']);
        [$match] = $this->matchupWithAthletes(['type' => 'art']);

        $response = $this->actingAs($user)
            ->from('/admin/matches')
            ->post("/admin/matches/{$match->id}/start");

        $response->assertRedirect(route('admin.performances.index'));
        $response->assertSessionHas('error', 'Partai seni dijalankan melalui menu Seni, bukan menu pertandingan.');
        $this->assertDatabaseHas('matches', ['id' => $match->id, 'status' => 'pending']);
    }

    public function test_start_rejects_finished_match(): void
    {
        $user = $this->userWithPermissions(['matches.start']);
        [$match] = $this->matchupWithAthletes([], ['status' => 'finished', 'finished_at' => now()]);

        $response = $this->actingAs($user)
            ->from('/admin/matches')
            ->post("/admin/matches/{$match->id}/start");

        $response->assertSessionHas('error', 'Pertandingan sudah selesai dan tidak bisa dimulai ulang.');
    }

    public function test_start_rejects_running_match(): void
    {
        $user = $this->userWithPermissions(['matches.start']);
        [$match] = $this->matchupWithAthletes([], ['status' => 'running', 'started_at' => now()]);

        $response = $this->actingAs($user)
            ->from('/admin/matches')
            ->post("/admin/matches/{$match->id}/start");

        $response->assertSessionHas('info', 'Pertandingan sudah berlangsung.');
    }

    public function test_start_requires_both_athletes(): void
    {
        $user = $this->userWithPermissions(['matches.start']);
        [$match] = $this->matchupWithAthletes([], ['athlete_b_id' => null]);

        $response = $this->actingAs($user)
            ->from('/admin/matches')
            ->post("/admin/matches/{$match->id}/start");

        $response->assertSessionHas('error', 'Kedua atlet belum ditentukan. Lengkapi bracket terlebih dahulu.');
    }

    public function test_match_result_can_be_saved(): void
    {
        $user = $this->userWithPermissions(['matches.result']);
        [$match, $schedule, $athleteA, $athleteB] = $this->matchupWithAthletes();

        $response = $this->actingAs($user)
            ->from('/admin/matches')
            ->post("/admin/matches/{$match->id}/finish", [
                'winner_id' => $athleteA->id,
                'score_a' => 7,
                'score_b' => 3,
            ]);

        $response->assertSessionHas('success', 'Hasil pertandingan berhasil disimpan.');
        $this->assertDatabaseHas('matches', [
            'id' => $match->id,
            'status' => 'finished',
            'winner_athlete_id' => $athleteA->id,
            'winner_id' => $athleteA->id,
            'score_a' => 7,
            'score_b' => 3,
        ]);
        $this->assertNotNull($match->fresh()->finished_at);
        $this->assertDatabaseHas('schedules', ['id' => $schedule->id, 'status' => 'finished']);
    }

    public function test_finish_rejects_winner_with_lower_score(): void
    {
        $user = $this->userWithPermissions(['matches.result']);
        [$match, , $athleteA, $athleteB] = $this->matchupWithAthletes();

        $response = $this->actingAs($user)
            ->from('/admin/matches')
            ->post("/admin/matches/{$match->id}/finish", [
                'winner_id' => $athleteA->id,
                'score_a' => 1,
                'score_b' => 9,
            ]);

        $response->assertSessionHas('error', 'Skor pemenang tidak boleh lebih rendah dari skor lawan.');
        $this->assertDatabaseHas('matches', ['id' => $match->id, 'status' => 'pending']);
    }

    public function test_finish_rejects_winner_outside_match(): void
    {
        $user = $this->userWithPermissions(['matches.result']);
        [$match] = $this->matchupWithAthletes();
        $outsider = AthleteFactory::new()->create();

        $response = $this->actingAs($user)
            ->from('/admin/matches')
            ->post("/admin/matches/{$match->id}/finish", [
                'winner_id' => $outsider->id,
                'score_a' => 5,
                'score_b' => 1,
            ]);

        $response->assertSessionHasErrors('winner_id', 'Pemenang harus salah satu atlet pada partai ini.');
    }

    public function test_finish_requires_scores(): void
    {
        $user = $this->userWithPermissions(['matches.result']);
        [$match, , $athleteA] = $this->matchupWithAthletes();

        $response = $this->actingAs($user)
            ->from('/admin/matches')
            ->post("/admin/matches/{$match->id}/finish", [
                'winner_id' => $athleteA->id,
            ]);

        $response->assertSessionHasErrors(['score_a', 'score_b']);
    }

    public function test_finish_rejects_art_schedule(): void
    {
        $user = $this->userWithPermissions(['matches.result']);
        [$match, , $athleteA] = $this->matchupWithAthletes(['type' => 'art']);

        $response = $this->actingAs($user)
            ->from('/admin/matches')
            ->post("/admin/matches/{$match->id}/finish", [
                'winner_id' => $athleteA->id,
                'score_a' => 5,
                'score_b' => 2,
            ]);

        $response->assertRedirect(route('admin.performances.index'));
        $response->assertSessionHas('error', 'Penilaian partai seni dilakukan melalui menu Seni (input nilai juri).');
    }

    public function test_match_module_requires_permission(): void
    {
        $user = $this->userWithPermissions(['matches.view']);
        [$match] = $this->matchupWithAthletes();

        $this->actingAs($user)->get('/admin/matches')->assertOk();
        $this->actingAs($user)->post("/admin/matches/{$match->id}/start")->assertForbidden();
        $this->actingAs($user)->get("/admin/matches/{$match->id}")->assertOk();
    }
}
