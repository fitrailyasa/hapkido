<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\BracketNeedsSchedule;
use App\Http\Controllers\Controller;
use App\Models\Athlete;
use App\Models\BracketSlot;
use App\Models\Category;
use App\Models\Matchup;
use App\Models\Schedule;
use App\Services\BracketViewerService;
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
        $jadwalCount = 0;
        $firstRound = null;
        $manualPartai = collect();
        $athletes = collect();
        $usedAthleteIds = collect();

        if ($selected) {
            $athleteCount = $selected->athletes()->where('status', 'active')->count();
            $viewer = app(BracketViewerService::class);
            $hasBracket = $viewer->hasBracket($selected);

            // The partai of the bracket come from the jadwal of the class, so
            // the page also shows how many partai are still empty and which
            // athletes can be paired by hand.
            $jadwal = $selected->schedules()->where('type', 'daeryun')->get();
            $jadwalCount = $jadwal->count();
            $firstRound = $jadwal->countBy('round')->sortDesc()->keys()->first();

            $manualPartai = $jadwal
                ->filter(fn (Schedule $schedule) => $schedule->round === $firstRound && $schedule->match === null)
                ->sortBy(['order_no', 'match_no', 'id'])
                ->values();

            $athletes = $selected->athletes()
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name', 'participant_number']);

            // Athletes that already have a partai on another jadwal of this
            // class: the manual partai shows them as "sudah bertanding" so
            // they are not paired twice.
            $usedAthleteIds = Matchup::whereIn('schedule_id', $jadwal->pluck('id'))
                ->get(['athlete_a_id', 'athlete_b_id'])
                ->flatMap(fn (Matchup $match) => [$match->athlete_a_id, $match->athlete_b_id])
                ->filter()
                ->unique()
                ->values();

            if ($hasBracket) {
                // Backfill Perempat Final / later partai so bracket boxes are
                // clickable even when the admin only created Penyisihan jadwal.
                app(\App\Services\BracketService::class)->ensureBracketMatches($selected);

                $rounds = $viewer->rounds($selected);

                $payload = $viewer->payload($selected, withAdminLinks: true);

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
            'jadwalCount' => $jadwalCount,
            'firstRound' => $firstRound,
            'manualPartai' => $manualPartai,
            'athletes' => $athletes,
            'usedAthleteIds' => $usedAthleteIds,
        ]);
    }

    public function generate(Request $request): RedirectResponse
    {
        $data = $request->validate(
            [
                'category_id' => ['required', 'integer', Rule::exists('categories', 'id')],
            ],
            [
                'category_id.required' => 'Kelas wajib dipilih.',
                'category_id.exists' => 'Kelas tidak ditemukan.',
            ]
        );

        $category = Category::find($data['category_id']);

        if (! $category) {
            return back()->with('error', 'Kelas tidak ditemukan.');
        }

        if ($category->type !== 'daeryun') {
            return back()->with('error', 'Bracket hanya bisa dibuat untuk kelas Daeryun.');
        }

        $athleteCount = $category->athletes()->where('status', 'active')->count();

        if ($athleteCount < 2) {
            return back()->with('error', "Bracket gagal dibuat: peserta aktif pada kelas {$category->name} baru {$athleteCount} orang (minimal 2).");
        }

        try {
            DB::transaction(function () use ($category) {
                app(\App\Services\BracketService::class)->generate($category);
            });
        } catch (BracketNeedsSchedule $exception) {
            return back()->with('error', $exception->getMessage());
        }

        if (! BracketSlot::where('category_id', $category->id)->exists()) {
            return back()->with('error', "Bracket kelas {$category->name} gagal dibuat. Silakan periksa kembali data peserta.");
        }

        return redirect()
            ->route('admin.brackets.index', ['category' => $category->id])
            ->with('success', "Bracket kelas {$category->name} berhasil dibuat untuk {$athleteCount} peserta.");
    }

    /**
     * Create a partai by hand: the admin picks which two athletes of the class
     * meet in one partai of the jadwal.
     */
    public function manual(Request $request): RedirectResponse
    {
        $data = $request->validate(
            [
                'schedule_id' => ['required', 'integer', Rule::exists('schedules', 'id')],
                'athlete_a_id' => ['required', 'integer', Rule::exists('athletes', 'id')],
                // Empty / "bye" = BYE (peserta ganjil, lolos tanpa lawan).
                'athlete_b_id' => ['nullable', 'integer', Rule::exists('athletes', 'id')],
            ],
            [
                'schedule_id.required' => 'Partai wajib dipilih.',
                'athlete_a_id.required' => 'Atlet pertama wajib dipilih.',
            ]
        );

        $schedule = Schedule::with('category')->find($data['schedule_id']);

        if (! $schedule || $schedule->type !== 'daeryun') {
            return back()->with('error', 'Partai manual hanya bisa dibuat untuk jadwal Daeryun.');
        }

        $category = $schedule->category;

        if ($schedule->match()->exists()) {
            return back()->with('error', "Partai {$schedule->match_no} sudah memiliki atlet.");
        }

        $athleteBId = isset($data['athlete_b_id']) ? (int) $data['athlete_b_id'] : null;
        $isBye = $athleteBId === null || $athleteBId === 0;

        if (! $isBye && (int) $data['athlete_a_id'] === $athleteBId) {
            return back()->with('error', 'Dua atlet yang dipilih tidak boleh sama.');
        }

        $athleteIds = array_values(array_filter([(int) $data['athlete_a_id'], $athleteBId]));

        $athletes = Athlete::whereIn('id', $athleteIds)
            ->where('category_id', $category->id)
            ->where('status', 'active')
            ->get()
            ->keyBy('id');

        if (! $athletes->has((int) $data['athlete_a_id'])) {
            return back()->with('error', 'Atlet pertama harus aktif dan berasal dari kelas yang sama.');
        }

        if (! $isBye && ! $athletes->has($athleteBId)) {
            return back()->with('error', 'Kedua atlet harus aktif dan berasal dari kelas yang sama.');
        }

        $athleteA = $athletes[(int) $data['athlete_a_id']];
        $athleteB = $isBye ? null : $athletes[$athleteBId];

        $scheduleIds = Schedule::where('category_id', $category->id)
            ->where('type', 'daeryun')
            ->pluck('id');

        $existingMatchups = Matchup::whereIn('schedule_id', $scheduleIds)
            ->get(['athlete_a_id', 'athlete_b_id']);

        if (! $isBye) {
            $alreadyFought = $existingMatchups->contains(function (Matchup $match) use ($athleteA, $athleteB) {
                $pair = [(int) $match->athlete_a_id, (int) $match->athlete_b_id];

                return in_array((int) $athleteA->id, $pair, true)
                    && in_array((int) $athleteB->id, $pair, true);
            });

            if ($alreadyFought) {
                return back()->with(
                    'error',
                    "{$athleteA->name} dan {$athleteB->name} sudah pernah dipertandingkan."
                );
            }
        }

        $usedAthleteIds = $existingMatchups
            ->flatMap(fn (Matchup $match) => [$match->athlete_a_id, $match->athlete_b_id])
            ->filter()
            ->unique()
            ->map(fn ($id) => (int) $id);

        $candidates = $isBye ? collect([$athleteA]) : collect([$athleteA, $athleteB]);

        $alreadyPaired = $candidates
            ->filter(fn (Athlete $athlete) => $usedAthleteIds->contains((int) $athlete->id))
            ->pluck('name')
            ->values();

        if ($alreadyPaired->isNotEmpty()) {
            return back()->with(
                'error',
                $alreadyPaired->count() === 1
                    ? "Atlet {$alreadyPaired[0]} sudah bertanding di partai lain."
                    : 'Atlet '.$alreadyPaired->implode(' dan ').' sudah bertanding di partai lain.'
            );
        }

        try {
            DB::transaction(function () use ($category, $schedule, $athleteA, $athleteB) {
                app(\App\Services\BracketService::class)->assignManual($category, $schedule, $athleteA, $athleteB);
            });
        } catch (BracketNeedsSchedule $exception) {
            return back()->with('error', $exception->getMessage());
        }

        if ($isBye) {
            return back()
                ->with('success', "Partai {$schedule->match_no}: {$athleteA->name} BYE (lolos tanpa lawan).");
        }

        return back()
            ->with('success', "Partai {$schedule->match_no} dibuat: {$athleteA->name} vs {$athleteB->name}.");
    }
}
