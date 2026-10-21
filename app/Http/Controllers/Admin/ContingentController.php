<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\BulkDeletes;
use App\Http\Controllers\Controller;
use App\Models\Contingent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContingentController extends Controller
{
    use BulkDeletes;
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
        if ($reason = $this->deleteBlocker($contingent)) {
            return back()->with('error', $reason);
        }

        $contingent->delete();

        return redirect()
            ->route('admin.contingents.index')
            ->with('success', 'Kontingen berhasil dihapus.');
    }

    /**
     * Delete every selected row that passes the guard of the single delete.
     */
    public function bulkDestroy(Request $request): RedirectResponse
    {
        $ids = $this->bulkIds($request, 'contingents');

        $deleted = 0;
        $skipped = [];

        foreach (Contingent::whereIn('id', $ids)->get() as $contingent) {
            if ($reason = $this->deleteBlocker($contingent)) {
                $skipped[] = $reason;

                continue;
            }

            $contingent->delete();
            $deleted++;
        }

        return $this->bulkResult('admin.contingents.index', $deleted, $skipped);
    }

    /**
     * Why the contingent cannot be deleted, or null when it can be deleted.
     */
    protected function deleteBlocker(Contingent $contingent): ?string
    {
        if ($contingent->athletes()->exists()) {
            return 'Kontingen tidak bisa dihapus karena masih memiliki atlet.';
        }

        return null;
    }
}
