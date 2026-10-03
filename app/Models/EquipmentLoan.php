<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipmentLoan extends Model
{
    protected $fillable = [
        'equipment_id', 'athlete_id', 'qty', 'schedule_id', 'loaned_at', 'returned_at',
        'loan_condition', 'return_condition', 'notes', 'status', 'loaned_by', 'returned_by',
    ];

    protected function casts(): array
    {
        return ['qty' => 'integer', 'loaned_at' => 'datetime', 'returned_at' => 'datetime'];
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function athlete(): BelongsTo
    {
        return $this->belongsTo(Athlete::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function loanedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'loaned_by');
    }

    public function returnedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returned_by');
    }

    public function isReturned(): bool
    {
        return $this->status === 'returned';
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'loaned' => 'Dipinjam',
            'returned' => 'Dikembalikan',
            default => $this->status,
        };
    }
}
