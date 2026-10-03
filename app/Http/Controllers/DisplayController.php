<?php

namespace App\Http\Controllers;

use App\Models\Arena;
use App\Models\Athlete;
use App\Models\Calling;
use App\Models\Category;
use App\Models\Matchup;
use App\Models\Performance;
use App\Models\Schedule;
use App\Models\Verification;
use App\Services\ArenaLiveService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DisplayController extends Controller
{
    public function index()
    {
        $arenas = Arena::orderBy('name')->get()->map(fn (Arena $arena) => [
            'id' => $arena->id,
            'name' => $arena->name,
            'label' => $arena->label,
            'status' => $arena->statusLabel(),
            'category' => $arena->category?->name ?? '—',
        ]);

        return view('display.index', ['arenas' => $arenas]);
    }

    public function data()
    {
        $today = now()->toDateString();

        $running = Schedule::with(['category', 'arena', 'match.athleteA.contingent', 'match.athleteB.contingent'])
            ->whereDate('match_date', $today)
            ->where('status', 'running')
            ->orderBy('order_no')
            ->get();

        $liveService = app(ArenaLiveService::class);

        $arenas = Arena::with('category')
            ->orderBy('name')
            ->get()
            ->map(function (Arena $arena) use ($today, $liveService) {
                $schedule = $liveService->resolve($arena, $today);
                $match = $schedule?->match;
                $performers = $this->performerRows($schedule);

                return [
                    'id' => $arena->id,
                    'name' => $arena->name,
                    'label' => $arena->label,
                    'status' => $arena->statusLabel(),
                    'category' => $schedule?->category?->name ?? $arena->category?->name ?? '—',
                    'match_no' => $schedule?->match_no ?? '—',
                    'status_match' => $schedule?->statusLabel() ?? '—',
                    'athlete_a' => $match?->athleteA?->name ?? $schedule?->performances->first()?->athlete?->name,
                    'contingent_a' => $match?->athleteA?->contingent?->code,
                    'athlete_b' => $match?->athleteB?->name,
                    'contingent_b' => $match?->athleteB?->contingent?->code,
                    'score_a' => $match?->score_a,
                    'score_b' => $match?->score_b,
                    'type' => $schedule?->type ?? $arena->match_type ?? 'daeryun',
                    'status_code' => $schedule?->status,
                    'is_live' => $schedule?->status === 'running',
                    'photo_a' => $this->photoUrl($match?->athleteA?->photo),
                    'photo_b' => $this->photoUrl($match?->athleteB?->photo),
                    'performers' => $performers,
                ];
            });

        $callings = Calling::with(['athlete.contingent', 'schedule.category', 'schedule.arena'])
            ->whereIn('status', ['called', 'waiting'])
            ->orderBy('updated_at', 'desc')
            ->take(10)
            ->get()
            ->map(fn (Calling $calling) => [
                'match_no' => $calling->schedule?->match_no ?? '—',
                'athlete' => $calling->athlete?->name ?? '—',
                'contingent' => $calling->athlete?->contingent?->code ?? '—',
                'level_label' => $calling->levelLabel(),
                'status_label' => $calling->statusLabel(),
                'category' => $calling->schedule?->category?->name ?? '—',
                'arena' => $calling->schedule?->arena?->name ?? '—',
            ]);

        $results = Matchup::with(['schedule.category', 'schedule.arena', 'winner.contingent'])
            ->where('status', 'finished')
            ->whereNotNull('winner_id')
            ->orderByDesc('finished_at')
            ->take(8)
            ->get()
            ->map(fn (Matchup $match) => [
                'match_no' => $match->schedule?->match_no,
                'category' => $match->schedule?->category?->name ?? '—',
                'score' => sprintf('%d - %d', (int) $match->score_a, (int) $match->score_b),
                'winner' => $match->winner?->name,
                'winner_code' => $match->winner?->contingent?->code,
                'finished_at' => optional($match->finished_at)->format('H:i'),
            ]);

        $performances = Performance::with(['athlete.contingent', 'schedule.category'])
            ->whereHas('schedule', fn ($builder) => $builder->whereDate('match_date', $today))
            ->get()
            ->sort(fn (Performance $left, Performance $right) => [
                $left->schedule?->order_no ?? 0,
                $left->order_no,
            ] <=> [
                $right->schedule?->order_no ?? 0,
                $right->order_no,
            ])
            ->take(12)
            ->values()
            ->map(fn (Performance $performance) => [
                'order_no' => $performance->order_no,
                'athlete' => $performance->athlete?->name ?? '—',
                'contingent' => $performance->athlete?->contingent?->code ?? '—',
                'category' => $performance->schedule?->category?->name ?? '—',
                'match_no' => $performance->schedule?->match_no ?? '—',
                'schedule_key' => (string) $performance->schedule?->id,
                'status' => $performance->status,
                'score' => $performance->final_score,
            ]);

        $next = Schedule::with(['category', 'arena'])
            ->whereDate('match_date', $today)
            ->whereIn('status', ['pending', 'preparation'])
            ->orderBy('order_no')
            ->take(6)
            ->get()
            ->map(fn (Schedule $schedule) => [
                'match_no' => $schedule->match_no,
                'time' => substr((string) $schedule->start_time, 0, 5),
                'category' => $schedule->category?->name ?? '—',
                'arena' => $schedule->arena?->name ?? '—',
                'type' => $schedule->type,
            ]);

        return response()->json([
            'now' => now()->format('H:i'),
            'present_count' => Verification::where('status', 'present')->count(),
            'arenas' => $arenas,
            'running' => $running->count(),
            'live' => $this->livePayload($this->liveSchedule($today)),
            'callings' => $callings,
            'results' => $results,
            'performances' => $performances,
            'next' => $next,
            'juara' => $this->juaraPayload(),
            'streamer' => Str::upper(config('app.name')),
        ]);
    }

    public function arena(Arena $arena)
    {
        return view('display.arena', [
            'arena' => $arena,
            'arenas' => Arena::orderBy('name')->get(),
        ]);
    }

    public function arenaData(Arena $arena)
    {
        $today = now()->toDateString();
        $schedule = app(ArenaLiveService::class)->resolve($arena, $today);

        $match = $schedule?->match;
        $isArt = $schedule ? $schedule->type === 'art' : $arena->match_type === 'art';

        $current = array_merge([
            'category' => $schedule?->category?->name ?? $arena->category?->name ?? '—',
            'match_no' => $schedule?->match_no ?? '—',
            'status' => $schedule?->statusLabel() ?? $arena->statusLabel(),
            'round' => $schedule?->round ? Str::ucfirst(str_replace('_', ' ', $schedule->round)) : '—',
            'type' => $schedule?->type ?? $arena->match_type ?? 'daeryun',
            'time' => $schedule ? substr((string) $schedule->start_time, 0, 5) : null,
            'athlete_a' => $match?->athleteA?->name,
            'contingent_a' => $match?->athleteA?->contingent?->code,
            'score_a' => $match?->score_a,
            'athlete_b' => $match?->athleteB?->name,
            'contingent_b' => $match?->athleteB?->contingent?->code,
            'score_b' => $match?->score_b,
            'match_status' => $match?->statusLabel() ?? '—',
        ], $this->frameExtras($schedule, $match));

        $callings = Calling::with(['athlete.contingent', 'schedule.category'])
            ->whereIn('status', ['called', 'waiting'])
            ->whereHas('schedule', fn ($builder) => $builder->where('arena_id', $arena->id))
            ->orderByDesc('updated_at')
            ->take(8)
            ->get()
            ->map(fn (Calling $calling) => [
                'match_no' => $calling->schedule?->match_no ?? '—',
                'athlete' => $calling->athlete?->name ?? '—',
                'contingent' => $calling->athlete?->contingent?->code ?? '—',
                'level_label' => $calling->levelLabel(),
                'status_label' => $calling->statusLabel(),
                'category' => $calling->schedule?->category?->name ?? '—',
            ]);

        $next = Schedule::with(['category'])
            ->where('arena_id', $arena->id)
            ->whereDate('match_date', $today)
            ->whereIn('status', ['pending', 'preparation'])
            ->orderBy('order_no')
            ->take(8)
            ->get()
            ->map(fn (Schedule $item) => [
                'match_no' => $item->match_no,
                'time' => substr((string) $item->start_time, 0, 5),
                'category' => $item->category?->name ?? '—',
                'type' => $item->type,
            ]);

        $performances = Performance::with(['athlete.contingent', 'schedule.category'])
            ->whereHas('schedule', fn ($builder) => $builder
                ->where('arena_id', $arena->id)
                ->whereDate('match_date', $today))
            ->get()
            ->sort(fn (Performance $left, Performance $right) => [
                $left->schedule?->order_no ?? 0,
                $left->order_no,
            ] <=> [
                $right->schedule?->order_no ?? 0,
                $right->order_no,
            ])
            ->take(20)
            ->values()
            ->map(fn (Performance $performance) => [
                'order_no' => $performance->order_no,
                'athlete' => $performance->athlete?->name ?? '—',
                'contingent' => $performance->athlete?->contingent?->code ?? '—',
                'status' => $performance->statusLabel(),
                'is_performing' => $performance->status === 'performing',
                'photo' => $this->photoUrl($performance->athlete?->photo),
                'score' => $performance->final_score,
                'match_no' => $performance->schedule?->match_no ?? '—',
                'category' => $performance->schedule?->category?->name ?? '—',
                'schedule_key' => (string) $performance->schedule?->id,
            ]);

        if ($isArt && $current['performers'] === []) {
            $current['performers'] = $performances->take(8)->values()->all();
        }

        $results = Matchup::with(['schedule.category', 'winner.contingent'])
            ->where('status', 'finished')
            ->whereNotNull('winner_id')
            ->whereHas('schedule', fn ($builder) => $builder->where('arena_id', $arena->id))
            ->orderByDesc('finished_at')
            ->take(6)
            ->get()
            ->map(fn (Matchup $item) => [
                'match_no' => $item->schedule?->match_no,
                'category' => $item->schedule?->category?->name ?? '—',
                'score' => $item->scoreLabel(),
                'winner' => $item->winner?->name,
                'winner_code' => $item->winner?->contingent?->code,
                'finished_at' => optional($item->finished_at)->format('H:i'),
            ]);

        return response()->json([
            'now' => now()->format('H:i'),
            'arena' => [
                'id' => $arena->id,
                'name' => $arena->name,
                'label' => $arena->label,
                'status' => $arena->statusLabel(),
                'category' => $arena->category?->name ?? '—',
            ],
            'arenas' => Arena::orderBy('name')->get(['id', 'name', 'label']),
            'current' => $current,
            'is_art' => (bool) $isArt,
            'callings' => $callings,
            'next' => $next,
            'performances' => $performances,
            'results' => $results,
        ]);
    }

    /**
     * Jadwal yang tampil di blok match utama layar publik.
     * Prioritas: sedang berlangsung, baru selesai (1 jam terakhir),
     * persiapan, menunggu, lalu hasil lama.
     */
    protected function liveSchedule(string $today): ?Schedule
    {
        $relations = [
            'category',
            'arena',
            'match.athleteA.contingent',
            'match.athleteB.contingent',
            'match.winner.contingent',
            'performances.athlete.contingent',
        ];

        return Schedule::with($relations)
            ->whereDate('match_date', $today)
            ->whereIn('status', ['running', 'finished', 'preparation', 'pending'])
            ->orderBy('order_no')
            ->get()
            ->sortBy(fn (Schedule $schedule) => $this->livePriority($schedule))
            ->first();
    }

    protected function livePriority(Schedule $schedule): int
    {
        return match (true) {
            $schedule->status === 'running' => 0,
            $schedule->status === 'finished'
                && $schedule->match?->finished_at?->greaterThan(now()->subHour()) => 1,
            $schedule->status === 'preparation' => 2,
            $schedule->status === 'pending' => 3,
            default => 4,
        };
    }

    protected function livePayload(?Schedule $schedule): ?array
    {
        if (! $schedule) {
            return null;
        }

        $match = $schedule->match;

        return array_merge([
            'id' => $schedule->id,
            'arena_id' => $schedule->arena?->id,
            'arena' => $schedule->arena?->label ?: ($schedule->arena?->name ?? '—'),
            'category' => $schedule->category?->name ?? '—',
            'match_no' => $schedule->match_no,
            'status' => $schedule->statusLabel(),
            'round' => $schedule->round ? Str::ucfirst(str_replace('_', ' ', $schedule->round)) : '—',
            'type' => $schedule->type,
            'time' => substr((string) $schedule->start_time, 0, 5),
            'athlete_a' => $match?->athleteA?->name ?? $schedule->performances->first()?->athlete?->name,
            'contingent_a' => $match?->athleteA?->contingent?->code,
            'score_a' => $match?->score_a,
            'athlete_b' => $match?->athleteB?->name,
            'contingent_b' => $match?->athleteB?->contingent?->code,
            'score_b' => $match?->score_b,
            'match_status' => $match?->statusLabel() ?? '—',
        ], $this->frameExtras($schedule, $match));
    }

    /**
     * Field tambahan untuk blok match (frame) — tidak menggantikan key lama.
     */
    protected function frameExtras(?Schedule $schedule, ?Matchup $match): array
    {
        $schedule?->loadMissing(['performances.athlete.contingent']);

        $round = $match?->round ?? $schedule?->round;
        $winner = null;

        if ($match && $match->winner_athlete_id) {
            $winner = $match->winner;
        } elseif ($match && $match->winner_id) {
            $winner = Athlete::find($match->winner_id);
        }

        $winnerSide = null;
        if ($winner && $match) {
            $winnerSide = (int) $winner->id === (int) $match->athlete_a_id ? 'a' : 'b';
        }

        return [
            'status_code' => $schedule?->status,
            'is_live' => $schedule?->status === 'running',
            'photo_a' => $this->photoUrl($match?->athleteA?->photo),
            'photo_b' => $this->photoUrl($match?->athleteB?->photo),
            'winner_name' => $winner?->name,
            'winner_code' => $winner?->contingent?->code,
            'winner_side' => $winnerSide,
            'is_final' => $round === 'final',
            'performers' => $this->performerRows($schedule),
        ];
    }

    protected function performerRows(?Schedule $schedule): array
    {
        if (! $schedule) {
            return [];
        }

        return $schedule->performances
            ->map(fn (Performance $performance) => [
                'order_no' => $performance->order_no,
                'athlete' => $performance->athlete?->name ?? '—',
                'contingent' => $performance->athlete?->contingent?->code ?? '—',
                'photo' => $this->photoUrl($performance->athlete?->photo),
                'status' => $performance->statusLabel(),
                'is_performing' => $performance->status === 'performing',
                'score' => $performance->final_score,
            ])
            ->values()
            ->all();
    }

    /**
     * Hanya foto lokal (di dalam storage aplikasi) yang ditampilkan
     * agar layar display tetap bekerja tanpa koneksi internet.
     */
    protected function photoUrl(?string $photo): ?string
    {
        if (! $photo || filter_var($photo, FILTER_VALIDATE_URL)) {
            return null;
        }

        return '/storage/' . ltrim($photo, '/');
    }

    /**
     * Juara 1-2-3 tiap kategori (daeryun dari bracket final/semifinal,
     * seni dari urutan nilai akhir) plus total medali per kontingen.
     */
    protected function juaraPayload(): array
    {
        $sections = ['daeryun' => [], 'art' => []];

        foreach (Category::orderBy('name')->get() as $category) {
            $items = $category->isArt()
                ? $this->artJuara($category)
                : $this->daeryunJuara($category);

            if ($items === []) {
                continue;
            }

            $sections[$category->isArt() ? 'art' : 'daeryun'][] = [
                'category_id' => $category->id,
                'category' => $category->name,
                'type' => $category->type,
                'items' => $items,
            ];
        }

        return [
            'daeryun' => $sections['daeryun'],
            'art' => $sections['art'],
            'total' => $this->juaraTotals(array_merge($sections['daeryun'], $sections['art'])),
        ];
    }

    protected function daeryunJuara(Category $category): array
    {
        $matches = Matchup::query()
            ->with(['athleteA.contingent', 'athleteB.contingent', 'winner.contingent', 'schedule'])
            ->where('status', 'finished')
            ->whereIn('round', ['final', 'semifinal'])
            ->whereHas('schedule', fn ($builder) => $builder
                ->where('category_id', $category->id)
                ->where('type', 'daeryun'))
            ->get();

        $final = $matches->firstWhere('round', 'final');

        if (! $final || ! $final->winner_athlete_id) {
            return [];
        }

        $items = [];
        $used = [];

        $gold = $this->juaraEntry($final->winner, 1, 'emas');
        if ($gold) {
            $items[] = $gold;
            $used[] = (int) $final->winner->id;
        }

        $runnerUp = (int) $final->winner_athlete_id === (int) $final->athlete_a_id
            ? $final->athleteB
            : $final->athleteA;

        $silver = $this->juaraEntry($runnerUp, 2, 'perak');
        if ($silver) {
            $items[] = $silver;
            $used[] = (int) $runnerUp->id;
        }

        $matches->where('round', 'semifinal')
            ->map(function (Matchup $match) {
                if (! $match->winner_athlete_id) {
                    return null;
                }

                return (int) $match->winner_athlete_id === (int) $match->athlete_a_id
                    ? $match->athleteB
                    : $match->athleteA;
            })
            ->filter()
            ->reject(fn (Athlete $athlete) => in_array((int) $athlete->id, $used, true))
            ->unique('id')
            ->take(2)
            ->each(function (Athlete $athlete) use (&$items) {
                $entry = $this->juaraEntry($athlete, 3, 'perunggu');
                if ($entry) {
                    $items[] = $entry;
                }
            });

        return $items;
    }

    protected function artJuara(Category $category): array
    {
        $rows = Performance::query()
            ->with(['athlete.contingent', 'scores'])
            ->whereHas('schedule', fn ($builder) => $builder
                ->where('category_id', $category->id)
                ->where('type', 'art'))
            ->get()
            ->map(function (Performance $performance) {
                $score = $performance->scores->isNotEmpty()
                    ? round((float) $performance->scores->avg('score'), 2)
                    : ($performance->final_score !== null ? round((float) $performance->final_score, 2) : null);

                return ['athlete' => $performance->athlete, 'score' => $score];
            })
            ->filter(fn (array $row) => $row['athlete'] !== null && $row['score'] !== null)
            ->sortByDesc('score')
            ->values()
            ->take(3);

        $medals = [1 => 'emas', 2 => 'perak', 3 => 'perunggu'];
        $items = [];

        foreach ($rows as $index => $row) {
            $rank = $index + 1;
            $entry = $this->juaraEntry($row['athlete'], $rank, $medals[$rank]);

            if ($entry) {
                $entry['score'] = $row['score'];
                $items[] = $entry;
            }
        }

        return $items;
    }

    protected function juaraEntry(?Athlete $athlete, int $rank, string $medal): ?array
    {
        if (! $athlete) {
            return null;
        }

        return [
            'rank' => $rank,
            'name' => $athlete->name,
            'contingent_id' => $athlete->contingent_id,
            'contingent' => $athlete->contingent?->name,
            'contingent_code' => $athlete->contingent?->code,
            'medal' => $medal,
        ];
    }

    protected function juaraTotals(array $sections): array
    {
        $tally = [];

        foreach ($sections as $section) {
            foreach ($section['items'] as $item) {
                $contingentId = $item['contingent_id'] ?? null;

                if (! $contingentId) {
                    continue;
                }

                if (! isset($tally[$contingentId])) {
                    $tally[$contingentId] = [
                        'contingent_id' => $contingentId,
                        'contingent' => $item['contingent'],
                        'code' => $item['contingent_code'],
                        'emas' => 0,
                        'perak' => 0,
                        'perunggu' => 0,
                        'total' => 0,
                        'poin' => 0,
                    ];
                }

                $tally[$contingentId][$item['medal']]++;
                $tally[$contingentId]['total']++;
            }
        }

        $rows = array_values($tally);

        usort($rows, fn (array $a, array $b) => [$b['emas'], $b['perak'], $b['perunggu']]
            <=> [$a['emas'], $a['perak'], $a['perunggu']]);

        foreach ($rows as $index => &$row) {
            $row['rank'] = $index + 1;
            $row['poin'] = ($row['emas'] * 3) + ($row['perak'] * 2) + $row['perunggu'];
        }
        unset($row);

        return $rows;
    }
}
