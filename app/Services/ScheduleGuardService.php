<?php

namespace App\Services;

use App\Models\Athlete;
use App\Models\Matchup;
use App\Models\Performance;
use App\Models\Schedule;

/**
 * Central place for the conflict rules of the competition flow.
 *
 * Every rule returns a ready to use message (or null when there is no
 * conflict), so a controller can simply do:
 *
 *     if ($message = $guard->guardStart($schedule)) {
 *         return back()->with('error', $message);
 *     }
 */
class ScheduleGuardService
{
    /**
     * Rules that must pass before a match or a performance can start.
     */
    public function guardStart(Schedule $schedule): ?string
    {
        if (! $schedule->arena_id) {
            return sprintf(
                'Jadwal %s belum memiliki arena. Tentukan arena sebelum memulai pertandingan.',
                $schedule->match_no
            );
        }

        $busy = $this->runningScheduleInArena($schedule);

        if ($busy) {
            return sprintf(
                'Arena %s masih dipakai oleh %s yang belum selesai. Selesaikan %s terlebih dahulu.',
                $busy->arena?->name ?? 'tersebut',
                $this->scheduleLabel($busy),
                $this->scheduleLabel($busy)
            );
        }

        $conflict = $this->athleteConflict($schedule);

        if ($conflict) {
            return sprintf(
                'Atlet %s sedang tampil pada %s di arena %s. Selesaikan terlebih dahulu sebelum memulai %s.',
                $conflict['athlete']?->name ?? '-',
                $this->scheduleLabel($conflict['schedule']),
                $conflict['schedule']->arena?->name ?? 'tanpa arena',
                $this->scheduleLabel($schedule)
            );
        }

        return null;
    }

    /**
     * Rules that must pass before a schedule is created or updated.
     */
    public function guardSchedule(Schedule $schedule): ?string
    {
        $start = $this->toMinutes($schedule->start_time);
        $end = $this->toMinutes($schedule->end_time);

        if ($start !== null && $end !== null && $end <= $start) {
            return 'Jam selesai harus lebih besar dari jam mulai.';
        }

        $busy = $this->runningScheduleInArena($schedule);

        if ($busy) {
            return sprintf(
                'Arena %s sedang digunakan oleh %s yang belum selesai. Jadwal yang sama tidak bisa memakai arena ini.',
                $busy->arena?->name ?? 'tersebut',
                $this->scheduleLabel($busy)
            );
        }

        $overlap = $this->timeConflict($schedule);

        if ($overlap) {
            return sprintf(
                'Waktu %s bentrok dengan %s (%s - %s) di arena %s yang sama.',
                $this->scheduleLabel($schedule),
                $this->scheduleLabel($overlap),
                $this->formatTime($overlap->start_time),
                $this->formatTime($overlap->end_time),
                $overlap->arena?->name ?? 'tersebut'
            );
        }

        return null;
    }

    /**
     * Rules that must pass before a match result is saved.
     */
    public function guardFinish(Schedule $schedule): ?string
    {
        $conflict = $this->athleteConflict($schedule, onlyRunning: true);

        if ($conflict) {
            return sprintf(
                'Atlet %s masih tampil pada %s di arena %s. Selesaikan pertandingan lain terlebih dahulu.',
                $conflict['athlete']?->name ?? '-',
                $this->scheduleLabel($conflict['schedule']),
                $conflict['schedule']->arena?->name ?? 'tanpa arena'
            );
        }

        return null;
    }

    /**
     * The schedule that is currently running in the same arena (if any).
     */
    public function runningScheduleInArena(Schedule $schedule): ?Schedule
    {
        if (! $schedule->arena_id) {
            return null;
        }

        return Schedule::query()
            ->with('arena')
            ->where('arena_id', $schedule->arena_id)
            ->where('status', 'running')
            ->when($schedule->id, fn ($query) => $query->where('id', '!=', $schedule->id))
            ->orderBy('order_no')
            ->first();
    }

    /**
     * Another schedule of the same arena whose time window overlaps.
     */
    public function timeConflict(Schedule $schedule): ?Schedule
    {
        if (! $schedule->arena_id || ! $schedule->match_date) {
            return null;
        }

        $start = $this->toMinutes($schedule->start_time);
        $end = $this->toMinutes($schedule->end_time);

        // An open ended schedule cannot be compared reliably.
        if ($start === null || $end === null) {
            return null;
        }

        $candidates = Schedule::query()
            ->with('arena')
            ->where('arena_id', $schedule->arena_id)
            ->whereDate('match_date', $schedule->match_date->toDateString())
            ->whereNotIn('status', ['finished', 'cancelled'])
            ->whereNotNull('start_time')
            ->whereNotNull('end_time')
            ->when($schedule->id, fn ($query) => $query->where('id', '!=', $schedule->id))
            ->get();

        foreach ($candidates as $candidate) {
            $candidateStart = $this->toMinutes($candidate->start_time);
            $candidateEnd = $this->toMinutes($candidate->end_time);

            if ($candidateStart === null || $candidateEnd === null) {
                continue;
            }

            if ($candidateStart < $end && $candidateEnd > $start) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * An athlete of this schedule that is currently competing somewhere else.
     *
     * @return array{schedule: Schedule, athlete: ?Athlete}|null
     */
    public function athleteConflict(Schedule $schedule, bool $onlyRunning = false): ?array
    {
        $athleteIds = $schedule->athletes()
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($athleteIds->isEmpty()) {
            return null;
        }

        $matches = Matchup::query()
            ->with('schedule.arena', 'athleteA', 'athleteB')
            ->where('status', 'running')
            ->when($schedule->id, fn ($query) => $query->where('schedule_id', '!=', $schedule->id))
            ->get()
            ->filter(fn (Matchup $match) => $this->containsAthlete($match, $athleteIds->all()));

        foreach ($matches as $match) {
            if (! $match->schedule) {
                continue;
            }

            $athlete = $match->athleteA && in_array((int) $match->athlete_a_id, $athleteIds->all(), true)
                ? $match->athleteA
                : $match->athleteB;

            return ['schedule' => $match->schedule, 'athlete' => $athlete];
        }

        $performances = Performance::query()
            ->with('schedule.arena', 'athlete')
            ->where('status', 'performing')
            ->when($schedule->id, fn ($query) => $query->where('schedule_id', '!=', $schedule->id))
            ->whereIn('athlete_id', $athleteIds)
            ->get();

        foreach ($performances as $performance) {
            if (! $performance->schedule) {
                continue;
            }

            return ['schedule' => $performance->schedule, 'athlete' => $performance->athlete];
        }

        if ($onlyRunning) {
            return null;
        }

        // Schedules flagged as running even though the match itself is not started yet.
        $running = Schedule::query()
            ->with('arena', 'match', 'performances')
            ->where('status', 'running')
            ->when($schedule->id, fn ($query) => $query->where('id', '!=', $schedule->id))
            ->get();

        foreach ($running as $other) {
            $athlete = $other->athletes()->whereIn('id', $athleteIds)->first();

            if ($athlete) {
                return ['schedule' => $other, 'athlete' => $athlete];
            }
        }

        return null;
    }

    /**
     * The schedule where the athlete is currently on stage (if any).
     */
    public function athleteBusyInPerformance(int $athleteId, ?int $ignoreScheduleId = null): ?Schedule
    {
        $performance = Performance::query()
            ->with('schedule')
            ->where('athlete_id', $athleteId)
            ->where('status', 'performing')
            ->when($ignoreScheduleId, fn ($query) => $query->where('schedule_id', '!=', $ignoreScheduleId))
            ->first();

        return $performance?->schedule;
    }

    /**
     * Does the match contain one of the given athletes?
     *
     * @param  array<int, int>  $athleteIds
     */
    protected function containsAthlete(Matchup $match, array $athleteIds): bool
    {
        return in_array((int) $match->athlete_a_id, $athleteIds, true)
            || in_array((int) $match->athlete_b_id, $athleteIds, true);
    }

    /**
     * Human readable name of a schedule.
     */
    protected function scheduleLabel(Schedule $schedule): string
    {
        return ($schedule->type === 'art' ? 'Seni' : 'Partai') . ' ' . $schedule->match_no;
    }

    /**
     * Convert a "HH:MM" / "HH:MM:SS" string into minutes.
     */
    protected function toMinutes(?string $time): ?int
    {
        if ($time === null || $time === '') {
            return null;
        }

        if (! preg_match('/^(\d{1,2}):(\d{2})/', trim($time), $matches)) {
            return null;
        }

        return ((int) $matches[1]) * 60 + (int) $matches[2];
    }

    /**
     * Format a stored time value for the messages.
     */
    protected function formatTime(?string $time): string
    {
        if ($time === null || $time === '') {
            return '-';
        }

        return substr($time, 0, 5);
    }
}
