<?php

namespace Tests\Feature;

use App\Models\ReadinessCheck;
use Database\Factories\AthleteFactory;
use Database\Factories\CategoryFactory;
use Database\Factories\ContingentFactory;
use Database\Factories\MatchupFactory;
use Database\Factories\ReadinessCheckFactory;
use Database\Factories\ScheduleFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class ReadinessTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    private function scheduleWithAthletes(): array
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
        $schedule = ScheduleFactory::new()->create(['category_id' => $category->id]);
        MatchupFactory::new()->create([
            'schedule_id' => $schedule->id,
            'athlete_a_id' => $athleteA->id,
            'athlete_b_id' => $athleteB->id,
        ]);

        return [$schedule, $athleteA, $athleteB];
    }

    public function test_readiness_index_can_be_rendered(): void
    {
        $user = $this->userWithPermissions(['readiness.view']);
        $this->scheduleWithAthletes();

        $response = $this->actingAs($user)->get('/admin/readiness');

        $response->assertOk();
        $response->assertSee('Kesiapan');
        $this->assertSame(2, ReadinessCheck::count());
    }

    public function test_readiness_check_can_be_marked_ready(): void
    {
        $user = $this->userWithPermissions(['readiness.update']);
        $check = ReadinessCheckFactory::new()->create();

        $response = $this->actingAs($user)
            ->from('/admin/readiness')
            ->post("/admin/readiness/{$check->id}", ['status' => 'ready']);

        $response->assertSessionHas('success', "{$check->athlete->name} ditandai SIAP bertanding.");
        $this->assertDatabaseHas('readiness_checks', [
            'id' => $check->id,
            'status' => 'ready',
            'attendance_check' => true,
            'athlete_check' => true,
            'equipment_check' => true,
            'checked_by' => $user->id,
        ]);
        $this->assertNotNull($check->fresh()->checked_at);
    }

    public function test_readiness_check_can_be_toggled_back_to_pending(): void
    {
        $user = $this->userWithPermissions(['readiness.update']);
        $check = ReadinessCheckFactory::new()->create(['status' => 'ready', 'checked_at' => now()]);

        $response = $this->actingAs($user)
            ->from('/admin/readiness')
            ->post("/admin/readiness/{$check->id}", ['status' => 'pending']);

        $response->assertSessionHas('success', "{$check->athlete->name} ditandai belum siap.");
        $this->assertDatabaseHas('readiness_checks', [
            'id' => $check->id,
            'status' => 'pending',
            'checked_by' => null,
        ]);
    }

    public function test_readiness_check_notes_are_saved(): void
    {
        $user = $this->userWithPermissions(['readiness.update']);
        $check = ReadinessCheckFactory::new()->create();

        $this->actingAs($user)
            ->from('/admin/readiness')
            ->post("/admin/readiness/{$check->id}", [
                'status' => 'ready',
                'notes' => 'Alat lengkap',
            ]);

        $this->assertDatabaseHas('readiness_checks', [
            'id' => $check->id,
            'notes' => 'Alat lengkap',
        ]);
    }

    public function test_readiness_rejects_invalid_status(): void
    {
        $user = $this->userWithPermissions(['readiness.update']);
        $check = ReadinessCheckFactory::new()->create();

        $response = $this->actingAs($user)
            ->from('/admin/readiness')
            ->post("/admin/readiness/{$check->id}", ['status' => 'siap']);

        $response->assertSessionHasErrors('status', 'status kesiapan');
    }

    public function test_whole_schedule_can_be_marked_ready(): void
    {
        $user = $this->userWithPermissions(['readiness.update']);
        [$schedule] = $this->scheduleWithAthletes();

        $response = $this->actingAs($user)
            ->from('/admin/readiness')
            ->post("/admin/readiness-schedule/{$schedule->id}/ready");

        $response->assertSessionHas(
            'success',
            "Seluruh 2 atlet partai {$schedule->match_no} ditandai siap."
        );
        $this->assertSame(2, ReadinessCheck::where('schedule_id', $schedule->id)->where('status', 'ready')->count());
    }

    public function test_mark_ready_without_athletes_fails(): void
    {
        $user = $this->userWithPermissions(['readiness.update']);
        $schedule = ScheduleFactory::new()->create();

        $response = $this->actingAs($user)
            ->from('/admin/readiness')
            ->post("/admin/readiness-schedule/{$schedule->id}/ready");

        $response->assertSessionHas('error', "Partai {$schedule->match_no} belum memiliki atlet.");
        $this->assertSame(0, ReadinessCheck::count());
    }

    public function test_readiness_module_requires_permission(): void
    {
        $user = $this->userWithPermissions(['readiness.view']);
        $check = ReadinessCheckFactory::new()->create();

        $this->actingAs($user)->get('/admin/readiness')->assertOk();
        $this->actingAs($user)->post("/admin/readiness/{$check->id}", ['status' => 'ready'])->assertForbidden();
    }
}
