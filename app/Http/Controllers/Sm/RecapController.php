<?php

namespace App\Http\Controllers\Sm;

use App\Http\Controllers\Controller;
use App\Models\DailyLog;
use App\Models\Division;
use App\Models\User;
use App\Role;
use App\Services\ScoreService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

class RecapController extends Controller
{
    private const PER_PAGE_OPTIONS = [5, 10, 25, 50];
    private const SORTS = ['low', 'high', 'name'];

    public function index(Request $request, ScoreService $scores)
    {
        $perPage = (int) $request->query('per_page');
        $perPage = in_array($perPage, self::PER_PAGE_OPTIONS, true) ? $perPage : 10;

        $search = trim((string) $request->query('q'));

        $divisionFilter = (string) $request->query('division', '');
        $divisionFilter = ctype_digit($divisionFilter) ? $divisionFilter : '';

        $sort = in_array($request->query('sort'), self::SORTS, true) ? $request->query('sort') : 'low';

        // default periode: bulan ini
        $from = $this->parseDate($request->query('from'), today()->startOfMonth());
        $to   = $this->parseDate($request->query('to'), today());

        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }
        if ($to->gt(today())) {
            $to = today();
        }
        if ($from->gt($to)) {
            $from = $to->copy();
        }

        $rows = $scores->forPeriod(
            $from, $to,
            $divisionFilter !== '' ? (int) $divisionFilter : null,
            $search,
        );

        // ringkasan (dihitung dari semua baris, bukan hanya halaman aktif)
        $scored = $rows->whereNotNull('score');
        $summary = [
            'count'   => $rows->count(),
            'average' => $scored->isEmpty() ? null : round($scored->avg('score'), 1),
            'empty'   => $rows->sum('empty'),
        ];

        // urutkan, lalu paginasi manual (nilai dihitung di PHP, bukan di SQL)
        $key = fn (array $r) => match ($sort) {
            'high'  => [-($r['score'] ?? -1), $r['name']],
            'name'  => [$r['name']],
            default => [$r['score'] ?? 101, $r['name']],
        };

        $rows = $rows->sort(fn ($a, $b) => $key($a) <=> $key($b))->values();

        $lastPage = max(1, (int) ceil($rows->count() / $perPage));
        $page     = min(LengthAwarePaginator::resolveCurrentPage(), $lastPage);

        $paginator = new LengthAwarePaginator(
            $rows->forPage($page, $perPage)->values(),
            $rows->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        return view('sm.recap.index', [
            'rows'           => $paginator,
            'summary'        => $summary,
            'search'         => $search,
            'perPage'        => $perPage,
            'perPageOptions' => self::PER_PAGE_OPTIONS,
            'divisionFilter' => $divisionFilter,
            'sort'           => $sort,
            'from'           => $from->toDateString(),
            'to'             => $to->toDateString(),
            'divisions'      => Division::orderBy('name')->get(['id', 'name']),
            'presets'        => $this->presets(),
        ]);
    }

    private function presets(): array
    {
        $lastMonth = today()->subMonthNoOverflow();

        return [
            ['label' => 'Bulan ini',
             'from' => today()->startOfMonth()->toDateString(), 'to' => today()->toDateString()],
            ['label' => 'Bulan lalu',
             'from' => $lastMonth->copy()->startOfMonth()->toDateString(),
             'to'   => $lastMonth->copy()->endOfMonth()->toDateString()],
            ['label' => '7 hari terakhir',
             'from' => today()->subDays(6)->toDateString(), 'to' => today()->toDateString()],
            ['label' => '30 hari terakhir',
             'from' => today()->subDays(29)->toDateString(), 'to' => today()->toDateString()],
        ];
    }

    private function parseDate(?string $value, Carbon $fallback): Carbon
    {
        if (! $value) {
            return $fallback->copy()->startOfDay();
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
        } catch (\Throwable) {
            return $fallback->copy()->startOfDay();
        }
    }

    public function show(Request $request, User $user, ScoreService $scores)
{
    abort_unless($user->role === Role::Staff, 404);

    $from = $this->parseDate($request->query('from'), today()->startOfMonth());
    $to   = $this->parseDate($request->query('to'), today());

    if ($from->gt($to)) {
        [$from, $to] = [$to, $from];
    }
    if ($to->gt(today())) {
        $to = today();
    }
    if ($from->gt($to)) {
        $from = $to->copy();
    }
    if ($from->lt($to->copy()->subDays(91))) {      // maksimal 92 hari
        $from = $to->copy()->subDays(91);
    }

    $user->load('division:id,name');

    $detail = $scores->detailFor($user, $from, $to);
    $tasks  = $scores->topTasks($user, $from, $to);

    $otherCount = DailyLog::where('user_id', $user->id)
        ->whereBetween('log_date', [$from->toDateString(), $to->toDateString()])
        ->whereNotNull('other_note')
        ->count();

    // data kerangka kalender per bulan
    $months = [];
    $cursor = $from->copy()->startOfMonth();
    while ($cursor->lte($to)) {
        $months[] = [
            'first'  => $cursor->copy(),
            'label'  => $cursor->translatedFormat('F Y'),
            'offset' => $cursor->dayOfWeekIso - 1,      // kalender mulai hari Senin
            'length' => $cursor->daysInMonth,
        ];
        $cursor->addMonthNoOverflow()->startOfMonth();
    }

    $datesOf = fn (string $state) => collect($detail['days'])
        ->filter(fn ($s) => $s === $state)
        ->keys()
        ->map(fn ($d) => Carbon::parse($d)->translatedFormat('D, d M'))
        ->values();

    return view('sm.recap.show', [
        'user'       => $user,
        'from'       => $from->toDateString(),
        'to'         => $to->toDateString(),
        'detail'     => $detail,
        'tasks'      => $tasks,
        'otherCount' => $otherCount,
        'months'     => $months,
        'offDates'   => $datesOf('off'),
        'emptyDates' => $datesOf('empty'),
        'presets'    => $this->presets(),
    ]);
}
}