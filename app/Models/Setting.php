<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Key-value store for the application identity
 * (title, description, logo, favicon, footer, etc.).
 */
class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    protected $casts = ['value' => 'string'];

    public const CACHE_KEY = 'settings.all';

    /**
     * Read all settings (cached; safe to call before the migration runs).
     */
    public static function allSettings(): array
    {
        try {
            return Cache::rememberForever(self::CACHE_KEY, function () {
                return static::query()->pluck('value', 'key')->all();
            });
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Get a single value; return $default when it is empty or missing.
     */
    public static function get(string $key, ?string $default = null): ?string
    {
        $value = static::allSettings()[$key] ?? null;

        return ($value === null || $value === '') ? $default : $value;
    }

    /**
     * Save a single value, then clear the cache.
     */
    public static function set(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);

        static::flushCache();
    }

    public static function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    protected static function booted(): void
    {
        static::saved(fn () => static::flushCache());
        static::deleted(fn () => static::flushCache());
    }

    /**
     * Logo URL once it has been uploaded, otherwise null.
     * Used by the <x-application-logo> component and the layout.
     */
    public static function logoUrl(): ?string
    {
        $path = static::get('app_logo');

        return $path ? '/storage/' . ltrim($path, '/') : null;
    }

    public static function faviconUrl(): ?string
    {
        $path = static::get('app_favicon');

        return $path ? '/storage/' . ltrim($path, '/') : null;
    }
}
