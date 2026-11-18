<?php

namespace App\Models;

use App\AreaType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Member extends Model
{
    protected $fillable = [
        'member_no', 'name', 'address', 'kelurahan', 'kecamatan', 'nik',
        'phone', 'occupation', 'radius_m', 'area_type', 'input_date',
        'created_by', 'created_by_name', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'input_date' => 'date',
            'area_type'  => AreaType::class,
        ];
    }

    protected static function booted(): void
    {
        // versi HP yang dirapikan, dipakai untuk pencarian
        static::saving(function (Member $member) {
            $member->phone_search = self::normalizePhone($member->phone);
        });
    }

    /** 0812-3456, +62 812 3456, dan 62812... dianggap sama: semuanya jadi 62812... */
    public static function normalizePhone(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            return '62' . substr($digits, 1);
        }

        if (str_starts_with($digits, '8')) {
            return '62' . $digits;
        }

        return $digits;
    }

    /** Teks untuk ditampilkan: "850 m" atau "2,5 km". */
    public function radiusLabel(): string
    {
        if ($this->radius_m === null) {
            return '-';
        }

        if ($this->radius_m >= 1000) {
            $km = number_format($this->radius_m / 1000, 2, ',', '.');
            return rtrim(rtrim($km, '0'), ',') . ' km';
        }

        return $this->radius_m . ' m';
    }

    /** Nilai dan satuan untuk mengisi form edit. */
    public function radiusForm(): array
    {
        if ($this->radius_m === null) {
            return ['value' => '', 'unit' => 'm'];
        }

        if ($this->radius_m >= 1000) {
            $km = number_format($this->radius_m / 1000, 2, '.', '');
            return ['value' => rtrim(rtrim($km, '0'), '.'), 'unit' => 'km'];
        }

        return ['value' => (string) $this->radius_m, 'unit' => 'm'];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}