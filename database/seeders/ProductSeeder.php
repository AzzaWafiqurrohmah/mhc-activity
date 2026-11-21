<?php

// database/seeders/ProductSeeder.php
namespace Database\Seeders;

use App\Models\Division;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /** [barcode, name, division name or null] */
    private const PRODUCTS = [
        // Potong: lantai
        ['8991001000011', 'Keramik Lantai Roman 40x40 Putih',        'Flooring'],
        ['8991001000028', 'Granit Polished 60x60 Abu-abu',           'Flooring'],
        ['8991001000035', 'Vinyl Plank SPC 4 mm Oak',                'Flooring'],

        // Mixing: cat
        ['8992002000013', 'Cat Tembok Dulux Catylac Interior 5 kg',  'Cat'],
        ['8992002000020', 'Cat Tembok Nippon Vinilex 25 kg',         'Cat'],
        ['8992002000037', 'Cat Kayu dan Besi Avian Avitex 1 kg',     'Cat'],
        ['8992002000044', 'Cat Eksterior Mowilex Emulsion 20 kg',    'Cat'],

        // Potong: pipa
        ['8993003000019', 'Pipa PVC Rucika AW 3/4 inch 4 m',         'Plumbing'],
        ['8993003000026', 'Pipa PVC Rucika D 3 inch 4 m',            'Plumbing'],

        // Potong: kayu
        ['8994004000015', 'Triplek 9 mm 122x244',                    'Kayu'],
        ['8994004000022', 'Multipleks 18 mm 122x244',                'Kayu'],

        // Potong: besi
        ['8995005000011', 'Besi Beton Polos 8 mm 12 m',              'Besi'],
        ['8995005000028', 'Hollow Galvalum 4x4 cm',                  'Besi'],

        // Potong: kabel (contoh barcode berawalan nol, harus tetap utuh)
        ['0123456789012', 'Kabel NYM 2x1,5 mm per meter',            'Elektrikal'],

        // Contoh produk tanpa divisi
        ['8999999000010', 'Kawat Seng BWG 21 per kg',                null],
    ];

    public function run(): void
    {
        $divisions = Division::pluck('id', 'name')
            ->mapWithKeys(fn ($id, $name) => [Str::lower($name) => $id]);

        $missing = [];

        foreach (self::PRODUCTS as [$barcode, $name, $divisionName]) {
            $divisionId = $divisionName ? $divisions->get(Str::lower($divisionName)) : null;

            if ($divisionName && ! $divisionId) {
                $missing[$divisionName] = true;
            }

            // Aman dijalankan berulang: barcode yang sudah ada dilewati.
            Product::firstOrCreate(
                ['barcode' => $barcode],
                ['name' => $name, 'division_id' => $divisionId],
            );
        }

        if ($missing) {
            $this->command->warn(
                'Divisi tidak ditemukan, produknya dibuat tanpa divisi: ' . implode(', ', array_keys($missing))
            );
        }
    }
}