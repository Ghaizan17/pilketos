<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StudentsTemplateExport implements FromArray, WithHeadings, WithStyles
{
    public function headings(): array
    {
        return ['nama', 'nipd', 'nisn', 'jk', 'kelas'];
    }

    public function array(): array
    {
        return [
            ['Ahmad Yani', '12345', '0012345678', 'L', 'XII RPL A'],
            ['Siti Aminah', '12346', '0012345679', 'P', 'XII RPL A'],
        ];
    }

    public function styles(Worksheet $sheet): ?array
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'color' => ['argb' => 'FFE2E8F0'],
                ],
            ],
        ];
    }
}
