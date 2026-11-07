<?php

namespace Database\Seeders;

use App\Models\Division;
use App\Models\User;
use App\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $start = now()->subDays(60)->toDateString();

        // Admin dan SM (tanpa divisi)
        $staffless = [
            ['Admin Sistem', 'ADM001', Role::Admin],
            ['Store Manager', 'SM001',  Role::Sm],
            ['Atasan SM',     'SM002',  Role::Sm],
        ];

        foreach ($staffless as [$name, $code, $role]) {
            User::updateOrCreate(
                ['employee_code' => $code],
                ['name' => $name, 'password' => '123456', 'role' => $role,
                'active_from' => $start, 'is_active' => true],
            );
        }

        // Staff dummy per divisi
        $staff = [
            'Kasir'  => ['Rina', 'Dewi', 'Sari', 'Putri'],
            'Gudang' => ['Budi', 'Andi', 'Joko', 'Eko'],
            'Display'=> ['Maya', 'Lina', 'Tono'],
        ];

        $n = 1;
        foreach ($staff as $divisionName => $names) {
            $division = Division::where('name', $divisionName)->firstOrFail();

            foreach ($names as $name) {
                User::updateOrCreate(
                    ['employee_code' => 'STF' . str_pad($n++, 3, '0', STR_PAD_LEFT)],
                    ['name' => $name, 'password' => '123456', 'role' => Role::Staff,
                    'division_id' => $division->id,
                    'active_from' => $start, 'is_active' => true],
                );
            }
        }
    }
}
