<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contingent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContingentController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q'));

        $query = Contingent::query()->withCount('athletes')->orderBy('name');

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('region', 'like', "%{$search}%");
            });
        }

        return view('admin.contingent.index', [
            'contingents' => $query->paginate(10)->withQueryString(),
            'search' => $search,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255', Rule::unique('contingents', 'code')],
            'region' => ['nullable', 'string', 'max:255'],
            'coach_name' => ['nullable', 'string', 'max:255'],
        ], [
            'code.unique' => 'Kode kontingen sudah digunakan.',
        ]);

        Contingent::create($data);

        return redirect()
            ->route('admin.contingents.index')
            ->with('success', 'Kontingen berhasil ditambahkan.');
    }

    public function update(Request $request, Contingent $contingent): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255', Rule::unique('contingents', 'code')->ignore($contingent->id)],
            'region' => ['nullable', 'string', 'max:255'],
            'coach_name' => ['nullable', 'string', 'max:255'],
        ], [
            'code.unique' => 'Kode kontingen sudah digunakan.',
        ]);

        $contingent->update($data);

        return redirect()
            ->route('admin.contingents.index')
            ->with('success', 'Kontingen berhasil diperbarui.');
    }

    public function destroy(Contingent $contingent): RedirectResponse
    {
        if ($contingent->athletes()->exists()) {
            return back()->with('error', 'Kontingen tidak bisa dihapus karena masih memiliki atlet.');
        }

        $contingent->delete();

        return redirect()
            ->route('admin.contingents.index')
            ->with('success', 'Kontingen berhasil dihapus.');
    }
}
