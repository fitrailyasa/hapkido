<?php

namespace App\Services;

use App\Models\Athlete;
use App\Models\BracketSlot;
use App\Models\Category;
use App\Models\Matchup;
use App\Models\Schedule;
use Illuminate\Support\Collection;

class BracketService
{
    /**
     * Bangun bracket otomatis: peserta pada round pertama, bye otomatis,
     * lalu dibuat match untuk setiap round sampai final.
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

        // Slot yang masih mungkin terisi: ada peserta, atau menjadi tujuan
        // pemenang partai sebelumnya. Slot mati = daerah bracket kosong.
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

                // Kedua sisi berasal dari daerah kosong: tidak ada partai.
                if (! $aliveA && ! $aliveB) {
                    continue;
                }

                // Bye: hanya satu sisi yang hidup. Bila pesertanya sudah
                // diketahui langsung loloskan; bila belum, partai memang tidak
                // dibuat dan pemenangnya diloloskan oleh advance().
                if ($aliveA !== $aliveB) {
                    $winnerId = ($aliveA ? $athleteA : $athleteB) ?? null;

                    if ($winnerId !== null) {
                        $this->fillNextSlot($slots, $roundSize, $position, $winnerId);
                    }

                    continue;
                }

                // Dua sisi hidup: partai dibuat meski pesertanya belum lengkap
                // (akan diisi oleh advance() atau sudah terisi lewat bye).
                $schedule = Schedule::create([
                    'match_no' => $this->nextMatchNo(),
                    'category_id' => $category->id,
                    'arena_id' => null,
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
     * Simpan pemenang match lalu lanjutkan atlet ke round berikutnya.
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

        // Match ke-P (mulai 1) memperebutkan slot 2(P-1) dan 2(P-1)+1 dari
        // roundnya, jadi pemenangnya menempati slot (P-1) pada round berikutnya.
        $size = $currentSize;
        $pairing = $match->position - 1;

        while (true) {
            $nextSize = (int) ($size / 2);

            if ($nextSize < 2) {
                // Pemenang final
                return;
            }

            BracketSlot::where('category_id', $category->id)
                ->where('round', (string) $nextSize)
                ->where('position', $pairing)
                ->update(['athlete_id' => $winner->id]);

            // Pasangan slot (2Q dan 2Q+1) pada round berikutnya memberi makan
            // match ke-(Q+1) pada round berikutnya, berapa pun urutan selesainya.
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

            // Partai tidak ada (lawan berasal dari daerah kosong): pemenang
            // lolos terus melewati round ini.
            $size = $nextSize;
            $pairing = $nextMatchPosition;
        }
    }

    /**
     * Cari match pada round berukuran $size dengan posisi $position.
     * Bila label round dipakai beberapa ukuran (mis. 32 dan 16 sama-sama
     * "penyisihan"), match_no dipakai sebagai pembeda karena round terbesar
     * selalu dibuat paling awal saat generate.
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
     * Konteks pemetaan match_no -> ukuran round untuk sebuah kategori.
     * Generate membuat match round terbesar lebih dulu, jadi match_no yang
     * berurutan bisa dipetakan ke blok round per ukuran.
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
     * Tentukan ukuran round sebuah match dari match_no-nya.
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
     * Tentukan ukuran round (jumlah slot) sebuah match dari match_no-nya.
     * Satu label bisa dipakai beberapa ukuran (mis. 32 dan 16 sama-sama
     * "penyisihan"); urutan match_no dipakai sebagai pembedanya karena
     * round terbesar selalu dibuat paling awal saat generate.
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
     * Loloskan atlet (bye) ke round berikutnya.
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
