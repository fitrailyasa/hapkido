<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Schedule extends Model
{
    /**
     * The rounds a schedule can belong to.
     *
     * The daeryun rounds come from the bracket, the art (seni) schedules use
     * "penampilan" because they are performed one after the other.
     *
     * @var array<string, string>
     */
    public const ROUNDS = [
        'penyisihan' => 'Penyisihan',
        'perempat_final' => 'Perempat Final',
        'semifinal' => 'Semifinal',
        'final' => 'Final',
        'penampilan' => 'Penampilan',
    ];

    /**
     * The rounds that belong to the given type.
     *
     * @return array<string, string>
     */
    public static function roundsFor(?string $type): array
    {
        if ($type === 'art') {
            return ['penampilan' => self::ROUNDS['penampilan']];
        }

        return array_diff_key(self::ROUNDS, ['penampilan' => true]);
    }

    protected $fillable = [
        'match_no', 'category_id', 'arena_id', 'type', 'round', 'order_no',
        'match_date', 'start_time', 'end_time', 'status',
    ];

    protected function casts(): array
    {
        return ['match_date' => 'date'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function arena(): BelongsTo
    {
        return $this->belongsTo(Arena::class);
    }

    public function match(): HasOne
    {
        return $this->hasOne(Matchup::class);
    }

    public function performances(): HasMany
    {
        return $this->hasMany(Performance::class)->orderBy('order_no');
    }

    public function callings(): HasMany
    {
        return $this->hasMany(Calling::class);
    }

    public function verifications(): HasMany
    {
        return $this->hasMany(Verification::class);
    }

    public function readinessChecks(): HasMany
    {
        return $this->hasMany(ReadinessCheck::class);
    }

    public function isDaeryun(): bool
    {
        return $this->type === 'daeryun';
    }

    public static function statusText(?string $status): string
    {
        return match ($status) {
            'pending' => 'Menunggu',
            'preparation' => 'Persiapan',
            'running' => 'Tampil',
            'finished' => 'Selesai',
            'cancelled' => 'Batal',
            default => (string) $status,
        };
    }

    public function statusLabel(): string
    {
        return self::statusText($this->status);
    }

    /**
     * Human readable name of a round.
     */
    public static function roundText(?string $round): string
    {
        if ($round === null || $round === '') {
            return '—';
        }

        return self::ROUNDS[$round] ?? ucfirst(str_replace('_', ' ', $round));
    }

    public function athletes()
    {
        if ($this->isDaeryun()) {
            $match = $this->match;

            return $match
                ? collect([$match->athleteA, $match->athleteB])->filter()
                : collect();
        }

        return $this->performances->pluck('athlete');
    }
}
