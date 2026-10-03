<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Equipment extends Model
{
    protected $table = 'equipments';

    protected $fillable = [
        'type', 'color', 'size', 'code', 'status', 'total_qty', 'available_qty',
    ];

    protected function casts(): array
    {
        return ['total_qty' => 'integer', 'available_qty' => 'integer'];
    }

    public function loans(): HasMany
    {
        return $this->hasMany(EquipmentLoan::class);
    }

    public function activeLoan(): ?EquipmentLoan
    {
        return $this->loans()->where('status', 'loaned')->latest('loaned_at')->first();
    }

    public function loanedQty(): int
    {
        return max(0, (int) $this->total_qty - (int) $this->available_qty);
    }

    public function hasStock(int $qty): bool
    {
        return $this->status !== 'maintenance' && $this->available_qty >= $qty;
    }

    public function typeLabel(): string
    {
        return $this->type === 'head_guard' ? 'Head Guard' : 'Body Protector';
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'available' => 'Tersedia',
            'loaned' => 'Dipinjam',
            'maintenance' => 'Perawatan',
            default => $this->status,
        };
    }
}
