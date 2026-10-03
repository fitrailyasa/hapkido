<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Matchup extends Model
{
    protected $table = 'matches';

    protected $fillable = [
        'schedule_id', 'athlete_a_id', 'athlete_b_id', 'winner_athlete_id', 'winner_id',
        'score_a', 'score_b',
        'status', 'round', 'position', 'started_at', 'finished_at',
    ];

    protected function casts(): array
    {
        return ['started_at' => 'datetime', 'finished_at' => 'datetime'];
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function athleteA(): BelongsTo
    {
        return $this->belongsTo(Athlete::class, 'athlete_a_id');
    }

    public function athleteB(): BelongsTo
    {
        return $this->belongsTo(Athlete::class, 'athlete_b_id');
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(Athlete::class, 'winner_athlete_id');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'pending' => 'Menunggu',
            'running' => 'Berlangsung',
            'finished' => 'Selesai',
            default => $this->status,
        };
    }

    public function statusBadge(): string
    {
        return match ($this->status) {
            'pending' => 'bg-info',
            'running' => 'bg-success',
            'finished' => 'bg-secondary',
            default => 'bg-secondary',
        };
    }

    public function hasScore(): bool
    {
        return $this->score_a !== null && $this->score_b !== null;
    }

    public function scoreLabel(): string
    {
        return $this->hasScore() ? "{$this->score_a} - {$this->score_b}" : '-';
    }

    public function scoreOf(?int $athleteId): ?int
    {
        if ($athleteId === null || ! $this->hasScore()) {
            return null;
        }

        if ($athleteId === (int) $this->athlete_a_id) {
            return (int) $this->score_a;
        }

        if ($athleteId === (int) $this->athlete_b_id) {
            return (int) $this->score_b;
        }

        return null;
    }
}
