<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SchedulesExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    /**
     * Kolom Excel (Indonesia-friendly) yang dipetakan ke kolom tabel schedules.
     */
    public const HEADINGS = [
        'match_no', 'kategori', 'arena', 'jenis', 'ronde',
        'urutan', 'tanggal', 'mulai', 'selesai', 'status',
    ];

    /**
     * @var Collection<int, \App\Models\Schedule>
     */
    protected Collection $schedules;

    public function __construct(Collection $schedules)
    {
        $this->schedules = $schedules;
    }

    public function collection(): Collection
    {
        return $this->schedules;
    }

    public function headings(): array
    {
        return self::HEADINGS;
    }

    public function map($schedule): array
    {
        return [
            $schedule->match_no,
            $schedule->category?->name ?? '',
            $schedule->arena?->name ?? '',
            $schedule->type,
            $schedule->round,
            $schedule->order_no,
            optional($schedule->match_date)->format('Y-m-d'),
            $this->time($schedule->start_time),
            $this->time($schedule->end_time),
            $schedule->status,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    protected function time(?string $value): string
    {
        return $value !== null && $value !== '' ? substr($value, 0, 5) : '';
    }
}
