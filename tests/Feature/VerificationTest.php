<?php

namespace Tests\Feature;

use App\Models\Verification;
use Database\Factories\AthleteFactory;
use Database\Factories\CategoryFactory;
use Database\Factories\ContingentFactory;
use Database\Factories\MatchupFactory;
use Database\Factories\ScheduleFactory;
use Database\Factories\VerificationFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class VerificationTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    private function athleteInTodaySchedule(array $scheduleAttributes = []): array
    {
        $category = CategoryFactory::new()->daeryun()->create();
        $contingent = ContingentFactory::new()->create();
        $athlete = AthleteFactory::new()->create([
            'contingent_id' => $contingent->id,
            'category_id' => $category->id,
        ]);
        $schedule = ScheduleFactory::new()->create(array_merge(
            ['category_id' => $category->id],
            $scheduleAttributes
        ));
        MatchupFactory::new()->create([
            'schedule_id' => $schedule->id,
            'athlete_a_id' => $athlete->id,
        ]);

        return [$athlete, $schedule];
    }

    public function test_verification_index_can_be_rendered(): void
    {
        $user = $this->userWithPermissions(['verifications.view']);
        [$athlete, $schedule] = $this->athleteInTodaySchedule();
        VerificationFactory::new()->create([
            'schedule_id' => $schedule->id,
            'athlete_id' => $athlete->id,
        ]);

        $response = $this->actingAs($user)->get('/admin/verifications');

        $response->assertOk();
        $response->assertSee($athlete->name);
    }

    public function test_athlete_can_be_verified_by_qr(): void
    {
        $user = $this->userWithPermissions(['verifications.create']);
        [$athlete, $schedule] = $this->athleteInTodaySchedule();

        $response = $this->actingAs($user)->post('/admin/verifications', [
            'code' => $athlete->qr_code,
        ]);

        $response->assertRedirect(route('admin.verifications.index'));
        $response->assertSessionHas(
            'success',
            "{$athlete->name} terverifikasi pada partai {$schedule->match_no}."
        );
        $this->assertDatabaseHas('verifications', [
            'schedule_id' => $schedule->id,
            'athlete_id' => $athlete->id,
            'method' => 'qr',
            'status' => 'present',
            'verified_by' => $user->id,
        ]);
    }

    public function test_double_verification_updates_timestamp(): void
    {
        $user = $this->userWithPermissions(['verifications.create']);
        [$athlete, $schedule] = $this->athleteInTodaySchedule();

        $this->actingAs($user)->post('/admin/verifications', ['code' => $athlete->qr_code]);
        $response = $this->actingAs($user)->post('/admin/verifications', ['code' => $athlete->qr_code]);

        $response->assertSessionHas(
            'success',
            "{$athlete->name} sudah terverifikasi, waktu diperbarui."
        );
        $this->assertSame(1, Verification::count());
    }

    public function test_unknown_code_is_rejected(): void
    {
        $user = $this->userWithPermissions(['verifications.create']);

        $response = $this->actingAs($user)->from('/admin/verifications')->post('/admin/verifications', [
            'code' => 'TIDAK-ADA',
        ]);

        $response->assertSessionHas('error', 'Kode "TIDAK-ADA" tidak terdaftar sebagai atlet.');
        $this->assertSame(0, Verification::count());
    }

    public function test_inactive_athlete_is_rejected(): void
    {
        $user = $this->userWithPermissions(['verifications.create']);
        [$athlete] = $this->athleteInTodaySchedule();
        $athlete->update(['status' => 'inactive']);

        $response = $this->actingAs($user)->from('/admin/verifications')->post('/admin/verifications', [
            'code' => $athlete->qr_code,
        ]);

        $response->assertSessionHas('error', "Atlet {$athlete->name} berstatus nonaktif.");
        $this->assertSame(0, Verification::count());
    }

    public function test_verification_without_schedule_is_rejected(): void
    {
        $user = $this->userWithPermissions(['verifications.create']);
        $athlete = AthleteFactory::new()->create();

        $response = $this->actingAs($user)->from('/admin/verifications')->post('/admin/verifications', [
            'code' => $athlete->qr_code,
        ]);

        $response->assertSessionHas('error', "Tidak ada jadwal hari ini untuk atlet {$athlete->name}.");
        $this->assertSame(0, Verification::count());
    }

    public function test_verification_requires_code(): void
    {
        $user = $this->userWithPermissions(['verifications.create']);

        $response = $this->actingAs($user)->from('/admin/verifications')->post('/admin/verifications', []);

        $response->assertSessionHasErrors('code', 'Kode atlet wajib diisi.');
    }

    public function test_verification_json_response(): void
    {
        $user = $this->userWithPermissions(['verifications.create']);
        [$athlete, $schedule] = $this->athleteInTodaySchedule();

        $response = $this->actingAs($user)->postJson('/admin/verifications', [
            'code' => $athlete->participant_number,
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['message', 'already', 'athlete', 'schedule', 'verified_at']);
        $response->assertJsonPath('athlete.id', $athlete->id);
        $response->assertJsonPath('schedule.id', $schedule->id);
        $response->assertJsonPath('already', false);
    }

    public function test_verification_json_error_for_unknown_code(): void
    {
        $user = $this->userWithPermissions(['verifications.create']);

        $response = $this->actingAs($user)->postJson('/admin/verifications', ['code' => 'SALAH']);

        $response->assertStatus(422);
        $response->assertJsonPath('message', 'Kode "SALAH" tidak terdaftar sebagai atlet.');
    }

    public function test_verification_module_requires_permission(): void
    {
        $user = $this->userWithPermissions(['verifications.view']);

        $this->actingAs($user)->get('/admin/verifications')->assertOk();
        $this->actingAs($user)->post('/admin/verifications', ['code' => 'X'])->assertForbidden();
    }
}
