<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Arena;
use App\Models\Calling;
use App\Models\Matchup;
use App\Models\ReadinessCheck;
use App\Models\Schedule;
use App\Models\Verification;

class DashboardController extends Controller
{
    public function index()
    {
        return view('admin.dashboard', $this->summary());
    }

    /**
     * Endpoint polling realtime (tiap 3 detik).
     */
    public function data()
    {
        return response()->json($this->summary());
    }

    protected function summary(): array
    {
        $today = now()->toDateString();

        $arenas = Arena::with('category')
            ->orderBy('name')
            ->get()
            ->map(function (Arena $arena) use ($today) {
                $schedule = app(\App\Services\ArenaLiveService::class)->resolve($arena, $today);

                $match = $schedule?->match;
                $athletes = $schedule ? $schedule->athletes() : collect();

                return [
                    'id' => $arena->id,
                    'name' => $arena->name,
                    'label' => $arena->label,
                    'status' => $arena->status,
                    'status_label' => $arena->statusLabel(),
                    'category' => $schedule?->category?->name ?? $arena->category?->name ?? '—',
                    'type' => $schedule?->type ?? $arena->match_type,
                    'match_no' => $schedule?->match_no ?? '—',
                    'round' => $schedule?->round ?? '',
                    'athletes' => $athletes->filter()->map(fn ($athlete) => [
                        'id' => $athlete->id,
                        'name' => $athlete->name,
                        'contingent' => $athlete->contingent?->code ?? '—',
                    ])->values(),
                    'match_status' => $match?->status_label ?? $schedule?->status_label ?? '—',
                    'started_at' => optional($match?->started_at)->format('H:i'),
                ];
            })->values();

        $runningSchedules = Schedule::with(['category', 'arena', 'match.athleteA.contingent', 'match.athleteB.contingent'])
            ->whereDate('match_date', $today)
            ->whereIn('status', ['running', 'preparation'])
            ->orderBy('order_no')
            ->get();

        $activeCalling = $runningSchedules
            ->filter(fn ($schedule) => $schedule->match)
            ->first();

        $jadwalHariIni = Schedule::with(['category', 'arena'])
            ->whereDate('match_date', $today)
            ->orderBy('order_no')
            ->take(8)
            ->get()
            ->map(fn ($schedule) => [
                'id' => $schedule->id,
                'match_no' => $schedule->match_no,
                'time' => substr((string) $schedule->start_time, 0, 5),
                'arena' => $schedule->arena?->name ?? '—',
                'category' => $schedule->category?->name ?? '—',
                'type' => $schedule->type,
                'status' => $schedule->status,
                'status_label' => $schedule->statusLabel(),
            ]);

        $callingList = Calling::with(['athlete.contingent', 'schedule.category', 'schedule.arena'])
            ->whereIn('status', ['waiting', 'called'])
            ->orderBy('updated_at', 'desc')
            ->take(8)
            ->get()
            ->map(fn (Calling $calling) => [
                'id' => $calling->id,
                'match_no' => $calling->schedule?->match_no,
                'athlete' => $calling->athlete?->name ?? '—',
                'contingent' => $calling->athlete?->contingent?->code ?? '—',
                'level' => $calling->level,
                'level_label' => $calling->levelLabel(),
                'status' => $calling->status,
                'status_label' => $calling->statusLabel(),
                'category' => $calling->schedule?->category?->name ?? '—',
                'arena' => $calling->schedule?->arena?->name ?? '—',
            ]);

        $nextMatch = $activeCalling ? null : Schedule::with(['category', 'arena'])
            ->whereDate('match_date', $today)
            ->where('status', 'pending')
            ->orderBy('order_no')
            ->first();

        return [
            'now' => now()->format('H:i'),
            'arenas' => $arenas,
            'active_match' => $this->matchPayload($activeCalling),
            'next_match' => $nextMatch ? [
                'match_no' => $nextMatch->match_no,
                'category' => $nextMatch->category?->name ?? '—',
                'arena' => $nextMatch->arena?->name ?? '—',
            ] : null,
            'jadwal' => $jadwalHariIni,
            'callings' => $callingList,
            'counts' => [
                'verification_present' => Verification::where('status', 'present')->count(),
                'verification_total' => Verification::count(),
                'readiness_ready' => ReadinessCheck::where('status', 'ready')->count(),
                'readiness_total' => ReadinessCheck::count(),
                'running_matches' => Matchup::where('status', 'running')->count(),
                'waiting_callings' => Calling::where('status', 'waiting')->count(),
            ],
        ];
    }

    protected function matchPayload(?Schedule $schedule): ?array
    {
        if (! $schedule) {
            return null;
        }

        $match = $schedule->match;

        return [
            'id' => $match?->id,
            'match_no' => $schedule->match_no,
            'category' => $schedule->category?->name ?? '—',
            'type' => $schedule->type,
            'arena' => $schedule->arena?->name ?? '—',
            'status' => $schedule->status,
            'status_label' => $schedule->statusLabel(),
            'round' => $schedule->round,
            'athlete_a' => $match?->athleteA ? [
                'name' => $match->athleteA->name,
                'contingent' => $match->athleteA->contingent?->code ?? '—',
            ] : null,
            'athlete_b' => $match?->athleteB ? [
                'name' => $match->athleteB->name,
                'contingent' => $match->athleteB->contingent?->code ?? '—',
            ] : null,
            'winner' => $match?->winner?->name ?? null,
            'performances' => $schedule->type === 'art'
                ? $schedule->performances()->with('athlete.contingent')->get()->map(fn ($performance) => [
                    'order_no' => $performance->order_no,
                    'athlete' => $performance->athlete?->name ?? '—',
                    'contingent' => $performance->athlete?->contingent?->code ?? '—',
                    'status' => $performance->status,
                    'score' => $performance->final_score,
                ])->values()
                : [],
        ];
    }
}
