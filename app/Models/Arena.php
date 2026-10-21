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

    /**
     * The arena that handles the given class.
     *
     * Every arena handles one class, so a schedule only needs the class: the
     * arena follows the class. When the class has no arena yet, a general
     * arena (without a class) of the same type is used.
     */
    public static function forCategory(?int $categoryId, ?string $type = null): ?self
    {
        if ($categoryId) {
            $arena = static::query()
                ->where('category_id', $categoryId)
                ->orderBy('id')
                ->first();

            if ($arena) {
                return $arena;
            }
        }

        if ($type === null) {
            return null;
        }

        return static::query()
            ->whereNull('category_id')
            ->where('match_type', $type)
            ->orderBy('id')
            ->first();
    }
}
