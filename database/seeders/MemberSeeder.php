<?php

namespace Database\Seeders;

use App\Role;
use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Seeder;

class MemberSeeder extends Seeder
{
    public function run(): void
    {   
        $faker = \Faker\Factory::create('id_ID');

        $csUsers = User::where('role', Role::Staff->value)
            ->whereHas('division', fn ($q) => $q->where('code', 'cs'))
            ->get();

        // nama CS dari "Excel lama" yang tidak punya akun
        $legacyNames = ['Ratna', 'Dian (lama)'];

        $inCityAreas = [
            ['Sumbersari', ['Karangrejo', 'Kranjingan', 'Tegalgede', 'Wirolegi']],
            ['Patrang',    ['Patrang', 'Baratan', 'Gebang', 'Slawu']],
            ['Kaliwates',  ['Kaliwates', 'Kepatihan', 'Jemberlor', 'Mangli']],
            ['Ajung',      ['Ajung', 'Mangaran', 'Wirowongso']],
        ];
        $outside    = ['Bondowoso', 'Lumajang', 'Banyuwangi', 'Situbondo'];
        $occupation = ['Wiraswasta', 'PNS', 'Karyawan Swasta', 'Ibu Rumah Tangga',
                       'Pelajar/Mahasiswa', 'Petani', 'Guru', 'Pedagang'];

        for ($i = 1; $i <= 200; $i++) {
            $inCity = random_int(1, 100) <= 85;

            if ($inCity) {
                [$kecamatan, $kelurahans] = $faker->randomElement($inCityAreas);
                $kelurahan = $faker->randomElement($kelurahans);
                $radius    = random_int(100, 9000);
            } else {
                $kecamatan = $faker->randomElement($outside);
                $kelurahan = 'Desa ' . $faker->lastName();
                $radius    = random_int(12000, 60000);
            }

            $cs = ($csUsers->isNotEmpty() && random_int(1, 100) <= 85) ? $csUsers->random() : null;
            $no = (string) (100000 + $i);

            Member::updateOrCreate(['member_no' => $no], [
                'name'            => $faker->name(),
                'address'         => $faker->streetAddress(),
                'kelurahan'       => $kelurahan,
                'kecamatan'       => $kecamatan,
                'nik'             => '3509' . $faker->numerify('############'),
                'phone'           => '08' . random_int(1, 9) . random_int(10000000, 99999999),
                'occupation'      => $faker->randomElement($occupation),
                'radius_m'        => $radius,
                'area_type'       => $inCity ? 'dalam_kota' : 'luar_kota',
                'input_date'      => now()->subDays(random_int(0, 90))->toDateString(),
                'created_by'      => $cs?->id,
                'created_by_name' => $cs?->name ?? $faker->randomElement($legacyNames),
            ]);
        }
    }
}