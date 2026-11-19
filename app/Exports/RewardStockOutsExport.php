<?php

// app/Exports/RewardStockOutsExport.php
namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

class RewardStockOutsExport extends DefaultValueBinder implements FromQuery, ShouldAutoSize, WithCustomValueBinder, WithHeadings, WithMapping
{
    /** Receipt no, member no, phone: always text. */
    private const TEXT_COLUMNS = ['B', 'C', 'E'];

    public function __construct(private Builder $query) {}

    public function query(): Builder
    {
        return $this->query;
    }

    public function headings(): array
    {
        return ['Tanggal', 'No Struk', 'No Member', 'Nama Member', 'No HP', 'Total Belanja',
                'Brand', 'Jenis', 'Jumlah', 'Keterangan', 'Diinput oleh'];
    }

    public function map($row): array
    {
        return [
            $row->given_on->format('d/m/Y'), $row->receipt_no, $row->member_no, $row->member_name, $row->phone,
            $row->purchase_total, $row->reward->brand, $row->reward->type, $row->quantity,
            $row->notes, $row->created_by_name,
        ];
    }

    public function bindValue(Cell $cell, mixed $value): bool
    {
        if ($value !== null && in_array($cell->getColumn(), self::TEXT_COLUMNS, true)) {
            $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }
}