<?php

namespace App\Services;

use App\Models\Arena;
use App\Models\Schedule;

/**
 * Menjaga agar layar display (dan dashboard) selalu menampilkan
 * partai terbaru di setiap arena, berapa pun status yang berubah.
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
     * Tandai jadwal ini sedang tampil di arena (dipanggil saat partai/seni dimulai).
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
     * Lepaskan status tampil setelah selesai.
     * Jadwal tetap ditautkan agar layar menampilkan hasil akhir.
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
     * Jadwal terbaik untuk sebuah arena: yang sedang berlangsung,
     * lalu jadwal hari ini yang sedang dipersiapkan/menunggu,
     * terakhir tautan terakhir yang tersimpan.
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

        // Hasil partai yang baru saja selesai tetap ditampilkan agar pemenang
        // langsung terlihat di layar sebelum berganti ke jadwal berikutnya.
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
     * Partai selesai dianggap masih "hangat" selama 15 menit setelah selesai.
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
