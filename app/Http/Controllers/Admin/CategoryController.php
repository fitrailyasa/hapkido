<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q'));
        $typeFilter = (string) $request->query('type');

        $query = Category::query()->withCount(['athletes', 'schedules'])->orderBy('name');

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('age_class', 'like', "%{$search}%");
            });
        }

        if ($typeFilter !== '') {
            $query->where('type', $typeFilter);
        }

        return view('admin.category.index', [
            'categories' => $query->paginate(10)->withQueryString(),
            'search' => $search,
            'typeFilter' => $typeFilter,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:daeryun,art'],
            'gender' => ['required', 'in:male,female,open'],
            'age_class' => ['nullable', 'string', 'max:255'],
        ]);

        $data['slug'] = $this->uniqueSlug($data['name']);

        Category::create($data);

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:daeryun,art'],
            'gender' => ['required', 'in:male,female,open'],
            'age_class' => ['nullable', 'string', 'max:255'],
        ]);

        if ($category->name !== $data['name']) {
            $data['slug'] = $this->uniqueSlug($data['name'], $category->id);
        } else {
            unset($data['slug']);
        }

        $category->update($data);

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->athletes()->exists()) {
            return back()->with('error', 'Kategori tidak bisa dihapus karena masih memiliki atlet.');
        }

        if ($category->schedules()->exists() || $category->bracketSlots()->exists()) {
            return back()->with('error', 'Kategori tidak bisa dihapus karena masih terkait jadwal / bracket.');
        }

        $category->delete();

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Kategori berhasil dihapus.');
    }

    protected function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $slug = Str::slug($name);
        $base = $slug !== '' ? $slug : 'kategori';
        $slug = $base;
        $suffix = 1;

        while (
            Category::where('slug', $slug)
                ->when($ignoreId, fn ($builder) => $builder->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base . '-' . $suffix++;
        }

        return $slug;
    }
}
