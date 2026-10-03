<?php

namespace Tests\Feature;

use Database\Factories\MatchupFactory;
use Database\Factories\ScheduleFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class HistoryTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    public function test_history_index_can_be_rendered(): void
    {
        $user = $this->userWithPermissions(['history.view']);

        $response = $this->actingAs($user)->get('/admin/history');

        $response->assertOk();
    }

    public function test_finished_match_appears_in_history(): void
    {
        $user = $this->userWithPermissions(['history.view']);
        $schedule = ScheduleFactory::new()->create();
        MatchupFactory::new()->finished()->create(['schedule_id' => $schedule->id]);

        $response = $this->actingAs($user)->get('/admin/history');

        $response->assertOk();
        $response->assertSee('Pertandingan');
        $response->assertSee('Partai ' . $schedule->match_no);
    }

    public function test_history_can_be_filtered_by_type(): void
    {
        $user = $this->userWithPermissions(['history.view']);

        $response = $this->actingAs($user)->get('/admin/history?type=match');

        $response->assertOk();
    }

    public function test_history_unknown_type_shows_all(): void
    {
        $user = $this->userWithPermissions(['history.view']);

        $response = $this->actingAs($user)->get('/admin/history?type=nonexistent');

        $response->assertOk();
    }

    public function test_history_rejects_invalid_date_filter(): void
    {
        $user = $this->userWithPermissions(['history.view']);

        $response = $this->actingAs($user)
            ->from('/admin/history')
            ->get('/admin/history?from=bukan-tanggal');

        $response->assertSessionHasErrors('from', 'Tanggal mulai tidak valid.');
    }

    public function test_history_filters_by_date_range(): void
    {
        $user = $this->userWithPermissions(['history.view']);
        $schedule = ScheduleFactory::new()->create();
        MatchupFactory::new()->finished()->create([
            'schedule_id' => $schedule->id,
            'finished_at' => now()->subDays(5),
        ]);
        $future = now()->addDays(30)->toDateString();

        $response = $this->actingAs($user)->get("/admin/history?type=match&from={$future}");

        $response->assertOk();
        $response->assertDontSee('Partai ' . $schedule->match_no);
    }

    public function test_history_requires_permission(): void
    {
        $user = $this->userWithPermissions(['matches.view']);

        $this->actingAs($user)->get('/admin/history')->assertForbidden();
    }
}
