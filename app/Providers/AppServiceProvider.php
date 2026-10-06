<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->syncSettingsIntoConfig();
    }

    /**
     * Timpa config() dengan nilai dari tabel settings
     * (judul, tagline, deskripsi, logo, favicon, footer, copyright).
     * Aman dipanggil sebelum tabel settings ada.
     */
    protected function syncSettingsIntoConfig(): void
    {
        $overrides = [
            'app.name' => Setting::get('app_name'),
            'app.short_name' => Setting::get('app_short_name'),
            'app.tagline' => Setting::get('app_tagline'),
            'app.description' => Setting::get('app_description'),
            'app.footer' => Setting::get('app_footer'),
            'app.copyright' => Setting::get('app_copyright'),
            'app.logo' => Setting::logoUrl(),
            'app.favicon' => Setting::faviconUrl(),
        ];

        foreach ($overrides as $configKey => $value) {
            if ($value !== null && $value !== '') {
                config()->set($configKey, $value);
            }
        }

        foreach (['app.logo', 'app.favicon'] as $configKey) {
            $value = config($configKey);

            // Dukung nilai mentah dari .env (mis. "settings/logo.png").
            if (is_string($value) && $value !== '' && ! str_starts_with($value, '/') && ! str_contains($value, '://')) {
                config()->set($configKey, '/storage/' . ltrim($value, '/'));
            }
        }
    }
}
