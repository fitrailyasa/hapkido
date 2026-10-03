<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Arena;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ArenaController extends Controller
{
    public function index()
    {
        return view('admin.arena.index', [
            'arenas' => Arena::query()
                ->with(['category', 'currentSchedule.category'])
                ->orderBy('name')
                ->get(),
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('arenas', 'name')],
            'label' => ['required', 'string', 'max:255'],
            'category_id' => ['nullable', Rule::exists('categories', 'id')],
            'match_type' => ['required', 'in:daeryun,art'],
            'status' => ['required', 'in:idle,preparation,running'],
        ], [
            'name.unique' => 'Nama arena sudah digunakan.',
        ]);

        Arena::create([
            'name' => $data['name'],
            'label' => $data['label'],
            'category_id' => $data['category_id'] ?: null,
            'match_type' => $data['match_type'],
            'status' => $data['status'],
        ]);

        return redirect()
            ->route('admin.arenas.index')
            ->with('success', "Arena {$data['name']} berhasil ditambahkan.");
    }

    public function update(Request $request, Arena $arena): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('arenas', 'name')->ignore($arena->id)],
            'label' => ['required', 'string', 'max:255'],
            'category_id' => ['nullable', Rule::exists('categories', 'id')],
            'match_type' => ['required', 'in:daeryun,art'],
            'status' => ['required', 'in:idle,preparation,running'],
        ], [
            'name.unique' => 'Nama arena sudah digunakan.',
        ]);

        $data['category_id'] = $data['category_id'] ?: null;

        $arena->update($data);

        return redirect()
            ->route('admin.arenas.index')
            ->with('success', 'Arena berhasil diperbarui.');
    }

    public function destroy(Arena $arena): RedirectResponse
    {
        if ($arena->current_schedule_id || \App\Models\Schedule::where('arena_id', $arena->id)->exists()) {
            return back()->with('error', "Arena {$arena->name} masih digunakan jadwal dan tidak bisa dihapus.");
        }

        $arena->delete();

        return redirect()
            ->route('admin.arenas.index')
            ->with('success', "Arena {$arena->name} berhasil dihapus.");
    }
}
