<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BracketSlot extends Model
{
    protected $fillable = ['category_id', 'round', 'position', 'athlete_id'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function athlete(): BelongsTo
    {
        return $this->belongsTo(Athlete::class);
    }
}
