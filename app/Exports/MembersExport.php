<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MembersExport extends DefaultValueBinder implements
 FromQuery, WithHeadings, WithMapping, WithColumnFormatting, WithCustomValueBinder, ShouldAutoSize, WithStyles
{
    public const HEADINGS = [
        'Tanggal Input', 'CS yang Input', 'No Member', 'Nama', 'Alamat', 'Kelurahan',
        'Kecamatan', 'No KTP', 'No HP', 'Pekerjaan', 'Radius', 'Keterangan',
    ];

    public function __construct(private Builder $query)
    {
    }

    public function query(): Builder
    {
        return $this->query;
    }

    public function headings(): array
    {
        return self::HEADINGS;
    }

    public function map($member): array
    {
        return [
            ExcelDate::dateTimeToExcel($member->input_date),
            $member->created_by_name,
            $member->member_no,
            $member->name,
            $member->address,
            $member->kelurahan,
            $member->kecamatan,
            $member->nik,
            $member->phone,
            $member->occupation,
            $member->radius_m === null ? null : $member->radiusLabel(),
            $member->area_type->label(),
        ];
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

    /** No member, KTP, dan HP dipaksa jadi teks supaya angkanya tidak rusak di Excel. */
    public function bindValue(Cell $cell, $value = null): bool
    {
        if (in_array($cell->getColumn(), ['C', 'H', 'I'], true) && $value !== null && $value !== '') {
            $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }
}