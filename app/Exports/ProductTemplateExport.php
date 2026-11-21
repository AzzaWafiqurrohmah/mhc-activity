<?php

// app/Exports/ProductTemplateExport.php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Exports\ProductTemplateSheet;
use App\Exports\ProductDivisionListSheet;

class ProductTemplateExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [new ProductTemplateSheet(), new ProductDivisionListSheet()];
    }
}