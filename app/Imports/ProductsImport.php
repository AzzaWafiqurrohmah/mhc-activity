<?php

// app/Imports/ProductsImport.php
namespace App\Imports;

use App\Models\Division;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;

class ProductsImport implements ToCollection
{
    /** Judul kolom yang dikenali (sudah dinormalkan: huruf kecil, spasi rapi). */
    private const HEADINGS = [
        'barcode'  => ['barcode', 'kode barcode', 'kode', 'sku'],
        'name'     => ['nama produk', 'nama barang', 'nama', 'produk', 'barang'],
        'division' => ['divisi', 'devisi', 'division'],
    ];

    public int $created = 0;
    public int $updated = 0;
    public int $skipped = 0;

    /** @var array<int, array{row: int, message: string}> */
    public array $errors = [];

    /** @var array<string, array{name: string, count: int, hint: ?string}> */
    public array $unmatchedDivisions = [];

    /** @var array<string, Division> nama atau kode (dinormalkan) => divisi */
    private array $divisionLookup = [];

    /** @var array<int, string> nama divisi aktif, untuk saran salah ketik */
    private array $activeNames = [];

    public function __construct(private string $onDuplicate)
    {
        foreach (Division::get(['id', 'name', 'code', 'is_active']) as $division) {
            $this->divisionLookup[$this->normalize($division->name)] = $division;

            if ($division->code) {
                $this->divisionLookup[$this->normalize($division->code)] ??= $division;
            }

            if ($division->is_active) {
                $this->activeNames[] = $division->name;
            }
        }
    }

    public function collection(Collection $rows): void
    {
        $rows     = $rows->values();
        $headerAt = $rows->search(fn ($row) => $this->isFilled($row));

        if ($headerAt === false) {
            throw new \InvalidArgumentException('File kosong.');
        }

        $columns = $this->mapColumns($rows[$headerAt]);
        $parsed  = [];
        $seen    = [];

        foreach ($rows->slice($headerAt + 1) as $index => $row) {
            $line = $index + 1;   // nomor baris di Excel

            if (! $this->isFilled($row)) {
                continue;
            }

            [$barcode, $error] = $this->cleanBarcode($row[$columns['barcode']] ?? null);

            if ($error) {
                $this->fail($line, $error);
                continue;
            }

            $name = Str::squish((string) ($row[$columns['name']] ?? ''));

            if ($name === '') {
                $this->fail($line, 'Nama produk kosong.');
                continue;
            }

            if (mb_strlen($name) > 150) {
                $this->fail($line, 'Nama produk lebih dari 150 huruf.');
                continue;
            }

            $key = Str::lower($barcode);

            if (isset($seen[$key])) {
                $this->fail($line, "Barcode {$barcode} sama dengan baris {$seen[$key]}, baris ini dilewati.");
                continue;
            }

            $seen[$key] = $line;

            $parsed[$key] = [
                'barcode'     => $barcode,
                'name'        => $name,
                'division_id' => isset($columns['division'])
                    ? $this->resolveDivision($row[$columns['division']] ?? null)
                    : null,
            ];
        }

        $this->save($parsed);
    }

    /** @param array<string, array{barcode: string, name: string, division_id: ?int}> $parsed */
    private function save(array $parsed): void
    {
        DB::transaction(function () use ($parsed) {
            foreach (array_chunk($parsed, 500, true) as $chunk) {
                $existing = Product::whereIn('barcode', array_column($chunk, 'barcode'))
                    ->get()
                    ->keyBy(fn (Product $p) => Str::lower($p->barcode));

                foreach ($chunk as $key => $item) {
                    $product = $existing->get($key);

                    if ($product === null) {
                        Product::create($item);
                        $this->created++;
                        continue;
                    }

                    if ($this->onDuplicate === 'skip') {
                        $this->skipped++;
                        continue;
                    }

                    // Sel divisi kosong atau tidak dikenali tidak menghapus divisi yang sudah ada.
                    $product->fill(['name' => $item['name']]);

                    if ($item['division_id'] !== null) {
                        $product->division_id = $item['division_id'];
                    }

                    if ($product->isDirty()) {
                        $product->save();
                        $this->updated++;
                    } else {
                        $this->skipped++;
                    }
                }
            }
        });
    }

    private function mapColumns($headerRow): array
    {
        $columns = [];

        foreach ($headerRow as $i => $cell) {
            $heading = $this->normalize((string) $cell);

            foreach (self::HEADINGS as $field => $aliases) {
                if (! isset($columns[$field]) && in_array($heading, $aliases, true)) {
                    $columns[$field] = $i;
                }
            }
        }

        if (! isset($columns['barcode'], $columns['name'])) {
            throw new \InvalidArgumentException(
                'Kolom "Barcode" dan "Nama Produk" tidak ditemukan di baris pertama. Pakai template supaya judul kolomnya sesuai.'
            );
        }

        return $columns;
    }

    /** @return array{0: ?string, 1: ?string} [barcode, pesan error] */
    private function cleanBarcode(mixed $value): array
    {
        if (is_int($value) || is_float($value)) {
            if (is_float($value) && (floor($value) != $value || abs($value) >= 1e15)) {
                return [null, 'Barcode terbaca sebagai angka desimal atau ilmiah. Ubah format kolom Barcode jadi Text di Excel.'];
            }

            $value = sprintf('%.0f', $value);
        }

        $value = trim((string) $value);

        if ($value === '') {
            return [null, 'Barcode kosong.'];
        }

        if (preg_match('/^\d+(\.\d+)?e[+-]?\d+$/i', $value)) {
            return [null, "Barcode {$value} sudah rusak jadi format ilmiah. Ubah format kolom Barcode jadi Text lalu isi ulang."];
        }

        if (mb_strlen($value) > 50) {
            return [null, 'Barcode lebih dari 50 karakter.'];
        }

        if (! preg_match('/^[A-Za-z0-9._\-]+$/', $value)) {
            return [null, "Barcode {$value} hanya boleh huruf, angka, titik, minus, atau garis bawah."];
        }

        return [$value, null];
    }

    private function resolveDivision(mixed $raw): ?int
    {
        $raw = Str::squish((string) $raw);

        if ($raw === '') {
            return null;
        }

        $key      = $this->normalize($raw);
        $division = $this->divisionLookup[$key] ?? null;

        if ($division?->is_active) {
            return $division->id;
        }

        $this->unmatchedDivisions[$key] ??= [
            'name'  => $raw,
            'count' => 0,
            'hint'  => $division ? 'divisi ini nonaktif' : $this->suggest($key),
        ];
        $this->unmatchedDivisions[$key]['count']++;

        return null;
    }

    /** Saran kalau salah ketik satu atau dua huruf (misalnya "Floring" jadi "Flooring"). */
    private function suggest(string $needle): ?string
    {
        $best     = null;
        $distance = 3;

        foreach ($this->activeNames as $name) {
            $d = levenshtein($needle, $this->normalize($name));

            if ($d < $distance) {
                $best     = $name;
                $distance = $d;
            }
        }

        return $best ? "mungkin: {$best}" : null;
    }

    private function fail(int $row, string $message): void
    {
        $this->errors[] = ['row' => $row, 'message' => $message];
    }

    private function isFilled($row): bool
    {
        return collect($row)->contains(fn ($v) => trim((string) $v) !== '');
    }

    private function normalize(string $value): string
    {
        return Str::lower(Str::squish(str_replace("\u{FEFF}", '', $value)));
    }
}