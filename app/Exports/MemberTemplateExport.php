<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MemberTemplateExport extends DefaultValueBinder implements
    FromArray, WithHeadings, WithColumnFormatting, WithCustomValueBinder, ShouldAutoSize, WithStyles
{
    public function headings(): array
    {
        return MembersExport::HEADINGS;
    }

    public function array(): array
    {
        return [[
            ExcelDate::dateTimeToExcel(now()),
            'Nama CS',
            '100001',
            'Budi Santoso',
            'Jl. Mawar No. 12',
            'Kranjingan',
            'Sumbersari',
            '3509000000000001',
            '081234567890',
            'Wiraswasta',
            '2,5 km',
            'Dalam kota',
        ]];
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'C' => NumberFormat::FORMAT_TEXT,
            'H' => NumberFormat::FORMAT_TEXT,
            'I' => NumberFormat::FORMAT_TEXT,
        ];
    }

    public function styles(Worksheet $sheet): ?array
    {
        return [1 => ['font' => ['bold' => true]]];
    }

    public function bindValue(Cell $cell, $value = null): bool
    {
        if (in_array($cell->getColumn(), ['C', 'H', 'I'], true) && $value !== null && $value !== '') {
            $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }
}