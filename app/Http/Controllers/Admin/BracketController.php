<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BracketSlot;
use App\Models\Category;
use App\Models\Matchup;
use App\Services\BracketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BracketController extends Controller
{
    public function index(Request $request)
    {
        $categories = Category::where('type', 'daeryun')->orderBy('name')->get();

        $requestedId = (int) $request->query('category', 0);
        $selected = ($requestedId > 0 ? $categories->firstWhere('id', $requestedId) : null)
            ?? $categories->first();

        $rounds = [];
        $hasBracket = false;
        $athleteCount = 0;
        $viewerData = null;
        $roundLabels = [];
        $matchUrls = [];
        $byeIds = [];
        $emptyIds = [];
        $champion = null;

        if ($selected) {
            $athleteCount = $selected->athletes()->where('status', 'active')->count();
            $hasBracket = BracketSlot::where('category_id', $selected->id)->exists();

            if ($hasBracket) {
                $service = app(BracketService::class);

                $slotsByRound = BracketSlot::with('athlete.contingent')
                    ->where('category_id', $selected->id)
                    ->get()
                    ->groupBy('round');

                $matchesByRound = Matchup::with([
                    'schedule.category',
                    'athleteA.contingent',
                    'athleteB.contingent',
                    'winner.contingent',
                ])
                    ->whereHas('schedule', fn ($builder) => $builder
                        ->where('category_id', $selected->id)
                        ->where('type', 'daeryun'))
                    ->get()
                    ->groupBy('round');

                $rounds = collect($service->rounds($selected))->map(function (array $round) use ($slotsByRound, $matchesByRound) {
                    $slots = $slotsByRound->get((string) $round['size'], collect())
                        ->sortBy('position')
                        ->values();

                    $matches = $matchesByRound->get($round['key'], collect())
                        ->keyBy('position');

                    $pairings = [];

                    for ($position = 0; $position < (int) ($round['size'] / 2); $position++) {
                        $pairings[] = [
                            'position' => $position + 1,
                            'slot_a' => $slots->get($position * 2),
                            'slot_b' => $slots->get(($position * 2) + 1),
                            'match' => $matches->get($position + 1),
                        ];
                    }

                    return $round + ['pairings' => $pairings];
                })->all();

                $payload = $this->buildViewerPayload($selected, $rounds);

                $viewerData = $payload['viewerData'];
                $roundLabels = $payload['roundLabels'];
                $matchUrls = $payload['matchUrls'];
                $byeIds = $payload['byeIds'];
                $emptyIds = $payload['emptyIds'];
                $champion = $payload['champion'];
            }
        }

        return view('admin.bracket.index', [
            'categories' => $categories,
            'selected' => $selected,
            'rounds' => $rounds,
            'hasBracket' => $hasBracket,
            'athleteCount' => $athleteCount,
            'viewerData' => $viewerData,
            'roundLabels' => $roundLabels,
            'matchUrls' => $matchUrls,
            'byeIds' => $byeIds,
            'emptyIds' => $emptyIds,
            'champion' => $champion,
        ]);
    }

    /**
     * Susun data bracket (brackets-viewer.js) dari round + pairing hasil
     * bracket_slots dan matches: peserta, bye, slot kosong, skor, status,
     * serta tautan partai untuk tiap match.
     */
    protected function buildViewerPayload(Category $category, array $rounds): array
    {
        $participants = [];
        $seen = [];
        $matches = [];
        $roundLabels = [];
        $matchUrls = [];
        $byeIds = [];
        $emptyIds = [];
        $champion = null;
        $matchId = 0;

        foreach ($rounds as $roundIndex => $round) {
            $roundLabels[] = $round['label'];

            foreach ($round['pairings'] as $pairing) {
                $matchId++;

                $slotA = $pairing['slot_a'];
                $slotB = $pairing['slot_b'];
                $match = $pairing['match'];

                $athleteA = $slotA && $slotA->athlete_id !== null ? (int) $slotA->athlete_id : null;
                $athleteB = $slotB && $slotB->athlete_id !== null ? (int) $slotB->athlete_id : null;

                foreach ([$slotA, $slotB] as $slot) {
                    $athlete = $slot?->athlete;

                    if ($athlete && ! isset($seen[(int) $slot->athlete_id])) {
                        $seen[(int) $slot->athlete_id] = true;
                        $participants[] = [
                            'id' => (int) $slot->athlete_id,
                            'tournament_id' => $category->id,
                            'name' => $athlete->name,
                        ];
                    }
                }

                $opponentA = ['id' => $athleteA];
                $opponentB = ['id' => $athleteB];
                $status = 0;

                if ($match && $match->status === 'finished') {
                    $status = 4; // Completed
                    $winnerId = $match->winner_athlete_id !== null ? (int) $match->winner_athlete_id : null;

                    if ($winnerId !== null && $athleteA !== null) {
                        $opponentA['result'] = $winnerId === $athleteA ? 'win' : 'loss';
                    }

                    if ($winnerId !== null && $athleteB !== null) {
                        $opponentB['result'] = $winnerId === $athleteB ? 'win' : 'loss';
                    }

                    if ($match->hasScore()) {
                        if ($athleteA !== null) {
                            $opponentA['score'] = (int) $match->score_a;
                        }

                        if ($athleteB !== null) {
                            $opponentB['score'] = (int) $match->score_b;
                        }
                    }
                } elseif ($match && $match->status === 'running') {
                    $status = 3; // Running
                } elseif ($match) {
                    $status = match (true) {
                        $athleteA !== null && $athleteB !== null => 2, // Ready
                        $athleteA !== null || $athleteB !== null => 1, // Waiting
                        default => 0, // Locked
                    };
                } elseif ($athleteA !== null && $athleteB !== null) {
                    $status = 2; // Ready (pasangan penuh tanpa match: tidak lazim)
                } elseif ($athleteA !== null || $athleteB !== null) {
                    // Bye: peserta lolos tanpa lawan, sisi kosong ditampilkan "BYE".
                    $status = 4; // Completed

                    if ($athleteA !== null) {
                        $opponentA['result'] = 'win';
                        $opponentB = null;
                    } else {
                        $opponentB['result'] = 'win';
                        $opponentA = null;
                    }

                    $byeIds[] = $matchId;
                } else {
                    // Slot kosong: tidak pernah ada peserta pada posisi ini.
                    $emptyIds[] = $matchId;
                }

                $matches[] = [
                    'id' => $matchId,
                    'number' => (int) $pairing['position'],
                    'stage_id' => 0,
                    'group_id' => 0,
                    'round_id' => $roundIndex,
                    'child_count' => 0,
                    'status' => $status,
                    'opponent1' => $opponentA,
                    'opponent2' => $opponentB,
                ];

                if ($match && $match->schedule) {
                    $matchUrls[$matchId] = route('admin.matches.show', $match);

                    if ($match->round === 'final' && $match->status === 'finished' && $match->winner) {
                        $champion = [
                            'id' => (int) $match->winner->id,
                            'name' => $match->winner->name,
                        ];
                    }
                }
            }
        }

        return [
            'viewerData' => [
                'stages' => [[
                    'id' => 0,
                    'tournament_id' => $category->id,
                    'name' => $category->name,
                    'type' => 'single_elimination',
                    'settings' => [
                        'size' => (int) ($rounds[0]['size'] ?? 2),
                        'skipFirstRound' => false,
                    ],
                    'number' => 1,
                ]],
                'matches' => $matches,
                'matchGames' => [],
                'participants' => $participants,
            ],
            'roundLabels' => $roundLabels,
            'matchUrls' => $matchUrls,
            'byeIds' => $byeIds,
            'emptyIds' => $emptyIds,
            'champion' => $champion,
        ];
    }

    public function generate(Request $request): RedirectResponse
    {
        $data = $request->validate(
            [
                'category_id' => ['required', 'integer', Rule::exists('categories', 'id')],
            ],
            [
                'category_id.required' => 'Kategori wajib dipilih.',
                'category_id.exists' => 'Kategori tidak ditemukan.',
            ]
        );

        $category = Category::find($data['category_id']);

        if (! $category) {
            return back()->with('error', 'Kategori tidak ditemukan.');
        }

        if ($category->type !== 'daeryun') {
            return back()->with('error', 'Bracket hanya bisa dibuat untuk kategori Daeryun.');
        }

        $athleteCount = $category->athletes()->where('status', 'active')->count();

        if ($athleteCount < 2) {
            return back()->with('error', "Bracket gagal dibuat: peserta aktif pada kategori {$category->name} baru {$athleteCount} orang (minimal 2).");
        }

        DB::transaction(function () use ($category) {
            app(BracketService::class)->generate($category);
        });

        if (! BracketSlot::where('category_id', $category->id)->exists()) {
            return back()->with('error', "Bracket kategori {$category->name} gagal dibuat. Silakan periksa kembali data peserta.");
        }

        return redirect()
            ->route('admin.brackets.index', ['category' => $category->id])
            ->with('success', "Bracket kategori {$category->name} berhasil dibuat untuk {$athleteCount} peserta.");
    }
}
