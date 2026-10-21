<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ScheduleTemplateExport;
use App\Exports\SchedulesExport;
use App\Http\Controllers\Admin\Concerns\BulkDeletes;
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
    use BulkDeletes;

    public const STATUSES = ['pending', 'preparation', 'running', 'finished', 'cancelled'];

    public function index(Request $request)
    {
        $filters = $this->filters($request);

        return view('admin.schedule.index', [
            'schedules' => $this->query($filters)->paginate(10)->withQueryString(),
            'arenas' => Arena::orderBy('name')->get(['id', 'name']),
            'categories' => Category::orderBy('name')->get(),
            'arenaByCategory' => $this->arenaByCategory(),
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

        $conflict = app(\App\Services\ScheduleGuardService::class)->guardSchedule(new Schedule($data));

        if ($conflict) {
            return back()->with('error', $conflict)->withInput();
        }

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

        $draft = clone $schedule;
        $draft->fill($data);

        $conflict = app(\App\Services\ScheduleGuardService::class)->guardSchedule($draft);

        if ($conflict) {
            return back()->with('error', $conflict)->withInput();
        }

        $schedule->update($data);

        return redirect()
            ->route('admin.schedules.index')
            ->with('success', 'Jadwal berhasil diperbarui.');
    }

    public function destroy(Schedule $schedule): RedirectResponse
    {
        if ($reason = $this->deleteBlocker($schedule)) {
            return back()->with('error', $reason);
        }

        $category = $schedule->category;
        $schedule->delete();

        // The bracket is built from the jadwal, so it disappears with the
        // last jadwal of the class.
        if ($category && $schedule->type === 'daeryun') {
            app(\App\Services\BracketService::class)->forgetIfEmpty($category);
        }

        return redirect()
            ->route('admin.schedules.index')
            ->with('success', 'Jadwal berhasil dihapus.');
    }

    /**
     * Delete every selected row that passes the guard of the single delete.
     */
    public function bulkDestroy(Request $request): RedirectResponse
    {
        $ids = $this->bulkIds($request, 'schedules');

        $deleted = 0;
        $skipped = [];
        $categories = collect();

        foreach (Schedule::whereIn('id', $ids)->with('category')->get() as $schedule) {
            if ($reason = $this->deleteBlocker($schedule)) {
                $skipped[] = $reason;

                continue;
            }

            if ($schedule->type === 'daeryun' && $schedule->category) {
                $categories->push($schedule->category);
            }

            $schedule->delete();
            $deleted++;
        }

        // The bracket is built from the jadwal, so it disappears with the
        // last jadwal of the class.
        foreach ($categories->unique('id') as $category) {
            app(\App\Services\BracketService::class)->forgetIfEmpty($category);
        }

        return $this->bulkResult('admin.schedules.index', $deleted, $skipped);
    }

    /**
     * Why the schedule cannot be deleted, or null when it can be deleted.
     */
    protected function deleteBlocker(Schedule $schedule): ?string
    {
        if ($schedule->status === 'running' || ($schedule->match && $schedule->match->status === 'running')) {
            return 'Jadwal tidak bisa dihapus saat pertandingan sedang berlangsung.';
        }

        if ($schedule->performances()->where('status', 'performing')->exists()) {
            return 'Jadwal tidak bisa dihapus saat masih ada penampil yang sedang tampil.';
        }

        return null;
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
            'type' => ['required', 'in:daeryun,art'],
            'round' => [
                'nullable',
                function (string $attribute, mixed $value, \Closure $fail) use ($request): void {
                    $rounds = Schedule::roundsFor($request->input('type'));

                    if (! array_key_exists((string) $value, $rounds)) {
                        $fail('Ronde tidak sesuai dengan jenis pertandingan.');
                    }
                },
            ],
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

        $data['round'] = $data['round'] ?? ($data['type'] === 'art' ? 'penampilan' : 'penyisihan');
        $data['order_no'] = (int) ($data['order_no'] ?? 1);
        $data['arena_id'] = $this->resolveArenaId((int) $data['category_id'], $data['type']);
        $data['start_time'] = $this->normalizeTime($data['start_time'] ?? null);
        $data['end_time'] = $this->normalizeTime($data['end_time'] ?? null);

        return $data;
    }

    /**
     * The arena that handles the class of the schedule.
     *
     * The arena follows the class, so the schedule form only asks for the
     * class. When no arena handles the class yet, the schedule is rejected
     * with a hint to assign the class to an arena first.
     */
    protected function resolveArenaId(int $categoryId, string $type): ?int
    {
        $arena = Arena::forCategory($categoryId, $type);

        if ($arena) {
            return $arena->id;
        }

        $category = Category::find($categoryId);

        throw \Illuminate\Validation\ValidationException::withMessages([
            'category_id' => sprintf(
                'Kelas %s belum memiliki arena. Tetapkan kelas ini ke arena pada menu Arena terlebih dahulu.',
                $category?->name ?? 'terpilih'
            ),
        ]);
    }

    /**
     * The arena of every class, so the form can show which arena will be used.
     *
     * @return array<string, string>
     */
    protected function arenaByCategory(): array
    {
        $map = [];

        foreach (Arena::query()->whereNotNull('category_id')->orderBy('id')->get() as $arena) {
            $map[(string) $arena->category_id] ??= $arena->name;
        }

        return $map;
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
        $query = Schedule::query()
            ->with([
                'category',
                'arena',
                'match.athleteA',
                'match.athleteB',
                'performances.athlete',
            ])
            ->orderBy('match_date')
            ->orderByRaw(Schedule::roundSortSql())
            ->orderBy('order_no')
            ->orderBy('start_time')
            ->orderBy('match_no');

        if ($filters['q'] !== '') {
            $term = '%'.$filters['q'].'%';
            $query->where(function ($builder) use ($term) {
                $builder->where('match_no', 'like', $term)
                    ->orWhereHas('match.athleteA', fn ($q) => $q->where('name', 'like', $term))
                    ->orWhereHas('match.athleteB', fn ($q) => $q->where('name', 'like', $term))
                    ->orWhereHas('performances.athlete', fn ($q) => $q->where('name', 'like', $term));
            });
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
