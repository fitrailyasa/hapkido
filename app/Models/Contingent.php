<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contingent extends Model
{
    protected $fillable = ['name', 'code', 'region', 'coach_name'];

    public function athletes(): HasMany
    {
        return $this->hasMany(Athlete::class);
    }
}
