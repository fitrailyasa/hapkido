<?php

namespace App\Services;

use App\Models\Schedule;
use Carbon\Carbon;

/**
 * Resolves the day that is shown on the calling board and on the public display.
 *
 * During a tournament the schedules belong to the current day. Outside of the
 * event days the latest day that has schedules is used, so the screen never
 * shows an empty list.
 */
class ScheduleDayService
{
    /**
     * The current date.
     */
    public function today(): string
    {
        return now()->toDateString();
    }

    /**
     * The requested day, otherwise today, otherwise the latest day with schedules.
     */
    public function resolve(?string $requested = null): string
    {
        if ($requested !== null && $this->isValidDate($requested)) {
            return Carbon::parse($requested)->toDateString();
        }

        $today = $this->today();

        if (Schedule::whereDate('match_date', $today)->exists()) {
            return $today;
        }

        $latest = Schedule::query()
            ->whereNotNull('match_date')
            ->orderByDesc('match_date')
            ->value('match_date');

        if (! $latest) {
            return $today;
        }

        return Carbon::parse($latest)->toDateString();
    }

    /**
     * Is the given value a usable date (YYYY-MM-DD)?
     */
    public function isValidDate(string $date): bool
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return false;
        }

        return Carbon::hasFormat($date, 'Y-m-d') && Carbon::parse($date)->isValid();
    }
}
