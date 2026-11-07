<?php

namespace Database\Seeders;

use App\Models\Division;
use App\Models\Task;
use App\TaskType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            'Kasir' => [
                ['Jaga kasir',        TaskType::Routine],
                ['Hitung uang',       TaskType::Routine],
                ['Beres-beres kasir', TaskType::Routine],
                ['Retur barang',      TaskType::Incidental],
                ['Komplain customer', TaskType::Incidental],
                ['Selisih kas',       TaskType::Incidental],
            ],
            'Gudang' => [
                ['Terima barang',     TaskType::Routine],
                ['Restock rak',       TaskType::Routine],
                ['Stok opname',       TaskType::Routine],
                ['Barang rusak',      TaskType::Incidental],
                ['Barang salah kirim',TaskType::Incidental],
            ],
            'Display' => [
                ['Tata display',      TaskType::Routine],
                ['Pasang label harga',TaskType::Routine],
                ['Bersihkan area',    TaskType::Routine],
                ['Ganti promo',       TaskType::Incidental],
            ],
        ];

        foreach ($data as $divisionName => $tasks) {
            $division = Division::where('name', $divisionName)->firstOrFail();

            foreach ($tasks as $i => [$name, $type]) {
                Task::firstOrCreate(
                    ['division_id' => $division->id, 'name' => $name],
                    ['type' => $type, 'sort_order' => $i + 1],
                );
            }
        }
    }
}
