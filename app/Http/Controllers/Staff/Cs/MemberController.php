<?php

namespace App\Http\Controllers\Staff\Cs;

use App\AreaType;
use App\Exports\MemberTemplateExport;
use App\Exports\MembersExport;
use App\Http\Controllers\Controller;
use App\Imports\MembersImport;
use App\Models\Member;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class MemberController extends Controller
{
    private const PER_PAGE_OPTIONS = [10, 25, 50];

    public function index(Request $request)
    {
        $f = $this->filters($request);

        $perPage = (int) $request->query('per_page');
        $perPage = in_array($perPage, self::PER_PAGE_OPTIONS, true) ? $perPage : 10;

        $members = $this->query($f)->paginate($perPage)->withQueryString();

        return view('staff.cs.member.index', [
            'members'        => $members,
            'search'         => $f['search'],
            'perPage'        => $perPage,
            'perPageOptions' => self::PER_PAGE_OPTIONS,
            'sort'           => $f['sort'],
            'today'          => today()->toDateString(),
            'filters'        => [
                'kecamatan' => $f['kecamatan'], 'kelurahan' => $f['kelurahan'],
                'area'      => $f['area'],      'cs'        => $f['cs'],
                'from'      => $f['from'],      'to'        => $f['to'],
            ],

            // pilihan di filter
            'kecamatans' => Member::distinct()->orderBy('kecamatan')->pluck('kecamatan'),
            'kelurahans' => Member::when($f['kecamatan'] !== '', fn ($q) => $q->where('kecamatan', $f['kecamatan']))
                                  ->distinct()->orderBy('kelurahan')->pluck('kelurahan'),
            'csNames'    => Member::distinct()->orderBy('created_by_name')->pluck('created_by_name'),

            // saran otomatis di form
            'allKelurahans' => Member::distinct()->orderBy('kelurahan')->pluck('kelurahan'),
            'areaTypes'     => AreaType::cases(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules(), [], $this->labels());

        Member::create($this->payload($data) + [
            'member_no'       => $data['member_no'],
            'input_date'      => today()->toDateString(),
            'created_by'      => $request->user()->id,
            'created_by_name' => $request->user()->name,
        ]);

        return back()->with('success', 'Member berhasil ditambahkan.');
    }

    public function update(Request $request, Member $member)
    {
        $data = $request->validate($this->rules($member), [], $this->labels());

        $member->update($this->payload($data) + [
            'member_no'  => $data['member_no'],
            'updated_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Data member berhasil diperbarui.');
    }

    /** Ekspor mengikuti cari, filter, dan urutan yang sedang aktif (semua baris, bukan hanya satu halaman). */
    public function export(Request $request)
    {
        $query = $this->query($this->filters($request));
        $count = (clone $query)->count();

        $response = Excel::download(new MembersExport($query), 'member-' . today()->format('Y-m-d') . '.xlsx');
        $response->headers->set('X-Export-Count', (string) $count);

        return $response;
    }

    public function template()
    {
        return Excel::download(new MemberTemplateExport(), 'template-impor-member.xlsx');
    }

    public function import(Request $request)
    {
        $data = $request->validate([
            'file'         => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:10240'],
            'default_unit' => ['required', Rule::in(['km', 'm'])],
            'on_duplicate' => ['required', Rule::in(['skip', 'update'])],
        ], [], [
            'file' => 'File', 'default_unit' => 'Satuan radius', 'on_duplicate' => 'Aksi untuk no member yang sudah ada',
        ]);

        set_time_limit(0);

        $import = new MembersImport($data['default_unit'], $data['on_duplicate'], $request->user()->id);

        try {
            Excel::import($import, $request->file('file'));
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
            'unmatched' => array_map('strval', array_keys($import->unmatchedCs)),
        ]);
    }

    /** Parameter cari, filter, dan urutan yang sudah dibersihkan. */
    private function filters(Request $request): array
    {
        return [
            'search'    => trim((string) $request->query('q')),
            'kecamatan' => trim((string) $request->query('kecamatan')),
            'kelurahan' => trim((string) $request->query('kelurahan')),
            'cs'        => trim((string) $request->query('cs')),
            'area'      => AreaType::tryFrom((string) $request->query('area'))?->value ?? '',
            'from'      => $this->cleanDate($request->query('from')),
            'to'        => $this->cleanDate($request->query('to')),
            'sort'      => $request->query('sort') === 'oldest' ? 'oldest' : 'newest',
        ];
    }

    private function query(array $f): Builder
    {
        $direction = $f['sort'] === 'oldest' ? 'asc' : 'desc';

        return Member::query()
            ->when($f['search'] !== '', fn ($q) => $this->applySearch($q, $f['search']))
            ->when($f['kecamatan'] !== '', fn ($q) => $q->where('kecamatan', $f['kecamatan']))
            ->when($f['kelurahan'] !== '', fn ($q) => $q->where('kelurahan', $f['kelurahan']))
            ->when($f['area'] !== '', fn ($q) => $q->where('area_type', $f['area']))
            ->when($f['cs'] !== '', fn ($q) => $q->where('created_by_name', $f['cs']))
            ->when($f['from'] !== '', fn ($q) => $q->whereDate('input_date', '>=', $f['from']))
            ->when($f['to'] !== '', fn ($q) => $q->whereDate('input_date', '<=', $f['to']))
            ->orderBy('input_date', $direction)
            ->orderBy('id', $direction);
    }

    private function rules(?Member $member = null): array
    {
        return [
            'member_no'    => ['required', 'string', 'max:50', Rule::unique('members', 'member_no')->ignore($member?->id)],
            'name'         => ['required', 'string', 'max:150'],
            'phone'        => ['required', 'string', 'max:30'],
            'nik'          => ['nullable', 'string', 'max:20'],
            'address'      => ['required', 'string', 'max:500'],
            'kelurahan'    => ['required', 'string', 'max:100'],
            'kecamatan'    => ['required', 'string', 'max:100'],
            'occupation'   => ['nullable', 'string', 'max:100'],
            'radius_value' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'radius_unit'  => ['required_with:radius_value', Rule::in(['km', 'm'])],
            'area_type'    => ['required', Rule::in(array_column(AreaType::cases(), 'value'))],
        ];
    }

    private function labels(): array
    {
        return [
            'member_no' => 'No member', 'name' => 'Nama', 'phone' => 'No HP', 'nik' => 'No KTP',
            'address' => 'Alamat', 'kelurahan' => 'Kelurahan', 'kecamatan' => 'Kecamatan',
            'occupation' => 'Pekerjaan', 'radius_value' => 'Radius', 'radius_unit' => 'Satuan radius',
            'area_type' => 'Keterangan wilayah',
        ];
    }

    /** Isi form jadi kolom database (radius selalu disimpan dalam meter). */
    private function payload(array $data): array
    {
        $radius = null;

        if (($data['radius_value'] ?? '') !== '' && $data['radius_value'] !== null) {
            $radius = (int) round(
                (float) $data['radius_value'] * ($data['radius_unit'] === 'km' ? 1000 : 1)
            );
        }

        return [
            'name'       => $data['name'],
            'phone'      => $data['phone'],
            'nik'        => $data['nik'] ?? null,
            'address'    => $data['address'],
            'kelurahan'  => $data['kelurahan'],
            'kecamatan'  => $data['kecamatan'],
            'occupation' => $data['occupation'] ?? null,
            'radius_m'   => $radius,
            'area_type'  => $data['area_type'],
        ];
    }

    /** Cari di semua kolom. Beberapa kata = semuanya harus cocok (di kolom mana pun). */
    private function applySearch($query, string $search): void
    {
        $columns = ['member_no', 'name', 'address', 'kelurahan', 'kecamatan',
                    'nik', 'phone', 'occupation', 'created_by_name'];

        foreach (preg_split('/\s+/', $search) as $word) {
            $like = '%' . addcslashes($word, '%_\\') . '%';

            $phoneLike = null;
            if (preg_match('/^[0-9+\-]+$/', $word)) {
                $normalized = Member::normalizePhone($word);
                $phoneLike  = $normalized !== null ? '%' . addcslashes($normalized, '%_\\') . '%' : null;
            }

            $query->where(function ($q) use ($columns, $like, $phoneLike) {
                foreach ($columns as $column) {
                    $q->orWhere($column, 'like', $like);
                }

                if ($phoneLike) {
                    $q->orWhere('phone_search', 'like', $phoneLike);
                }
            });
        }
    }

    private function cleanDate(?string $value): string
    {
        if (! $value) {
            return '';
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value)->toDateString();
        } catch (\Throwable) {
            return '';
        }
    }
}