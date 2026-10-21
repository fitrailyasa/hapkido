<?php

namespace App\Services;

use App\Models\Athlete;
use App\Models\Category;
use App\Models\Matchup;
use App\Models\Performance;
use Illuminate\Support\Collection;

/**
 * The podium of a category.
 *
 * Daeryun: the winner of the final is gold, the loser of the final is silver
 * and the two losers of the semifinals are bronze.
 *
 * Art: the best final score is gold, the second is silver and the third and
 * the fourth are both bronze.
 *
 * The third place always consists of two athletes.
 */
class PodiumService
{
    /**
     * The medal entries of a category.
     *
     * @return array<int, array{athlete: Athlete, rank: int, medal: string, score: float|null}>
     */
    public function forCategory(Category $category): array
    {
        return $category->isArt()
            ? $this->artPodium($category)
            : $this->daeryunPodium($category);
    }

    /**
     * Daeryun podium: the final decides gold and silver, the losers of the
     * semifinals take the two bronze places.
     */
    protected function daeryunPodium(Category $category): array
    {
        $matches = Matchup::query()
            ->with(['athleteA', 'athleteB', 'winner'])
            ->where('status', 'finished')
            ->whereHas('schedule', fn ($builder) => $builder
                ->where('category_id', $category->id)
                ->where('type', 'daeryun'))
            ->get();

        $final = $matches->firstWhere('round', 'final');

        if (! $final || ! $final->winner_athlete_id) {
            return [];
        }

        $entries = [];

        if ($final->winner) {
            $entries[] = $this->entry($final->winner, 1, 'gold');
        }

        $silver = $this->loser($final);

        if ($silver) {
            $entries[] = $this->entry($silver, 2, 'silver');
        }

        foreach ($this->bronzeAthletes($matches, $final) as $athlete) {
            $entries[] = $this->entry($athlete, 3, 'bronze');
        }

        return $entries;
    }

    /**
     * Art podium: the four best final scores, the third and the fourth both
     * take the bronze place.
     */
    protected function artPodium(Category $category): array
    {
        $rows = Performance::query()
            ->with(['athlete'])
            ->whereHas('schedule', fn ($builder) => $builder
                ->where('category_id', $category->id)
                ->where('type', 'art'))
            ->get()
            ->map(function (Performance $performance) {
                $score = $performance->final_score !== null
                    ? round((float) $performance->final_score, 2)
                    : null;

                return ['athlete' => $performance->athlete, 'score' => $score];
            })
            ->filter(fn (array $row) => $row['athlete'] !== null && $row['score'] !== null)
            ->sortByDesc('score')
            ->values();

        $placements = [
            ['rank' => 1, 'medal' => 'gold'],
            ['rank' => 2, 'medal' => 'silver'],
            ['rank' => 3, 'medal' => 'bronze'],
            ['rank' => 3, 'medal' => 'bronze'],
        ];

        $entries = [];

        foreach ($rows->take(count($placements)) as $index => $row) {
            $placement = $placements[$index];

            $entries[] = $this->entry(
                $row['athlete'],
                $placement['rank'],
                $placement['medal'],
                $row['score']
            );
        }

        return $entries;
    }

    /**
     * The two athletes of the third place: the losers of the semifinals.
     *
     * When a semifinal is not finished (yet), the best losers of the earlier
     * rounds step in, so the third place always consists of two athletes.
     */
    protected function bronzeAthletes(Collection $matches, Matchup $final): array
    {
        $excluded = array_filter([
            (int) $final->winner_athlete_id,
            (int) ($this->loser($final)?->id ?? 0),
        ]);

        $losers = $this->sortedLosers($matches->where('round', 'semifinal'));

        if (count($losers) < 2) {
            $earlier = $this->sortedLosers(
                $matches->whereIn('round', ['perempat_final', 'penyisihan'])
            );

            foreach ($earlier as $loser) {
                if (count($losers) >= 2) {
                    break;
                }

                $losers[] = $loser;
            }
        }

        return collect($losers)
            ->reject(fn (Athlete $athlete) => in_array((int) $athlete->id, $excluded, true))
            ->unique('id')
            ->take(2)
            ->values()
            ->all();
    }

    /**
     * The losers of the given matches, the best one (most points) first.
     *
     * @return array<int, Athlete>
     */
    protected function sortedLosers(Collection $matches): array
    {
        return $matches
            ->map(function (Matchup $match) {
                $loser = $this->loser($match);

                if (! $loser) {
                    return null;
                }

                $points = (int) $match->winner_athlete_id === (int) $match->athlete_a_id
                    ? (int) $match->score_b
                    : (int) $match->score_a;

                return ['athlete' => $loser, 'points' => $points];
            })
            ->filter()
            ->sortByDesc('points')
            ->pluck('athlete')
            ->all();
    }

    /**
     * The athlete that lost the match.
     */
    protected function loser(Matchup $match): ?Athlete
    {
        if (! $match->winner_athlete_id) {
            return null;
        }

        return (int) $match->winner_athlete_id === (int) $match->athlete_a_id
            ? $match->athleteB
            : $match->athleteA;
    }

    /**
     * @return array{athlete: Athlete, rank: int, medal: string, score: float|null}
     */
    protected function entry(Athlete $athlete, int $rank, string $medal, ?float $score = null): array
    {
        return [
            'athlete' => $athlete,
            'rank' => $rank,
            'medal' => $medal,
            'score' => $score,
        ];
    }
}
