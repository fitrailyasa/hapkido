<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReadinessCheck;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class ReadinessController extends Controller
{
    public function index()
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

        $this->syncChecks($athletesBySchedule);

        $checks = ReadinessCheck::whereIn('schedule_id', array_keys($athletesBySchedule))
            ->get()
            ->mapWithKeys(fn (ReadinessCheck $check) => [
                $check->schedule_id . '-' . $check->athlete_id => $check,
            ]);

        $totals = ['ready' => 0, 'pending' => 0, 'total' => 0];
        $groups = [];

        foreach ($schedules as $schedule) {
            $counts = ['ready' => 0, 'pending' => 0, 'total' => 0];
            $rows = [];

            foreach ($athletesBySchedule[$schedule->id] as $athlete) {
                $check = $checks->get($schedule->id . '-' . $athlete->id);
                $status = $check?->status ?? 'pending';

                $counts[$status] = ($counts[$status] ?? 0) + 1;
                $counts['total']++;
                $totals[$status] = ($totals[$status] ?? 0) + 1;
                $totals['total']++;

                $rows[] = [
                    'athlete' => $athlete,
                    'contingent' => $athlete->contingent?->code ?? '—',
                    'check' => $check,
                ];
            }

            $groupKey = $schedule->arena?->id ?? 0;

            if (! isset($groups[$groupKey])) {
                $groups[$groupKey] = [
                    'id' => $schedule->arena?->id,
                    'name' => $schedule->arena?->name ?? '—',
                    'label' => $schedule->arena?->label ?? 'Tanpa Arena',
                    'counts' => ['ready' => 0, 'pending' => 0, 'total' => 0],
                    'schedules' => [],
                ];
            }

            foreach ($counts as $key => $value) {
                $groups[$groupKey]['counts'][$key] += $value;
            }

            $groups[$groupKey]['schedules'][] = [
                'schedule' => $schedule,
                'counts' => $counts,
                'rows' => $rows,
            ];
        }

        return view('admin.readiness.index', [
            'groups' => array_values($groups),
            'totals' => $totals,
            'date' => $today,
        ]);
    }

    public function update(Request $request, ReadinessCheck $readinessCheck)
    {
        $data = $request->validate([
            'status' => ['nullable', Rule::in(['pending', 'ready'])],
            'attendance_check' => ['nullable', 'boolean'],
            'athlete_check' => ['nullable', 'boolean'],
            'equipment_check' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [], [
            'status' => 'status kesiapan',
            'notes' => 'catatan',
        ]);

        $status = $data['status'] ?? ($readinessCheck->status === 'ready' ? 'pending' : 'ready');
        $ready = $status === 'ready';

        $readinessCheck->update([
            'attendance_check' => $request->boolean('attendance_check', $ready),
            'athlete_check' => $request->boolean('athlete_check', $ready),
            'equipment_check' => $request->boolean('equipment_check', $ready),
            'notes' => array_key_exists('notes', $data) ? $data['notes'] : $readinessCheck->notes,
            'status' => $status,
            'checked_by' => $ready ? auth()->id() : null,
            'checked_at' => $ready ? now() : null,
        ]);

        $athleteName = $readinessCheck->athlete?->name ?? 'Atlet';

        return back()->with(
            'success',
            sprintf(
                '%s ditandai %s.',
                $athleteName,
                $ready ? 'SIAP bertanding' : 'belum siap'
            )
        );
    }

    public function markReady(Schedule $schedule)
    {
        $athletes = $this->scheduleAthletes($schedule);

        if ($athletes->isEmpty()) {
            return back()->with('error', sprintf('Partai %s belum memiliki atlet.', $schedule->match_no));
        }

        foreach ($athletes as $athlete) {
            ReadinessCheck::updateOrCreate(
                ['schedule_id' => $schedule->id, 'athlete_id' => $athlete->id],
                [
                    'attendance_check' => true,
                    'athlete_check' => true,
                    'equipment_check' => true,
                    'notes' => null,
                    'status' => 'ready',
                    'checked_by' => auth()->id(),
                    'checked_at' => now(),
                ]
            );
        }

        return back()->with(
            'success',
            sprintf('Seluruh %d atlet partai %s ditandai siap.', $athletes->count(), $schedule->match_no)
        );
    }

    /**
     * Buat baris pemeriksaan kesiapan untuk setiap atlet jadwal hari ini.
     */
    protected function syncChecks(array $athletesBySchedule): void
    {
        if ($athletesBySchedule === []) {
            return;
        }

        $existing = ReadinessCheck::whereIn('schedule_id', array_keys($athletesBySchedule))
            ->get()
            ->mapWithKeys(fn (ReadinessCheck $check) => [
                $check->schedule_id . '-' . $check->athlete_id => true,
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
                    'attendance_check' => false,
                    'athlete_check' => false,
                    'equipment_check' => false,
                    'notes' => null,
                    'status' => 'pending',
                    'checked_by' => null,
                    'checked_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if ($rows !== []) {
            ReadinessCheck::insert($rows);
        }
    }

    /**
     * @return Collection<int, \App\Models\Athlete>
     */
    protected function scheduleAthletes(Schedule $schedule): Collection
    {
        return $schedule->athletes()->filter()->values();
    }
}
