<?php

namespace App\Services;

use App\Models\Arena;
use App\Models\Schedule;

/**
 * Keep the display screen (and the dashboard) always showing the most recent
 * match in every arena, whatever status changes occur.
 */
class ArenaLiveService
{
    protected const RELATIONS = [
        'category',
        'match.athleteA.contingent',
        'match.athleteB.contingent',
        'match.winner.contingent',
        'performances.athlete.contingent',
    ];

    /**
     * Mark this schedule as currently shown in the arena (called when a match or performance starts).
     */
    public function push(Schedule $schedule): void
    {
        if (! $schedule->arena_id) {
            return;
        }

        Arena::whereKey($schedule->arena_id)->update([
            'current_schedule_id' => $schedule->id,
            'status' => 'running',
        ]);
    }

    /**
     * Release the display status once finished.
     * The schedule stays linked so the screen shows the final result.
     */
    public function release(Schedule $schedule): void
    {
        if (! $schedule->arena_id) {
            return;
        }

        Arena::whereKey($schedule->arena_id)
            ->where('current_schedule_id', $schedule->id)
            ->update(['status' => 'idle']);
    }

    /**
     * Best schedule for an arena: the one in progress,
     * then today's schedule that is being prepared or waiting,
     * and finally the last stored link.
     */
    public function resolve(Arena $arena, ?string $today = null): ?Schedule
    {
        $today ??= now()->toDateString();

        $current = $arena->currentSchedule()->with(self::RELATIONS)->first();

        if ($current && $current->status === 'running') {
            return $current;
        }

        $running = Schedule::with(self::RELATIONS)
            ->where('arena_id', $arena->id)
            ->whereDate('match_date', $today)
            ->where('status', 'running')
            ->orderBy('order_no')
            ->first();

        if ($running) {
            return $running;
        }

        // A match that has just finished stays on screen so the winner is
        // immediately visible before switching to the next schedule.
        if ($current && $current->status === 'finished' && $this->resultIsFresh($current)) {
            return $current;
        }

        $next = Schedule::with(self::RELATIONS)
            ->where('arena_id', $arena->id)
            ->whereDate('match_date', $today)
            ->whereIn('status', ['preparation', 'pending'])
            ->orderBy('order_no')
            ->first();

        return $next ?? $current;
    }

    /**
     * A finished match is considered still "fresh" for 15 minutes after it ends.
     */
    protected function resultIsFresh(Schedule $schedule): bool
    {
        $finishedAt = $schedule->match?->finished_at;

        if (! $finishedAt && $schedule->end_time && $schedule->match_date) {
            $time = substr((string) $schedule->end_time, 0, 8);

            if (preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $time)) {
                $finishedAt = \Illuminate\Support\Carbon::parse(
                    $schedule->match_date->toDateString() . ' ' . $time
                );
            }
        }

        return $finishedAt !== null && $finishedAt->greaterThan(now()->subMinutes(15));
    }
}
