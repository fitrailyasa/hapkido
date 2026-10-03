<?php

namespace App\Imports;

use App\Exports\SchedulesExport;
use App\Exports\ScheduleTemplateExport;
use App\Http\Controllers\Admin\ScheduleController;
use App\Models\Arena;
use App\Models\Category;
use App\Models\Schedule;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class SchedulesImport implements ToModel, WithHeadingRow, WithValidation, SkipsEmptyRows
{
    protected ?Collection $categories = null;

    protected ?Collection $arenas = null;

    protected int $processedRows = 0;

    public function rules(): array
    {
        return [
            'match_no' => ['required', 'string', 'max:255', 'unique:schedules,match_no'],
            'kategori' => ['required', 'string', function ($attribute, $value, $fail) {
                if (! $this->resolveCategory($value)) {
                    $fail('Kategori "' . $value . '" tidak ditemukan. Samakan dengan nama kategori.');
                }
            }],
            'arena' => ['nullable', 'string', function ($attribute, $value, $fail) {
                if ($value === null || trim((string) $value) === '') {
                    return;
                }

                if (! $this->resolveArena($value)) {
                    $fail('Arena "' . $value . '" tidak ditemukan. Samakan dengan nama arena.');
                }
            }],
            'jenis' => ['required', Rule::in(['daeryun', 'art'])],
            'ronde' => ['nullable', 'string', 'max:255'],
            'urutan' => ['nullable', 'integer', 'min:1'],
            'tanggal' => ['required', 'date'],
            'mulai' => ['nullable', function ($attribute, $value, $fail) {
                if ($value !== null && $value !== '' && ! $this->isTime($value)) {
                    $fail('Jam mulai tidak valid. Gunakan format HH:MM.');
                }
            }],
            'selesai' => ['nullable', function ($attribute, $value, $fail) {
                if ($value !== null && $value !== '' && ! $this->isTime($value)) {
                    $fail('Jam selesai tidak valid. Gunakan format HH:MM.');
                }
            }],
            'status' => ['nullable', Rule::in(ScheduleController::STATUSES)],
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            '*.match_no.required' => 'Kode laga wajib diisi.',
            '*.match_no.unique' => 'Kode laga sudah ada di database.',
            '*.kategori.required' => 'Kategori wajib diisi.',
            '*.jenis.required' => 'Jenis wajib diisi.',
            '*.jenis.in' => 'Jenis harus "daeryun" atau "art".',
            '*.tanggal.required' => 'Tanggal wajib diisi.',
            '*.tanggal.date' => 'Tanggal tidak valid.',
            '*.urutan.integer' => 'Urutan harus berupa angka.',
            '*.urutan.min' => 'Urutan minimal 1.',
            '*.status.in' => 'Status tidak dikenali (pending/preparation/running/finished/cancelled).',
        ];
    }

    public function customValidationAttributes(): array
    {
        return [
            'match_no' => 'Kode laga',
            'kategori' => 'Kategori',
            'arena' => 'Arena',
            'jenis' => 'Jenis',
            'ronde' => 'Ronde',
            'urutan' => 'Urutan',
            'tanggal' => 'Tanggal',
            'mulai' => 'Jam mulai',
            'selesai' => 'Jam selesai',
            'status' => 'Status',
        ];
    }

    public function isEmptyWhen(array $row): bool
    {
        $values = $this->scheduleValues($row);

        if ($values === []) {
            return true;
        }

        if ($this->isExample($values)) {
            return true;
        }

        foreach ($values as $value) {
            if ($value !== null && $value !== '') {
                return false;
            }
        }

        return true;
    }

    public function processedRows(): int
    {
        return $this->processedRows;
    }

    public function prepareForValidation(array $row, int $rowNumber): array
    {
        $this->processedRows++;

        unset($row['keterangan']);

        foreach ($row as $key => $value) {
            if (is_string($value)) {
                $row[$key] = trim($value);
            }
        }

        $row['jenis'] = strtolower(trim((string) ($row['jenis'] ?? '')));
        $row['status'] = strtolower(trim((string) ($row['status'] ?? '')));
        $row['tanggal'] = $this->normalizeDate($row['tanggal'] ?? null);
        $row['mulai'] = $this->normalizeTime($row['mulai'] ?? null);
        $row['selesai'] = $this->normalizeTime($row['selesai'] ?? null);

        return $row;
    }

    public function model(array $row): ?Schedule
    {
        $values = $this->scheduleValues($row);
        if ($values === [] || $this->isExample($values)) {
            return null;
        }

        $category = $this->resolveCategory($row['kategori'] ?? null);
        if (! $category) {
            return null;
        }

        $arena = $this->resolveArena($row['arena'] ?? null);

        return new Schedule([
            'match_no' => (string) ($row['match_no'] ?? ''),
            'category_id' => $category->id,
            'arena_id' => $arena?->id,
            'type' => ($row['jenis'] ?? '') !== '' ? $row['jenis'] : 'daeryun',
            'round' => ($row['ronde'] ?? '') !== '' ? $row['ronde'] : 'penyisihan',
            'order_no' => (int) (($row['urutan'] ?? '') !== '' ? $row['urutan'] : 1),
            'match_date' => $this->normalizeDate($row['tanggal'] ?? null),
            'start_time' => $this->normalizeTime($row['mulai'] ?? null),
            'end_time' => $this->normalizeTime($row['selesai'] ?? null),
            'status' => ($row['status'] ?? '') !== '' ? $row['status'] : 'pending',
        ]);
    }

    protected function scheduleValues(array $row): array
    {
        $values = [];

        foreach (SchedulesExport::HEADINGS as $column) {
            if (! array_key_exists($column, $row)) {
                continue;
            }

            $value = $row[$column];

            $values[$column] = is_string($value) ? trim($value) : $value;
        }

        return $values;
    }

    protected function isExample(array $values): bool
    {
        $first = $values['match_no'] ?? reset($values);

        return is_scalar($first)
            && stripos(trim((string) $first), ScheduleTemplateExport::EXAMPLE_PREFIX) === 0;
    }

    protected function resolveCategory(mixed $value): ?Category
    {
        if ($this->categories === null) {
            $this->categories = Category::all();
        }

        $key = is_scalar($value) ? trim((string) $value) : '';
        if ($key === '') {
            return null;
        }

        return $this->categories->first(function (Category $category) use ($key) {
            return strcasecmp($category->name, $key) === 0
                || $category->slug === Str::slug($key)
                || (string) $category->id === $key;
        });
    }

    protected function resolveArena(mixed $value): ?Arena
    {
        if ($this->arenas === null) {
            $this->arenas = Arena::all();
        }

        $key = is_scalar($value) ? trim((string) $value) : '';
        if ($key === '') {
            return null;
        }

        return $this->arenas->first(function (Arena $arena) use ($key) {
            return strcasecmp($arena->name, $key) === 0
                || strcasecmp($arena->label, $key) === 0
                || (string) $arena->id === $key;
        });
    }

    protected function normalizeDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_numeric($value)) {
            return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
        }

        $value = trim((string) $value);

        if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $value, $matches)) {
            return sprintf('%04d-%02d-%02d', $matches[3], $matches[2], $matches[1]);
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable $e) {
            return $value;
        }
    }

    protected function normalizeTime(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format('H:i:s');
        }

        if (is_numeric($value)) {
            $seconds = (float) $value < 1 ? (int) round((float) $value * 86400) : (int) round((float) $value);

            return gmdate('H:i:s', $seconds % 86400);
        }

        $value = trim((string) $value);

        if (preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $value, $matches)) {
            return sprintf('%02d:%s:%s', (int) $matches[1], $matches[2], $matches[3] ?? '00');
        }

        if (preg_match('/(\d{1,2}):(\d{2})(?::(\d{2}))?/', $value, $matches)) {
            return sprintf('%02d:%s:%s', (int) $matches[1], $matches[2], $matches[3] ?? '00');
        }

        return $value;
    }

    protected function isTime(mixed $value): bool
    {
        if ($value instanceof DateTimeInterface || is_numeric($value)) {
            return true;
        }

        return (bool) preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', trim((string) $value));
    }
}
