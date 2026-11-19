<?php

// app/Exports/RewardStockInsExport.php
namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class RewardStockInsExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    public function __construct(private Builder $query) {}

    public function query(): Builder
    {
        return $this->query;
    }

    public function headings(): array
    {
        return ['Tanggal', 'Brand', 'Jenis', 'Jumlah', 'Keterangan', 'Diinput oleh'];
    }

    public function map($row): array
    {
        return [
            $row->received_on->format('d/m/Y'), $row->reward->brand, $row->reward->type,
            $row->quantity, $row->notes, $row->created_by_name,
        ];
    }
}