<?php

namespace App\Exports;

use App\Models\Schedule;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SchedulesExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    /**
     * Excel columns (Indonesia-friendly) mapped to the schedules table columns.
     */
    public const HEADINGS = [
        'match_no', 'kategori', 'arena', 'jenis', 'ronde',
        'urutan', 'tanggal', 'mulai', 'selesai', 'status',
    ];

    /**
     * Read-only column: the partai (two athletes) the jadwal already has.
     *
     * The partai comes from the bracket, so it is exported for the operator
     * but ignored on import.
     */
    public const PARTAI_HEADING = 'partai';

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
        return array_merge(self::HEADINGS, [self::PARTAI_HEADING]);
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
            $this->partai($schedule),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    /**
     * "Atlet A vs Atlet B" when the jadwal already has a partai.
     */
    protected function partai(Schedule $schedule): string
    {
        $match = $schedule->match;

        if (! $match) {
            return '';
        }

        $names = array_filter([
            $match->athleteA?->name,
            $match->athleteB?->name,
        ], fn (?string $name) => $name !== null && $name !== '');

        return $names === [] ? '' : implode(' vs ', $names);
    }

    protected function time(?string $value): string
    {
        return $value !== null && $value !== '' ? substr($value, 0, 5) : '';
    }
}
