<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReadinessCheck extends Model
{
    protected $fillable = [
        'schedule_id', 'athlete_id', 'attendance_check', 'athlete_check',
        'equipment_check', 'notes', 'status', 'checked_by', 'checked_at',
    ];

    protected function casts(): array
    {
        return [
            'attendance_check' => 'boolean',
            'athlete_check' => 'boolean',
            'equipment_check' => 'boolean',
            'checked_at' => 'datetime',
        ];
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function athlete(): BelongsTo
    {
        return $this->belongsTo(Athlete::class);
    }

    public function checkedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }

    public function isReady(): bool
    {
        return $this->status === 'ready';
    }
}
