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
            'Kasir'   => 'kasir',
            'Gudang'  => 'gudang',
            'Display' => 'display',
            'CS'      => 'cs',
        ];

        foreach ($divisions as $name => $code) {
            Division::updateOrCreate(['name' => $name], ['code' => $code]);
        }
    }
}
