<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ScheduleTemplateExport;
use App\Exports\SchedulesExport;
use App\Http\Controllers\Controller;
use App\Imports\SchedulesImport;
use App\Models\Arena;
use App\Models\Category;
use App\Models\Schedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;
use Throwable;

class ScheduleController extends Controller
{
    public const STATUSES = ['pending', 'preparation', 'running', 'finished', 'cancelled'];

    public function index(Request $request)
    {
        $filters = $this->filters($request);

        return view('admin.schedule.index', [
            'schedules' => $this->query($filters)->paginate(10)->withQueryString(),
            'arenas' => Arena::orderBy('name')->get(['id', 'name']),
            'categories' => Category::orderBy('name')->get(),
            'statuses' => self::STATUSES,
            'q' => $filters['q'],
            'dateFilter' => $filters['date'],
            'arenaFilter' => $filters['arena'],
            'categoryFilter' => $filters['category'],
            'statusFilter' => $filters['status'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        Schedule::create($data);

        return redirect()
            ->route('admin.schedules.index')
            ->with('success', 'Jadwal berhasil ditambahkan.');
    }

    public function show(Schedule $schedule)
    {
        $schedule->load([
            'category',
            'arena',
            'match.athleteA.contingent',
            'match.athleteB.contingent',
            'performances.athlete.contingent',
        ]);

        $athletes = $schedule->athletes()->filter()->map(fn ($athlete) => [
            'name' => $athlete->name,
            'contingent' => $athlete->contingent?->code ?? '—',
        ])->values();

        return response()->json([
            'id' => $schedule->id,
            'match_no' => $schedule->match_no,
            'category' => $schedule->category?->name ?? '—',
            'arena' => $schedule->arena?->name ?? 'Belum ditentukan',
            'type' => $schedule->type,
            'type_label' => $schedule->type === 'art' ? 'Seni' : 'Daeryun',
            'round' => $schedule->round,
            'order_no' => $schedule->order_no,
            'match_date' => optional($schedule->match_date)->format('d M Y'),
            'start_time' => $this->formatTime($schedule->start_time),
            'end_time' => $this->formatTime($schedule->end_time),
            'status' => $schedule->status,
            'status_label' => $schedule->statusLabel(),
            'athletes' => $athletes,
            'created_at' => optional($schedule->created_at)->format('d M Y, H:i'),
            'updated_at' => optional($schedule->updated_at)->format('d M Y, H:i'),
        ]);
    }

    public function update(Request $request, Schedule $schedule): RedirectResponse
    {
        $data = $this->validated($request, $schedule);

        $schedule->update($data);

        return redirect()
            ->route('admin.schedules.index')
            ->with('success', 'Jadwal berhasil diperbarui.');
    }

    public function destroy(Schedule $schedule): RedirectResponse
    {
        if ($schedule->match && $schedule->match->status === 'running') {
            return back()->with('error', 'Jadwal tidak bisa dihapus saat pertandingan sedang berlangsung.');
        }

        $schedule->delete();

        return redirect()
            ->route('admin.schedules.index')
            ->with('success', 'Jadwal berhasil dihapus.');
    }

    public function export(Request $request)
    {
        $schedules = $this->query($this->filters($request))->get();

        return Excel::download(
            new SchedulesExport($schedules),
            'jadwal-pertandingan-' . now()->format('Ymd-His') . '.xlsx'
        );
    }

    public function template()
    {
        return Excel::download(new ScheduleTemplateExport, 'template-jadwal.xlsx');
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:5120'],
        ], [
            'file.mimes' => 'Format file harus xlsx atau xls.',
            'file.max' => 'Ukuran file maksimal 5 MB.',
        ]);

        try {
            $import = new SchedulesImport;
            Excel::import($import, $request->file('file'));
        } catch (ValidationException $e) {
            $messages = collect($e->failures())
                ->map(fn ($failure) => 'Baris ' . $failure->row() . ': ' . implode(', ', $failure->errors()))
                ->implode(' | ');

            return back()->with('error', 'Import gagal. ' . $messages);
        } catch (Throwable $e) {
            $message = str_contains($e->getMessage(), 'UNIQUE constraint')
                ? 'Terdapat kode laga duplikat, baik di dalam file maupun di database.'
                : 'File tidak dapat diproses. Pastikan format kolom sesuai template.';

            return back()->with('error', 'Import gagal. ' . $message);
        }

        if ($import->processedRows() === 0) {
            return back()->with(
                'error',
                'Import gagal. Tidak ada baris jadwal yang bisa diimpor. Pastikan file sesuai template dan kolom jadwal sudah diisi (baris CONTOH diabaikan otomatis).'
            );
        }

        return redirect()
            ->route('admin.schedules.index')
            ->with('success', 'Jadwal berhasil diimpor dari file Excel.');
    }

    protected function validated(Request $request, ?Schedule $schedule = null): array
    {
        $uniqueMatchNo = Rule::unique('schedules', 'match_no');
        if ($schedule) {
            $uniqueMatchNo->ignore($schedule->id);
        }

        $data = $request->validate([
            'match_no' => ['required', 'string', 'max:255', $uniqueMatchNo],
            'category_id' => ['required', Rule::exists('categories', 'id')],
            'arena_id' => ['nullable', Rule::exists('arenas', 'id')],
            'type' => ['required', 'in:daeryun,art'],
            'round' => ['nullable', 'string', 'max:255'],
            'order_no' => ['nullable', 'integer', 'min:1'],
            'match_date' => ['required', 'date'],
            'start_time' => ['nullable', 'regex:/^\d{1,2}:\d{2}(:\d{2})?$/'],
            'end_time' => ['nullable', 'regex:/^\d{1,2}:\d{2}(:\d{2})?$/'],
            'status' => ['required', Rule::in(self::STATUSES)],
        ], [
            'match_no.unique' => 'Kode laga sudah digunakan.',
            'start_time.regex' => 'Format jam mulai tidak valid (HH:MM).',
            'end_time.regex' => 'Format jam selesai tidak valid (HH:MM).',
        ]);

        $data['round'] = $data['round'] ?: 'penyisihan';
        $data['order_no'] = (int) ($data['order_no'] ?: 1);
        $data['arena_id'] = $data['arena_id'] ?: null;
        $data['start_time'] = $this->normalizeTime($data['start_time']);
        $data['end_time'] = $this->normalizeTime($data['end_time']);

        return $data;
    }

    /**
     * @return array{q: string, date: string, arena: string, category: string, status: string}
     */
    protected function filters(Request $request): array
    {
        return [
            'q' => trim((string) $request->query('q')),
            'date' => (string) $request->query('date'),
            'arena' => (string) $request->query('arena'),
            'category' => (string) $request->query('category'),
            'status' => (string) $request->query('status'),
        ];
    }

    protected function query(array $filters)
    {
        $query = Schedule::query()->with(['category', 'arena'])->orderBy('match_date')->orderBy('order_no');

        if ($filters['q'] !== '') {
            $query->where('match_no', 'like', "%{$filters['q']}%");
        }

        if ($filters['date'] !== '') {
            $query->whereDate('match_date', $filters['date']);
        }

        if ($filters['arena'] !== '') {
            $query->where('arena_id', $filters['arena']);
        }

        if ($filters['category'] !== '') {
            $query->where('category_id', $filters['category']);
        }

        if ($filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        return $query;
    }

    protected function normalizeTime(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = trim($value);

        if (preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $value, $matches)) {
            return sprintf('%02d:%s:%s', (int) $matches[1], $matches[2], $matches[3] ?? '00');
        }

        return $value;
    }

    protected function formatTime(?string $value): ?string
    {
        return $value !== null && $value !== '' ? substr($value, 0, 5) : null;
    }
}
