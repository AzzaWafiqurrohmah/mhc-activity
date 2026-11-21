<?php

namespace Database\Seeders;

use App\Models\Division;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DevisionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $divisions = [
            // divisi yang sudah ada
            'Kasir'      => 'kasir',
            'Gudang'     => 'gudang',
            'Display'    => 'display',
            'CS'         => 'cs',

            // divisi barang (dipakai untuk produk potong / mixing)
            'Flooring'   => 'flooring',
            'Cat'        => 'cat',
            'Plumbing'   => 'plumbing',
            'Kayu'       => 'kayu',
            'Besi'       => 'besi',
            'Elektrikal' => 'elektrikal',
        ];

        foreach ($divisions as $name => $code) {
            Division::updateOrCreate(['name' => $name], ['code' => $code]);
        }
    }
}