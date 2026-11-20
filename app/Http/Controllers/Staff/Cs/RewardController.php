<?php

// app/Http/Controllers/Staff/Cs/RewardController.php
namespace App\Http\Controllers\Staff\Cs;
use App\Http\Controllers\Concerns\CleansFilters;

use App\Http\Controllers\Controller;
use App\Models\Reward;
use App\Services\RewardStockService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\Builder;
use App\Exports\RewardsExport;
use Maatwebsite\Excel\Facades\Excel;

class RewardController extends Controller
{
    use CleansFilters;
    public function __construct(private RewardStockService $stock) {}

    public function index(Request $request)
    {
        $search  = trim((string) $request->query('q'));
        $perPage = $this->resolvePerPage($request);

        return view('staff.cs.reward.index', [
            'rewards'        => $this->query($search)->paginate($perPage)->withQueryString(),
            'search'         => $search,
            'perPage'        => $perPage,
            'perPageOptions' => $this->perPageOptions(),
            'today'          => today()->toDateString(),
            'brands'         => Reward::distinct()->orderBy('brand')->pluck('brand'),
            'summary'        => [
                'kinds'   => Reward::count(),
                'stock'   => (int) Reward::sum('stock'),
                'restock' => Reward::needsRestock()->count(),
            ],
        ]);
    }

    /** Ekspor mengikuti pencarian yang sedang aktif. */
    public function export(Request $request)
    {
        $query = $this->query(trim((string) $request->query('q')));
        $count = (clone $query)->count();

        $response = Excel::download(new RewardsExport($query), 'stok-hadiah-' . today()->format('Y-m-d') . '.xlsx');
        $response->headers->set('X-Export-Count', (string) $count);

        return $response;
    }

    private function query(string $search): Builder
    {
        return Reward::query()
            ->when($search !== '', fn ($q) => $q->search($search))
            ->orderBy('brand')
            ->orderBy('type');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'brand'         => ['required', 'string', 'max:100'],
            'type'          => ['required', 'string', 'max:100',
                                Rule::unique('rewards', 'type')->where('brand', $request->input('brand'))],
            'opening_stock' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'min_stock'     => ['required', 'integer', 'min:0', 'max:1000000'],
        ], ['type.unique' => 'Hadiah dengan brand dan jenis ini sudah ada.'], $this->labels());

        $this->stock->create(
            $data['brand'], $data['type'], (int) ($data['opening_stock'] ?? 0),
            $request->user(), (int) $data['min_stock'],
        );
        return back()->with('success', 'Hadiah baru berhasil ditambahkan.');
    }

    /** Only brand and type are editable. Stock changes go through stock-ins and stock-outs. */
    public function update(Request $request, Reward $reward)
    {
        $data = $request->validate([
            'brand' => ['required', 'string', 'max:100'],
            'min_stock' => ['required', 'integer', 'min:0', 'max:1000000'],
            'type'  => ['required', 'string', 'max:100',
                        Rule::unique('rewards', 'type')->where('brand', $request->input('brand'))->ignore($reward->id)],
        ], ['type.unique' => 'Hadiah dengan brand dan jenis ini sudah ada.'], $this->labels());

        $reward->update($data + ['updated_by' => $request->user()->id]);

        return back()->with('success', 'Data hadiah berhasil diperbarui.');
    }

    private function labels(): array
    {
        return ['brand' => 'Brand', 'type' => 'Jenis', 'opening_stock' => 'Stok awal', 'min_stock' => 'Stok minimum'];
    }
}
