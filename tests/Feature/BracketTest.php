<?php

namespace Tests\Feature;

use App\Models\BracketSlot;
use App\Models\Category;
use App\Models\Matchup;
use App\Models\Schedule;
use App\Services\BracketService;
use Database\Factories\AthleteFactory;
use Database\Factories\CategoryFactory;
use Database\Factories\ScheduleFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class BracketTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    /**
     * The jadwal of a bracket of the given size: one partai per pairing of
     * every round. The bracket is generated from these partai.
     */
    private function jadwalFor(Category $category, int $size): void
    {
        $service = app(BracketService::class);

        for ($roundSize = $size; $roundSize >= 2; $roundSize = (int) ($roundSize / 2)) {
            for ($position = 1; $position <= $roundSize / 2; $position++) {
                ScheduleFactory::new()->create([
                    'category_id' => $category->id,
                    'type' => 'daeryun',
                    'round' => $service->label($roundSize),
                    'order_no' => $position,
                ]);
            }
        }
    }

    public function test_bracket_index_can_be_rendered(): void
    {
        $user = $this->userWithPermissions(['brackets.view']);

        $response = $this->actingAs($user)->get('/admin/brackets');

        $response->assertOk();
    }

    public function test_bracket_index_shows_category_without_bracket(): void
    {
        $user = $this->userWithPermissions(['brackets.view']);
        $category = CategoryFactory::new()->daeryun()->create(['name' => 'Kategori Kosong']);

        $response = $this->actingAs($user)->get("/admin/brackets?category={$category->id}");

        $response->assertOk();
        $response->assertSee('Kategori Kosong');
    }

    public function test_bracket_index_shows_the_manual_partai_card_without_jadwal(): void
    {
        $user = $this->userWithPermissions(['brackets.view', 'brackets.generate']);
        $category = CategoryFactory::new()->daeryun()->create();
        foreach (range(1, 4) as $i) {
            AthleteFactory::new()->create(['category_id' => $category->id, 'status' => 'active']);
        }

        $response = $this->actingAs($user)->get("/admin/brackets?category={$category->id}");

        $response->assertOk();
        $response->assertSee('Partai Manual');
        $response->assertSee('tanpa wajib isi jadwal');
        $this->assertGreaterThan(0, Schedule::where('category_id', $category->id)->where('type', 'daeryun')->count());
    }

    public function test_bracket_index_reports_a_full_first_round(): void
    {
        $user = $this->userWithPermissions(['brackets.view', 'brackets.generate']);
        $category = CategoryFactory::new()->daeryun()->create();

        foreach (range(1, 2) as $index) {
            AthleteFactory::new()->create(['category_id' => $category->id, 'status' => 'active']);
        }

        $this->jadwalFor($category, 2);

        $athletes = $category->athletes()->orderBy('id')->get();

        app(BracketService::class)->assignManual(
            $category,
            $category->schedules()->where('type', 'daeryun')->firstOrFail(),
            $athletes[0],
            $athletes[1]
        );

        $response = $this->actingAs($user)->get("/admin/brackets?category={$category->id}");

        $response->assertOk();
        $response->assertSee('Partai Manual');
        $response->assertSee('sudah terisi atlet');
    }

    public function test_bracket_cannot_be_generated_for_art_category(): void
    {
        $user = $this->userWithPermissions(['brackets.generate']);
        $category = CategoryFactory::new()->art()->create();
        AthleteFactory::new()->create(['category_id' => $category->id, 'status' => 'active']);
        AthleteFactory::new()->create(['category_id' => $category->id, 'status' => 'active']);

        $response = $this->actingAs($user)
            ->from('/admin/brackets')
            ->post('/admin/brackets/generate', ['category_id' => $category->id]);

        $response->assertSessionHas('error', 'Bracket hanya bisa dibuat untuk kelas Daeryun.');
        $this->assertSame(0, BracketSlot::count());
    }

    public function test_bracket_requires_two_athletes(): void
    {
        $user = $this->userWithPermissions(['brackets.generate']);
        $category = CategoryFactory::new()->daeryun()->create();
        AthleteFactory::new()->create(['category_id' => $category->id, 'status' => 'active']);
        AthleteFactory::new()->create(['category_id' => $category->id, 'status' => 'inactive']);

        $response = $this->actingAs($user)
            ->from('/admin/brackets')
            ->post('/admin/brackets/generate', ['category_id' => $category->id]);

        $response->assertSessionHas(
            'error',
            "Bracket gagal dibuat: peserta aktif pada kelas {$category->name} baru 1 orang (minimal 2)."
        );
        $this->assertSame(0, BracketSlot::count());
    }

    public function test_bracket_generation_requires_category(): void
    {
        $user = $this->userWithPermissions(['brackets.generate']);

        $response = $this->actingAs($user)
            ->from('/admin/brackets')
            ->post('/admin/brackets/generate', []);

        $response->assertSessionHasErrors('category_id', 'Kelas wajib dipilih.');
    }

    public function test_bracket_can_be_generated(): void
    {
        $user = $this->userWithPermissions(['brackets.generate']);
        $category = CategoryFactory::new()->daeryun()->create(['name' => 'Kategori A']);
        foreach (range(1, 4) as $i) {
            AthleteFactory::new()->create(['category_id' => $category->id, 'status' => 'active']);
        }
        $this->jadwalFor($category, 4);

        $response = $this->actingAs($user)
            ->from('/admin/brackets')
            ->post('/admin/brackets/generate', ['category_id' => $category->id]);

        $response->assertRedirect(route('admin.brackets.index', ['category' => $category->id]));
        $response->assertSessionHas(
            'success',
            "Bracket kelas {$category->name} berhasil dibuat untuk 4 peserta. "
            .'Lengkapi jam dan arena partai di menu Jadwal bila perlu.'
        );
        $this->assertGreaterThan(0, BracketSlot::where('category_id', $category->id)->count());
        $this->assertGreaterThan(0, Matchup::count());
        $this->assertSame(
            4,
            BracketSlot::where('category_id', $category->id)->where('round', '4')->count()
        );

        // The partai are bound to the jadwal of the class, the jadwal itself
        // (time, arena, date) is kept.
        $this->assertSame(3, Schedule::where('category_id', $category->id)->where('type', 'daeryun')->count());
        $this->assertSame(
            3,
            Matchup::whereHas('schedule', fn ($query) => $query
                ->where('category_id', $category->id)
                ->where('type', 'daeryun'))->count()
        );
    }

    public function test_bracket_can_be_generated_without_jadwal(): void
    {
        $user = $this->userWithPermissions(['brackets.generate']);
        $category = CategoryFactory::new()->daeryun()->create();
        foreach (range(1, 4) as $i) {
            AthleteFactory::new()->create(['category_id' => $category->id, 'status' => 'active']);
        }

        $this->actingAs($user)
            ->from('/admin/brackets')
            ->post('/admin/brackets/generate', ['category_id' => $category->id])
            ->assertSessionHas('success');

        $this->assertSame(
            4,
            BracketSlot::where('category_id', $category->id)->where('round', '4')->count()
        );
        $this->assertGreaterThanOrEqual(3, Matchup::count());
        $this->assertGreaterThanOrEqual(3, Schedule::where('category_id', $category->id)->where('type', 'daeryun')->count());
    }

    public function test_generate_does_not_inflate_size_from_oversized_jadwal(): void
    {
        $user = $this->userWithPermissions(['brackets.generate']);
        $category = CategoryFactory::new()->daeryun()->create();

        foreach (range(1, 5) as $i) {
            AthleteFactory::new()->create(['category_id' => $category->id, 'status' => 'active']);
        }

        // 8 penyisihan rows would formerly force size 16 (11 BYEs). Size must
        // stay at 8 for 5 athletes (only 3 single-step BYEs).
        foreach (range(1, 8) as $order) {
            ScheduleFactory::new()->create([
                'category_id' => $category->id,
                'type' => 'daeryun',
                'round' => 'penyisihan',
                'order_no' => $order,
            ]);
        }

        $this->actingAs($user)
            ->post('/admin/brackets/generate', ['category_id' => $category->id])
            ->assertSessionHas('success');

        // 5 athletes → size 8 (not inflated to 16 by oversized jadwal).
        $this->assertSame(
            0,
            BracketSlot::where('category_id', $category->id)->where('round', '16')->count()
        );
        $this->assertSame(
            8,
            BracketSlot::where('category_id', $category->id)->where('round', '8')->count()
        );
    }

    public function test_generate_expands_bracket_when_jadwal_is_smaller_than_athletes(): void
    {
        $user = $this->userWithPermissions(['brackets.generate']);
        $category = CategoryFactory::new()->daeryun()->create();
        foreach (range(1, 4) as $i) {
            AthleteFactory::new()->create(['category_id' => $category->id, 'status' => 'active']);
        }
        // Only one jadwal row — generate grows to size 4 and auto-creates the rest.
        ScheduleFactory::new()->create([
            'category_id' => $category->id,
            'type' => 'daeryun',
            'round' => 'semifinal',
            'order_no' => 1,
        ]);

        $this->actingAs($user)
            ->post('/admin/brackets/generate', ['category_id' => $category->id])
            ->assertSessionHas('success');

        $this->assertSame(
            4,
            BracketSlot::where('category_id', $category->id)->where('round', '4')->count()
        );
        $this->assertGreaterThanOrEqual(3, Matchup::count());
    }

    public function test_regenerate_follows_shrunk_jadwal_and_relabels_first_round(): void
    {
        $user = $this->userWithPermissions(['brackets.generate']);
        $category = CategoryFactory::new()->daeryun()->create();

        foreach (range(1, 8) as $i) {
            AthleteFactory::new()->create(['category_id' => $category->id, 'status' => 'active']);
        }

        // Leftover labels from a larger bracket: 4× penyisihan + SF + Final.
        // Generate must shrink to size 8 and rename penyisihan → perempat_final.
        foreach (range(1, 4) as $order) {
            ScheduleFactory::new()->create([
                'category_id' => $category->id,
                'type' => 'daeryun',
                'round' => 'penyisihan',
                'order_no' => $order,
            ]);
        }
        foreach (range(1, 2) as $order) {
            ScheduleFactory::new()->create([
                'category_id' => $category->id,
                'type' => 'daeryun',
                'round' => 'semifinal',
                'order_no' => $order,
            ]);
        }
        ScheduleFactory::new()->create([
            'category_id' => $category->id,
            'type' => 'daeryun',
            'round' => 'final',
            'order_no' => 1,
        ]);

        $this->actingAs($user)
            ->post('/admin/brackets/generate', ['category_id' => $category->id])
            ->assertSessionHas('success');

        $this->assertSame(
            8,
            BracketSlot::where('category_id', $category->id)->where('round', '8')->count()
        );
        $this->assertSame(
            4,
            Schedule::where('category_id', $category->id)->where('round', 'perempat_final')->count()
        );
        $this->assertSame(
            0,
            Schedule::where('category_id', $category->id)->where('round', 'penyisihan')->count()
        );
        $this->assertSame(7, Matchup::count());
    }

    public function test_ensure_bracket_matches_creates_clickable_quarterfinals(): void
    {
        $user = $this->userWithPermissions(['brackets.view', 'brackets.generate']);
        $category = CategoryFactory::new()->daeryun()->create();

        foreach (range(1, 8) as $i) {
            AthleteFactory::new()->create(['category_id' => $category->id, 'status' => 'active']);
        }

        // Only Penyisihan jadwal (size-16 tree). No Perempat Final rows yet.
        foreach (range(1, 8) as $order) {
            ScheduleFactory::new()->create([
                'category_id' => $category->id,
                'type' => 'daeryun',
                'round' => 'penyisihan',
                'order_no' => $order,
            ]);
        }

        $this->actingAs($user)
            ->post('/admin/brackets/generate', ['category_id' => $category->id])
            ->assertSessionHas('success');

        $this->assertGreaterThan(
            0,
            Schedule::where('category_id', $category->id)->where('round', 'perempat_final')->count()
        );
        $this->assertGreaterThan(
            0,
            Matchup::where('round', 'perempat_final')->count()
        );

        $this->actingAs($user)->get("/admin/brackets?category={$category->id}")->assertOk();

        $qf = Matchup::where('round', 'perempat_final')->with('schedule')->first();
        $this->assertNotNull($qf);
        $this->assertNotNull($qf->schedule);
    }

    public function test_generate_auto_creates_semifinal_and_final_jadwal(): void
    {
        $user = $this->userWithPermissions(['brackets.generate']);
        $category = CategoryFactory::new()->daeryun()->create();

        foreach (range(1, 8) as $i) {
            AthleteFactory::new()->create(['category_id' => $category->id, 'status' => 'active']);
        }

        // Only the 4 first-round partai — the case that used to fail asking for Semifinal.
        foreach (range(1, 4) as $order) {
            ScheduleFactory::new()->create([
                'category_id' => $category->id,
                'type' => 'daeryun',
                'round' => 'penyisihan',
                'order_no' => $order,
            ]);
        }

        $this->actingAs($user)
            ->post('/admin/brackets/generate', ['category_id' => $category->id])
            ->assertSessionHas('success');

        $this->assertSame(
            4,
            Schedule::where('category_id', $category->id)->where('round', 'perempat_final')->count()
        );
        $this->assertSame(
            2,
            Schedule::where('category_id', $category->id)->where('round', 'semifinal')->count()
        );
        $this->assertSame(
            1,
            Schedule::where('category_id', $category->id)->where('round', 'final')->count()
        );
        $this->assertSame(7, Matchup::count());
    }

    public function test_generate_gives_bye_for_odd_athlete_count(): void
    {
        $user = $this->userWithPermissions(['brackets.generate']);
        $category = CategoryFactory::new()->daeryun()->create();

        foreach (range(1, 5) as $i) {
            AthleteFactory::new()->create(['category_id' => $category->id, 'status' => 'active']);
        }

        $this->jadwalFor($category, 8);

        $this->actingAs($user)
            ->post('/admin/brackets/generate', ['category_id' => $category->id])
            ->assertSessionHas('success');

        // 5 athletes → size 8: penyisihan column has all names (BYE + 1 real match).
        $this->assertSame(
            8,
            BracketSlot::where('category_id', $category->id)->where('round', '8')->count()
        );
        $this->assertSame(
            5,
            BracketSlot::where('category_id', $category->id)
                ->where('round', '8')
                ->whereNotNull('athlete_id')
                ->count()
        );
        $this->assertSame(
            1,
            Matchup::where('round', 'perempat_final')->count()
        );
        $this->assertSame(
            3,
            BracketSlot::where('category_id', $category->id)
                ->where('round', '4')
                ->whereNotNull('athlete_id')
                ->count()
        );
        $this->assertSame(
            0,
            BracketSlot::where('category_id', $category->id)
                ->where('round', '2')
                ->whereNotNull('athlete_id')
                ->count()
        );
    }

    public function test_nine_athletes_get_one_prelim_and_byes_into_quarterfinals(): void
    {
        $user = $this->userWithPermissions(['brackets.generate']);
        $category = CategoryFactory::new()->daeryun()->create();

        foreach (range(1, 9) as $i) {
            AthleteFactory::new()->create(['category_id' => $category->id, 'status' => 'active']);
        }

        // Enough first-round jadwal width for a 16-slot tree.
        foreach (range(1, 8) as $order) {
            ScheduleFactory::new()->create([
                'category_id' => $category->id,
                'type' => 'daeryun',
                'round' => 'penyisihan',
                'order_no' => $order,
            ]);
        }

        $this->actingAs($user)
            ->post('/admin/brackets/generate', ['category_id' => $category->id])
            ->assertSessionHas('success');

        // Like the paper sheet: 1 penyisihan + 1 BYE + 3 partai penuh di bagan 8.
        $this->assertSame(1, Matchup::where('round', 'penyisihan')->count());
        $this->assertSame(4, Matchup::where('round', 'perempat_final')->count());
        $this->assertSame(
            0,
            BracketSlot::where('category_id', $category->id)->where('round', '16')->count()
        );
        $this->assertSame(
            8,
            BracketSlot::where('category_id', $category->id)->where('round', '8')->count()
        );
        // 1 BYE seed + 6 in full QF pairs = 7 names in slots; slot 1 waits for prelim.
        $this->assertSame(
            7,
            BracketSlot::where('category_id', $category->id)
                ->where('round', '8')
                ->whereNotNull('athlete_id')
                ->count()
        );
        $this->assertNull(
            BracketSlot::where('category_id', $category->id)
                ->where('round', '8')
                ->where('position', 1)
                ->value('athlete_id')
        );
        $this->assertNotNull(
            BracketSlot::where('category_id', $category->id)
                ->where('round', '8')
                ->where('position', 0)
                ->value('athlete_id')
        );
        $this->assertSame(
            0,
            BracketSlot::where('category_id', $category->id)
                ->where('round', '2')
                ->whereNotNull('athlete_id')
                ->count()
        );

        $prelim = Matchup::with(['athleteA', 'athleteB'])->where('round', 'penyisihan')->first();
        $this->assertNotNull($prelim);
        $payload = app(\App\Services\BracketViewerService::class)->payload($category);
        $this->assertNotNull($payload);
        $names = collect($payload['viewerData']['participants'])->pluck('name');
        $this->assertTrue(
            $names->contains("{$prelim->athleteA->name} vs {$prelim->athleteB->name}"),
            'BYE slot opponent shows both penyisihan athletes. Got: '.$names->implode(', ')
        );
        $this->assertCount(8, $names);
        $this->assertNotEmpty($payload['byeIds']);
    }

    public function test_reconcile_clears_premature_finalist(): void
    {
        $user = $this->userWithPermissions(['brackets.generate', 'brackets.view']);
        $category = CategoryFactory::new()->daeryun()->create();

        foreach (range(1, 4) as $i) {
            AthleteFactory::new()->create(['category_id' => $category->id, 'status' => 'active']);
        }

        $this->jadwalFor($category, 4);

        $this->actingAs($user)
            ->post('/admin/brackets/generate', ['category_id' => $category->id])
            ->assertSessionHas('success');

        $semifinal = Matchup::where('round', 'semifinal')->orderBy('position')->first();
        $this->assertNotNull($semifinal);
        $winner = $semifinal->athleteA;
        $this->assertNotNull($winner);

        // Corrupt state: put the athlete in the Final before the semifinal is played.
        BracketSlot::where('category_id', $category->id)
            ->where('round', '2')
            ->where('position', 0)
            ->update(['athlete_id' => $winner->id]);

        app(BracketService::class)->ensureBracketMatches($category);

        $this->assertFalse(
            BracketSlot::where('category_id', $category->id)
                ->where('round', '2')
                ->where('athlete_id', $winner->id)
                ->exists(),
            'Atlet tidak boleh tampil di Final sebelum Semifinal selesai.'
        );
    }

    public function test_finishing_a_match_only_advances_one_round(): void
    {
        $user = $this->userWithPermissions(['brackets.generate']);
        $category = CategoryFactory::new()->daeryun()->create();

        foreach (range(1, 8) as $i) {
            AthleteFactory::new()->create(['category_id' => $category->id, 'status' => 'active']);
        }

        $this->jadwalFor($category, 8);

        $this->actingAs($user)
            ->post('/admin/brackets/generate', ['category_id' => $category->id])
            ->assertSessionHas('success');

        $firstRound = Matchup::with(['athleteA', 'schedule.category'])
            ->where('round', 'perempat_final')
            ->orderBy('position')
            ->first();
        $this->assertNotNull($firstRound);

        $winner = $firstRound->athleteA;
        $this->assertNotNull($winner);

        $firstRound->update([
            'status' => 'finished',
            'winner_athlete_id' => $winner->id,
            'winner_id' => $winner->id,
        ]);

        app(BracketService::class)->advance($firstRound, $winner);

        // Winner must appear in the immediate next round (semifinal), not the final.
        $this->assertTrue(
            BracketSlot::where('category_id', $category->id)
                ->where('round', '4')
                ->where('athlete_id', $winner->id)
                ->exists()
        );
        $this->assertFalse(
            BracketSlot::where('category_id', $category->id)
                ->where('round', '2')
                ->where('athlete_id', $winner->id)
                ->exists(),
            'Pemenang tidak boleh langsung masuk Final sebelum Semifinal selesai.'
        );

        $final = Matchup::where('round', 'final')->first();
        $this->assertNotNull($final);
        $this->assertNotSame('finished', $final->status);
        $this->assertNull($final->winner_athlete_id);
    }

    public function test_manual_partai_can_assign_a_bye(): void
    {
        $user = $this->userWithPermissions(['brackets.generate']);
        $category = CategoryFactory::new()->daeryun()->create();
        $athlete = AthleteFactory::new()->create([
            'category_id' => $category->id,
            'status' => 'active',
        ]);
        AthleteFactory::new()->create([
            'category_id' => $category->id,
            'status' => 'active',
        ]);
        $this->jadwalFor($category, 2);

        $schedule = Schedule::where('category_id', $category->id)->first();

        $response = $this->actingAs($user)
            ->from('/admin/brackets')
            ->post('/admin/brackets/manual', [
                'schedule_id' => $schedule->id,
                'athlete_a_id' => $athlete->id,
                // athlete_b_id omitted → BYE
            ]);

        $response->assertSessionHas(
            'success',
            "Partai {$schedule->match_no}: {$athlete->name} BYE (lolos tanpa lawan)."
        );
        $this->assertDatabaseHas('matches', [
            'schedule_id' => $schedule->id,
            'athlete_a_id' => $athlete->id,
            'athlete_b_id' => null,
            'winner_athlete_id' => $athlete->id,
            'status' => 'finished',
        ]);
    }

    public function test_regenerating_the_bracket_keeps_the_jadwal(): void
    {
        $user = $this->userWithPermissions(['brackets.generate']);
        $category = CategoryFactory::new()->daeryun()->create();
        foreach (range(1, 4) as $i) {
            AthleteFactory::new()->create(['category_id' => $category->id, 'status' => 'active']);
        }
        $this->jadwalFor($category, 4);

        $this->actingAs($user)
            ->post('/admin/brackets/generate', ['category_id' => $category->id]);

        $this->actingAs($user)
            ->post('/admin/brackets/generate', ['category_id' => $category->id])
            ->assertSessionHas('success');

        $this->assertSame(3, Schedule::where('category_id', $category->id)->where('type', 'daeryun')->count());
        $this->assertSame(3, Matchup::count());
    }

    public function test_manual_partai_can_be_created(): void
    {
        $user = $this->userWithPermissions(['brackets.generate']);
        $category = CategoryFactory::new()->daeryun()->create();
        $athletes = AthleteFactory::new()->times(4)->create([
            'category_id' => $category->id,
            'status' => 'active',
        ]);
        $this->jadwalFor($category, 4);

        $schedule = Schedule::where('category_id', $category->id)
            ->where('round', 'semifinal')
            ->orderBy('order_no')
            ->first();

        $response = $this->actingAs($user)
            ->from('/admin/brackets')
            ->post('/admin/brackets/manual', [
                'schedule_id' => $schedule->id,
                'athlete_a_id' => $athletes[0]->id,
                'athlete_b_id' => $athletes[1]->id,
            ]);

        $response->assertSessionHas(
            'success',
            "Partai {$schedule->match_no} dibuat: {$athletes[0]->name} vs {$athletes[1]->name}."
        );
        $this->assertDatabaseHas('matches', [
            'schedule_id' => $schedule->id,
            'athlete_a_id' => $athletes[0]->id,
            'athlete_b_id' => $athletes[1]->id,
            'round' => 'semifinal',
            'position' => 1,
        ]);
    }

    public function test_manual_partai_rejects_an_athlete_of_another_class(): void
    {
        $user = $this->userWithPermissions(['brackets.generate']);
        $category = CategoryFactory::new()->daeryun()->create();
        $other = CategoryFactory::new()->daeryun()->create();
        $athletes = AthleteFactory::new()->times(2)->create([
            'category_id' => $category->id,
            'status' => 'active',
        ]);
        $stranger = AthleteFactory::new()->create(['category_id' => $other->id, 'status' => 'active']);
        $this->jadwalFor($category, 2);

        $schedule = Schedule::where('category_id', $category->id)->first();

        $response = $this->actingAs($user)
            ->from('/admin/brackets')
            ->post('/admin/brackets/manual', [
                'schedule_id' => $schedule->id,
                'athlete_a_id' => $athletes[0]->id,
                'athlete_b_id' => $stranger->id,
            ]);

        $response->assertSessionHas(
            'error',
            'Kedua atlet harus aktif dan berasal dari kelas yang sama.'
        );
        $this->assertSame(0, Matchup::count());
    }

    public function test_manual_partai_rejects_the_same_athlete_on_both_sides(): void
    {
        $user = $this->userWithPermissions(['brackets.generate']);
        $category = CategoryFactory::new()->daeryun()->create();
        $athlete = AthleteFactory::new()->create([
            'category_id' => $category->id,
            'status' => 'active',
        ]);
        AthleteFactory::new()->create([
            'category_id' => $category->id,
            'status' => 'active',
        ]);
        $this->jadwalFor($category, 2);

        $schedule = Schedule::where('category_id', $category->id)->first();

        $response = $this->actingAs($user)
            ->from('/admin/brackets')
            ->post('/admin/brackets/manual', [
                'schedule_id' => $schedule->id,
                'athlete_a_id' => $athlete->id,
                'athlete_b_id' => $athlete->id,
            ]);

        $response->assertSessionHas('error', 'Dua atlet yang dipilih tidak boleh sama.');
        $this->assertSame(0, Matchup::count());
    }

    public function test_manual_partai_rejects_an_athlete_already_paired(): void
    {
        $user = $this->userWithPermissions(['brackets.generate']);
        $category = CategoryFactory::new()->daeryun()->create();
        $athletes = AthleteFactory::new()->times(4)->create([
            'category_id' => $category->id,
            'status' => 'active',
        ]);
        $this->jadwalFor($category, 4);

        $schedules = Schedule::where('category_id', $category->id)
            ->where('round', 'semifinal')
            ->orderBy('order_no')
            ->get();

        $this->actingAs($user)
            ->post('/admin/brackets/manual', [
                'schedule_id' => $schedules[0]->id,
                'athlete_a_id' => $athletes[0]->id,
                'athlete_b_id' => $athletes[1]->id,
            ])
            ->assertSessionHas('success');

        $response = $this->actingAs($user)
            ->from('/admin/brackets')
            ->post('/admin/brackets/manual', [
                'schedule_id' => $schedules[1]->id,
                'athlete_a_id' => $athletes[0]->id,
                'athlete_b_id' => $athletes[2]->id,
            ]);

        $response->assertSessionHas(
            'error',
            "Atlet {$athletes[0]->name} sudah bertanding di partai lain."
        );
        $this->assertSame(1, Matchup::count());
    }

    public function test_manual_partai_rejects_athletes_that_already_fought(): void
    {
        $user = $this->userWithPermissions(['brackets.generate']);
        $category = CategoryFactory::new()->daeryun()->create();
        $athletes = AthleteFactory::new()->times(4)->create([
            'category_id' => $category->id,
            'status' => 'active',
        ]);
        $this->jadwalFor($category, 4);

        $schedules = Schedule::where('category_id', $category->id)
            ->where('round', 'semifinal')
            ->orderBy('order_no')
            ->get();

        $this->actingAs($user)
            ->post('/admin/brackets/manual', [
                'schedule_id' => $schedules[0]->id,
                'athlete_a_id' => $athletes[0]->id,
                'athlete_b_id' => $athletes[1]->id,
            ])
            ->assertSessionHas('success');

        $response = $this->actingAs($user)
            ->from('/admin/brackets')
            ->post('/admin/brackets/manual', [
                'schedule_id' => $schedules[1]->id,
                'athlete_a_id' => $athletes[1]->id,
                'athlete_b_id' => $athletes[0]->id,
            ]);

        $response->assertSessionHas(
            'error',
            "{$athletes[1]->name} dan {$athletes[0]->name} sudah pernah dipertandingkan."
        );
        $this->assertSame(1, Matchup::count());
    }

    public function test_manual_partai_marks_athletes_that_already_fought(): void
    {
        $user = $this->userWithPermissions(['brackets.view', 'brackets.generate']);
        $category = CategoryFactory::new()->daeryun()->create();
        $athletes = AthleteFactory::new()->times(4)->create([
            'category_id' => $category->id,
            'status' => 'active',
        ]);
        $this->jadwalFor($category, 4);

        $schedule = Schedule::where('category_id', $category->id)
            ->where('round', 'semifinal')
            ->orderBy('order_no')
            ->first();

        app(BracketService::class)->assignManual(
            $category,
            $schedule,
            $athletes[0],
            $athletes[1]
        );

        $response = $this->actingAs($user)->get("/admin/brackets?category={$category->id}");

        $response->assertOk();
        $response->assertSee('— sudah bertanding');
        $response->assertSee($athletes[0]->name);
        $response->assertSee($athletes[1]->name);
    }

    public function test_generated_bracket_is_shown_on_index(): void
    {
        $user = $this->userWithPermissions(['brackets.generate', 'brackets.view']);
        $category = CategoryFactory::new()->daeryun()->create(['name' => 'Kategori B']);
        foreach (range(1, 3) as $i) {
            AthleteFactory::new()->create(['category_id' => $category->id, 'status' => 'active']);
        }
        $this->jadwalFor($category, 4);

        $this->actingAs($user)
            ->post('/admin/brackets/generate', ['category_id' => $category->id]);

        $response = $this->actingAs($user)->get("/admin/brackets?category={$category->id}");

        $response->assertOk();
        $response->assertSee('Kategori B');
    }

    public function test_bracket_module_requires_permission(): void
    {
        $user = $this->userWithPermissions(['brackets.view']);
        $category = CategoryFactory::new()->daeryun()->create();

        $this->actingAs($user)->get('/admin/brackets')->assertOk();
        $this->actingAs($user)
            ->post('/admin/brackets/generate', ['category_id' => $category->id])
            ->assertForbidden();
    }
}
