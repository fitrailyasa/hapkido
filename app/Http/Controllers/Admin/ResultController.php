<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Athlete;
use App\Models\Category;
use App\Models\Matchup;
use App\Models\Performance;

class ResultController extends Controller
{
    public function index()
    {
        $categories = Category::orderBy('name')->get();

        $daeryun = $categories->filter(fn (Category $category) => $category->type === 'daeryun')
            ->map(fn (Category $category) => [
                'category' => $category,
                'rows' => $this->daeryunRows($category),
                'medals' => $this->daeryunMedals($category),
            ])
            ->values();

        $art = $categories->filter(fn (Category $category) => $category->type === 'art')
            ->map(function (Category $category) {
                $rows = $this->artRows($category);

                return [
                    'category' => $category,
                    'rows' => $rows,
                    'medals' => $this->topThreeMedals($rows),
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
     * Ranking daeryun: menang → selisih skor → total poin.
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
     * Ranking seni: nilai akhir dari tabel scores (rata-rata juri).
     */
    protected function artRows(Category $category): array
    {
        $performances = Performance::with(['athlete.contingent', 'scores', 'schedule'])
            ->whereHas('schedule', fn ($builder) => $builder
                ->where('category_id', $category->id)
                ->where('type', 'art'))
            ->get();

        $rows = $performances->map(function (Performance $performance) {
            $score = $performance->scores->isNotEmpty()
                ? round((float) $performance->scores->avg('score'), 2)
                : ($performance->final_score !== null ? round((float) $performance->final_score, 2) : null);

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
     * Medali daeryun diambil dari hasil bracket (final & semifinal).
     */
    protected function daeryunMedals(Category $category): array
    {
        $matches = Matchup::query()
            ->with(['athleteA.contingent', 'athleteB.contingent', 'winner.contingent'])
            ->where('status', 'finished')
            ->whereIn('round', ['final', 'semifinal'])
            ->whereHas('schedule', fn ($builder) => $builder
                ->where('category_id', $category->id)
                ->where('type', 'daeryun'))
            ->get();

        $final = $matches->firstWhere('round', 'final');
        $medals = [];

        if ($final && $final->winner_athlete_id) {
            $medals[] = $this->medalEntry($final->winner, 'gold');

            $loser = (int) $final->winner_athlete_id === (int) $final->athlete_a_id
                ? $final->athleteB
                : $final->athleteA;

            if ($loser) {
                $medals[] = $this->medalEntry($loser, 'silver');
            }
        }

        $losers = $matches->where('round', 'semifinal')
            ->map(function (Matchup $match) {
                if (! $match->winner_athlete_id) {
                    return null;
                }

                return (int) $match->winner_athlete_id === (int) $match->athlete_a_id
                    ? $match->athleteB
                    : $match->athleteA;
            })
            ->filter()
            ->unique('id')
            ->take(2);

        foreach ($losers as $loser) {
            $medals[] = $this->medalEntry($loser, 'bronze');
        }

        return array_values(array_filter($medals));
    }

    protected function topThreeMedals(array $rows): array
    {
        $map = [1 => 'gold', 2 => 'silver', 3 => 'bronze'];
        $medals = [];

        foreach ($rows as $row) {
            if (! isset($map[$row['rank']]) || ! $row['athlete']) {
                continue;
            }

            $medals[] = $this->medalEntry($row['athlete'], $map[$row['rank']]);
        }

        return $medals;
    }

    protected function medalEntry(?Athlete $athlete, string $medal): ?array
    {
        if (! $athlete) {
            return null;
        }

        return [
            'athlete_id' => $athlete->id,
            'athlete_name' => $athlete->name,
            'contingent_id' => $athlete->contingent_id,
            'contingent' => $athlete->contingent,
            'medal' => $medal,
        ];
    }

    /**
     * Juara umum: perolehan medali tiap kontingen.
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
