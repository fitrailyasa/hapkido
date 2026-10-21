<?php

namespace Tests\Feature;

use App\Models\Athlete;
use App\Models\Category;
use App\Services\PodiumService;
use Database\Factories\AthleteFactory;
use Database\Factories\CategoryFactory;
use Database\Factories\MatchupFactory;
use Database\Factories\PerformanceFactory;
use Database\Factories\ScheduleFactory;
use Database\Factories\ScoreFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PodiumTest extends TestCase
{
    use RefreshDatabase;

    public function test_daeryun_podium_has_two_bronze_medals_for_the_semifinal_losers(): void
    {
        $category = CategoryFactory::new()->daeryun()->create();
        $athletes = $this->athletes($category, 4);
        [$a, $b, $c, $d] = $athletes;

        $schedule = ScheduleFactory::new()->create([
            'category_id' => $category->id,
            'type' => 'daeryun',
        ]);

        $this->match($schedule, 'semifinal', $a, $b, $a, 9, 2);
        $this->match($schedule, 'semifinal', $c, $d, $c, 8, 5);
        $this->match($schedule, 'final', $a, $c, $a, 7, 3);

        $podium = app(PodiumService::class)->forCategory($category);

        $this->assertCount(4, $podium);
        $this->assertSame($a->id, $podium[0]['athlete']->id);
        $this->assertSame('gold', $podium[0]['medal']);
        $this->assertSame(1, $podium[0]['rank']);
        $this->assertSame($c->id, $podium[1]['athlete']->id);
        $this->assertSame('silver', $podium[1]['medal']);
        $this->assertSame(2, $podium[1]['rank']);

        $bronze = collect($podium)->where('medal', 'bronze');

        $this->assertCount(2, $bronze);
        // The bronze places are ordered by the points of the loser.
        $this->assertSame([$d->id, $b->id], $bronze->pluck('athlete.id')->all());
        $this->assertSame([3, 3], $bronze->pluck('rank')->all());
    }

    public function test_daeryun_podium_uses_the_earlier_round_losers_when_a_semifinal_is_missing(): void
    {
        $category = CategoryFactory::new()->daeryun()->create();
        $athletes = $this->athletes($category, 4);
        [$a, $b, $c, $d] = $athletes;

        $schedule = ScheduleFactory::new()->create([
            'category_id' => $category->id,
            'type' => 'daeryun',
        ]);

        $this->match($schedule, 'perempat_final', $c, $d, $c, 5, 1);
        $this->match($schedule, 'semifinal', $a, $b, $a, 9, 2);
        $this->match($schedule, 'final', $a, $c, $a, 7, 3);

        $podium = app(PodiumService::class)->forCategory($category);

        $bronze = collect($podium)->where('medal', 'bronze');

        $this->assertCount(2, $bronze);
        $this->assertSame([3, 3], $bronze->pluck('rank')->all());
        $this->assertSame([$b->id, $d->id], $bronze->pluck('athlete.id')->all());
        $this->assertNotContains($c->id, $bronze->pluck('athlete.id')->all());
    }

    public function test_daeryun_podium_is_empty_when_the_final_is_not_finished(): void
    {
        $category = CategoryFactory::new()->daeryun()->create();
        $athletes = $this->athletes($category, 4);
        [$a, $b, $c, $d] = $athletes;

        $schedule = ScheduleFactory::new()->create([
            'category_id' => $category->id,
            'type' => 'daeryun',
        ]);

        $this->match($schedule, 'semifinal', $a, $b, $a, 9, 2);
        $this->match($schedule, 'semifinal', $c, $d, $c, 8, 5);

        $this->assertSame([], app(PodiumService::class)->forCategory($category));
    }

    public function test_art_podium_has_two_bronze_medals(): void
    {
        $category = CategoryFactory::new()->art()->create();
        $schedule = ScheduleFactory::new()->art()->create(['category_id' => $category->id]);

        $scores = [20, 19, 15, 13];
        $athletes = [];

        foreach ($scores as $index => $score) {
            $athlete = AthleteFactory::new()->create(['category_id' => $category->id]);
            $athletes[] = $athlete;

            PerformanceFactory::new()->create([
                'schedule_id' => $schedule->id,
                'athlete_id' => $athlete->id,
                'status' => 'finished',
                'final_score' => $score,
            ]);
        }

        $podium = app(PodiumService::class)->forCategory($category);

        $this->assertCount(4, $podium);
        $this->assertSame($athletes[0]->id, $podium[0]['athlete']->id);
        $this->assertSame('gold', $podium[0]['medal']);
        $this->assertSame($athletes[1]->id, $podium[1]['athlete']->id);
        $this->assertSame('silver', $podium[1]['medal']);
        $this->assertSame($athletes[2]->id, $podium[2]['athlete']->id);
        $this->assertSame($athletes[3]->id, $podium[3]['athlete']->id);
        $this->assertSame([3, 3], collect($podium)->where('medal', 'bronze')->pluck('rank')->all());
        $this->assertSame([3, 3], array_slice(array_column($podium, 'rank'), 2));
    }

    public function test_art_podium_uses_the_single_final_score(): void
    {
        $category = CategoryFactory::new()->art()->create();
        $schedule = ScheduleFactory::new()->art()->create(['category_id' => $category->id]);

        $athlete = AthleteFactory::new()->create(['category_id' => $category->id]);
        $other = AthleteFactory::new()->create(['category_id' => $category->id]);

        $performance = PerformanceFactory::new()->create([
            'schedule_id' => $schedule->id,
            'athlete_id' => $athlete->id,
            'status' => 'finished',
            'final_score' => 70,
        ]);

        // The judge scores are only a reference, they no longer decide the result.
        foreach ([1, 2, 3] as $judgeNo) {
            ScoreFactory::new()->create([
                'performance_id' => $performance->id,
                'judge_no' => $judgeNo,
                'score' => 90,
            ]);
        }

        PerformanceFactory::new()->create([
            'schedule_id' => $schedule->id,
            'athlete_id' => $other->id,
            'status' => 'finished',
            'final_score' => 80,
        ]);

        $podium = app(PodiumService::class)->forCategory($category);

        $this->assertCount(2, $podium);
        $this->assertSame($other->id, $podium[0]['athlete']->id);
        $this->assertSame(80.0, $podium[0]['score']);
        $this->assertSame($athlete->id, $podium[1]['athlete']->id);
        $this->assertSame(70.0, $podium[1]['score']);
    }

    /**
     * @return array<int, Athlete>
     */
    protected function athletes(Category $category, int $total): array
    {
        $athletes = [];

        for ($index = 0; $index < $total; $index++) {
            $athletes[] = AthleteFactory::new()->create(['category_id' => $category->id]);
        }

        return $athletes;
    }

    protected function match(
        $schedule,
        string $round,
        Athlete $athleteA,
        Athlete $athleteB,
        Athlete $winner,
        int $scoreA,
        int $scoreB
    ): void {
        MatchupFactory::new()->finished()->create([
            'schedule_id' => $schedule->id,
            'round' => $round,
            'athlete_a_id' => $athleteA->id,
            'athlete_b_id' => $athleteB->id,
            'winner_athlete_id' => $winner->id,
            'score_a' => $scoreA,
            'score_b' => $scoreB,
        ]);
    }
}
