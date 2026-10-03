<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Athlete;
use App\Models\Equipment;
use App\Models\EquipmentLoan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EquipmentLoanController extends Controller
{
    public function index(Request $request)
    {
        $statusFilter = (string) $request->query('status');
        $search = trim((string) $request->query('q'));

        $query = EquipmentLoan::with([
            'equipment',
            'athlete.contingent',
            'schedule',
            'loanedBy',
            'returnedBy',
        ])->orderByDesc('loaned_at')->orderByDesc('id');

        if (in_array($statusFilter, ['loaned', 'returned'], true)) {
            $query->where('status', $statusFilter);
        }

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->whereHas('athlete', fn ($builder) => $builder
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('participant_number', 'like', "%{$search}%"))
                    ->orWhereHas('equipment', fn ($builder) => $builder
                        ->where('code', 'like', "%{$search}%"));
            });
        }

        $totalQty = (int) Equipment::sum('total_qty');
        $availableQty = (int) Equipment::sum('available_qty');

        return view('admin.equipment-loan.index', [
            'loans' => $query->paginate(10)->withQueryString(),
            'athletes' => Athlete::with('contingent')
                ->where('status', 'active')
                ->orderBy('name')
                ->get(),
            'equipments' => Equipment::orderBy('type')->orderBy('code')->get(),
            'statuses' => ['loaned' => 'Sedang Dipinjam', 'returned' => 'Sudah Dikembalikan'],
            'statusFilter' => $statusFilter,
            'search' => $search,
            'totalQty' => $totalQty,
            'availableQty' => $availableQty,
            'loanedQty' => max(0, $totalQty - $availableQty),
            'activeLoans' => EquipmentLoan::where('status', 'loaned')->count(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'athlete_id' => ['required', 'integer', Rule::exists('athletes', 'id')],
            'equipment_id' => ['required', 'integer', Rule::exists('equipments', 'id')],
            'qty' => ['required', 'integer', 'min:1', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'athlete_id.exists' => 'Atlet tidak ditemukan.',
            'equipment_id.exists' => 'Perlengkapan tidak ditemukan.',
            'qty.min' => 'Jumlah pinjam minimal 1.',
        ], [
            'athlete_id' => 'atlet',
            'equipment_id' => 'perlengkapan',
            'qty' => 'jumlah',
            'notes' => 'catatan',
        ]);

        $qty = (int) $data['qty'];

        $result = DB::transaction(function () use ($data, $qty) {
            $equipment = Equipment::lockForUpdate()->find($data['equipment_id']);

            if (! $equipment) {
                return ['error' => 'Perlengkapan tidak ditemukan.'];
            }

            if ($equipment->status === 'maintenance') {
                return ['error' => sprintf('Perlengkapan %s sedang dalam perawatan.', $equipment->code)];
            }

            if ($equipment->available_qty < $qty) {
                return ['error' => sprintf(
                    'Stok %s tidak mencukupi: tersedia %d dari total %d.',
                    $equipment->code,
                    $equipment->available_qty,
                    $equipment->total_qty
                )];
            }

            $equipment->available_qty = $equipment->available_qty - $qty;

            if ($equipment->available_qty <= 0) {
                $equipment->status = 'loaned';
            }

            $equipment->save();

            $loan = EquipmentLoan::create([
                'equipment_id' => $equipment->id,
                'athlete_id' => $data['athlete_id'],
                'qty' => $qty,
                'loaned_at' => now(),
                'notes' => $data['notes'] ?? null,
                'status' => 'loaned',
                'loaned_by' => auth()->id(),
            ]);

            return ['loan' => $loan->load('equipment', 'athlete.contingent')];
        });

        if (isset($result['error'])) {
            return back()->withInput()->with('error', $result['error']);
        }

        $loan = $result['loan'];

        return redirect()
            ->route('admin.equipment-loans.index')
            ->with('success', sprintf(
                '%s (%s) dipinjam oleh %s sebanyak %d.',
                $loan->equipment?->code,
                $loan->equipment?->typeLabel(),
                $loan->athlete?->name,
                $qty
            ));
    }

    public function return(Request $request, EquipmentLoan $equipmentLoan)
    {
        if ($equipmentLoan->isReturned()) {
            return back()->with('error', 'Peminjaman ini sudah dikembalikan sebelumnya.');
        }

        $data = $request->validate([
            'return_condition' => ['nullable', 'string', 'max:255'],
        ], [], ['return_condition' => 'kondisi barang']);

        $qty = max(1, (int) $equipmentLoan->qty);

        DB::transaction(function () use ($equipmentLoan, $data, $qty) {
            $equipment = Equipment::lockForUpdate()->find($equipmentLoan->equipment_id);

            if ($equipment) {
                $equipment->available_qty = min(
                    (int) $equipment->total_qty,
                    (int) $equipment->available_qty + $qty
                );

                if ($equipment->status === 'loaned' && $equipment->available_qty > 0) {
                    $equipment->status = 'available';
                }

                $equipment->save();
            }

            $equipmentLoan->update([
                'status' => 'returned',
                'returned_at' => now(),
                'returned_by' => auth()->id(),
                'return_condition' => $data['return_condition'] ?? null,
            ]);
        });

        return back()->with('success', sprintf(
            'Perlengkapan %s dikembalikan oleh %s.',
            $equipmentLoan->equipment?->code ?? '—',
            $equipmentLoan->athlete?->name ?? 'atlet'
        ));
    }
}
