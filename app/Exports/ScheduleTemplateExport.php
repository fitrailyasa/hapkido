<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ScheduleTemplateExport implements WithMultipleSheets
{
    public const SHEET_DATA = 'Template Jadwal';

    public const SHEET_GUIDE = 'Petunjuk Isi';

    public const KETERANGAN = 'Keterangan';

    public const EXAMPLE_PREFIX = 'CONTOH';

    public function sheets(): array
    {
        return [
            new ScheduleTemplateJadwalSheet,
            new ScheduleTemplatePetunjukSheet,
        ];
    }

    public static function headerStyle(): array
    {
        return [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2F5496']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ];
    }

    public static function borderStyle(): array
    {
        return [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'BFBFBF'],
                ],
            ],
        ];
    }
}

class ScheduleTemplateJadwalSheet implements FromCollection, WithColumnWidths, WithHeadings, WithMapping, WithStyles, WithTitle
{
    public const EXAMPLES = [
        [
            'match_no' => 'CONTOH-001',
            'kategori' => 'Remaja Putra',
            'arena' => 'Arena Utama',
            'jenis' => 'daeryun',
            'ronde' => 'penyisihan',
            'urutan' => 1,
            'tanggal' => '2026-10-20',
            'mulai' => '08:00',
            'selesai' => '08:30',
            'status' => 'pending',
            'Keterangan' => 'Kolom ini wajib diisi sesuai contoh. CONTOH — hapus baris ini sebelum import. Tanggal format dd/mm/yyyy atau yyyy-mm-dd.',
        ],
        [
            'match_no' => 'CONTOH-002',
            'kategori' => 'Remaja Putri',
            'arena' => 'Arena 2',
            'jenis' => 'daeryun',
            'ronde' => 'perempat_final',
            'urutan' => 2,
            'tanggal' => '21/10/2026',
            'mulai' => '09:15',
            'selesai' => '09:45',
            'status' => 'preparation',
            'Keterangan' => 'CONTOH — hapus baris ini sebelum import. Jenis: daeryun / art. Ronde: penyisihan / perempat_final / semifinal / final.',
        ],
        [
            'match_no' => 'CONTOH-003',
            'kategori' => 'Dewasa Putra',
            'arena' => '',
            'jenis' => 'art',
            'ronde' => 'semifinal',
            'urutan' => 3,
            'tanggal' => '2026-10-22',
            'mulai' => '10:00',
            'selesai' => '10:30',
            'status' => 'finished',
            'Keterangan' => 'CONTOH — hapus baris ini sebelum import. Status: pending / preparation / running / finished. Jam format HH:MM. Arena boleh dikosongkan.',
        ],
    ];

    public function title(): string
    {
        return ScheduleTemplateExport::SHEET_DATA;
    }

    public function collection(): Collection
    {
        return collect(self::EXAMPLES);
    }

    public function headings(): array
    {
        return array_merge(SchedulesExport::HEADINGS, [ScheduleTemplateExport::KETERANGAN]);
    }

    public function map($example): array
    {
        $row = [];

        foreach (SchedulesExport::HEADINGS as $column) {
            $row[] = $example[$column] ?? '';
        }

        $row[] = $example[ScheduleTemplateExport::KETERANGAN] ?? '';

        return $row;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 14,
            'B' => 18,
            'C' => 16,
            'D' => 10,
            'E' => 18,
            'F' => 8,
            'G' => 14,
            'H' => 10,
            'I' => 10,
            'J' => 15,
            'K' => 60,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $contoh = [
            'font' => ['italic' => true, 'color' => ['rgb' => '7F6000']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF2CC']],
        ];

        return [
            1 => ScheduleTemplateExport::headerStyle(),
            2 => $contoh,
            3 => $contoh,
            4 => $contoh,
            'A1:K4' => ScheduleTemplateExport::borderStyle(),
            'A2:K4' => ['alignment' => ['vertical' => Alignment::VERTICAL_TOP]],
            'K1:K4' => ['alignment' => ['wrapText' => true, 'vertical' => Alignment::VERTICAL_TOP]],
        ];
    }
}

class ScheduleTemplatePetunjukSheet implements FromCollection, WithColumnWidths, WithHeadings, WithStyles, WithTitle
{
    public function title(): string
    {
        return ScheduleTemplateExport::SHEET_GUIDE;
    }

    public function headings(): array
    {
        return ['Kolom', 'Wajib?', 'Keterangan', 'Contoh'];
    }

    public function collection(): Collection
    {
        return collect([
            ['match_no', 'Ya', 'Kode laga unik, tidak boleh sama dengan data yang sudah ada. Diawali "CONTOH-" berarti baris contoh dan otomatis diabaikan saat import.', 'CONTOH-001 / M-001'],
            ['kategori', 'Ya', 'Nama kategori persis seperti yang ada di database (huruf besar-kecil bebas).', 'Remaja Putra'],
            ['arena', 'Tidak', 'Nama atau label arena; kosongkan bila belum ditentukan.', 'Arena Utama'],
            ['jenis', 'Ya', 'Jenis: daeryun / art', 'daeryun'],
            ['ronde', 'Tidak', 'Ronde: penyisihan / perempat_final / semifinal / final', 'penyisihan'],
            ['urutan', 'Tidak', 'Urutan laga dalam sehari, angka bulat minimal 1.', '1'],
            ['tanggal', 'Ya', 'Tanggal format dd/mm/yyyy atau yyyy-mm-dd', '20/10/2026'],
            ['mulai', 'Tidak', 'Jam mulai format HH:MM.', '08:00'],
            ['selesai', 'Tidak', 'Jam selesai format HH:MM.', '08:30'],
            ['status', 'Tidak', 'Status: pending / preparation / running / finished', 'pending'],
            [ScheduleTemplateExport::KETERANGAN, 'Tidak', 'Kolom bantuan untuk operator, diabaikan saat import.', 'CONTOH — hapus baris ini sebelum import'],
            ['', '', '', ''],
            ['Cara pakai', '', 'Urutan penggunaan template ini.', ''],
            ['Langkah 1', '', 'Download template Excel lewat tombol "Template" pada halaman jadwal.', ''],
            ['Langkah 2', '', 'Isi data pada sheet "' . ScheduleTemplateExport::SHEET_DATA . '" mengikuti kolom dan petunjuk di atas.', ''],
            ['Langkah 3', '', 'Hapus semua baris yang diawali "CONTOH-" sebelum import.', ''],
            ['Langkah 4', '', 'Simpan file dengan format .xlsx, lalu upload lewat menu Import.', ''],
        ]);
    }

    public function columnWidths(): array
    {
        return [
            'A' => 16,
            'B' => 10,
            'C' => 72,
            'D' => 34,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ScheduleTemplateExport::headerStyle(),
            14 => [
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D9E1F2']],
            ],
            'A1:D12' => ScheduleTemplateExport::borderStyle(),
            'A1:D18' => ['alignment' => ['vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true]],
        ];
    }
}
