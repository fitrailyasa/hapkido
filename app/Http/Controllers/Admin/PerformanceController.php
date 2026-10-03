<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Athlete;
use App\Models\Category;
use App\Models\Performance;
use App\Models\Schedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PerformanceController extends Controller
{
    public function index(Request $request)
    {
        $categoryFilter = (string) $request->query('category');
        $statusFilter = (string) $request->query('status');

        $statuses = [
            'waiting' => 'Menunggu',
            'performing' => 'Tampil',
            'finished' => 'Selesai',
        ];

        $query = Schedule::query()
            ->with(['category', 'arena', 'performances.athlete.contingent'])
            ->where('type', 'art')
            ->orderBy('match_date')
            ->orderBy('order_no');

        if ($categoryFilter !== '') {
            $query->where('category_id', $categoryFilter);
        }

        if (isset($statuses[$statusFilter])) {
            $query->whereHas('performances', fn ($builder) => $builder->where('status', $statusFilter));
        }

        $schedules = $query->get();

        $athletesByCategory = Athlete::query()
            ->with('contingent')
            ->whereIn('category_id', Category::where('type', 'art')->pluck('id'))
            ->where('status', 'active')
            ->orderBy('name')
            ->get()
            ->groupBy('category_id')
            ->map(fn ($athletes) => $athletes->map(fn (Athlete $athlete) => [
                'id' => $athlete->id,
                'name' => $athlete->name,
                'contingent' => $athlete->contingent?->code ?? '-',
            ])->values());

        return view('admin.performance.index', [
            'schedules' => $schedules,
            'categories' => Category::where('type', 'art')->orderBy('name')->get(),
            'statuses' => $statuses,
            'categoryFilter' => $categoryFilter,
            'statusFilter' => $statusFilter,
            'athletesByCategory' => $athletesByCategory,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(
            [
                'schedule_id' => ['required', 'integer', Rule::exists('schedules', 'id')->where('type', 'art')],
                'athlete_id' => ['required', 'integer', Rule::exists('athletes', 'id')],
                'order_no' => ['required', 'integer', 'min:1', 'max:999'],
            ],
            [
                'schedule_id.required' => 'Jadwal wajib dipilih.',
                'schedule_id.exists' => 'Jadwal seni yang dipilih tidak ditemukan.',
                'athlete_id.required' => 'Atlet wajib dipilih.',
                'athlete_id.exists' => 'Atlet yang dipilih tidak ditemukan.',
                'order_no.required' => 'No. urut tampil wajib diisi.',
                'order_no.min' => 'No. urut tampil minimal 1.',
            ]
        );

        $schedule = Schedule::find($data['schedule_id']);
        $athlete = Athlete::find($data['athlete_id']);

        if (! $schedule || ! $athlete) {
            return back()->with('error', 'Jadwal atau atlet tidak ditemukan.');
        }

        if ((int) $athlete->category_id !== (int) $schedule->category_id) {
            return back()->with('error', 'Atlet yang dipilih bukan berasal dari kategori jadwal tersebut.');
        }

        $exists = $schedule->performances()
            ->where('athlete_id', $athlete->id)
            ->exists();

        if ($exists) {
            return back()->with('error', 'Atlet tersebut sudah terdaftar pada jadwal ini.');
        }

        DB::transaction(function () use ($schedule, $athlete, $data) {
            $schedule->performances()->create([
                'athlete_id' => $athlete->id,
                'order_no' => (int) $data['order_no'],
                'status' => 'waiting',
            ]);
        });

        return back()->with('success', "Penampil {$athlete->name} berhasil ditambahkan.");
    }

    public function updateStatus(Request $request, Performance $performance): RedirectResponse
    {
        $data = $request->validate(
            [
                'status' => ['required', 'string', Rule::in(['waiting', 'performing', 'finished'])],
            ],
            [
                'status.required' => 'Status wajib dipilih.',
                'status.in' => 'Status penampil tidak valid.',
            ]
        );

        if ($performance->status === $data['status']) {
            return back()->with('info', 'Status penampil sudah seperti itu.');
        }

        $payload = ['status' => $data['status']];

        if ($data['status'] === 'performing' && ! $performance->performed_at) {
            $payload['performed_at'] = now();
        }

        if ($data['status'] === 'finished') {
            $payload['performed_at'] = $performance->performed_at ?? now();

            if ($performance->final_score === null) {
                $average = $performance->scores()->avg('score');

                if ($average !== null) {
                    $payload['final_score'] = round((float) $average, 2);
                }
            }
        }

        DB::transaction(function () use ($performance, $payload, $data) {
            $performance->update($payload);

            $this->syncScheduleStatus($performance->schedule_id, $data['status']);
        });

        return back()->with('success', 'Status tampil diperbarui menjadi "' . $performance->statusLabel() . '".');
    }

    /**
     * Samakan status jadwal seni dengan progres penampilannya.
     */
    protected function syncScheduleStatus(int $scheduleId, string $performanceStatus): void
    {
        $schedule = Schedule::find($scheduleId);

        if (! $schedule || $schedule->type !== 'art' || $schedule->status === 'cancelled') {
            return;
        }

        $performances = $schedule->performances()->get();

        if ($performances->isNotEmpty() && $performances->every(fn (Performance $item) => $item->status === 'finished')) {
            $schedule->status = 'finished';

            if (empty($schedule->end_time)) {
                $schedule->end_time = now()->format('H:i:s');
            }

            $schedule->save();

            app(\App\Services\ArenaLiveService::class)->release($schedule);

            return;
        }

        if ($performanceStatus === 'performing' && in_array($schedule->status, ['pending', 'preparation'], true)) {
            $schedule->status = 'running';

            if (empty($schedule->start_time)) {
                $schedule->start_time = now()->format('H:i:s');
            }

            $schedule->save();

            app(\App\Services\ArenaLiveService::class)->push($schedule);
        }
    }
}
