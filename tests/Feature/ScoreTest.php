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

    public function test_judge_scores_can_be_saved(): void
    {
        $user = $this->userWithPermissions(['scores.create']);
        $performance = PerformanceFactory::new()->create(['status' => 'performing']);

        $response = $this->actingAs($user)->post("/admin/scores/{$performance->id}", [
            'score_1' => 80,
            'score_2' => 90,
            'score_3' => 85,
        ]);

        $response->assertRedirect(route('admin.scores.edit', $performance));
        $response->assertSessionHas('success', 'Nilai juri tersimpan. Nilai akhir: 85.00');
        $this->assertDatabaseHas('scores', ['performance_id' => $performance->id, 'judge_no' => 1, 'score' => 80]);
        $this->assertDatabaseHas('scores', ['performance_id' => $performance->id, 'judge_no' => 2, 'score' => 90]);
        $this->assertDatabaseHas('scores', ['performance_id' => $performance->id, 'judge_no' => 3, 'score' => 85]);
        $this->assertSame(85.0, (float) $performance->fresh()->final_score);
        $this->assertSame('finished', $performance->fresh()->status);
        $this->assertNotNull($performance->fresh()->performed_at);
    }

    public function test_existing_scores_are_updated(): void
    {
        $user = $this->userWithPermissions(['scores.create']);
        $performance = PerformanceFactory::new()->create();
        ScoreFactory::new()->create([
            'performance_id' => $performance->id,
            'judge_no' => 1,
            'score' => 50,
        ]);

        $this->actingAs($user)->post("/admin/scores/{$performance->id}", [
            'score_1' => 70,
            'score_2' => 70,
            'score_3' => 70,
        ]);

        $this->assertSame(3, \App\Models\Score::where('performance_id', $performance->id)->count());
        $this->assertSame(
            1,
            \App\Models\Score::where('performance_id', $performance->id)->where('judge_no', 1)->count()
        );
        $this->assertDatabaseHas('scores', [
            'performance_id' => $performance->id,
            'judge_no' => 1,
            'score' => 70,
        ]);
        $this->assertSame(70.0, (float) $performance->fresh()->final_score);
    }

    public function test_score_validation_requires_all_judges(): void
    {
        $user = $this->userWithPermissions(['scores.create']);
        $performance = PerformanceFactory::new()->create();

        $response = $this->actingAs($user)
            ->from("/admin/scores/{$performance->id}")
            ->post("/admin/scores/{$performance->id}", [
                'score_1' => 80,
            ]);

        $response->assertSessionHasErrors(['score_2', 'score_3']);
        $this->assertSame(0, \App\Models\Score::count());
    }

    public function test_score_range_is_validated(): void
    {
        $user = $this->userWithPermissions(['scores.create']);
        $performance = PerformanceFactory::new()->create();

        $response = $this->actingAs($user)
            ->from("/admin/scores/{$performance->id}")
            ->post("/admin/scores/{$performance->id}", [
                'score_1' => 120,
                'score_2' => 80,
                'score_3' => 80,
            ]);

        $response->assertSessionHasErrors('score_1', 'Nilai Juri 1 maksimal 100.');
    }

    public function test_finishing_all_performances_finishes_schedule(): void
    {
        $user = $this->userWithPermissions(['scores.create']);
        $performance = PerformanceFactory::new()->create(['status' => 'performing']);

        $this->actingAs($user)->post("/admin/scores/{$performance->id}", [
            'score_1' => 80,
            'score_2' => 80,
            'score_3' => 80,
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
            'score_1' => 80,
            'score_2' => 80,
            'score_3' => 80,
        ])->assertForbidden();
    }
}
