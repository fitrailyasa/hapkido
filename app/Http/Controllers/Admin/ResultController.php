<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Athlete;
use App\Models\Category;
use App\Models\Matchup;
use App\Models\Performance;
use App\Services\PodiumService;

class ResultController extends Controller
{
    public function index()
    {
        $categories = Category::orderBy('name')->get();
        $podiumService = app(PodiumService::class);

        $daeryun = $categories->filter(fn (Category $category) => $category->type === 'daeryun')
            ->map(function (Category $category) use ($podiumService) {
                $rows = $this->daeryunRows($category);
                $podium = $podiumService->forCategory($category);

                return [
                    'category' => $category,
                    'type' => 'daeryun',
                    'rows' => $this->applyPodium($rows, $podium),
                    'podium' => $this->podiumWithStats($podium, $rows),
                    'medals' => $this->medalsFromPodium($podium),
                ];
            })
            ->values();

        $art = $categories->filter(fn (Category $category) => $category->type === 'art')
            ->map(function (Category $category) use ($podiumService) {
                $rows = $this->artRows($category);
                $podium = $podiumService->forCategory($category);

                return [
                    'category' => $category,
                    'type' => 'art',
                    'rows' => $this->applyPodium($rows, $podium),
                    'podium' => $this->podiumWithStats($podium, $rows),
                    'medals' => $this->medalsFromPodium($podium),
                ];
            })
            ->values();

        return view('admin.result.index', [
            'daeryun' => $daeryun,
            'art' => $art,
            'juaraUmum' => $this->generalChampion(collect($daeryun)->merge($art->all())),
        ]);
    }

    /**
     * Daeryun ranking: wins → score difference → total points.
     */
    protected function daeryunRows(Category $category): array
    {
        $athletes = $category->athletes()->with('contingent')->orderBy('name')->get();

        $matches = Matchup::query()
            ->with(['athleteA', 'athleteB'])
            ->where('status', 'finished')
            ->whereHas('schedule', fn ($builder) => $builder
                ->where('category_id', $category->id)
                ->where('type', 'daeryun'))
            ->get();

        $rows = $athletes->map(function (Athlete $athlete) use ($matches) {
            $played = 0;
            $wins = 0;
            $pointsFor = 0;
            $pointsAgainst = 0;

            foreach ($matches as $match) {
                $isA = (int) $match->athlete_a_id === (int) $athlete->id;
                $isB = (int) $match->athlete_b_id === (int) $athlete->id;

                if (! $isA && ! $isB) {
                    continue;
                }

                $played++;
                $pointsFor += (int) ($isA ? $match->score_a : $match->score_b);
                $pointsAgainst += (int) ($isA ? $match->score_b : $match->score_a);

                if ((int) $match->winner_athlete_id === (int) $athlete->id) {
                    $wins++;
                }
            }

            return [
                'athlete' => $athlete,
                'contingent' => $athlete->contingent,
                'played' => $played,
                'wins' => $wins,
                'losses' => $played - $wins,
                'points_for' => $pointsFor,
                'points_against' => $pointsAgainst,
                'diff' => $pointsFor - $pointsAgainst,
                'score' => null,
            ];
        })->all();

        return $this->sortAndRank($rows);
    }

    /**
     * Art ranking: the single final score of every performance.
     */
    protected function artRows(Category $category): array
    {
        $performances = Performance::with(['athlete.contingent', 'schedule'])
            ->whereHas('schedule', fn ($builder) => $builder
                ->where('category_id', $category->id)
                ->where('type', 'art'))
            ->get();

        $rows = $performances->map(function (Performance $performance) {
            $score = $performance->final_score !== null
                ? round((float) $performance->final_score, 2)
                : null;

            return [
                'athlete' => $performance->athlete,
                'contingent' => $performance->athlete?->contingent,
                'performance' => $performance,
                'schedule' => $performance->schedule,
                'score' => $score,
                'played' => null,
                'wins' => 0,
                'losses' => 0,
                'points_for' => 0,
                'points_against' => 0,
                'diff' => 0,
            ];
        })->filter(fn (array $row) => $row['athlete'] !== null)->all();

        return $this->sortAndRank($rows);
    }

    protected function sortAndRank(array $rows): array
    {
        usort($rows, function (array $a, array $b) {
            if ($a['score'] !== null || $b['score'] !== null) {
                $comparison = ($b['score'] ?? -1) <=> ($a['score'] ?? -1);

                if ($comparison !== 0) {
                    return $comparison;
                }
            }

            return [$b['wins'], $b['diff'], $b['points_for'], $a['athlete']->name]
                <=> [$a['wins'], $a['diff'], $a['points_for'], $b['athlete']->name];
        });

        foreach ($rows as $index => &$row) {
            $row['rank'] = $index + 1;
        }
        unset($row);

        return $rows;
    }

    /**
     * The medalists take the podium places (1, 2, 3 and 3), the other athletes
     * follow the ranking.
     */
    protected function applyPodium(array $rows, array $podium): array
    {
        if ($podium === []) {
            return $rows;
        }

        $podiumRanks = [];

        foreach ($podium as $entry) {
            $podiumRanks[(int) $entry['athlete']->id] = $entry['rank'];
        }

        $next = 1;

        foreach ($podiumRanks as $rank) {
            $next = max($next, $rank + 1);
        }

        foreach ($rows as &$row) {
            $athleteId = (int) $row['athlete']->id;

            if (isset($podiumRanks[$athleteId])) {
                $row['rank'] = $podiumRanks[$athleteId];
            } else {
                $row['rank'] = $next++;
            }
        }
        unset($row);

        usort($rows, fn (array $a, array $b) => $a['rank'] <=> $b['rank']);

        return $rows;
    }

    /**
     * The podium entries enriched with the statistics of the ranking rows.
     */
    protected function podiumWithStats(array $podium, array $rows): array
    {
        $rowsByAthlete = [];

        foreach ($rows as $row) {
            $rowsByAthlete[(int) $row['athlete']->id] = $row;
        }

        return array_map(function (array $entry) use ($rowsByAthlete) {
            $athlete = $entry['athlete'];
            $row = $rowsByAthlete[(int) $athlete->id] ?? [];

            return [
                'athlete' => $athlete,
                'rank' => $entry['rank'],
                'medal' => $entry['medal'],
                'contingent' => $athlete->contingent,
                'score' => $entry['score'] ?? ($row['score'] ?? null),
                'wins' => $row['wins'] ?? 0,
                'points_for' => $row['points_for'] ?? 0,
            ];
        }, $podium);
    }

    /**
     * The medals of a class, taken from the podium.
     */
    protected function medalsFromPodium(array $podium): array
    {
        $medals = [];

        foreach ($podium as $entry) {
            $athlete = $entry['athlete'];

            $medals[] = [
                'athlete_id' => $athlete->id,
                'athlete_name' => $athlete->name,
                'contingent_id' => $athlete->contingent_id,
                'contingent' => $athlete->contingent,
                'medal' => $entry['medal'],
            ];
        }

        return $medals;
    }

    /**
     * Overall champion: medal haul per contingent.
     */
    protected function generalChampion($sections): array
    {
        $tally = [];

        foreach ($sections as $section) {
            foreach ($section['medals'] as $medal) {
                $contingentId = $medal['contingent_id'];

                if (! $contingentId) {
                    continue;
                }

                if (! isset($tally[$contingentId])) {
                    $tally[$contingentId] = [
                        'contingent' => $medal['contingent'],
                        'gold' => 0,
                        'silver' => 0,
                        'bronze' => 0,
                    ];
                }

                $tally[$contingentId][$medal['medal']]++;
            }
        }

        $rows = array_values($tally);

        usort($rows, fn (array $a, array $b) => [$b['gold'], $b['silver'], $b['bronze']]
            <=> [$a['gold'], $a['silver'], $a['bronze']]);

        foreach ($rows as $index => &$row) {
            $row['rank'] = $index + 1;
            $row['total'] = $row['gold'] + $row['silver'] + $row['bronze'];
        }
        unset($row);

        return $rows;
    }
}
