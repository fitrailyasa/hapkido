<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    /**
     * Halaman identitas & tampilan aplikasi.
     */
    public function index()
    {
        return view('admin.setting.index', [
            'settings' => [
                'app_name' => config('app.name'),
                'app_short_name' => config('app.short_name'),
                'app_tagline' => config('app.tagline'),
                'app_description' => config('app.description'),
                'app_footer' => config('app.footer'),
                'app_copyright' => config('app.copyright'),
                'app_logo' => config('app.logo'),
                'app_favicon' => config('app.favicon'),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'app_name' => ['required', 'string', 'max:255'],
            'app_short_name' => ['required', 'string', 'max:60'],
            'app_tagline' => ['nullable', 'string', 'max:255'],
            'app_description' => ['nullable', 'string', 'max:1000'],
            'app_footer' => ['nullable', 'string', 'max:500'],
            'app_copyright' => ['nullable', 'string', 'max:255'],
            'app_logo' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp,svg', 'max:2048'],
            'app_favicon' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp,ico,svg', 'max:1024'],
            'remove_logo' => ['nullable', 'boolean'],
            'remove_favicon' => ['nullable', 'boolean'],
        ], [
            'app_name.required' => 'Judul aplikasi wajib diisi.',
            'app_short_name.required' => 'Nama singkat wajib diisi.',
            'app_logo.mimes' => 'Logo harus berupa PNG, JPG, WEBP, atau SVG.',
            'app_favicon.mimes' => 'Favicon harus berupa PNG, JPG, ICO, atau SVG.',
        ]);

        $this->syncFiles($request);

        foreach (['app_name', 'app_short_name', 'app_tagline', 'app_description', 'app_footer', 'app_copyright'] as $key) {
            $value = trim((string) ($data[$key] ?? ''));

            Setting::set($key, $value === '' ? null : $value);
        }

        Setting::flushCache();

        return redirect()
            ->route('admin.settings.index')
            ->with('success', 'Identitas & tampilan aplikasi berhasil diperbarui.');
    }

    /**
     * Simpan / hapus file logo & favicon (storage public: folder "settings").
     */
    protected function syncFiles(Request $request): void
    {
        $files = [
            'app_logo' => 'remove_logo',
            'app_favicon' => 'remove_favicon',
        ];

        foreach ($files as $fileKey => $removeFlag) {
            if ($request->hasFile($fileKey)) {
                $old = Setting::get($fileKey);

                $path = $request->file($fileKey)->store('settings', 'public');

                if ($old && $old !== $path) {
                    Storage::disk('public')->delete($old);
                }

                Setting::set($fileKey, $path);

                continue;
            }

            // Hanya hapus file lama bila dicentang & tidak ada unggahan baru.
            if ($request->boolean($removeFlag) && ($old = Setting::get($fileKey))) {
                Storage::disk('public')->delete($old);

                Setting::set($fileKey, null);
            }
        }
    }

    /**
     * Kembalikan semua setting ke nilai default dari config/.env.
     */
    public function reset(): RedirectResponse
    {
        foreach (['app_logo', 'app_favicon'] as $fileKey) {
            if ($path = Setting::get($fileKey)) {
                Storage::disk('public')->delete($path);
            }
        }

        Setting::query()->delete();
        Setting::flushCache();

        return redirect()
            ->route('admin.settings.index')
            ->with('success', 'Identitas aplikasi dikembalikan ke pengaturan default.');
    }
}
