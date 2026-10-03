<?php

namespace Tests\Feature;

use App\Models\Calling;
use Database\Factories\AthleteFactory;
use Database\Factories\CallingFactory;
use Database\Factories\CategoryFactory;
use Database\Factories\ContingentFactory;
use Database\Factories\MatchupFactory;
use Database\Factories\ScheduleFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class CallingTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    private function scheduleWithAthletes(): array
    {
        $category = CategoryFactory::new()->daeryun()->create();
        $contingent = ContingentFactory::new()->create();
        $athleteA = AthleteFactory::new()->create([
            'contingent_id' => $contingent->id,
            'category_id' => $category->id,
        ]);
        $athleteB = AthleteFactory::new()->create([
            'contingent_id' => $contingent->id,
            'category_id' => $category->id,
        ]);
        $schedule = ScheduleFactory::new()->create(['category_id' => $category->id]);
        MatchupFactory::new()->create([
            'schedule_id' => $schedule->id,
            'athlete_a_id' => $athleteA->id,
            'athlete_b_id' => $athleteB->id,
        ]);

        return [$schedule, $athleteA, $athleteB];
    }

    public function test_calling_index_can_be_rendered(): void
    {
        $user = $this->userWithPermissions(['callings.view']);

        $response = $this->actingAs($user)->get('/admin/callings');

        $response->assertOk();
        $response->assertSee('Calling');
    }

    public function test_calling_data_syncs_rows_for_today(): void
    {
        $user = $this->userWithPermissions(['callings.view']);
        [$schedule] = $this->scheduleWithAthletes();

        $response = $this->actingAs($user)->getJson('/admin/callings-data');

        $response->assertOk();
        $response->assertJsonStructure(['date', 'generated_at', 'counts', 'arenas']);
        $this->assertSame(2, Calling::where('schedule_id', $schedule->id)->count());
        $this->assertSame(2, $response->json('counts.total'));
        $this->assertSame(2, $response->json('counts.waiting'));
    }

    public function test_calling_can_be_sent(): void
    {
        $user = $this->userWithPermissions(['callings.send']);
        $calling = CallingFactory::new()->create();

        $response = $this->actingAs($user)->post("/admin/callings/{$calling->id}/send", ['level' => '15']);

        $response->assertSessionHas('success', "Calling dikirim ke {$calling->athlete->name}.");
        $this->assertDatabaseHas('callings', [
            'id' => $calling->id,
            'level' => '15',
            'status' => 'called',
        ]);
        $this->assertNotNull($calling->fresh()->called_at);
    }

    public function test_calling_send_rejects_invalid_level(): void
    {
        $user = $this->userWithPermissions(['callings.send']);
        $calling = CallingFactory::new()->create();

        $response = $this->actingAs($user)
            ->from('/admin/callings')
            ->post("/admin/callings/{$calling->id}/send", ['level' => '60']);

        $response->assertSessionHasErrors('level', 'level calling');
        $this->assertDatabaseHas('callings', ['id' => $calling->id, 'status' => 'waiting']);
    }

    public function test_calling_can_be_sent_for_all_athletes(): void
    {
        $user = $this->userWithPermissions(['callings.send']);
        [$schedule] = $this->scheduleWithAthletes();

        $response = $this->actingAs($user)
            ->from('/admin/callings')
            ->post("/admin/callings-schedule/{$schedule->id}/send-all", ['level' => '30']);

        $response->assertSessionHas('success', "Calling dikirim untuk 2 atlet pada partai {$schedule->match_no}.");
        $this->assertSame(2, Calling::where('schedule_id', $schedule->id)->where('status', 'called')->count());
        $this->assertSame(2, Calling::where('schedule_id', $schedule->id)->where('level', '30')->count());
    }

    public function test_calling_send_all_without_athletes_fails(): void
    {
        $user = $this->userWithPermissions(['callings.send']);
        $schedule = ScheduleFactory::new()->create();

        $response = $this->actingAs($user)
            ->from('/admin/callings')
            ->post("/admin/callings-schedule/{$schedule->id}/send-all");

        $response->assertSessionHas('error', "Partai {$schedule->match_no} belum memiliki atlet.");
        $this->assertSame(0, Calling::count());
    }

    public function test_calling_module_requires_permission(): void
    {
        $viewUser = $this->userWithPermissions(['callings.view']);
        $calling = CallingFactory::new()->create();

        $this->actingAs($viewUser)->get('/admin/callings')->assertOk();
        $this->actingAs($viewUser)->post("/admin/callings/{$calling->id}/send", ['level' => '30'])->assertForbidden();
    }
}
