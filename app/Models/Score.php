<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Score extends Model
{
    protected $fillable = ['performance_id', 'judge_no', 'score'];

    protected function casts(): array
    {
        return ['score' => 'decimal:2'];
    }

    public function performance(): BelongsTo
    {
        return $this->belongsTo(Performance::class);
    }
}
