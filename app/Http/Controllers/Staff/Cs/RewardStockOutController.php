<?php

// app/Http/Controllers/Staff/Cs/RewardStockOutController.php
namespace App\Http\Controllers\Staff\Cs;

use App\Exports\RewardStockOutsExport;
use App\Http\Controllers\Concerns\CleansFilters;
use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\Reward;
use App\Models\RewardStockOut;
use App\Services\RewardStockService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class RewardStockOutController extends Controller
{
    use CleansFilters;

    public function __construct(private RewardStockService $stock) {}

    public function index(Request $request)
    {
        $f       = $this->filters($request);
        $perPage = $this->resolvePerPage($request);

        return view('staff.cs.reward.stock-outs', [
            'entries'        => $this->query($f)->paginate($perPage)->withQueryString(),
            'search'         => $f['search'],
            'perPage'        => $perPage,
            'perPageOptions' => $this->perPageOptions(),
            'sort'           => $f['sort'],
            'today'          => today()->toDateString(),
            'filters' => ['brand' => $f['brand'], 'type' => $f['type'], 'cs' => $f['cs'], 'from' => $f['from'], 'to' => $f['to']],
            'rewards' => Reward::orderBy('brand')->orderBy('type')->get(),
            'brands'  => Reward::distinct()->orderBy('brand')->pluck('brand'),
            'types'   => Reward::distinct()->orderBy('type')->pluck('type'),
            'rewards'        => Reward::orderBy('brand')->orderBy('type')->get(),
            'csNames'        => RewardStockOut::distinct()->orderBy('created_by_name')->pluck('created_by_name'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'given_on'       => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'receipt_no'     => ['required', 'string', 'max:50'],   // intentionally not unique
            'member_no'      => ['nullable', 'string', 'max:50'],
            'member_name'    => ['required', 'string', 'max:150'],
            'phone'          => ['nullable', 'string', 'max:30'],
            'purchase_total' => ['required', 'integer', 'min:0', 'max:9999999999'],
            'reward_id'      => ['required', 'exists:rewards,id'],
            'quantity'       => ['required', 'integer', 'min:1', 'max:1000000'],
            'notes'          => ['nullable', 'string', 'max:255'],
        ], [], [
            'given_on' => 'Tanggal', 'receipt_no' => 'No struk', 'member_no' => 'No member',
            'member_name' => 'Nama', 'phone' => 'No HP', 'purchase_total' => 'Total belanja',
            'reward_id' => 'Hadiah', 'quantity' => 'Jumlah', 'notes' => 'Keterangan',
        ]);

        $this->stock->release($data, $request->user());

        return back()->with('success', 'Pengeluaran hadiah berhasil dicatat.');
    }

    /** Used by the form to auto-fill name and phone from the member data. */
    public function memberLookup(Request $request)
    {
        $member = Member::where('member_no', trim((string) $request->query('no')))
            ->first(['id', 'name', 'phone']);

        return response()->json($member
            ? ['found' => true, 'name' => $member->name, 'phone' => $member->phone]
            : ['found' => false]);
    }

    public function export(Request $request)
    {
        $query = $this->query($this->filters($request));
        $count = (clone $query)->count();

        $response = Excel::download(new RewardStockOutsExport($query), 'pengeluaran-hadiah-' . today()->format('Y-m-d') . '.xlsx');
        $response->headers->set('X-Export-Count', (string) $count);

        return $response;
    }

    private function filters(Request $request): array
    {
        return [
            'search' => trim((string) $request->query('q')),
            'brand'  => trim((string) $request->query('brand')),
            'type'   => trim((string) $request->query('type')),
            'cs'     => trim((string) $request->query('cs')),
            'from'   => $this->cleanDate($request->query('from')),
            'to'     => $this->cleanDate($request->query('to')),
            'sort'   => $request->query('sort') === 'oldest' ? 'oldest' : 'newest',
        ];
    }

    private function query(array $f): Builder
    {
        $direction = $f['sort'] === 'oldest' ? 'asc' : 'desc';

        return RewardStockOut::query()->with('reward')
            ->when($f['search'] !== '', fn ($q) => $this->applySearch($q, $f['search']))
            ->when($f['brand'] !== '' || $f['type'] !== '', fn ($q) => $q->whereHas(
                'reward', fn ($r) => $r->matching($f['brand'], $f['type'])
            ))
            ->when($f['cs'] !== '', fn ($q) => $q->where('created_by_name', $f['cs']))
            ->when($f['from'] !== '', fn ($q) => $q->whereDate('given_on', '>=', $f['from']))
            ->when($f['to'] !== '', fn ($q) => $q->whereDate('given_on', '<=', $f['to']))
            ->orderBy('given_on', $direction)
            ->orderBy('id', $direction);
    }

    private function applySearch(Builder $query, string $search): void
    {
        $columns = ['receipt_no', 'member_no', 'member_name', 'phone', 'notes', 'created_by_name'];

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

                $q->orWhereHas('reward', fn ($r) => $r->where('brand', 'like', $like)->orWhere('type', 'like', $like));
            });
        }
    }
}

