<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Calling;
use App\Models\EquipmentLoan;
use App\Models\Matchup;
use App\Models\Verification;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class HistoryController extends Controller
{
    public function index(Request $request)
    {
        $types = [
            'match' => 'Pertandingan',
            'calling' => 'Calling',
            'verification' => 'Verifikasi',
            'equipment' => 'Pengembalian Perlengkapan',
        ];

        $request->validate(
            [
                'from' => ['nullable', 'date'],
                'to' => ['nullable', 'date'],
            ],
            [
                'from.date' => 'Tanggal mulai tidak valid.',
                'to.date' => 'Tanggal akhir tidak valid.',
            ]
        );

        $type = array_key_exists((string) $request->query('type'), $types)
            ? (string) $request->query('type')
            : '';
        $from = (string) $request->query('from');
        $to = (string) $request->query('to');

        $items = collect();

        if ($type === '' || $type === 'match') {
            $items = $items->merge($this->matchHistory());
        }

        if ($type === '' || $type === 'calling') {
            $items = $items->merge($this->callingHistory());
        }

        if ($type === '' || $type === 'verification') {
            $items = $items->merge($this->verificationHistory());
        }

        if ($type === '' || $type === 'equipment') {
            $items = $items->merge($this->equipmentHistory());
        }

        $items = $items
            ->filter(function (array $item) use ($from, $to) {
                $date = $item['time']->toDateString();

                if ($from !== '' && $date < $from) {
                    return false;
                }

                if ($to !== '' && $date > $to) {
                    return false;
                }

                return true;
            })
            ->sortByDesc('time')
            ->values();

        $page = max(1, (int) $request->query('page', 1));
        $perPage = 20;

        $history = new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('admin.history.index', [
            'history' => $history,
            'types' => $types,
            'type' => $type,
            'from' => $from,
            'to' => $to,
        ]);
    }

    protected function matchHistory()
    {
        return Matchup::query()
            ->with(['schedule.category', 'athleteA.contingent', 'athleteB.contingent', 'winner.contingent'])
            ->where('status', 'finished')
            ->whereNotNull('finished_at')
            ->get()
            ->map(fn (Matchup $match) => [
                'type' => 'match',
                'type_label' => 'Pertandingan',
                'icon' => 'bi-broadcast-pin',
                'badge' => 'bg-success',
                'title' => 'Partai ' . ($match->schedule?->match_no ?? '-') . ' - ' . ($match->schedule?->category?->name ?? '-'),
                'detail' => sprintf(
                    '%s [%s] vs %s [%s] | Skor %s | Pemenang: %s',
                    $match->athleteA?->name ?? '-',
                    $match->athleteA?->contingent?->code ?? '-',
                    $match->athleteB?->name ?? '-',
                    $match->athleteB?->contingent?->code ?? '-',
                    $match->scoreLabel(),
                    $match->winner?->name ?? '-'
                ),
                'time' => $match->finished_at,
            ]);
    }

    protected function callingHistory()
    {
        $callings = Calling::query()
            ->with(['athlete.contingent', 'schedule.category'])
            ->whereNotNull('called_at')
            ->get();

        $items = collect();

        foreach ($callings as $calling) {
            $base = [
                'type' => 'calling',
                'type_label' => 'Calling',
                'icon' => 'bi-bell-fill',
                'badge' => 'bg-warning text-dark',
                'title' => ($calling->athlete?->name ?? '-') . ' - ' . ($calling->schedule?->category?->name ?? '-'),
            ];

            $items->push($base + [
                'detail' => sprintf(
                    'Partai %s | Level %s | Status: %s',
                    $calling->schedule?->match_no ?? '-',
                    $calling->levelLabel(),
                    $calling->statusLabel()
                ),
                'time' => $calling->called_at,
            ]);

            if ($calling->ready_at) {
                $items->push($base + [
                    'detail' => sprintf(
                        'Partai %s | Atlet dinyatakan siap di arena',
                        $calling->schedule?->match_no ?? '-'
                    ),
                    'time' => $calling->ready_at,
                ]);
            }
        }

        return $items;
    }

    protected function verificationHistory()
    {
        return Verification::query()
            ->with(['athlete.contingent', 'schedule.category', 'verifiedBy'])
            ->whereNotNull('verified_at')
            ->get()
            ->map(fn (Verification $verification) => [
                'type' => 'verification',
                'type_label' => 'Verifikasi',
                'icon' => 'bi-qr-code-scan',
                'badge' => $verification->status === 'present' ? 'bg-primary' : 'bg-danger',
                'title' => ($verification->athlete?->name ?? '-') . ' - ' . ($verification->schedule?->category?->name ?? '-'),
                'detail' => sprintf(
                    'Partai %s | Metode: %s | Status: %s | Petugas: %s',
                    $verification->schedule?->match_no ?? '-',
                    strtoupper($verification->method),
                    $verification->status === 'present' ? 'Hadir' : 'Tidak hadir',
                    $verification->verifiedBy?->name ?? '-'
                ),
                'time' => $verification->verified_at,
            ]);
    }

    protected function equipmentHistory()
    {
        return EquipmentLoan::query()
            ->with(['equipment', 'athlete.contingent', 'returnedBy'])
            ->where('status', 'returned')
            ->whereNotNull('returned_at')
            ->get()
            ->map(fn (EquipmentLoan $loan) => [
                'type' => 'equipment',
                'type_label' => 'Pengembalian Perlengkapan',
                'icon' => 'bi-box-seam-fill',
                'badge' => 'bg-secondary',
                'title' => ($loan->equipment?->code ?? '-') . ' - ' . ($loan->athlete?->name ?? '-'),
                'detail' => sprintf(
                    '%s %s %s | Kondisi kembali: %s | Dikembalikan oleh: %s',
                    $loan->equipment?->typeLabel() ?? '-',
                    $loan->equipment?->color ?? '-',
                    $loan->equipment?->size ?? '-',
                    $loan->return_condition ?: '-',
                    $loan->returnedBy?->name ?? '-'
                ),
                'time' => $loan->returned_at,
            ]);
    }
}
