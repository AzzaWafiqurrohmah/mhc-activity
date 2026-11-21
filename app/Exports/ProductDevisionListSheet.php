<?php

// app/Exports/ProductDivisionListSheet.php
namespace App\Exports;

use App\Models\Division;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductDivisionListSheet implements FromCollection, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    public function collection(): Collection
    {
        return Division::where('is_active', true)->orderBy('name')->get(['name', 'code']);
    }

    public function headings(): array
    {
        return ['Nama Divisi', 'Kode'];
    }

    public function title(): string
    {
        return 'Daftar Divisi';
    }

    public function styles(Worksheet $sheet): ?array
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}