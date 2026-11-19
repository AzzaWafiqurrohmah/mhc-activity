<?php

// app/Exports/RewardsExport.php
namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RewardsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function __construct(private Builder $query)
    {
    }

    public function query(): Builder
    {
        return $this->query;
    }

    public function headings(): array
    {
        return ['Brand', 'Jenis', 'Stok', 'Status'];
    }

    public function map($reward): array
    {
        return [
            $reward->brand,
            $reward->type,
            $reward->stock,
            $reward->status()->label(),
        ];
    }

    public function styles(Worksheet $sheet): ?array
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}