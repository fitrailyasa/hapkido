<?php

namespace Tests\Feature;

use App\Models\BracketSlot;
use App\Models\Matchup;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The seeder is what a fresh installation runs (php artisan migrate --seed),
 * so it must never blow up, whatever the state of the bracket rules.
 */
class SeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_database_can_be_seeded(): void
    {
        $this->seed();

        $this->assertNotEmpty(Role::pluck('name'));
        $this->assertTrue(User::where('email', 'test@example.com')->exists());

        // A daeryun class of 8 athletes has 7 partai (4 + 2 + 1).
        $category = \App\Models\Category::where('name', 'Daeryun Putra')->firstOrFail();

        $jadwal = Schedule::where('category_id', $category->id)
            ->where('type', 'daeryun')
            ->orderBy('id')
            ->get();

        $this->assertCount(7, $jadwal);
        $this->assertSame(
            ['perempat_final', 'perempat_final', 'perempat_final', 'perempat_final', 'semifinal', 'semifinal', 'final'],
            $jadwal->pluck('round')->all()
        );

        // Every generated partai is bound to an existing jadwal.
        $this->assertGreaterThan(0, BracketSlot::where('category_id', $category->id)->count());
        $this->assertSame(
            $jadwal->count(),
            Matchup::whereIn('schedule_id', $jadwal->pluck('id'))->count()
        );
    }
}
