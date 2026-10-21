<?php

namespace App\Services;

use App\Exceptions\BracketNeedsSchedule;
use App\Models\Arena;
use App\Models\Athlete;
use App\Models\BracketSlot;
use App\Models\Category;
use App\Models\Matchup;
use App\Models\Schedule;
use Illuminate\Support\Collection;

class BracketService
{
    /**
     * Build the bracket automatically from active athletes of the class.
     *
     * Paper-style when the count is not a power of two (e.g. 9 → 1 penyisihan
     * + bagan 8 besar). Bye recipients are seeded into the main bracket — no
     * stack of "Nama vs BYE" boxes. Jadwal stubs are created as needed.
     */
    public function generate(Category $category): void
    {
        $athletes = $category->athletes()
            ->where('status', 'active')
            ->orderBy('id')
            ->get();

        if ($athletes->count() < 2) {
            return;
        }

        $roundCounts = $this->daeryunRoundCounts($category);

        // Drop the previous bracket first so a regenerate always follows the
        // latest athlete count (not leftover slots from a larger tree).
        $this->clear($category);

        $n = $athletes->count();
        $fullSize = $this->nextPowerOfTwo($n);
        $hasPrelim = $n !== $fullSize;
        $mainSize = $hasPrelim ? (int) ($fullSize / 2) : $fullSize;
        $byeCount = $fullSize - $n;
        $entryLabel = $this->label($hasPrelim ? $fullSize : $mainSize);

        if ($roundCounts->isNotEmpty()) {
            $this->normalizeFirstRoundLabels($category, $roundCounts, $entryLabel);
        }

        $jadwal = $this->jadwalQueue($category);
        $list = $athletes->values();
        $index = 0;

        $slots = [];

        for ($roundSize = $mainSize; $roundSize >= 2; $roundSize = (int) ($roundSize / 2)) {
            for ($position = 0; $position < $roundSize; $position++) {
                $slots[$roundSize][$position] = BracketSlot::create([
                    'category_id' => $category->id,
                    'round' => (string) $roundSize,
                    'position' => $position,
                    'athlete_id' => null,
                ]);
            }
        }

        $alive = [$mainSize => []];

        if ($hasPrelim) {
            // Which main-bracket slots are seeded (bye) vs waiting for a prelim.
            $byeSlots = $this->distributedByePairings($mainSize, $byeCount);
            $prelimLabel = $this->label($fullSize);

            for ($slot = 0; $slot < $mainSize; $slot++) {
                if (isset($byeSlots[$slot])) {
                    $slots[$mainSize][$slot]->update(['athlete_id' => $list[$index]->id]);
                    $index++;
                    $alive[$mainSize][$slot] = true;
                } else {
                    // Prelim match feeds this slot; keep it empty until finished.
                    $alive[$mainSize][$slot] = true;
                    $athleteA = $list[$index]->id;
                    $index++;
                    $athleteB = $list[$index]->id;
                    $index++;

                    $schedule = $jadwal->get($prelimLabel, collect())->shift()
                        ?? $this->createMissingRoundSchedule(
                            $category,
                            $prelimLabel,
                            $slot + 1,
                            $this->scheduleTemplate($category)
                        );

                    Matchup::create([
                        'schedule_id' => $schedule->id,
                        'athlete_a_id' => $athleteA,
                        'athlete_b_id' => $athleteB,
                        'winner_athlete_id' => null,
                        'status' => 'pending',
                        'round' => $prelimLabel,
                        'position' => $slot + 1,
                    ]);
                }
            }
        } else {
            for ($position = 0; $position < $mainSize; $position++) {
                $slots[$mainSize][$position]->update(['athlete_id' => $list[$index]->id]);
                $index++;
                $alive[$mainSize][$position] = true;
            }
        }

        for ($roundSize = $mainSize; $roundSize >= 4; $roundSize = (int) ($roundSize / 2)) {
            $childSize = (int) ($roundSize / 2);

            for ($position = 0; $position < $childSize; $position++) {
                $alive[$childSize][$position] = ($alive[$roundSize][$position * 2] ?? false)
                    || ($alive[$roundSize][($position * 2) + 1] ?? false);
            }
        }

        $roundSize = $mainSize;

        while ($roundSize >= 2) {
            $matchCount = (int) ($roundSize / 2);

            for ($position = 0; $position < $matchCount; $position++) {
                $athleteA = $slots[$roundSize][$position * 2]->athlete_id;
                $athleteB = $slots[$roundSize][($position * 2) + 1]->athlete_id;
                $aliveA = $alive[$roundSize][$position * 2] ?? false;
                $aliveB = $alive[$roundSize][($position * 2) + 1] ?? false;

                if (! $aliveA && ! $aliveB) {
                    continue;
                }

                if ($aliveA !== $aliveB) {
                    $winnerId = ($aliveA ? $athleteA : $athleteB) ?? null;

                    if ($winnerId !== null) {
                        $this->fillNextSlot($slots, $roundSize, $position, $winnerId);
                    }

                    continue;
                }

                $label = $this->label($roundSize);
                $schedule = $jadwal->get($label, collect())->shift()
                    ?? $this->createMissingRoundSchedule(
                        $category,
                        $label,
                        $position + 1,
                        $this->scheduleTemplate($category)
                    );

                Matchup::create([
                    'schedule_id' => $schedule->id,
                    'athlete_a_id' => $athleteA,
                    'athlete_b_id' => $athleteB,
                    'winner_athlete_id' => null,
                    'status' => 'pending',
                    'round' => $label,
                    'position' => $position + 1,
                ]);
            }

            $roundSize = (int) ($roundSize / 2);
        }

        $this->ensureBracketMatches($category);
    }

    /**
     * Next power of two ≥ $count (minimum 2).
     */
    protected function nextPowerOfTwo(int $count): int
    {
        $size = 2;

        while ($size < $count) {
            $size *= 2;
        }

        return $size;
    }

    /**
     * Place athletes into first-round slots with balanced single-step BYEs.
     *
     * Example (9 athletes → size 16): 7 BYE pairings + 1 real match so the
     * BYE recipients enter the round of 8, not the Final.
     *
     * @return array{slots: array<int, int|null>, byePairings: array<int, true>}
     */
    protected function placeAthletesWithByes(Collection $athletes, int $size): array
    {
        $list = $athletes->values();
        $n = $list->count();
        $slots = array_fill(0, $size, null);

        $pairingCount = (int) ($size / 2);
        $byes = $size - $n;

        $byePairings = $this->distributedByePairings($pairingCount, $byes);

        $index = 0;

        for ($pairing = 0; $pairing < $pairingCount; $pairing++) {
            if (isset($byePairings[$pairing])) {
                if ($index < $n) {
                    $slots[$pairing * 2] = $list[$index]->id;
                    $index++;
                }

                continue;
            }

            if ($index < $n) {
                $slots[$pairing * 2] = $list[$index]->id;
                $index++;
            }

            if ($index < $n) {
                $slots[($pairing * 2) + 1] = $list[$index]->id;
                $index++;
            }
        }

        // Safety: if distribution left athletes unused (duplicate bye indices),
        // append them into remaining empty match slots.
        for ($pairing = 0; $pairing < $pairingCount && $index < $n; $pairing++) {
            if (isset($byePairings[$pairing])) {
                continue;
            }

            if ($slots[$pairing * 2] === null) {
                $slots[$pairing * 2] = $list[$index]->id;
                $index++;
            }

            if ($index < $n && $slots[($pairing * 2) + 1] === null) {
                $slots[($pairing * 2) + 1] = $list[$index]->id;
                $index++;
            }
        }

        return [
            'slots' => $slots,
            'byePairings' => $byePairings,
        ];
    }

    /**
     * Spread BYE pairings evenly around the bracket so each half stays balanced.
     *
     * @return array<int, true> pairing index => true
     */
    protected function distributedByePairings(int $pairingCount, int $byes): array
    {
        $set = [];

        if ($byes <= 0 || $pairingCount <= 0) {
            return $set;
        }

        $byes = min($byes, $pairingCount);

        for ($i = 0; $i < $byes; $i++) {
            $index = (int) floor($i * $pairingCount / $byes);

            while (isset($set[$index])) {
                $index = ($index + 1) % $pairingCount;
            }

            $set[$index] = true;
        }

        return $set;
    }

    /**
     * Rebuild later-round slots from real results only.
     *
     * An athlete may occupy a slot in round N only when:
     * - the feeder partai in round 2N is finished and they are the winner, or
     * - the feeder pairing is a structural BYE (one entrant, no matchup).
     *
     * This clears premature names in Semifinal/Final when earlier partai are
     * still open (e.g. after an old multi-hop advance bug).
     */
    public function reconcileAdvancement(Category $category): void
    {
        $sizes = BracketSlot::where('category_id', $category->id)
            ->distinct()
            ->pluck('round')
            ->map(fn ($round) => (int) $round)
            ->sortDesc()
            ->values();

        if ($sizes->count() < 2) {
            return;
        }

        $largest = (int) $sizes->first();

        $hasPrelimFeeders = Matchup::whereHas('schedule', fn ($query) => $query
            ->where('category_id', $category->id)
            ->where('type', 'daeryun'))
            ->where('round', $this->label($largest * 2))
            ->exists();

        // Main bracket slots may be fed by prelim matches (no slots at prevSize).
        if ($hasPrelimFeeders) {
            for ($position = 0; $position < $largest; $position++) {
                $currentId = BracketSlot::where('category_id', $category->id)
                    ->where('round', (string) $largest)
                    ->where('position', $position)
                    ->value('athlete_id');

                $expected = $this->expectedAthleteForSlot(
                    $category,
                    $sizes,
                    $largest * 2,
                    $position,
                    $currentId !== null ? (int) $currentId : null,
                    forMainSlot: true
                );

                BracketSlot::where('category_id', $category->id)
                    ->where('round', (string) $largest)
                    ->where('position', $position)
                    ->update(['athlete_id' => $expected]);
            }
        }

        foreach ($sizes as $roundSize) {
            $roundSize = (int) $roundSize;

            if ($roundSize >= $largest) {
                continue;
            }

            $prevSize = $roundSize * 2;

            if (! $sizes->contains($prevSize)) {
                continue;
            }

            for ($position = 0; $position < $roundSize; $position++) {
                $expected = $this->expectedAthleteForSlot($category, $sizes, $prevSize, $position);

                BracketSlot::where('category_id', $category->id)
                    ->where('round', (string) $roundSize)
                    ->where('position', $position)
                    ->update(['athlete_id' => $expected]);
            }
        }
    }

    /**
     * Who belongs in a slot, based on the previous round / prelim feeder.
     *
     * When $forMainSlot is true, $pairingIndex is a slot index in the main
     * bracket (prelim position = slot+1), not a pairing index into two feeders.
     */
    protected function expectedAthleteForSlot(
        Category $category,
        Collection $sizes,
        int $prevSize,
        int $pairingIndex,
        ?int $currentAthleteId = null,
        bool $forMainSlot = false
    ): ?int {
        $feederMatch = $this->findMatch(
            $category,
            $sizes,
            $prevSize,
            $forMainSlot ? ($pairingIndex + 1) : ($pairingIndex + 1)
        );

        if ($forMainSlot) {
            if ($feederMatch) {
                if ($feederMatch->status === 'finished' && $feederMatch->winner_athlete_id) {
                    return (int) $feederMatch->winner_athlete_id;
                }

                // Prelim still open — clear premature fill, keep empty.
                return null;
            }

            // No prelim for this slot: keep the seeded bye athlete.
            return $currentAthleteId;
        }

        if ($feederMatch) {
            if ($feederMatch->status === 'finished' && $feederMatch->winner_athlete_id) {
                return (int) $feederMatch->winner_athlete_id;
            }

            // Partai still open — nobody has advanced yet.
            return null;
        }

        $slotA = BracketSlot::where('category_id', $category->id)
            ->where('round', (string) $prevSize)
            ->where('position', $pairingIndex * 2)
            ->value('athlete_id');

        $slotB = BracketSlot::where('category_id', $category->id)
            ->where('round', (string) $prevSize)
            ->where('position', ($pairingIndex * 2) + 1)
            ->value('athlete_id');

        // Structural BYE: only one side ever had an entrant (no matchup row).
        if ($slotA !== null && $slotB === null) {
            return (int) $slotA;
        }

        if ($slotB !== null && $slotA === null) {
            return (int) $slotB;
        }

        return null;
    }

    /**
     * Ensure every competitive pairing has a Matchup + Schedule so bracket
     * boxes (Perempat Final, Semifinal, Final) are clickable.
     *
     * Safe to call on page load: existing rows are reused; only gaps are filled.
     */
    public function ensureBracketMatches(Category $category): void
    {
        $this->reconcileAdvancement($category);

        $sizes = BracketSlot::where('category_id', $category->id)
            ->distinct()
            ->pluck('round')
            ->map(fn ($round) => (int) $round)
            ->sortDesc()
            ->values();

        if ($sizes->isEmpty()) {
            return;
        }

        $largest = (int) $sizes->first();
        $slotsByRound = BracketSlot::where('category_id', $category->id)
            ->orderBy('position')
            ->get(['round', 'position', 'athlete_id'])
            ->groupBy('round');

        $alive = [];

        for ($position = 0; $position < $largest; $position++) {
            $row = $slotsByRound->get((string) $largest)?->firstWhere('position', $position);
            $alive[$largest][$position] = ($row?->athlete_id !== null);
        }

        // First-round matchups keep a branch alive even before slots look filled.
        Matchup::whereHas('schedule', fn ($query) => $query
            ->where('category_id', $category->id)
            ->where('type', 'daeryun'))
            ->where('round', $this->label($largest))
            ->get(['position', 'athlete_a_id', 'athlete_b_id'])
            ->each(function (Matchup $match) use (&$alive, $largest) {
                $index = max(0, ((int) $match->position) - 1);
                if ($match->athlete_a_id) {
                    $alive[$largest][$index * 2] = true;
                }
                if ($match->athlete_b_id) {
                    $alive[$largest][($index * 2) + 1] = true;
                }
            });

        // Prelim matches feed a single main-bracket slot (position = slot+1).
        Matchup::whereHas('schedule', fn ($query) => $query
            ->where('category_id', $category->id)
            ->where('type', 'daeryun'))
            ->where('round', $this->label($largest * 2))
            ->get(['position'])
            ->each(function (Matchup $match) use (&$alive, $largest) {
                $slot = max(0, ((int) $match->position) - 1);
                if ($slot < $largest) {
                    $alive[$largest][$slot] = true;
                }
            });

        for ($roundSize = $largest; $roundSize >= 4; $roundSize = (int) ($roundSize / 2)) {
            $nextSize = (int) ($roundSize / 2);

            for ($position = 0; $position < $nextSize; $position++) {
                $alive[$nextSize][$position] = ($alive[$roundSize][$position * 2] ?? false)
                    || ($alive[$roundSize][($position * 2) + 1] ?? false);
            }
        }

        $template = $this->scheduleTemplate($category);

        // Refresh slots after reconcile.
        $slotsByRound = BracketSlot::where('category_id', $category->id)
            ->orderBy('position')
            ->get(['round', 'position', 'athlete_id'])
            ->groupBy('round');

        foreach ($sizes as $roundSize) {
            $roundSize = (int) $roundSize;
            $matchCount = (int) ($roundSize / 2);

            for ($position = 0; $position < $matchCount; $position++) {
                $aliveA = $alive[$roundSize][$position * 2] ?? false;
                $aliveB = $alive[$roundSize][($position * 2) + 1] ?? false;

                // Only real meetings (both branches alive) need a clickable partai.
                if (! $aliveA || ! $aliveB) {
                    continue;
                }

                $slotA = $slotsByRound->get((string) $roundSize)?->firstWhere('position', $position * 2);
                $slotB = $slotsByRound->get((string) $roundSize)?->firstWhere('position', ($position * 2) + 1);

                $this->ensureMatchForPairing(
                    $category,
                    $sizes,
                    $roundSize,
                    $position,
                    $slotA?->athlete_id,
                    $slotB?->athlete_id,
                    $template
                );
            }
        }
    }

    /**
     * Find or create the Matchup (+ jadwal) for one pairing of a round.
     */
    protected function ensureMatchForPairing(
        Category $category,
        Collection $sizes,
        int $roundSize,
        int $pairingIndex,
        ?int $athleteAId,
        ?int $athleteBId,
        ?Schedule $template = null
    ): Matchup {
        $position = $pairingIndex + 1;
        $label = $this->label($roundSize);
        $existing = $this->findMatch($category, $sizes, $roundSize, $position);

        if ($existing) {
            if ($existing->status !== 'finished') {
                // Sync from slots (null clears a premature advance).
                $existing->update([
                    'athlete_a_id' => $athleteAId,
                    'athlete_b_id' => $athleteBId,
                ]);
            }

            return $existing->fresh();
        }

        $schedule = Schedule::where('category_id', $category->id)
            ->where('type', 'daeryun')
            ->where('round', $label)
            ->whereDoesntHave('match')
            ->orderBy('order_no')
            ->orderBy('id')
            ->first()
            ?? $this->createMissingRoundSchedule(
                $category,
                $label,
                $position,
                $template ?? $this->scheduleTemplate($category)
            );

        return Matchup::create([
            'schedule_id' => $schedule->id,
            'athlete_a_id' => $athleteAId,
            'athlete_b_id' => $athleteBId,
            'winner_athlete_id' => null,
            'status' => 'pending',
            'round' => $label,
            'position' => $position,
        ]);
    }

    /**
     * An existing jadwal row used as a template (arena, tanggal, jam) when
     * generate has to invent Semifinal / Final partai automatically.
     */
    protected function scheduleTemplate(Category $category): ?Schedule
    {
        return Schedule::where('category_id', $category->id)
            ->where('type', 'daeryun')
            ->orderByDesc('order_no')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Create a jadwal partai for a bracket round (used when generating bracket
     * before any jadwal exists, or when later rounds are still missing).
     */
    protected function createMissingRoundSchedule(
        Category $category,
        string $label,
        int $orderNo,
        ?Schedule $template
    ): Schedule {
        $sequence = (int) Schedule::query()->count() + 1;

        do {
            $matchNo = sprintf('B-%s-%02d', $category->id, $sequence);
            $sequence++;
        } while (Schedule::where('match_no', $matchNo)->exists());

        $arenaId = $template?->arena_id
            ?? Arena::where('category_id', $category->id)->value('id');

        return Schedule::create([
            'match_no' => $matchNo,
            'category_id' => $category->id,
            'arena_id' => $arenaId,
            'type' => 'daeryun',
            'round' => $label,
            'order_no' => $orderNo,
            'match_date' => $template?->match_date?->toDateString() ?? now()->toDateString(),
            'start_time' => $template?->start_time,
            'end_time' => $template?->end_time,
            'status' => 'pending',
        ]);
    }

    /**
     * The schedules (jadwal) of the class that can still hold a partai, per
     * round label, in the order of the round.
     *
     * @return \Illuminate\Support\Collection<string, \Illuminate\Support\Collection<int, Schedule>>
     */
    protected function jadwalQueue(Category $category)
    {
        return Schedule::where('category_id', $category->id)
            ->where('type', 'daeryun')
            ->whereDoesntHave('match')
            ->orderBy('order_no')
            ->orderBy('match_no')
            ->orderBy('id')
            ->get()
            ->groupBy('round');
    }

    /**
     * Create a partai by hand. Pass null for $athleteB to award a BYE (odd
     * participant / single entrant advances without an opponent).
     */
    public function assignManual(Category $category, Schedule $schedule, Athlete $athleteA, ?Athlete $athleteB = null): void
    {
        if (! BracketSlot::where('category_id', $category->id)->exists()) {
            $this->prepareManual($category);
        }

        $sizes = $this->ensureSlots($category);
        $schedule->refresh();
        $mainSize = (int) max($sizes);
        $mainLabel = $this->label($mainSize);
        $prelimLabel = $this->label($mainSize * 2);

        $siblings = Schedule::where('category_id', $category->id)
            ->where('type', 'daeryun')
            ->where('round', $schedule->round)
            ->orderBy('order_no')
            ->orderBy('match_no')
            ->orderBy('id')
            ->pluck('id');

        $index = (int) $siblings->search($schedule->id);

        if ($schedule->round === $prelimLabel) {
            if ($athleteB === null) {
                throw new BracketNeedsSchedule(
                    'Partai penyisihan harus diisi dua atlet (bukan BYE).'
                );
            }

            Matchup::updateOrCreate(
                ['schedule_id' => $schedule->id],
                [
                    'athlete_a_id' => $athleteA->id,
                    'athlete_b_id' => $athleteB->id,
                    'winner_athlete_id' => null,
                    'status' => 'pending',
                    'round' => $prelimLabel,
                    'position' => $index + 1,
                    'finished_at' => null,
                ]
            );

            $this->ensureBracketMatches($category);

            return;
        }

        if ($schedule->round !== $mainLabel) {
            throw new BracketNeedsSchedule(
                "Partai manual hanya bisa diisi pada ronde masuk bracket "
                . "({$this->labelHuman($prelimLabel)} / {$this->labelHuman($mainLabel)}). "
                . "Ronde {$this->labelHuman($schedule->round)} diisi otomatis dari pemenang ronde sebelumnya."
            );
        }

        BracketSlot::where('category_id', $category->id)
            ->where('round', (string) $mainSize)
            ->where('position', $index * 2)
            ->update(['athlete_id' => $athleteA->id]);

        BracketSlot::where('category_id', $category->id)
            ->where('round', (string) $mainSize)
            ->where('position', ($index * 2) + 1)
            ->update(['athlete_id' => $athleteB?->id]);

        if ($athleteB === null) {
            Matchup::updateOrCreate(
                ['schedule_id' => $schedule->id],
                [
                    'athlete_a_id' => $athleteA->id,
                    'athlete_b_id' => null,
                    'winner_athlete_id' => $athleteA->id,
                    'status' => 'finished',
                    'round' => $mainLabel,
                    'position' => $index + 1,
                    'finished_at' => now(),
                ]
            );

            $this->advanceByeToNextRound($category, $mainSize, $index, $athleteA->id);

            return;
        }

        Matchup::updateOrCreate(
            ['schedule_id' => $schedule->id],
            [
                'athlete_a_id' => $athleteA->id,
                'athlete_b_id' => $athleteB->id,
                'winner_athlete_id' => null,
                'status' => 'pending',
                'round' => $mainLabel,
                'position' => $index + 1,
                'finished_at' => null,
            ]
        );

        $this->ensureBracketMatches($category);
    }

    /**
     * Advance a BYE winner into the next round slot (and into the next match
     * if that partai already exists).
     */
    protected function advanceByeToNextRound(Category $category, int $roundSize, int $pairing, int $athleteId): void
    {
        $nextSize = (int) ($roundSize / 2);

        if ($nextSize < 2) {
            return;
        }

        BracketSlot::where('category_id', $category->id)
            ->where('round', (string) $nextSize)
            ->where('position', $pairing)
            ->update(['athlete_id' => $athleteId]);

        $sizes = collect(
            BracketSlot::where('category_id', $category->id)
                ->distinct()
                ->pluck('round')
                ->map(fn ($round) => (int) $round)
                ->sortDesc()
                ->values()
                ->all()
        );

        $nextMatchPosition = intdiv($pairing, 2);
        $nextMatch = $this->findMatch($category, $sizes, $nextSize, $nextMatchPosition + 1);

        if ($nextMatch === null) {
            return;
        }

        $slotA = BracketSlot::where('category_id', $category->id)
            ->where('round', (string) $nextSize)
            ->where('position', $nextMatchPosition * 2)
            ->first();

        $slotB = BracketSlot::where('category_id', $category->id)
            ->where('round', (string) $nextSize)
            ->where('position', ($nextMatchPosition * 2) + 1)
            ->first();

        $nextMatch->update([
            'athlete_a_id' => $slotA?->athlete_id,
            'athlete_b_id' => $slotB?->athlete_id,
        ]);
    }

    /**
     * Ensure empty entry jadwal rows exist so Partai Manual can be filled
     * before generate (bracket-first). Does not create bracket slots.
     */
    public function ensureEntrySchedules(Category $category): void
    {
        $athleteCount = $category->athletes()->where('status', 'active')->count();

        if ($athleteCount < 2) {
            return;
        }

        $fullSize = $this->nextPowerOfTwo($athleteCount);
        $hasPrelim = $athleteCount !== $fullSize;
        $mainSize = $hasPrelim ? (int) ($fullSize / 2) : $fullSize;
        $prelimCount = $hasPrelim ? ($athleteCount - $mainSize) : 0;
        $mainFirstCount = (int) ($mainSize / 2);
        $template = $this->scheduleTemplate($category);

        if ($prelimCount > 0) {
            $prelimLabel = $this->label($fullSize);
            $existingPrelim = Schedule::where('category_id', $category->id)
                ->where('type', 'daeryun')
                ->where('round', $prelimLabel)
                ->count();

            for ($order = $existingPrelim + 1; $order <= $prelimCount; $order++) {
                $this->createMissingRoundSchedule($category, $prelimLabel, $order, $template);
            }
        }

        $mainLabel = $this->label($mainSize);
        $existingMain = Schedule::where('category_id', $category->id)
            ->where('type', 'daeryun')
            ->where('round', $mainLabel)
            ->count();

        for ($order = $existingMain + 1; $order <= $mainFirstCount; $order++) {
            $this->createMissingRoundSchedule($category, $mainLabel, $order, $template);
        }
    }

    /**
     * Prepare an empty bracket + entry partai for manual pairing from the
     * athletes of this class (no auto-draw, jadwal not required beforehand).
     */
    public function prepareManual(Category $category): void
    {
        $athleteCount = $category->athletes()->where('status', 'active')->count();

        if ($athleteCount < 2) {
            throw new BracketNeedsSchedule(
                "Partai manual gagal disiapkan: peserta aktif pada kelas {$category->name} "
                . "baru {$athleteCount} orang (minimal 2)."
            );
        }

        $fullSize = $this->nextPowerOfTwo($athleteCount);
        $hasPrelim = $athleteCount !== $fullSize;
        $mainSize = $hasPrelim ? (int) ($fullSize / 2) : $fullSize;

        if (! BracketSlot::where('category_id', $category->id)->exists()) {
            for ($roundSize = $mainSize; $roundSize >= 2; $roundSize = (int) ($roundSize / 2)) {
                for ($position = 0; $position < $roundSize; $position++) {
                    BracketSlot::create([
                        'category_id' => $category->id,
                        'round' => (string) $roundSize,
                        'position' => $position,
                        'athlete_id' => null,
                    ]);
                }
            }
        }

        $this->ensureEntrySchedules($category);
    }

    /**
     * The bracket slots of the class.
     *
     * @return array<int, int>
     */
    protected function ensureSlots(Category $category): array
    {
        $sizes = BracketSlot::where('category_id', $category->id)
            ->distinct()
            ->pluck('round')
            ->map(fn ($round) => (int) $round)
            ->sortDesc()
            ->values();

        if ($sizes->isNotEmpty()) {
            return $sizes->all();
        }

        throw new BracketNeedsSchedule(
            "Kelas {$category->name} belum memiliki bracket. "
            . 'Generate bracket atau siapkan partai manual terlebih dahulu.'
        );
    }

    /**
     * @return \Illuminate\Support\Collection<string, int>
     */
    protected function daeryunRoundCounts(Category $category): Collection
    {
        return Schedule::where('category_id', $category->id)
            ->where('type', 'daeryun')
            ->get(['round'])
            ->countBy('round');
    }

    /**
     * Bracket width = next power of two that fits (first-round partai × 2).
     *
     * @param  \Illuminate\Support\Collection<string, int>  $roundCounts
     */
    protected function sizeFromJadwalCounts(Collection $roundCounts): int
    {
        $firstRoundCount = (int) $roundCounts->max();

        $size = 1;

        while ($size < $firstRoundCount * 2) {
            $size *= 2;
        }

        return max(2, $size);
    }

    /**
     * Rename the busiest jadwal round to the canonical first-round label for
     * the chosen bracket size (e.g. leftover "penyisihan" → "perempat_final").
     *
     * @param  \Illuminate\Support\Collection<string, int>  $roundCounts
     */
    protected function normalizeFirstRoundLabels(Category $category, Collection $roundCounts, string $canonicalFirst): void
    {
        $busiest = $roundCounts->sortDesc()->keys()->first();

        if ($busiest === null || $busiest === $canonicalFirst) {
            return;
        }

        // Do not clobber an existing canonical round if both labels somehow coexist.
        if ($roundCounts->has($canonicalFirst)) {
            return;
        }

        Schedule::where('category_id', $category->id)
            ->where('type', 'daeryun')
            ->where('round', $busiest)
            ->update(['round' => $canonicalFirst]);
    }

    /**
     * Save the match winner, then move the athlete to the next round only.
     *
     * Structural BYEs are already applied when the bracket is generated
     * (fillNextSlot). This method must not hop across multiple rounds —
     * otherwise a winner appears in the Final while earlier partai are still open.
     */
    public function advance(Matchup $match, Athlete $winner): void
    {
        $category = $match->schedule->category;

        $sizes = BracketSlot::where('category_id', $category->id)
            ->distinct()
            ->pluck('round')
            ->map(fn ($round) => (int) $round)
            ->sortDesc()
            ->values();

        $currentSize = $this->resolveRoundSize($match, $sizes);

        // Match number P (starting at 1) contests slots 2(P-1) and 2(P-1)+1 of
        // its round, so its winner takes slot (P-1) in the next round.
        $pairing = $match->position - 1;
        $nextSize = (int) ($currentSize / 2);

        if ($nextSize < 2) {
            // Final has no further round.
            return;
        }

        BracketSlot::where('category_id', $category->id)
            ->where('round', (string) $nextSize)
            ->where('position', $pairing)
            ->update(['athlete_id' => $winner->id]);

        $nextMatchPosition = intdiv($pairing, 2);

        $slotA = BracketSlot::where('category_id', $category->id)
            ->where('round', (string) $nextSize)
            ->where('position', $nextMatchPosition * 2)
            ->first();

        $slotB = BracketSlot::where('category_id', $category->id)
            ->where('round', (string) $nextSize)
            ->where('position', ($nextMatchPosition * 2) + 1)
            ->first();

        // Create Perempat Final / Semifinal / Final on demand when the admin
        // only prepared Penyisihan jadwal — otherwise the box is not clickable.
        $this->ensureMatchForPairing(
            $category,
            $sizes,
            $nextSize,
            $nextMatchPosition,
            $slotA?->athlete_id,
            $slotB?->athlete_id
        );
    }

    /**
     * Find the match in the round of size $size at position $position.
     * If a round label is used for several sizes (e.g. 32 and 16 are both
     * "penyisihan"), match_no is used to tell them apart because the largest
     * round is always created first when generating.
     */
    protected function findMatch(Category $category, Collection $sizes, int $size, int $position): ?Matchup
    {
        $label = $this->label($size);

        $matches = Matchup::whereHas('schedule', fn ($query) => $query
            ->where('category_id', $category->id)
            ->where('type', 'daeryun'))
            ->where('round', $label)
            ->where('position', $position)
            ->orderBy('id')
            ->get();

        if ($matches->isEmpty()) {
            return null;
        }

        $ambiguous = $sizes
            ->filter(fn ($candidate) => $this->label((int) $candidate) === $label)
            ->count() > 1;

        if (! $ambiguous) {
            return $matches->first();
        }

        $context = $this->matchNoContext($category, $sizes);

        foreach ($matches as $candidate) {
            if ($this->sizeForMatchNo((int) $candidate->schedule->match_no, $sizes, $context) === $size) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Context for mapping match_no -> round size for a category.
     * Generate creates the largest round's matches first, so sequential
     * match_no values can be mapped to the round blocks per size.
     */
    protected function matchNoContext(Category $category, Collection $sizes): array
    {
        $firstMatchNo = (int) Schedule::where('category_id', $category->id)
            ->where('type', 'daeryun')
            ->pluck('match_no')
            ->filter(fn ($number) => is_numeric($number))
            ->map(fn ($number) => (int) $number)
            ->min();

        $largest = (int) $sizes->first();

        $byRound = BracketSlot::where('category_id', $category->id)
            ->orderBy('position')
            ->get(['round', 'athlete_id'])
            ->groupBy('round');

        $alive = [];

        $row = $byRound->get((string) $largest);

        for ($position = 0; $position < $largest; $position++) {
            $alive[$largest][$position] = (($row[$position]->athlete_id ?? null) !== null);
        }

        $counts = [];

        foreach ($sizes as $size) {
            $size = (int) $size;
            $count = 0;

            for ($position = 0; $position < $size; $position += 2) {
                if (($alive[$size][$position] ?? false) && ($alive[$size][$position + 1] ?? false)) {
                    $count++;
                }
            }

            $counts[$size] = $count;

            $nextSize = (int) ($size / 2);

            if ($nextSize >= 2) {
                for ($position = 0; $position < $nextSize; $position++) {
                    $alive[$nextSize][$position] = ($alive[$size][$position * 2] ?? false)
                        || ($alive[$size][($position * 2) + 1] ?? false);
                }
            }
        }

        return ['firstMatchNo' => $firstMatchNo, 'counts' => $counts];
    }

    /**
     * Determine the round size of a match from its match_no.
     */
    protected function sizeForMatchNo(int $matchNo, Collection $sizes, array $context): int
    {
        $offset = $matchNo - $context['firstMatchNo'];

        if ($offset < 0) {
            return (int) $sizes->first();
        }

        foreach ($sizes as $size) {
            $size = (int) $size;
            $width = $context['counts'][$size] ?? 0;

            if ($offset < $width) {
                return $size;
            }

            $offset -= $width;
        }

        return (int) $sizes->last();
    }

    /**
     * Determine the round size (number of slots) of a match from its match_no.
     * A single label can be used for several sizes (e.g. 32 and 16 are both
     * "penyisihan"); the match_no order is used to tell them apart because
     * the largest round is always created first when generating.
     */
    protected function resolveRoundSize(Matchup $match, Collection $sizes): int
    {
        $candidates = $sizes
            ->filter(fn ($size) => $this->label((int) $size) === $match->round)
            ->values();

        if ($candidates->isEmpty()) {
            // Prelim round: no slots at fullSize, only at mainSize = fullSize/2.
            $largest = (int) ($sizes->first() ?? 2);
            if ($largest >= 2 && $this->label($largest * 2) === $match->round) {
                return $largest * 2;
            }

            return 2;
        }

        if ($candidates->count() === 1) {
            return (int) $candidates->first();
        }

        $context = $this->matchNoContext($match->schedule->category, $sizes);

        return $this->sizeForMatchNo((int) $match->schedule->match_no, $sizes, $context);
    }

    /**
     * Advance the athlete (bye) to the next round.
     */
    protected function fillNextSlot(array &$slots, int $roundSize, int $position, ?int $athleteId): void
    {
        $nextSize = (int) ($roundSize / 2);

        if ($nextSize < 2 || ! isset($slots[$nextSize][$position])) {
            return;
        }

        $slots[$nextSize][$position]->update(['athlete_id' => $athleteId]);
    }

    public function label(int $size): string
    {
        return match (true) {
            $size >= 16 => 'penyisihan',
            $size === 8 => 'perempat_final',
            $size === 4 => 'semifinal',
            default => 'final',
        };
    }

    public function labelHuman(string $label): string
    {
        return match ($label) {
            'penyisihan' => 'Penyisihan',
            'perempat_final' => 'Perempat Final',
            'semifinal' => 'Semifinal',
            'final' => 'Final',
            default => ucfirst($label),
        };
    }

    public function rounds(Category $category): array
    {
        $sizes = BracketSlot::where('category_id', $category->id)
            ->distinct()
            ->pluck('round')
            ->map(fn ($round) => (int) $round)
            ->sortDesc()
            ->values();

        return $sizes->map(fn ($size) => [
            'size' => $size,
            'key' => $this->label($size),
            'label' => $this->labelHuman($this->label($size)),
        ])->all();
    }

    /**
     * Drop the bracket of a class once its last jadwal is removed: the
     * bracket is built from the jadwal, so without jadwal there is no
     * bracket.
     */
    public function forgetIfEmpty(Category $category): void
    {
        $hasJadwal = Schedule::where('category_id', $category->id)
            ->where('type', 'daeryun')
            ->exists();

        if (! $hasJadwal) {
            $this->clear($category);
        }
    }

    /**
     * Remove the bracket of a class: the partai (matchups) it produced and the
     * slots. The jadwal of the class is kept, because it is the source of the
     * partai.
     */
    protected function clear(Category $category): void
    {
        $schedules = Schedule::where('category_id', $category->id)
            ->where('type', 'daeryun')
            ->get();

        Matchup::whereIn('schedule_id', $schedules->pluck('id'))->delete();

        BracketSlot::where('category_id', $category->id)->delete();
    }
}
