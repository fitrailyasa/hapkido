<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\BulkDeletes;
use App\Http\Controllers\Controller;
use App\Models\Equipment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EquipmentController extends Controller
{
    use BulkDeletes;
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q'));
        $typeFilter = (string) $request->query('type');
        $statusFilter = (string) $request->query('status');

        $query = Equipment::query()->orderBy('type')->orderBy('code');

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('code', 'like', "%{$search}%")
                    ->orWhere('color', 'like', "%{$search}%")
                    ->orWhere('size', 'like', "%{$search}%");
            });
        }

        if ($typeFilter !== '') {
            $query->where('type', $typeFilter);
        }

        if ($statusFilter !== '') {
            $query->where('status', $statusFilter);
        }

        $totalQty = (int) Equipment::sum('total_qty');
        $availableQty = (int) Equipment::sum('available_qty');

        return view('admin.equipment.index', [
            'equipments' => $query->paginate(10)->withQueryString(),
            'types' => $this->types(),
            'statuses' => $this->statuses(),
            'search' => $search,
            'typeFilter' => $typeFilter,
            'statusFilter' => $statusFilter,
            'totalQty' => $totalQty,
            'availableQty' => $availableQty,
            'loanedQty' => max(0, $totalQty - $availableQty),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        $total = (int) $data['total_qty'];

        Equipment::create([
            'type' => $data['type'],
            'color' => $data['color'],
            'size' => $data['size'],
            'code' => $data['code'],
            'status' => $data['status'],
            'total_qty' => $total,
            'available_qty' => $total,
        ]);

        return redirect()
            ->route('admin.equipments.index')
            ->with('success', sprintf('Perlengkapan %s berhasil ditambahkan.', $data['code']));
    }

    public function update(Request $request, Equipment $equipment)
    {
        $data = $this->validateData($request, $equipment);

        $loaned = $equipment->loanedQty();
        $total = (int) $data['total_qty'];

        if ($total < $loaned) {
            return back()->with(
                'error',
                sprintf('Total stok tidak boleh kurang dari jumlah yang sedang dipinjam (%d).', $loaned)
            );
        }

        $equipment->update([
            'type' => $data['type'],
            'color' => $data['color'],
            'size' => $data['size'],
            'code' => $data['code'],
            'status' => $data['status'],
            'total_qty' => $total,
            'available_qty' => $total - $loaned,
        ]);

        return redirect()
            ->route('admin.equipments.index')
            ->with('success', sprintf('Perlengkapan %s berhasil diperbarui.', $equipment->code));
    }

    public function destroy(Equipment $equipment)
    {
        if ($reason = $this->deleteBlocker($equipment)) {
            return back()->with('error', $reason);
        }

        $equipment->delete();

        return redirect()
            ->route('admin.equipments.index')
            ->with('success', sprintf('Perlengkapan %s berhasil dihapus.', $equipment->code));
    }

    /**
     * Delete every selected row that passes the guard of the single delete.
     */
    public function bulkDestroy(Request $request): RedirectResponse
    {
        $ids = $this->bulkIds($request, 'equipments');

        $deleted = 0;
        $skipped = [];

        foreach (Equipment::whereIn('id', $ids)->get() as $equipment) {
            if ($reason = $this->deleteBlocker($equipment)) {
                $skipped[] = $reason;

                continue;
            }

            $equipment->delete();
            $deleted++;
        }

        return $this->bulkResult('admin.equipments.index', $deleted, $skipped);
    }

    /**
     * Why the equipment cannot be deleted, or null when it can be deleted.
     */
    protected function deleteBlocker(Equipment $equipment): ?string
    {
        $activeLoans = $equipment->loans()->where('status', 'loaned')->count();

        if ($activeLoans > 0) {
            return sprintf(
                'Tidak bisa menghapus %s, masih ada %d peminjaman aktif.',
                $equipment->code,
                $activeLoans
            );
        }

        return null;
    }

    protected function validateData(Request $request, ?Equipment $equipment = null): array
    {
        return $request->validate([
            'type' => ['required', Rule::in(['head_guard', 'body_protector'])],
            'color' => ['required', 'string', 'max:255'],
            'size' => ['required', 'string', 'max:50'],
            'code' => [
                'required', 'string', 'max:50',
                Rule::unique('equipments', 'code')->ignore($equipment?->id),
            ],
            'status' => ['required', Rule::in(['available', 'loaned', 'maintenance'])],
            'total_qty' => ['required', 'integer', 'min:1', 'max:1000'],
        ], [
            'type.in' => 'Jenis perlengkapan tidak valid.',
            'code.unique' => 'Kode perlengkapan sudah digunakan.',
            'total_qty.min' => 'Total stok minimal 1.',
        ], [
            'type' => 'jenis',
            'color' => 'warna',
            'size' => 'ukuran',
            'code' => 'kode',
            'status' => 'status',
            'total_qty' => 'total stok',
        ]);
    }

    protected function types(): array
    {
        return ['head_guard' => 'Head Guard', 'body_protector' => 'Body Protector'];
    }

    protected function statuses(): array
    {
        return ['available' => 'Tersedia', 'loaned' => 'Dipinjam', 'maintenance' => 'Perawatan'];
    }
}
