<?php

namespace Tests\Feature;

use Database\Factories\AthleteFactory;
use Database\Factories\CategoryFactory;
use Database\Factories\ScheduleFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    public function test_category_index_can_be_rendered(): void
    {
        $user = $this->userWithPermissions(['categories.view']);
        CategoryFactory::new()->daeryun()->create(['name' => 'Kategori Senior Putra']);

        $response = $this->actingAs($user)->get('/admin/categories');

        $response->assertOk();
        $response->assertSee('Kategori Senior Putra');
    }

    public function test_category_index_can_be_filtered_by_type(): void
    {
        $user = $this->userWithPermissions(['categories.view']);
        CategoryFactory::new()->daeryun()->create(['name' => 'Daeryun Satu']);
        CategoryFactory::new()->art()->create(['name' => 'Seni Dua']);

        $response = $this->actingAs($user)->get('/admin/categories?type=art');

        $response->assertOk();
        $response->assertSee('Seni Dua');
        $response->assertDontSee('Daeryun Satu');
    }

    public function test_category_can_be_created_with_unique_slug(): void
    {
        $user = $this->userWithPermissions(['categories.create']);

        $response = $this->actingAs($user)->post('/admin/categories', [
            'name' => 'Junior Putri',
            'type' => 'daeryun',
            'gender' => 'female',
            'age_class' => 'Junior',
        ]);

        $response->assertRedirect(route('admin.categories.index'));
        $response->assertSessionHas('success', 'Kategori berhasil ditambahkan.');
        $this->assertDatabaseHas('categories', ['name' => 'Junior Putri', 'slug' => 'junior-putri']);
    }

    public function test_category_slug_is_made_unique(): void
    {
        $user = $this->userWithPermissions(['categories.create']);
        CategoryFactory::new()->create(['name' => 'Junior Putri', 'slug' => 'junior-putri']);

        $this->actingAs($user)->post('/admin/categories', [
            'name' => 'Junior Putri',
            'type' => 'art',
            'gender' => 'open',
        ]);

        $this->assertDatabaseHas('categories', ['slug' => 'junior-putri-1']);
    }

    public function test_category_validation_rejects_invalid_type(): void
    {
        $user = $this->userWithPermissions(['categories.create']);

        $response = $this->actingAs($user)->from('/admin/categories')->post('/admin/categories', [
            'name' => 'Salah',
            'type' => 'salah',
            'gender' => 'open',
        ]);

        $response->assertSessionHasErrors('type');
        $this->assertDatabaseCount('categories', 0);
    }

    public function test_category_can_be_updated(): void
    {
        $user = $this->userWithPermissions(['categories.update']);
        $category = CategoryFactory::new()->create(['name' => 'Lama', 'slug' => 'lama']);

        $response = $this->actingAs($user)->put("/admin/categories/{$category->id}", [
            'name' => 'Baru Sekali',
            'type' => 'art',
            'gender' => 'male',
            'age_class' => 'Senior',
        ]);

        $response->assertSessionHas('success', 'Kategori berhasil diperbarui.');
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Baru Sekali',
            'slug' => 'baru-sekali',
            'type' => 'art',
        ]);
    }

    public function test_category_with_athletes_cannot_be_deleted(): void
    {
        $user = $this->userWithPermissions(['categories.delete']);
        $category = CategoryFactory::new()->create();
        AthleteFactory::new()->create(['category_id' => $category->id]);

        $response = $this->actingAs($user)->delete("/admin/categories/{$category->id}");

        $response->assertSessionHas('error', 'Kategori tidak bisa dihapus karena masih memiliki atlet.');
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_category_with_schedules_cannot_be_deleted(): void
    {
        $user = $this->userWithPermissions(['categories.delete']);
        $category = CategoryFactory::new()->create();
        ScheduleFactory::new()->create(['category_id' => $category->id]);

        $response = $this->actingAs($user)->delete("/admin/categories/{$category->id}");

        $response->assertSessionHas('error', 'Kategori tidak bisa dihapus karena masih terkait jadwal / bracket.');
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_unused_category_can_be_deleted(): void
    {
        $user = $this->userWithPermissions(['categories.delete']);
        $category = CategoryFactory::new()->create();

        $response = $this->actingAs($user)->delete("/admin/categories/{$category->id}");

        $response->assertRedirect(route('admin.categories.index'));
        $response->assertSessionHas('success', 'Kategori berhasil dihapus.');
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }
}
