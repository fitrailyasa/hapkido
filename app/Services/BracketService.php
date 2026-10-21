<?php

namespace App\Services;

use App\Models\Athlete;
use App\Models\BracketSlot;
use App\Models\Category;
use App\Models\Arena;
use App\Models\Matchup;
use App\Models\Schedule;
use Illuminate\Support\Collection;

class BracketService
{
    /**
     * Build the bracket automatically: entrants in the first round, automatic byes,
     * then matches are created for every round up to the final.
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

        $this->clear($category);

        $size = 2;
        while ($size < $athletes->count()) {
            $size *= 2;
        }

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
        // bracket region.
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

                // Bye: only one side is alive. If the entrant is already known
                // advance them straight away; if not, no match is created and
                // advance() passes the winner through later.
                if ($aliveA !== $aliveB) {
                    $winnerId = ($aliveA ? $athleteA : $athleteB) ?? null;

                    if ($winnerId !== null) {
                        $this->fillNextSlot($slots, $roundSize, $position, $winnerId);
                    }

                    continue;
                }

                // Both sides are alive: the match is created even if its entrants
                // are not complete yet (filled in by advance() or already set
                // through a bye).
                $schedule = Schedule::create([
                    'match_no' => $this->nextMatchNo(),
                    'category_id' => $category->id,
                    // The arena follows the class.
                    'arena_id' => Arena::forCategory($category->id, 'daeryun')?->id,
                    'type' => 'daeryun',
                    'round' => $this->label($roundSize),
                    'order_no' => $position + 1,
                    'match_date' => now()->toDateString(),
                    'status' => 'pending',
                ]);

                Matchup::create([
                    'schedule_id' => $schedule->id,
                    'athlete_a_id' => $athleteA,
                    'athlete_b_id' => $athleteB,
                    'winner_athlete_id' => null,
                    'status' => 'pending',
                    'round' => $this->label($roundSize),
                    'position' => $position + 1,
                ]);
            }

            $roundSize = (int) ($roundSize / 2);
        }
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

    protected function nextMatchNo(): string
    {
        $numbers = Schedule::pluck('match_no')
            ->filter(fn ($number) => is_numeric($number))
            ->map(fn ($number) => (int) $number);

        return sprintf('%03d', ($numbers->max() ?? 0) + 1);
    }

    protected function clear(Category $category): void
    {
        $schedules = Schedule::where('category_id', $category->id)
            ->where('type', 'daeryun')
            ->get();

        Matchup::whereIn('schedule_id', $schedules->pluck('id'))->delete();
        $schedules->each->delete();

        BracketSlot::where('category_id', $category->id)->delete();
    }
}
