<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Penyimpan key-value untuk identitas aplikasi
 * (judul, deskripsi, logo, favicon, footer, dll).
 */
class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    protected $casts = ['value' => 'string'];

    public const CACHE_KEY = 'settings.all';

    /**
     * Baca seluruh setting (dikutip cache; aman dipanggil sebelum migrasi).
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
     * Ambil satu nilai; kembalikan $default bila kosong/tidak ada.
     */
    public static function get(string $key, ?string $default = null): ?string
    {
        $value = static::allSettings()[$key] ?? null;

        return ($value === null || $value === '') ? $default : $value;
    }

    /**
     * Simpan satu nilai lalu bersihkan cache.
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
     * URL logo bila sudah diunggah, selain itu null.
     * Dipakai component <x-application-logo> dan layout.
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
