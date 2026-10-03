<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Athlete extends Model
{
    protected $fillable = [
        'name', 'gender', 'birth_date', 'id_number', 'contingent_id',
        'category_id', 'participant_number', 'qr_code', 'status', 'photo',
    ];

    protected function casts(): array
    {
        return ['birth_date' => 'date'];
    }

    public function contingent(): BelongsTo
    {
        return $this->belongsTo(Contingent::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
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

    public function equipmentLoans(): HasMany
    {
        return $this->hasMany(EquipmentLoan::class);
    }

    public function performances(): HasMany
    {
        return $this->hasMany(Performance::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function genderLabel(): string
    {
        return match ($this->gender) {
            'male' => 'Putra',
            'female' => 'Putri',
            default => '—',
        };
    }

    public function statusLabel(): string
    {
        return $this->status === 'active' ? 'Aktif' : 'Nonaktif';
    }
}
