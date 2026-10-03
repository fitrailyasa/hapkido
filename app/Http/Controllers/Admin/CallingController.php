<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Athlete;
use App\Models\Calling;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class CallingController extends Controller
{
    public function index()
    {
        return view('admin.calling.index', [
            'payload' => $this->payload(),
        ]);
    }

    public function data()
    {
        return response()->json($this->payload());
    }

    public function send(Request $request, Calling $calling)
    {
        $data = $request->validate([
            'level' => ['nullable', Rule::in(['30', '15', '5'])],
        ], [], ['level' => 'level calling']);

        $calling->update([
            'level' => $data['level'] ?? '30',
            'status' => 'called',
            'called_at' => now(),
        ]);

        return back()->with(
            'success',
            sprintf('Calling dikirim ke %s.', $calling->athlete?->name ?? 'atlet')
        );
    }

    public function sendAll(Request $request, Schedule $schedule)
    {
        $data = $request->validate([
            'level' => ['nullable', Rule::in(['30', '15', '5'])],
        ], [], ['level' => 'level calling']);

        $level = $data['level'] ?? '30';
        $athletes = $this->scheduleAthletes($schedule);
        $count = 0;

        foreach ($athletes as $athlete) {
            Calling::updateOrCreate(
                ['schedule_id' => $schedule->id, 'athlete_id' => $athlete->id],
                ['level' => $level, 'status' => 'called', 'called_at' => now(), 'ready_at' => null]
            );

            $count++;
        }

        if ($count === 0) {
            return back()->with('error', sprintf('Partai %s belum memiliki atlet.', $schedule->match_no));
        }

        return back()->with(
            'success',
            sprintf('Calling dikirim untuk %d atlet pada partai %s.', $count, $schedule->match_no)
        );
    }

    /**
     * Data polling: jadwal hari ini dikelompokkan per arena beserta daftar calling.
     */
    protected function payload(): array
    {
        $today = now()->toDateString();

        $schedules = Schedule::with([
            'category',
            'arena',
            'performances.athlete.contingent',
            'match.athleteA.contingent',
            'match.athleteB.contingent',
        ])
            ->whereDate('match_date', $today)
            ->orderBy('order_no')
            ->orderBy('id')
            ->get();

        $athletesBySchedule = [];

        foreach ($schedules as $schedule) {
            $athletesBySchedule[$schedule->id] = $this->scheduleAthletes($schedule);
        }

        $this->syncCallings($athletesBySchedule);
        $schedules->load('callings.athlete.contingent');

        $totals = ['waiting' => 0, 'called' => 0, 'ready' => 0, 'total' => 0];
        $groups = [];

        foreach ($schedules as $schedule) {
            $callingMap = $schedule->callings->keyBy('athlete_id');
            $counts = ['waiting' => 0, 'called' => 0, 'ready' => 0, 'total' => 0];
            $rows = [];

            foreach ($athletesBySchedule[$schedule->id] as $athlete) {
                $calling = $callingMap->get($athlete->id);
                $status = $calling?->status ?? 'waiting';

                if (isset($counts[$status])) {
                    $counts[$status]++;
                    $totals[$status]++;
                }

                $counts['total']++;
                $totals['total']++;

                $rows[] = [
                    'calling_id' => $calling?->id,
                    'athlete_id' => $athlete->id,
                    'athlete' => $athlete->name,
                    'participant_number' => $athlete->participant_number,
                    'contingent' => $athlete->contingent?->code ?? '—',
                    'level' => $calling?->level,
                    'level_label' => $calling?->level ? $calling->level . ' menit' : '—',
                    'status' => $status,
                    'status_label' => $calling?->statusLabel() ?? 'Menunggu',
                    'status_class' => ['waiting' => 'secondary', 'called' => 'danger', 'ready' => 'success'][$status] ?? 'secondary',
                    'called_at' => optional($calling?->called_at)->format('H:i'),
                ];
            }

            $schedulePayload = [
                'id' => $schedule->id,
                'match_no' => $schedule->match_no,
                'category' => $schedule->category?->name ?? '—',
                'type' => $schedule->type,
                'round' => $schedule->round,
                'start_time' => substr((string) $schedule->start_time, 0, 5),
                'status' => $schedule->status,
                'status_label' => $schedule->statusLabel(),
                'status_class' => ['pending' => 'info', 'preparation' => 'warning', 'running' => 'primary', 'finished' => 'secondary', 'cancelled' => 'dark'][$schedule->status] ?? 'secondary',
                'counts' => $counts,
                'callings' => $rows,
            ];

            $groupKey = $schedule->arena?->id ?? 0;

            if (! isset($groups[$groupKey])) {
                $groups[$groupKey] = [
                    'id' => $schedule->arena?->id,
                    'name' => $schedule->arena?->name ?? '—',
                    'label' => $schedule->arena?->label ?? 'Tanpa Arena',
                    'counts' => ['waiting' => 0, 'called' => 0, 'ready' => 0, 'total' => 0],
                    'schedules' => [],
                ];
            }

            foreach ($counts as $key => $value) {
                $groups[$groupKey]['counts'][$key] += $value;
            }

            $groups[$groupKey]['schedules'][] = $schedulePayload;
        }

        return [
            'date' => $today,
            'generated_at' => now()->format('H:i'),
            'counts' => $totals,
            'arenas' => array_values($groups),
        ];
    }

    /**
     * Pastikan setiap atlet pada jadwal hari ini punya baris calling.
     */
    protected function syncCallings(array $athletesBySchedule): void
    {
        if ($athletesBySchedule === []) {
            return;
        }

        $scheduleIds = array_keys($athletesBySchedule);

        $existing = Calling::whereIn('schedule_id', $scheduleIds)
            ->get()
            ->mapWithKeys(fn (Calling $calling) => [
                $calling->schedule_id . '-' . $calling->athlete_id => true,
            ]);

        $now = now();
        $rows = [];

        foreach ($athletesBySchedule as $scheduleId => $athletes) {
            foreach ($athletes as $athlete) {
                if (isset($existing[$scheduleId . '-' . $athlete->id])) {
                    continue;
                }

                $rows[] = [
                    'schedule_id' => $scheduleId,
                    'athlete_id' => $athlete->id,
                    'level' => null,
                    'status' => 'waiting',
                    'called_at' => null,
                    'ready_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if ($rows !== []) {
            Calling::insert($rows);
        }
    }

    /**
     * Atlet pada sebuah jadwal: seni dari performances, daeryun dari match.
     *
     * @return Collection<int, Athlete>
     */
    protected function scheduleAthletes(Schedule $schedule): Collection
    {
        return $schedule->athletes()->filter()->values();
    }
}
