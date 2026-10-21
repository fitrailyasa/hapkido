<?php

namespace App\Services;

use App\Exceptions\BracketNeedsSchedule;
use App\Models\Athlete;
use App\Models\BracketSlot;
use App\Models\Category;
use App\Models\Matchup;
use App\Models\Schedule;
use Illuminate\Support\Collection;

class BracketService
{
    /**
     * Build the bracket automatically: entrants in the first round, automatic byes,
     * then matches are created for every round up to the final.
     *
     * The bracket width follows the current jadwal (ronde dengan partai terbanyak).
     * Odd athlete counts get BYEs into the next power-of-two size that still fits
     * that jadwal. The partai are bound to existing schedules so times/arenas stay.
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

        if ($roundCounts->isEmpty()) {
            throw new BracketNeedsSchedule(
                "Kelas {$category->name} belum memiliki jadwal partai Daeryun. "
                . 'Buat jadwal terlebih dahulu di menu Jadwal.'
            );
        }

        // Drop the previous bracket first so a regenerate always follows the
        // latest jadwal width (not leftover slots from a larger tree).
        $this->clear($category);

        $size = $this->sizeFromJadwalCounts($roundCounts);
        $canonicalFirst = $this->label($size);

        if ($athletes->count() > $size) {
            $neededPartai = (int) ceil($athletes->count() / 2);

            throw new BracketNeedsSchedule(
                "Jadwal partai ronde pertama kelas {$category->name} hanya menampung "
                . "{$size} peserta (saat ini {$roundCounts->max()} partai), sementara ada "
                . "{$athletes->count()} peserta aktif. Tambahkan jadwal ronde pertama "
                . "hingga minimal {$neededPartai} partai, atau nonaktifkan kelebihan peserta."
            );
        }

        // Schedules may still carry an old label (e.g. "penyisihan" after the
        // tree shrank to 8). Align the busiest round to the canonical first-round
        // label so generate and partai manual share the same names.
        $this->normalizeFirstRoundLabels($category, $roundCounts, $canonicalFirst);

        $jadwal = $this->jadwalQueue($category);

        $slots = [];

        for ($roundSize = $size; $roundSize >= 2; $roundSize = (int) ($roundSize / 2)) {
            for ($position = 0; $position < $roundSize; $position++) {
                $athleteId = null;

                if ($roundSize === $size && isset($athletes[$position])) {
                    $athleteId = $athletes[$position]->id;
                }

                $slots[$roundSize][$position] = BracketSlot::create([
                    'category_id' => $category->id,
                    'round' => (string) $roundSize,
                    'position' => $position,
                    'athlete_id' => $athleteId,
                ]);
            }
        }

        // Slots that may still be filled: they hold an entrant, or they are the
        // destination of the winner of the previous match. A dead slot = empty
        // bracket region. Odd participant counts leave empty slots → BYE.
        $alive = [$size => []];

        for ($position = 0; $position < $size; $position++) {
            $alive[$size][$position] = $slots[$size][$position]->athlete_id !== null;
        }

        for ($roundSize = $size; $roundSize >= 4; $roundSize = (int) ($roundSize / 2)) {
            $nextSize = (int) ($roundSize / 2);

            for ($position = 0; $position < $nextSize; $position++) {
                $alive[$nextSize][$position] = $alive[$roundSize][$position * 2]
                    || $alive[$roundSize][($position * 2) + 1];
            }
        }

        $roundSize = $size;

        while ($roundSize >= 2) {
            $matchCount = $roundSize / 2;

            for ($position = 0; $position < $matchCount; $position++) {
                $athleteA = $slots[$roundSize][$position * 2]->athlete_id;
                $athleteB = $slots[$roundSize][($position * 2) + 1]->athlete_id;
                $aliveA = $alive[$roundSize][$position * 2];
                $aliveB = $alive[$roundSize][($position * 2) + 1];

                // Both sides come from empty regions: no match is created.
                if (! $aliveA && ! $aliveB) {
                    continue;
                }

                // Bye: only one side is alive. Advance the known entrant; no
                // jadwal partai is consumed for a BYE.
                if ($aliveA !== $aliveB) {
                    $winnerId = ($aliveA ? $athleteA : $athleteB) ?? null;

                    if ($winnerId !== null) {
                        $this->fillNextSlot($slots, $roundSize, $position, $winnerId);
                    }

                    continue;
                }

                // Both sides are alive: bind a jadwal partai of this round.
                // Later rounds (SF/Final) are created automatically when the
                // admin only prepared the first-round jadwal.
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
     * Create a missing jadwal partai for a later bracket round so generate does
     * not force the admin to add Semifinal/Final by hand after shrinking the
     * first round.
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

        return Schedule::create([
            'match_no' => $matchNo,
            'category_id' => $category->id,
            'arena_id' => $template?->arena_id,
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
        $sizes = $this->ensureSlots($category);
        $schedule->refresh();
        $firstSize = (int) max($sizes);
        $label = $this->label($firstSize);

        if ($schedule->round !== $label) {
            throw new BracketNeedsSchedule(
                "Partai manual hanya bisa diisi pada ronde pertama ({$this->labelHuman($label)}). "
                . "Ronde {$this->labelHuman($schedule->round)} diisi otomatis dari pemenang ronde sebelumnya."
            );
        }

        $siblings = Schedule::where('category_id', $category->id)
            ->where('type', 'daeryun')
            ->where('round', $schedule->round)
            ->orderBy('order_no')
            ->orderBy('match_no')
            ->orderBy('id')
            ->pluck('id');

        $index = (int) $siblings->search($schedule->id);

        BracketSlot::where('category_id', $category->id)
            ->where('round', (string) $firstSize)
            ->where('position', $index * 2)
            ->update(['athlete_id' => $athleteA->id]);

        BracketSlot::where('category_id', $category->id)
            ->where('round', (string) $firstSize)
            ->where('position', ($index * 2) + 1)
            ->update(['athlete_id' => $athleteB?->id]);

        if ($athleteB === null) {
            // BYE: one entrant, no opponent. Mark the partai finished and advance.
            Matchup::updateOrCreate(
                ['schedule_id' => $schedule->id],
                [
                    'athlete_a_id' => $athleteA->id,
                    'athlete_b_id' => null,
                    'winner_athlete_id' => $athleteA->id,
                    'status' => 'finished',
                    'round' => $label,
                    'position' => $index + 1,
                    'finished_at' => now(),
                ]
            );

            $this->advanceByeToNextRound($category, $firstSize, $index, $athleteA->id);

            return;
        }

        Matchup::updateOrCreate(
            ['schedule_id' => $schedule->id],
            [
                'athlete_a_id' => $athleteA->id,
                'athlete_b_id' => $athleteB->id,
                'winner_athlete_id' => null,
                'status' => 'pending',
                'round' => $label,
                'position' => $index + 1,
                'finished_at' => null,
            ]
        );
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
     * The bracket slots of the class. When the class does not have a bracket
     * yet — or the jadwal width changed — the slots are (re)built from jadwal.
     *
     * @return array<int, int>
     */
    protected function ensureSlots(Category $category): array
    {
        $roundCounts = $this->daeryunRoundCounts($category);

        if ($roundCounts->isEmpty()) {
            throw new BracketNeedsSchedule(
                "Kelas {$category->name} belum memiliki jadwal partai Daeryun. "
                . 'Buat jadwal terlebih dahulu di menu Jadwal.'
            );
        }

        $expectedSize = $this->sizeFromJadwalCounts($roundCounts);
        $canonicalFirst = $this->label($expectedSize);

        // Keep schedule.round labels aligned with the bracket width even when
        // slots already exist (e.g. leftover "penyisihan" after shrinking).
        $this->normalizeFirstRoundLabels($category, $roundCounts, $canonicalFirst);

        $sizes = BracketSlot::where('category_id', $category->id)
            ->distinct()
            ->pluck('round')
            ->map(fn ($round) => (int) $round)
            ->sortDesc()
            ->values();

        if ($sizes->isNotEmpty() && (int) $sizes->first() === $expectedSize) {
            return $sizes->all();
        }

        // Jadwal was added/removed: drop the stale tree and rebuild empty slots
        // that match the current first-round width.
        if ($sizes->isNotEmpty()) {
            $this->clear($category);
        }

        $built = [];

        for ($roundSize = $expectedSize; $roundSize >= 2; $roundSize = (int) ($roundSize / 2)) {
            $built[] = $roundSize;

            for ($position = 0; $position < $roundSize; $position++) {
                BracketSlot::create([
                    'category_id' => $category->id,
                    'round' => (string) $roundSize,
                    'position' => $position,
                    'athlete_id' => null,
                ]);
            }
        }

        return $built;
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
     * Save the match winner, then move the athlete to the next round.
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
        $size = $currentSize;
        $pairing = $match->position - 1;

        while (true) {
            $nextSize = (int) ($size / 2);

            if ($nextSize < 2) {
                // Final winner
                return;
            }

            BracketSlot::where('category_id', $category->id)
                ->where('round', (string) $nextSize)
                ->where('position', $pairing)
                ->update(['athlete_id' => $winner->id]);

            // The slot pair (2Q and 2Q+1) in the next round feeds match
            // number (Q+1) of that round, regardless of the finishing order.
            $nextMatchPosition = intdiv($pairing, 2);

            $nextMatch = $this->findMatch($category, $sizes, $nextSize, $nextMatchPosition + 1);

            if ($nextMatch !== null) {
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

                return;
            }

            // The match does not exist (the opponent comes from an empty
            // region): the winner advances straight through this round.
            $size = $nextSize;
            $pairing = $nextMatchPosition;
        }
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
