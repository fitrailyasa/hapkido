<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class SettingTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    public function test_setting_index_can_be_rendered(): void
    {
        $user = $this->userWithPermissions(['settings.view']);

        $response = $this->actingAs($user)->get('/admin/settings');

        $response->assertOk();
        $response->assertSee('Identitas & Tampilan');
        $response->assertSee('name="app_name"', false);
        $response->assertSee('value="Hapkido 2026"', false);
    }

    public function test_identity_can_be_updated(): void
    {
        $user = $this->userWithPermissions(['settings.view', 'settings.update']);

        $response = $this->actingAs($user)
            ->from('/admin/settings')
            ->post('/admin/settings', [
                'app_name' => 'Kejuaraan Hapkido 2027',
                'app_short_name' => 'HKD 2027',
                'app_tagline' => 'Tagline Baru',
                'app_description' => 'Deskripsi baru untuk meta description.',
                'app_footer' => 'Footer baru',
                'app_copyright' => '© 2027 Hapkido.',
            ]);

        $response->assertRedirect(route('admin.settings.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('settings', ['key' => 'app_name', 'value' => 'Kejuaraan Hapkido 2027']);
        $this->assertDatabaseHas('settings', ['key' => 'app_short_name', 'value' => 'HKD 2027']);
        $this->assertDatabaseHas('settings', ['key' => 'app_description', 'value' => 'Deskripsi baru untuk meta description.']);
    }

    public function test_blank_fields_fall_back_to_default(): void
    {
        $user = $this->userWithPermissions(['settings.view', 'settings.update']);
        Setting::set('app_tagline', 'Tagline Lama');

        $this->actingAs($user)
            ->from('/admin/settings')
            ->post('/admin/settings', [
                'app_name' => 'Judul Baru',
                'app_short_name' => 'Baru',
                'app_tagline' => '',
            ]);

        $this->assertDatabaseHas('settings', ['key' => 'app_tagline', 'value' => null]);
    }

    public function test_app_name_is_required(): void
    {
        $user = $this->userWithPermissions(['settings.view', 'settings.update']);

        $response = $this->actingAs($user)
            ->from('/admin/settings')
            ->post('/admin/settings', [
                'app_name' => '',
                'app_short_name' => 'HKD',
            ]);

        $response->assertSessionHasErrors('app_name');
    }

    public function test_logo_can_be_uploaded_and_removed(): void
    {
        Storage::fake('public');
        $user = $this->userWithPermissions(['settings.view', 'settings.update']);

        $this->actingAs($user)
            ->from('/admin/settings')
            ->post('/admin/settings', [
                'app_name' => 'Judul Baru',
                'app_short_name' => 'HKD',
                'app_logo' => UploadedFile::fake()->image('logo.png'),
            ]);

        $logo = Setting::get('app_logo');
        $this->assertNotNull($logo);
        Storage::disk('public')->assertExists($logo);

        $this->actingAs($user)
            ->from('/admin/settings')
            ->post('/admin/settings', [
                'app_name' => 'Judul Baru',
                'app_short_name' => 'HKD',
                'remove_logo' => 1,
            ]);

        $this->assertNull(Setting::get('app_logo'));
        Storage::disk('public')->assertMissing($logo);
    }

    public function test_settings_can_be_reset_to_default(): void
    {
        Storage::fake('public');
        $user = $this->userWithPermissions(['settings.view', 'settings.update']);
        Setting::set('app_name', 'Judul Sementara');
        Storage::disk('public')->put('settings/logo.png', 'x');
        Setting::set('app_logo', 'settings/logo.png');

        $response = $this->actingAs($user)
            ->from('/admin/settings')
            ->delete('/admin/settings');

        $response->assertRedirect(route('admin.settings.index'));
        $this->assertDatabaseCount('settings', 0);
        Storage::disk('public')->assertMissing('settings/logo.png');
    }

    public function test_setting_module_requires_permission(): void
    {
        $viewer = $this->userWithPermissions(['settings.view']);

        $this->actingAs($viewer)->get('/admin/settings')->assertOk();
        $this->actingAs($viewer)
            ->post('/admin/settings', ['app_name' => 'X', 'app_short_name' => 'Y'])
            ->assertForbidden();
        $this->actingAs($viewer)->delete('/admin/settings')->assertForbidden();
    }
}
