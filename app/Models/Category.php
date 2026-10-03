<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = ['name', 'slug', 'type', 'gender', 'age_class'];

    public function athletes(): HasMany
    {
        return $this->hasMany(Athlete::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function bracketSlots(): HasMany
    {
        return $this->hasMany(BracketSlot::class);
    }

    public function isArt(): bool
    {
        return $this->type === 'art';
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'art' => 'Seni',
            default => 'Daeryun',
        };
    }

    public function genderLabel(): string
    {
        return match ($this->gender) {
            'male' => 'Putra',
            'female' => 'Putri',
            default => 'Terbuka',
        };
    }
}
