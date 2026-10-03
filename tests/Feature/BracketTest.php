<?php

namespace Tests\Feature;

use App\Models\BracketSlot;
use App\Models\Matchup;
use Database\Factories\AthleteFactory;
use Database\Factories\CategoryFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class BracketTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

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

    public function test_bracket_cannot_be_generated_for_art_category(): void
    {
        $user = $this->userWithPermissions(['brackets.generate']);
        $category = CategoryFactory::new()->art()->create();
        AthleteFactory::new()->create(['category_id' => $category->id, 'status' => 'active']);
        AthleteFactory::new()->create(['category_id' => $category->id, 'status' => 'active']);

        $response = $this->actingAs($user)
            ->from('/admin/brackets')
            ->post('/admin/brackets/generate', ['category_id' => $category->id]);

        $response->assertSessionHas('error', 'Bracket hanya bisa dibuat untuk kategori Daeryun.');
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
            "Bracket gagal dibuat: peserta aktif pada kategori {$category->name} baru 1 orang (minimal 2)."
        );
        $this->assertSame(0, BracketSlot::count());
    }

    public function test_bracket_generation_requires_category(): void
    {
        $user = $this->userWithPermissions(['brackets.generate']);

        $response = $this->actingAs($user)
            ->from('/admin/brackets')
            ->post('/admin/brackets/generate', []);

        $response->assertSessionHasErrors('category_id', 'Kategori wajib dipilih.');
    }

    public function test_bracket_can_be_generated(): void
    {
        $user = $this->userWithPermissions(['brackets.generate']);
        $category = CategoryFactory::new()->daeryun()->create(['name' => 'Kategori A']);
        foreach (range(1, 4) as $i) {
            AthleteFactory::new()->create(['category_id' => $category->id, 'status' => 'active']);
        }

        $response = $this->actingAs($user)
            ->from('/admin/brackets')
            ->post('/admin/brackets/generate', ['category_id' => $category->id]);

        $response->assertRedirect(route('admin.brackets.index', ['category' => $category->id]));
        $response->assertSessionHas(
            'success',
            "Bracket kategori {$category->name} berhasil dibuat untuk 4 peserta."
        );
        $this->assertGreaterThan(0, BracketSlot::where('category_id', $category->id)->count());
        $this->assertGreaterThan(0, Matchup::count());
        $this->assertSame(
            4,
            BracketSlot::where('category_id', $category->id)->where('round', '4')->count()
        );
    }

    public function test_generated_bracket_is_shown_on_index(): void
    {
        $user = $this->userWithPermissions(['brackets.generate', 'brackets.view']);
        $category = CategoryFactory::new()->daeryun()->create(['name' => 'Kategori B']);
        foreach (range(1, 3) as $i) {
            AthleteFactory::new()->create(['category_id' => $category->id, 'status' => 'active']);
        }

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
