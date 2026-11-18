<?php

namespace App\Imports;

use App\AreaType;
use App\Role;
use App\Models\Member;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class MembersImport implements ToCollection, WithHeadingRow
{
    /** Judul kolom di Excel (setelah dijadikan huruf kecil dan underscore) yang dikenali untuk tiap isian. */
    private const ALIASES = [
        'date'       => ['tanggal_input', 'tanggal', 'tgl_input', 'tgl', 'tanggal_daftar'],
        'cs'         => ['cs_yang_input', 'cs', 'yang_input', 'diinput_oleh', 'input_by', 'petugas', 'nama_cs'],
        'member_no'  => ['no_member', 'nomor_member', 'no_id_member', 'id_member', 'member_no', 'no_mbr'],
        'name'       => ['nama', 'nama_member', 'nama_lengkap', 'name'],
        'address'    => ['alamat', 'address'],
        'kelurahan'  => ['kelurahan', 'kel', 'desa_kelurahan'],
        'kecamatan'  => ['kecamatan', 'kec'],
        'nik'        => ['no_ktp', 'ktp', 'nik', 'no_nik'],
        'phone'      => ['no_hp', 'hp', 'no_telp', 'telp', 'no_telepon', 'telepon', 'nomor_hp', 'no_handphone'],
        'occupation' => ['pekerjaan', 'profesi'],
        'radius'     => ['radius', 'jarak', 'radius_ke_mhc', 'jarak_ke_mhc'],
        'area'       => ['keterangan', 'dalam_luar_kota', 'wilayah', 'ket'],
    ];

    public int $created = 0;
    public int $updated = 0;
    public int $skipped = 0;
    public array $errors = [];
    public array $unmatchedCs = [];

    private array $userMap = [];

    public function __construct(
        private string $defaultUnit = 'km',
        private string $onDuplicate = 'skip',
        private ?int $actorId = null,
    ) {
        // nama CS -> id akun (hanya kalau namanya cocok dengan tepat satu akun staff)
        $this->userMap = User::where('role', Role::Staff->value)
            ->get(['id', 'name'])
            ->groupBy(fn ($user) => $this->normalize($user->name))
            ->filter(fn ($group) => $group->count() === 1)
            ->map(fn ($group) => $group->first()->id)
            ->all();
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $line = $index + 2;   // baris 1 adalah judul kolom

            if ($row->filter(fn ($v) => $v !== null && trim((string) $v) !== '')->isEmpty()) {
                continue;         // baris kosong
            }

            try {
                $memberNo = $this->text($this->pick($row, 'member_no'), 50);
                $name     = $this->text($this->pick($row, 'name'), 150);

                if ($memberNo === null) {
                    throw new InvalidArgumentException('No member kosong.');
                }
                if ($name === null) {
                    throw new InvalidArgumentException('Nama kosong.');
                }

                $csName = $this->text($this->pick($row, 'cs'), 150) ?? '-';
                $csId   = $this->userMap[$this->normalize($csName)] ?? null;

                if ($csId === null && $csName !== '-') {
                    $this->unmatchedCs[$csName] = true;
                }

                $payload = [
                    'name'       => $name,
                    'address'    => $this->text($this->pick($row, 'address'), 500) ?? '-',
                    'kelurahan'  => $this->text($this->pick($row, 'kelurahan'), 100) ?? '-',
                    'kecamatan'  => $this->text($this->pick($row, 'kecamatan'), 100) ?? '-',
                    'nik'        => $this->text($this->pick($row, 'nik'), 20),
                    'phone'      => $this->phone($this->pick($row, 'phone')),
                    'occupation' => $this->text($this->pick($row, 'occupation'), 100),
                    'radius_m'   => $this->radius($this->pick($row, 'radius')),
                    'area_type'  => $this->area($this->pick($row, 'area')),
                    'input_date' => $this->date($this->pick($row, 'date')),
                ];

                $existing = Member::where('member_no', $memberNo)->first();

                if ($existing) {
                    if ($this->onDuplicate === 'update') {
                        $existing->update($payload + ['updated_by' => $this->actorId]);
                        $this->updated++;
                    } else {
                        $this->skipped++;
                    }

                    continue;
                }

                Member::create($payload + [
                    'member_no'       => $memberNo,
                    'created_by'      => $csId,
                    'created_by_name' => $csName,
                ]);

                $this->created++;
            } catch (InvalidArgumentException $e) {
                $this->errors[] = ['row' => $line, 'message' => $e->getMessage()];
            } catch (\Throwable $e) {
                report($e);
                $this->errors[] = ['row' => $line, 'message' => 'Gagal menyimpan baris ini.'];
            }
        }
    }

    private function pick(Collection $row, string $field)
    {
        foreach (self::ALIASES[$field] as $alias) {
            $value = $row->get($alias);

            if ($value !== null && trim((string) $value) !== '') {
                return $value;
            }
        }

        return null;
    }

    private function normalize(string $name): string
    {
        return Str::lower(trim(preg_replace('/\s+/', ' ', $name)));
    }

    private function text($value, int $max = 255): ?string
    {
        if ($value === null) {
            return null;
        }

        // angka dari Excel (misal KTP) jangan sampai tampil sebagai 3,5E+15
        if (is_int($value) || is_float($value)) {
            $value = number_format($value, 0, '', '');
        }

        $value = trim((string) $value);

        return $value === '' ? null : mb_substr($value, 0, $max);
    }

    private function phone($value): ?string
    {
        $isNumber = is_int($value) || is_float($value);
        $text     = $this->text($value, 30);

        if ($text === null) {
            return null;
        }

        // Excel membuang angka nol di depan kalau selnya berformat angka
        if ($isNumber && preg_match('/^8\d{8,12}$/', $text)) {
            $text = '0' . $text;
        }

        return $text;
    }

    private function date($value): string
    {
        if ($value === null) {
            return today()->toDateString();   // tanggal kosong = hari ini
        }

        if (is_numeric($value)) {
            return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->toDateString();
        }

        $text = trim((string) $value);

        foreach (['d/m/Y', 'j/n/Y', 'd-m-Y', 'j-n-Y', 'Y-m-d', 'd/m/y', 'd-m-y'] as $format) {
            $date = Carbon::createFromFormat('!' . $format, $text);

            if ($date !== false && $date->format($format) === $text) {
                return $date->toDateString();
            }
        }

        throw new InvalidArgumentException("Tanggal \"{$text}\" tidak terbaca. Gunakan format tanggal seperti 18/11/2026.");
    }

    private function radius($value): ?int
    {
        if ($value === null) {
            return null;
        }

        if (is_numeric($value)) {
            $number = (float) $value;
            $unit   = $this->defaultUnit;
        } else {
            $text = Str::lower(trim((string) $value));

            if (! preg_match('/^([0-9]+(?:[.,][0-9]+)?)\s*(km|kilometer|m|meter|mtr)?\.?$/', $text, $match)) {
                throw new InvalidArgumentException("Radius \"{$value}\" tidak terbaca. Contoh yang benar: 850 m atau 2,5 km.");
            }

            $number = (float) str_replace(',', '.', $match[1]);
            $unit   = isset($match[2]) && $match[2] !== ''
                ? (str_starts_with($match[2], 'k') ? 'km' : 'm')
                : $this->defaultUnit;
        }

        return (int) round($number * ($unit === 'km' ? 1000 : 1));
    }

    private function area($value): string
    {
        $text = Str::lower((string) $value);

        if (str_contains($text, 'luar')) {
            return AreaType::Outside->value;
        }

        return AreaType::Inside->value;   // "dalam", kosong, atau tidak jelas = dalam kota
    }
}