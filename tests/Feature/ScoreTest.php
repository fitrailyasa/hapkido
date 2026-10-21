<?php

namespace Tests\Feature;

use Database\Factories\PerformanceFactory;
use Database\Factories\ScoreFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class ScoreTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    public function test_score_edit_page_can_be_rendered(): void
    {
        $user = $this->userWithPermissions(['scores.view']);
        $performance = PerformanceFactory::new()->create();

        $response = $this->actingAs($user)->get("/admin/scores/{$performance->id}");

        $response->assertOk();
        $response->assertSee($performance->athlete->name);
    }

    public function test_final_score_can_be_saved(): void
    {
        $user = $this->userWithPermissions(['scores.create']);
        $performance = PerformanceFactory::new()->create(['status' => 'performing']);

        $response = $this->actingAs($user)->post("/admin/scores/{$performance->id}", [
            'final_score' => 85,
        ]);

        $response->assertRedirect(route('admin.scores.edit', $performance));
        $response->assertSessionHas('success', 'Nilai akhir tersimpan: 85');
        $this->assertSame(85.0, (float) $performance->fresh()->final_score);
        $this->assertSame('finished', $performance->fresh()->status);
        $this->assertNotNull($performance->fresh()->performed_at);
    }

    public function test_final_score_is_stored_without_judge_scores(): void
    {
        $user = $this->userWithPermissions(['scores.create']);
        $performance = PerformanceFactory::new()->create();

        $this->actingAs($user)->post("/admin/scores/{$performance->id}", [
            'final_score' => 78.25,
        ]);

        $this->assertSame(78.25, (float) $performance->fresh()->final_score);
        $this->assertSame(0, \App\Models\Score::where('performance_id', $performance->id)->count());
    }

    public function test_existing_final_score_is_updated(): void
    {
        $user = $this->userWithPermissions(['scores.create']);
        $performance = PerformanceFactory::new()->create(['final_score' => 60]);
        ScoreFactory::new()->create([
            'performance_id' => $performance->id,
            'judge_no' => 1,
            'score' => 50,
        ]);

        $this->actingAs($user)->post("/admin/scores/{$performance->id}", [
            'final_score' => 70,
        ]);

        $this->assertSame(70.0, (float) $performance->fresh()->final_score);
    }

    public function test_score_validation_requires_final_score(): void
    {
        $user = $this->userWithPermissions(['scores.create']);
        $performance = PerformanceFactory::new()->create();

        $response = $this->actingAs($user)
            ->from("/admin/scores/{$performance->id}")
            ->post("/admin/scores/{$performance->id}", []);

        $response->assertSessionHasErrors('final_score', 'Nilai akhir wajib diisi.');
        $this->assertNull($performance->fresh()->final_score);
    }

    public function test_score_range_is_validated(): void
    {
        $user = $this->userWithPermissions(['scores.create']);
        $performance = PerformanceFactory::new()->create();

        $response = $this->actingAs($user)
            ->from("/admin/scores/{$performance->id}")
            ->post("/admin/scores/{$performance->id}", [
                'final_score' => 100000,
            ]);

        $response->assertSessionHasErrors('final_score', 'Nilai akhir maksimal 99999.');
    }

    public function test_finishing_all_performances_finishes_schedule(): void
    {
        $user = $this->userWithPermissions(['scores.create']);
        $performance = PerformanceFactory::new()->create(['status' => 'performing']);

        $this->actingAs($user)->post("/admin/scores/{$performance->id}", [
            'final_score' => 80,
        ]);

        $this->assertDatabaseHas('schedules', [
            'id' => $performance->schedule_id,
            'status' => 'finished',
        ]);
    }

    public function test_score_module_requires_permission(): void
    {
        $user = $this->userWithPermissions(['scores.view']);
        $performance = PerformanceFactory::new()->create();

        $this->actingAs($user)->get("/admin/scores/{$performance->id}")->assertOk();
        $this->actingAs($user)->post("/admin/scores/{$performance->id}", [
            'final_score' => 80,
        ])->assertForbidden();
    }
}
