<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Calling extends Model
{
    protected $fillable = [
        'schedule_id', 'athlete_id', 'level', 'status', 'called_at', 'ready_at',
    ];

    protected function casts(): array
    {
        return ['called_at' => 'datetime', 'ready_at' => 'datetime'];
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function athlete(): BelongsTo
    {
        return $this->belongsTo(Athlete::class);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'waiting' => 'Menunggu',
            'called' => 'Terpanggil',
            'ready' => 'Siap',
            default => $this->status,
        };
    }

    public function levelLabel(): string
    {
        return $this->level ? "{$this->level} menit" : '—';
    }
}
