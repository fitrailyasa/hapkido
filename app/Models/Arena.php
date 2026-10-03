<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Arena extends Model
{
    protected $fillable = [
        'name', 'label', 'category_id', 'match_type', 'status', 'current_schedule_id',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function currentSchedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class, 'current_schedule_id');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'running' => 'Tampil',
            'preparation' => 'Persiapan',
            default => 'Menunggu',
        };
    }
}
