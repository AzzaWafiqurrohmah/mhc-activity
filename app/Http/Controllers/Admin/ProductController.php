<?php

// app/Http/Controllers/Admin/ProductController.php
namespace App\Http\Controllers\Admin;

use App\Exports\ProductsExport;
use App\Exports\ProductTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\ProductsImport;
use App\Models\Division;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class ProductController extends Controller
{
    private const PER_PAGE_OPTIONS = [5, 10, 25, 50];

    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page');
        $perPage = in_array($perPage, self::PER_PAGE_OPTIONS, true) ? $perPage : 10;

        $f = $this->filters($request);

        return view('admin.products.index', [
            'products'       => $this->query($f)->paginate($perPage)->withQueryString(),
            'search'         => $f['search'],
            'perPage'        => $perPage,
            'perPageOptions' => self::PER_PAGE_OPTIONS,
            'divisionFilter' => $f['division'],
            'allDivisions'   => Division::orderBy('name')->get(['id', 'name', 'is_active']),
        ]);
    }

    public function store(Request $request)
    {
        $request->merge(['barcode' => trim((string) $request->input('barcode'))]);

        $data = $request->validate($this->rules(), $this->messages(), $this->labels());

        Product::create($data);

        return back()->with('success', 'Produk berhasil ditambahkan.');
    }

    public function update(Request $request, Product $product)
    {
        $request->merge(['barcode' => trim((string) $request->input('barcode'))]);

        $data = $request->validate($this->rules($product), $this->messages(), $this->labels());

        $product->update($data);

        return back()->with('success', 'Produk berhasil diperbarui.');
    }

    public function toggle(Product $product)
    {
        $product->update(['is_active' => ! $product->is_active]);

        return back()->with('success', $product->is_active
            ? "Produk {$product->name} diaktifkan."
            : "Produk {$product->name} dinonaktifkan.");
    }

    /** Ekspor mengikuti cari dan filter divisi yang sedang aktif (semua baris, bukan hanya satu halaman). */
    public function export(Request $request)
    {
        $query = $this->query($this->filters($request));
        $count = (clone $query)->count();

        $response = Excel::download(new ProductsExport($query), 'produk-' . today()->format('Y-m-d') . '.xlsx');
        $response->headers->set('X-Export-Count', (string) $count);

        return $response;
    }

    public function template()
    {
        return Excel::download(new ProductTemplateExport(), 'template-impor-produk.xlsx');
    }

    public function import(Request $request)
    {
        $data = $request->validate([
            'file'         => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:10240'],
            'on_duplicate' => ['required', Rule::in(['skip', 'update'])],
        ], [], [
            'file' => 'File', 'on_duplicate' => 'Aksi untuk barcode yang sudah ada',
        ]);

        set_time_limit(0);

        $import = new ProductsImport($data['on_duplicate']);

        try {
            Excel::import($import, $request->file('file'));
        } catch (\InvalidArgumentException $e) {
            // judul kolom tidak dikenali / file kosong: pesannya aman ditampilkan
            return back()->withInput($request->except('file'))->withErrors(['file' => $e->getMessage()]);
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->withInput($request->except('file'))
                ->withErrors(['file' => 'File tidak bisa dibaca. Pastikan formatnya .xlsx, .xls, atau .csv dan judul kolomnya sesuai template.']);
        }

        return back()->with('import_result', [
            'created'   => $import->created,
            'updated'   => $import->updated,
            'skipped'   => $import->skipped,
            'failed'    => count($import->errors),
            'errors'    => array_slice($import->errors, 0, 30),
            'unmatched' => array_values($import->unmatchedDivisions),
        ]);
    }

    private function filters(Request $request): array
    {
        // '' = semua, 'none' = tanpa divisi, angka = id divisi
        $division = (string) $request->query('division', '');

        return [
            'search'   => trim((string) $request->query('q')),
            'division' => ($division === 'none' || ctype_digit($division)) ? $division : '',
        ];
    }

    private function query(array $f): Builder
    {
        return Product::with('division')
            ->when($f['search'] !== '', fn ($q) => $q->search($f['search']))
            ->when($f['division'] === 'none', fn ($q) => $q->whereNull('division_id'))
            ->when(ctype_digit($f['division']), fn ($q) => $q->where('division_id', $f['division']))
            ->orderByDesc('is_active')
            ->orderBy('name');
    }

    private function rules(?Product $product = null): array
    {
        return [
            'barcode' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9._\-]+$/',
                          Rule::unique('products', 'barcode')->ignore($product?->id)],
            'name'    => ['required', 'string', 'max:150'],

            // Divisi opsional. Divisi nonaktif hanya boleh dipertahankan kalau memang sudah dipakai produk ini.
            'division_id' => ['nullable', Rule::exists('divisions', 'id')->where(function ($query) use ($product) {
                $query->where(function ($q) use ($product) {
                    $q->where('is_active', true);

                    if ($product?->division_id) {
                        $q->orWhere('id', $product->division_id);
                    }
                });
            })],
        ];
    }

    private function messages(): array
    {
        return [
            'barcode.unique' => 'Barcode ini sudah terdaftar.',
            'barcode.regex'  => 'Barcode hanya boleh huruf, angka, titik, minus, atau garis bawah.',
        ];
    }

    private function labels(): array
    {
        return ['barcode' => 'Barcode', 'name' => 'Nama produk', 'division_id' => 'Divisi'];
    }
}