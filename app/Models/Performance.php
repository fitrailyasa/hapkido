<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Performance extends Model
{
    protected $fillable = [
        'schedule_id', 'athlete_id', 'order_no', 'status',
        'final_score', 'rank', 'performed_at',
    ];

    protected function casts(): array
    {
        return ['final_score' => 'decimal:2', 'performed_at' => 'datetime'];
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function athlete(): BelongsTo
    {
        return $this->belongsTo(Athlete::class);
    }

    public function scores(): HasMany
    {
        return $this->hasMany(Score::class);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'waiting' => 'Menunggu',
            'performing' => 'Tampil',
            'finished' => 'Selesai',
            default => $this->status,
        };
    }
}
