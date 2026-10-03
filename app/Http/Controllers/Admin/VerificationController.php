<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Athlete;
use App\Models\Schedule;
use App\Models\Verification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    public function index(Request $request)
    {
        $today = now()->toDateString();

        $query = Verification::with(['athlete.contingent', 'schedule.arena', 'schedule.category', 'verifiedBy'])
            ->whereHas('schedule', fn ($builder) => $builder->whereDate('match_date', $today))
            ->orderByDesc('verified_at')
            ->orderByDesc('id');

        if ($request->filled('q')) {
            $search = trim((string) $request->query('q'));
            $query->whereHas('athlete', fn ($builder) => $builder
                ->where('name', 'like', "%{$search}%")
                ->orWhere('participant_number', 'like', "%{$search}%")
                ->orWhere('qr_code', 'like', "%{$search}%"));
        }

        $schedules = Schedule::with(['category', 'arena'])
            ->whereDate('match_date', $today)
            ->where('status', '!=', 'cancelled')
            ->orderBy('order_no')
            ->orderBy('id')
            ->get();

        return view('admin.verification.index', [
            'verifications' => $query->paginate(10)->withQueryString(),
            'schedules' => $schedules,
            'search' => trim((string) $request->query('q')),
            'presentCount' => Verification::whereHas('schedule', fn ($builder) => $builder->whereDate('match_date', $today))
                ->where('status', 'present')
                ->count(),
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:255'],
            'schedule_id' => ['nullable', 'integer', 'exists:schedules,id'],
        ], [
            'code.required' => 'Kode atlet wajib diisi.',
            'code.max' => 'Kode atlet terlalu panjang.',
            'schedule_id.exists' => 'Jadwal tidak ditemukan.',
        ], [
            'code' => 'kode atlet',
            'schedule_id' => 'jadwal',
        ]);

        $code = trim($data['code']);
        $athlete = $this->findAthlete($code);

        if (! $athlete) {
            return $this->respondError($request, sprintf('Kode "%s" tidak terdaftar sebagai atlet.', $code));
        }

        if (! $athlete->isActive()) {
            return $this->respondError($request, sprintf('Atlet %s berstatus nonaktif.', $athlete->name));
        }

        $schedule = $this->resolveSchedule($athlete, $data['schedule_id'] ?? null);

        if (! $schedule) {
            return $this->respondError($request, sprintf('Tidak ada jadwal hari ini untuk atlet %s.', $athlete->name));
        }

        $verification = Verification::updateOrCreate(
            ['schedule_id' => $schedule->id, 'athlete_id' => $athlete->id],
            [
                'method' => $athlete->qr_code === $code ? 'qr' : 'manual',
                'verified_by' => auth()->id(),
                'verified_at' => now(),
                'status' => 'present',
            ]
        );

        $message = $verification->wasRecentlyCreated
            ? sprintf('%s terverifikasi pada partai %s.', $athlete->name, $schedule->match_no)
            : sprintf('%s sudah terverifikasi, waktu diperbarui.', $athlete->name);

        $payload = [
            'message' => $message,
            'already' => ! $verification->wasRecentlyCreated,
            'athlete' => [
                'id' => $athlete->id,
                'name' => $athlete->name,
                'participant_number' => $athlete->participant_number,
                'contingent' => $athlete->contingent?->code ?? '—',
            ],
            'schedule' => [
                'id' => $schedule->id,
                'match_no' => $schedule->match_no,
                'arena' => $schedule->arena?->label ?? '—',
            ],
            'verified_at' => optional($verification->verified_at)->format('d M Y, H:i'),
        ];

        if ($request->expectsJson()) {
            return response()->json($payload);
        }

        return redirect()
            ->route('admin.verifications.index')
            ->with('success', $message);
    }

    protected function findAthlete(string $code): ?Athlete
    {
        return Athlete::query()
            ->where('qr_code', $code)
            ->orWhere('participant_number', $code)
            ->orWhere('id_number', $code)
            ->orWhere('id', is_numeric($code) ? $code : null)
            ->first();
    }

    /**
     * Jadwal hari ini untuk atlet: prioritas jadwal yang sedang berjalan,
     * lalu jadwal lain pada kategori yang sama.
     */
    protected function resolveSchedule(Athlete $athlete, ?int $scheduleId): ?Schedule
    {
        if ($scheduleId) {
            return Schedule::find($scheduleId);
        }

        $today = now()->toDateString();

        $candidates = Schedule::query()
            ->whereDate('match_date', $today)
            ->where('status', '!=', 'cancelled')
            ->where(function ($query) use ($athlete) {
                $query->whereHas('performances', fn ($builder) => $builder->where('athlete_id', $athlete->id))
                    ->orWhereHas('match', fn ($builder) => $builder
                        ->where('athlete_a_id', $athlete->id)
                        ->orWhere('athlete_b_id', $athlete->id));
            })
            ->orderBy('order_no')
            ->orderBy('id')
            ->get();

        if ($candidates->isNotEmpty()) {
            return $this->firstByPriority($candidates);
        }

        return $this->firstByPriority(
            Schedule::query()
                ->whereDate('match_date', $today)
                ->where('status', '!=', 'cancelled')
                ->where('category_id', $athlete->category_id)
                ->orderBy('order_no')
                ->orderBy('id')
                ->get()
        );
    }

    protected function firstByPriority($schedules): ?Schedule
    {
        $priority = ['running' => 0, 'preparation' => 1, 'pending' => 2, 'finished' => 3];

        return $schedules
            ->sortBy(fn (Schedule $schedule) => [
                $priority[$schedule->status] ?? 9,
                $schedule->order_no,
                $schedule->id,
            ])
            ->first();
    }

    protected function respondError(Request $request, string $message): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 422);
        }

        return back()->with('error', $message);
    }
}
