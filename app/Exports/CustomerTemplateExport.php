<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CustomerTemplateExport implements FromArray, WithColumnWidths, WithStyles
{
    use Exportable;

    public function array(): array
    {
        return [
            ['nama', 'kode', 'telepon', 'alamat', 'dusun', 'paket', 'tarif', 'tanggal_mulai', 'tanggal_jatuh_tempo', 'status', 'catatan'],
            ['Budi Santoso', 'RTW-0001', '081234567890', 'Jl. Melati No. 1', 'Dusun Makmur', 'Paket Reguler', 150000, '2026-01-05', 10, 'active', ''],
            ['Siti Aminah', '', '08987654321', 'Jl. Mawar No. 5', 'Dusun Sejahtera', 'Paket Premium', 250000, '2026-02-01', 15, 'active', ''],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20,
            'B' => 14,
            'C' => 18,
            'D' => 25,
            'E' => 18,
            'F' => 18,
            'G' => 12,
            'H' => 16,
            'I' => 22,
            'J' => 12,
            'K' => 25,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF6366F1'],
                ],
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 12],
            ],
        ];
    }
}
