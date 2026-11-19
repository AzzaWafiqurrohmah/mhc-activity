<?php

// app/Http/Controllers/Staff/Cs/RewardStockInController.php
namespace App\Http\Controllers\Staff\Cs;

use App\Exports\RewardStockInsExport;
use App\Http\Controllers\Concerns\CleansFilters;
use App\Http\Controllers\Controller;
use App\Models\Reward;
use App\Models\RewardStockIn;
use App\Services\RewardStockService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class RewardStockInController extends Controller
{
    use CleansFilters;

    public function __construct(private RewardStockService $stock) {}

    public function index(Request $request)
    {
        $f       = $this->filters($request);
        $perPage = $this->resolvePerPage($request);

        return view('staff.cs.reward.stock-ins', [
            'entries'        => $this->query($f)->paginate($perPage)->withQueryString(),
            'search'         => $f['search'],
            'perPage'        => $perPage,
            'perPageOptions' => $this->perPageOptions(),
            'sort'           => $f['sort'],
            'filters' => ['brand' => $f['brand'], 'type' => $f['type'], 'from' => $f['from'], 'to' => $f['to']],
            'brands'  => Reward::distinct()->orderBy('brand')->pluck('brand'),
            'types'   => Reward::distinct()->orderBy('type')->pluck('type'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'reward_id'   => ['required', 'exists:rewards,id'],
            'received_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'quantity'    => ['required', 'integer', 'not_in:0', 'between:-1000000,1000000'],
            'notes'       => ['nullable', 'string', 'max:255',
                              Rule::requiredIf(fn () => (int) $request->input('quantity') < 0)],
        ], [
            'notes.required' => 'Pengurangan stok wajib diisi keterangannya.',
            'quantity.not_in' => 'Jumlah tidak boleh 0.',
        ], [
            'reward_id' => 'Hadiah', 'received_on' => 'Tanggal', 'quantity' => 'Jumlah', 'notes' => 'Keterangan',
        ]);

        $this->stock->receive(
            (int) $data['reward_id'], $data['received_on'], (int) $data['quantity'],
            $data['notes'] ?? null, $request->user(),
        );

        return back()->with('success', $data['quantity'] > 0 ? 'Stok berhasil ditambahkan.' : 'Koreksi stok berhasil dicatat.');
    }

    public function export(Request $request)
    {
        $query = $this->query($this->filters($request));
        $count = (clone $query)->count();

        $response = Excel::download(new RewardStockInsExport($query), 'riwayat-masuk-' . today()->format('Y-m-d') . '.xlsx');
        $response->headers->set('X-Export-Count', (string) $count);

        return $response;
    }

    private function filters(Request $request): array
    {
        return [
            'search' => trim((string) $request->query('q')),
            'brand'  => trim((string) $request->query('brand')),
            'type'   => trim((string) $request->query('type')),
            'from'   => $this->cleanDate($request->query('from')),
            'to'     => $this->cleanDate($request->query('to')),
            'sort'   => $request->query('sort') === 'oldest' ? 'oldest' : 'newest',
        ];
    }

    private function query(array $f): Builder
    {
        $direction = $f['sort'] === 'oldest' ? 'asc' : 'desc';

        return RewardStockIn::query()->with('reward')
            ->when($f['search'] !== '', fn ($q) => $this->applySearch($q, $f['search']))
            ->when($f['brand'] !== '' || $f['type'] !== '', fn ($q) => $q->whereHas(
                'reward', fn ($r) => $r->matching($f['brand'], $f['type'])
            ))
            ->when($f['from'] !== '', fn ($q) => $q->whereDate('received_on', '>=', $f['from']))
            ->when($f['to'] !== '', fn ($q) => $q->whereDate('received_on', '<=', $f['to']))
            ->orderBy('received_on', $direction)
            ->orderBy('id', $direction);
    }

    private function applySearch(Builder $query, string $search): void
    {
        foreach (preg_split('/\s+/', $search) as $word) {
            $like = '%' . addcslashes($word, '%_\\') . '%';

            $query->where(fn ($q) => $q
                ->where('notes', 'like', $like)
                ->orWhere('created_by_name', 'like', $like)
                ->orWhereHas('reward', fn ($r) => $r->where('brand', 'like', $like)->orWhere('type', 'like', $like)));
        }
    }
}