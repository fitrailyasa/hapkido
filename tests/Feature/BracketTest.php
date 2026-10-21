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

        $response = $this->actingAs($user)->get("/admin/brackets?category={$category->id}");

        $response->assertOk();
        $response->assertSee('Partai Manual');
        $response->assertSee('Buat jadwal dulu di menu');
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
            "Bracket kelas {$category->name} berhasil dibuat untuk 4 peserta."
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

    public function test_bracket_cannot_be_generated_without_jadwal(): void
    {
        $user = $this->userWithPermissions(['brackets.generate']);
        $category = CategoryFactory::new()->daeryun()->create();
        foreach (range(1, 4) as $i) {
            AthleteFactory::new()->create(['category_id' => $category->id, 'status' => 'active']);
        }

        $response = $this->actingAs($user)
            ->from('/admin/brackets')
            ->post('/admin/brackets/generate', ['category_id' => $category->id]);

        $response->assertSessionHas(
            'error',
            "Kelas {$category->name} belum memiliki jadwal partai Daeryun. Buat jadwal terlebih dahulu di menu Jadwal."
        );
        $this->assertSame(0, BracketSlot::count());
        $this->assertSame(0, Matchup::count());
    }

    public function test_bracket_cannot_be_generated_with_too_few_partai(): void
    {
        $user = $this->userWithPermissions(['brackets.generate']);
        $category = CategoryFactory::new()->daeryun()->create();
        foreach (range(1, 4) as $i) {
            AthleteFactory::new()->create(['category_id' => $category->id, 'status' => 'active']);
        }
        // Only one first-round partai → bracket width 2, but there are 4 athletes.
        ScheduleFactory::new()->create([
            'category_id' => $category->id,
            'type' => 'daeryun',
            'round' => 'semifinal',
            'order_no' => 1,
        ]);

        $response = $this->actingAs($user)
            ->from('/admin/brackets')
            ->post('/admin/brackets/generate', ['category_id' => $category->id]);

        $response->assertSessionHas(
            'error',
            "Jadwal partai ronde pertama kelas {$category->name} hanya menampung "
            . '2 peserta (saat ini 1 partai), sementara ada 4 peserta aktif. '
            . 'Tambahkan jadwal ronde pertama hingga minimal 2 partai, atau nonaktifkan kelebihan peserta.'
        );
        $this->assertSame(0, BracketSlot::count());
        $this->assertSame(0, Matchup::count());
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

        // Two real first-round matches; the fifth athlete advances by BYE
        // (no matchup row for that pairing).
        $this->assertSame(
            2,
            Matchup::where('round', 'perempat_final')->count()
        );
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
