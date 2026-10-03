<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Arena;
use App\Models\BracketSlot;
use App\Models\Category;
use App\Models\Matchup;
use App\Services\BracketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MatchupController extends Controller
{
    public function index(Request $request)
    {
        $categoryFilter = (string) $request->query('category');
        $statusFilter = (string) $request->query('status');
        $arenaFilter = (string) $request->query('arena');

        $statuses = [
            'pending' => 'Menunggu',
            'running' => 'Berlangsung',
            'finished' => 'Selesai',
        ];

        $query = Matchup::query()
            ->with([
                'schedule.category',
                'schedule.arena',
                'athleteA.contingent',
                'athleteB.contingent',
                'winner.contingent',
            ])
            ->orderBy('id');

        if ($categoryFilter !== '') {
            $query->whereHas('schedule', fn ($builder) => $builder->where('category_id', $categoryFilter));
        }

        if (isset($statuses[$statusFilter])) {
            $query->where('status', $statusFilter);
        }

        if ($arenaFilter !== '') {
            $query->whereHas('schedule', fn ($builder) => $builder->where('arena_id', $arenaFilter));
        }

        return view('admin.match.index', [
            'matches' => $query->paginate(15)->withQueryString(),
            'categories' => Category::orderBy('name')->get(),
            'arenas' => Arena::orderBy('name')->get(),
            'statuses' => $statuses,
            'categoryFilter' => $categoryFilter,
            'statusFilter' => $statusFilter,
            'arenaFilter' => $arenaFilter,
            'bracketRounds' => $this->bracketRounds(),
        ]);
    }

    public function show(Matchup $matchup)
    {
        $matchup->load([
            'schedule.category',
            'schedule.arena',
            'schedule.callings.athlete',
            'schedule.verifications.athlete',
            'schedule.verifications.verifiedBy',
            'athleteA.contingent',
            'athleteB.contingent',
            'winner.contingent',
        ]);

        return view('admin.match.show', [
            'match' => $matchup,
            'bracketRounds' => $this->bracketRounds(),
        ]);
    }

    public function start(Matchup $matchup): RedirectResponse
    {
        $schedule = $matchup->schedule;

        if ($schedule && $schedule->type === 'art') {
            return redirect()
                ->route('admin.performances.index')
                ->with('error', 'Partai seni dijalankan melalui menu Seni, bukan menu pertandingan.');
        }

        if ($matchup->status === 'finished') {
            return back()->with('error', 'Pertandingan sudah selesai dan tidak bisa dimulai ulang.');
        }

        if ($matchup->status === 'running') {
            return back()->with('info', 'Pertandingan sudah berlangsung.');
        }

        if (! $matchup->athlete_a_id || ! $matchup->athlete_b_id) {
            return back()->with('error', 'Kedua atlet belum ditentukan. Lengkapi bracket terlebih dahulu.');
        }

        DB::transaction(function () use ($matchup, $schedule) {
            $matchup->update([
                'status' => 'running',
                'started_at' => $matchup->started_at ?? now(),
            ]);

            if ($schedule) {
                $schedule->status = 'running';

                if (empty($schedule->start_time)) {
                    $schedule->start_time = now()->format('H:i:s');
                }

                $schedule->save();

                app(\App\Services\ArenaLiveService::class)->push($schedule);
            }
        });

        return back()->with('success', 'Pertandingan dimulai.');
    }

    public function finish(Request $request, Matchup $matchup): RedirectResponse
    {
        $schedule = $matchup->schedule;

        if ($schedule && $schedule->type === 'art') {
            return redirect()
                ->route('admin.performances.index')
                ->with('error', 'Penilaian partai seni dilakukan melalui menu Seni (input nilai juri).');
        }

        if ($matchup->status === 'finished') {
            return back()->with('error', 'Pertandingan sudah pernah diselesaikan.');
        }

        $athleteIds = array_values(array_filter([
            $matchup->athlete_a_id ? (int) $matchup->athlete_a_id : null,
            $matchup->athlete_b_id ? (int) $matchup->athlete_b_id : null,
        ]));

        if (count($athleteIds) !== 2) {
            return back()->with('error', 'Kedua atlet pada partai ini belum lengkap.');
        }

        $data = $request->validate(
            [
                'winner_id' => ['required', 'integer', Rule::in($athleteIds)],
                'score_a' => ['required', 'integer', 'min:0', 'max:999'],
                'score_b' => ['required', 'integer', 'min:0', 'max:999'],
            ],
            [
                'winner_id.required' => 'Pemenang wajib dipilih.',
                'winner_id.in' => 'Pemenang harus salah satu atlet pada partai ini.',
                'score_a.required' => 'Skor atlet A wajib diisi.',
                'score_b.required' => 'Skor atlet B wajib diisi.',
                'score_a.integer' => 'Skor atlet A harus berupa angka bulat.',
                'score_b.integer' => 'Skor atlet B harus berupa angka bulat.',
                'score_a.min' => 'Skor atlet A tidak boleh kurang dari 0.',
                'score_b.min' => 'Skor atlet B tidak boleh kurang dari 0.',
                'score_a.max' => 'Skor atlet A maksimal 999.',
                'score_b.max' => 'Skor atlet B maksimal 999.',
            ]
        );

        $winnerIsA = (int) $data['winner_id'] === (int) $matchup->athlete_a_id;
        $winnerScore = $winnerIsA ? (int) $data['score_a'] : (int) $data['score_b'];
        $opponentScore = $winnerIsA ? (int) $data['score_b'] : (int) $data['score_a'];

        if ($winnerScore < $opponentScore) {
            return back()->with('error', 'Skor pemenang tidak boleh lebih rendah dari skor lawan.');
        }

        $winner = $matchup->athleteA()->whereKey($data['winner_id'])->first()
            ?? $matchup->athleteB()->whereKey($data['winner_id'])->first();

        if (! $winner) {
            return back()->with('error', 'Pemenang tidak terdaftar pada partai ini.');
        }

        $bracket = $this->isBracketMatch($matchup);
        $isFinal = $matchup->round === 'final';

        DB::transaction(function () use ($matchup, $schedule, $data, $winner, $bracket) {
            $matchup->update([
                'status' => 'finished',
                'winner_athlete_id' => $winner->id,
                'winner_id' => $winner->id,
                'score_a' => (int) $data['score_a'],
                'score_b' => (int) $data['score_b'],
                'finished_at' => $matchup->finished_at ?? now(),
            ]);

            if ($schedule) {
                $schedule->status = 'finished';

                if (empty($schedule->end_time)) {
                    $schedule->end_time = now()->format('H:i:s');
                }

                $schedule->save();

                app(\App\Services\ArenaLiveService::class)->release($schedule);
            }

            if ($bracket) {
                app(BracketService::class)->advance($matchup, $winner);
                $this->syncMatchesFromSlots($schedule->category);
            }
        });

        $message = $bracket
            ? ($isFinal
                ? 'Hasil final disimpan. Selamat kepada juara!'
                : 'Hasil disimpan. Pemenang lanjut ke babak berikutnya.')
            : 'Hasil pertandingan berhasil disimpan.';

        return back()->with('success', $message);
    }

    /**
     * Partai ini dibuat oleh BracketService (bukan jadwal manual).
     */
    protected function isBracketMatch(Matchup $matchup): bool
    {
        $schedule = $matchup->schedule;

        if (! $schedule || $schedule->type !== 'daeryun') {
            return false;
        }

        $rounds = $this->bracketRounds();

        return isset($rounds[$schedule->category_id])
            && in_array($matchup->round, $rounds[$schedule->category_id], true);
    }

    /**
     * Map category_id => label ronde yang ada pada bracket.
     */
    protected function bracketRounds(): array
    {
        $service = app(BracketService::class);

        return BracketSlot::query()
            ->get(['category_id', 'round'])
            ->groupBy('category_id')
            ->map(fn ($slots) => $slots
                ->map(fn (BracketSlot $slot) => $service->label((int) $slot->round))
                ->unique()
                ->values()
                ->all())
            ->all();
    }

    /**
     * Sinkronkan peserta seluruh babak dari bracket_slots.
     * (Complementary terhadap BracketService::advance.)
     */
    protected function syncMatchesFromSlots(Category $category): void
    {
        $service = app(BracketService::class);

        $slotsByRound = BracketSlot::where('category_id', $category->id)
            ->get()
            ->groupBy('round');

        foreach ($slotsByRound as $roundSize => $roundSlots) {
            $size = (int) $roundSize;
            $label = $service->label($size);
            $sorted = $roundSlots->sortBy('position')->values();

            for ($position = 0; $position < (int) ($size / 2); $position++) {
                $slotA = $sorted->get($position * 2);
                $slotB = $sorted->get(($position * 2) + 1);

                if (! $slotA || ! $slotB) {
                    continue;
                }

                Matchup::query()
                    ->whereHas('schedule', fn ($builder) => $builder
                        ->where('category_id', $category->id)
                        ->where('type', 'daeryun'))
                    ->where('round', $label)
                    ->where('position', $position + 1)
                    ->update([
                        'athlete_a_id' => $slotA->athlete_id,
                        'athlete_b_id' => $slotB->athlete_id,
                    ]);
            }
        }
    }
}
