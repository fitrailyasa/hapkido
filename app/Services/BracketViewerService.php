<?php

namespace App\Services;

use App\Models\BracketSlot;
use App\Models\Category;
use App\Models\Matchup;
use App\Models\Schedule;

/**
 * Builds the data for the brackets-viewer.js library.
 *
 * The same payload is used by the admin bracket page and by the public
 * display screens, so both always show the same result.
 */
class BracketViewerService
{
    public function __construct(private readonly BracketService $brackets)
    {
    }

    /**
     * Does the class already have a bracket?
     *
     * The bracket is built from the jadwal of the class, so a class without
     * jadwal never has a bracket (leftover slots of a removed jadwal are
     * ignored).
     */
    public function hasBracket(Category $category): bool
    {
        return BracketSlot::where('category_id', $category->id)->exists()
            && Schedule::where('category_id', $category->id)
                ->where('type', 'daeryun')
                ->exists();
    }

    /**
     * The rounds of the bracket with the pairings (slot A, slot B and match).
     *
     * @return array<int, array<string, mixed>>
     */
    public function rounds(Category $category): array
    {
        $slotsByRound = BracketSlot::with('athlete.contingent')
            ->where('category_id', $category->id)
            ->get()
            ->groupBy('round');

        $matchesByRound = Matchup::with([
            'schedule.category',
            'athleteA.contingent',
            'athleteB.contingent',
            'winner.contingent',
        ])
            ->whereHas('schedule', fn ($builder) => $builder
                ->where('category_id', $category->id)
                ->where('type', 'daeryun'))
            ->get()
            ->groupBy('round');

        return collect($this->brackets->rounds($category))->map(function (array $round) use ($slotsByRound, $matchesByRound) {
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
    }

    /**
     * Build the bracket data (brackets-viewer.js) from the rounds plus the pairings
     * resulting from bracket_slots and matches: entrants, byes, empty slots, scores,
     * and the match links for each match.
     *
     * @return array{
     *     viewerData: array<string, mixed>,
     *     roundLabels: array<int, string>,
     *     matchUrls: array<int, string>,
     *     byeIds: array<int, int>,
     *     emptyIds: array<int, int>,
     *     champion: array{id: int, name: string}|null
     * }|null
     */
    public function payload(Category $category, bool $withAdminLinks = false): ?array
    {
        if (! $this->hasBracket($category)) {
            return null;
        }

        $rounds = $this->rounds($category);

        if ($rounds === []) {
            return null;
        }

        $participants = [];
        $seen = [];
        $matches = [];
        $roundLabels = [];
        $matchUrls = [];
        $byeIds = [];
        $emptyIds = [];
        $champion = null;
        $matchId = 0;

        // Prelim feeders (no slots in the viewer tree): map main-bracket slot
        // index → matchup so the empty QF/SF slot still shows both names.
        $largest = (int) ($rounds[0]['size'] ?? 0);
        $prelimBySlot = collect();

        if ($largest >= 2) {
            $prelimBySlot = Matchup::with(['athleteA', 'athleteB', 'schedule'])
                ->whereHas('schedule', fn ($builder) => $builder
                    ->where('category_id', $category->id)
                    ->where('type', 'daeryun'))
                ->where('round', $this->brackets->label($largest * 2))
                ->get()
                ->keyBy(fn (Matchup $match) => max(0, ((int) $match->position) - 1));
        }

        $hasUnfinishedEarlier = [];
        $unfinishedSoFar = false;
        foreach ($rounds as $roundIndex => $round) {
            $hasUnfinishedEarlier[$roundIndex] = $unfinishedSoFar;
            foreach ($round['pairings'] as $pairing) {
                $prior = $pairing['match'];
                if ($prior && $prior->status !== 'finished') {
                    $unfinishedSoFar = true;
                    break;
                }
            }
        }

        // Prelim still open counts as unfinished for later-round BYE crowning.
        if ($prelimBySlot->contains(fn (Matchup $match) => $match->status !== 'finished')) {
            $unfinishedSoFar = true;
            foreach ($rounds as $roundIndex => $round) {
                if ($roundIndex > 0) {
                    $hasUnfinishedEarlier[$roundIndex] = true;
                }
            }
        }

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

                // First round of the main bagan: empty slot waiting on a prelim
                // shows "A vs B" so all 9 athletes are visible (not just 7).
                $prelimForUrl = null;

                if ($roundIndex === 0 && $largest >= 2) {
                    $slotAPos = ((int) $pairing['position'] - 1) * 2;
                    $slotBPos = $slotAPos + 1;

                    if ($athleteA === null && $prelimBySlot->has($slotAPos)) {
                        $athleteA = $this->registerPrelimPlaceholder(
                            $prelimBySlot->get($slotAPos),
                            $category->id,
                            $participants,
                            $seen
                        );
                        $prelimForUrl = $prelimBySlot->get($slotAPos);
                    }

                    if ($athleteB === null && $prelimBySlot->has($slotBPos)) {
                        $athleteB = $this->registerPrelimPlaceholder(
                            $prelimBySlot->get($slotBPos),
                            $category->id,
                            $participants,
                            $seen
                        );
                        $prelimForUrl = $prelimBySlot->get($slotBPos);
                    }
                }

                $opponentA = ['id' => $athleteA];
                $opponentB = ['id' => $athleteB];
                $status = 0;

                // Paper-style: athlete with BYE facing a penyisihan placeholder.
                if ($prelimForUrl !== null) {
                    $byeIds[] = $matchId;
                }

                $isByeMatch = ($athleteA !== null && $athleteB === null)
                    || ($athleteB !== null && $athleteA === null);

                if ($match && $match->status === 'finished' && $isByeMatch) {
                    // Manual / stored BYE: one athlete, no opponent.
                    $status = 4;
                    $byeIds[] = $matchId;

                    if ($athleteA !== null) {
                        $opponentA['result'] = 'win';
                        $opponentB = null;
                    } else {
                        $opponentB['result'] = 'win';
                        $opponentA = null;
                    }
                } elseif ($match && $match->status === 'finished') {
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
                    $status = 2; // Ready (full pairing without a match: unusual)
                } elseif ($athleteA !== null || $athleteB !== null) {
                    // One name, no matchup row: structural BYE from generate —
                    // but never crown a later-round "winner" while earlier partai
                    // are still open (stale slot after a bad advance).
                    if ($hasUnfinishedEarlier[$roundIndex] ?? false) {
                        $status = 1; // Waiting
                    } else {
                        $status = 4; // Completed BYE
                        $byeIds[] = $matchId;

                        if ($athleteA !== null) {
                            $opponentA['result'] = 'win';
                            $opponentB = null;
                        } else {
                            $opponentB['result'] = 'win';
                            $opponentA = null;
                        }
                    }
                } else {
                    // Empty slot: no entrant has ever been in this position.
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
                    if ($withAdminLinks) {
                        $matchUrls[$matchId] = route('admin.matches.show', $match);
                    }

                    if ($match->round === 'final' && $match->status === 'finished' && $match->winner) {
                        $champion = [
                            'id' => (int) $match->winner->id,
                            'name' => $match->winner->name,
                        ];
                    }
                } elseif ($withAdminLinks && $prelimForUrl?->schedule) {
                    // Slot still waiting on penyisihan — open that partai instead.
                    $matchUrls[$matchId] = route('admin.matches.show', $prelimForUrl);
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

    /**
     * Register a synthetic participant so the bagan shows both prelim athletes
     * in the empty main-bracket slot (e.g. "Hendra vs Atlet 1").
     *
     * @param  array<int, array<string, mixed>>  $participants
     * @param  array<int, true>  $seen
     */
    protected function registerPrelimPlaceholder(
        Matchup $prelim,
        int $tournamentId,
        array &$participants,
        array &$seen
    ): int {
        $virtualId = -1 * (int) $prelim->id;

        if (isset($seen[$virtualId])) {
            return $virtualId;
        }

        $nameA = $prelim->athleteA?->name ?? '—';
        $nameB = $prelim->athleteB?->name ?? '—';

        $seen[$virtualId] = true;
        $participants[] = [
            'id' => $virtualId,
            'tournament_id' => $tournamentId,
            'name' => "{$nameA} vs {$nameB}",
        ];

        return $virtualId;
    }
}
