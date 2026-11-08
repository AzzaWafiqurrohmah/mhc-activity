<?php

namespace App\Http\Controllers\Sm;

use App\LogStatus;
use App\Role;
use App\Http\Controllers\Controller;
use App\Models\DailyLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

class TodayController extends Controller
{
    private const PER_PAGE_OPTIONS = [10, 25, 50];
    private const STATUSES = ['empty', 'present', 'off', 'all'];

    public function index(Request $request)
    {
        $date    = $this->parseDate($request->query('date'));
        $isToday = $date->isToday();

        $perPage = (int) $request->query('per_page');
        $perPage = in_array($perPage, self::PER_PAGE_OPTIONS, true) ? $perPage : 25;

        $search = trim((string) $request->query('q'));

        $divisionFilter = (string) $request->query('division', '');
        $divisionFilter = ctype_digit($divisionFilter) ? $divisionFilter : '';

        $status = in_array($request->query('status'), self::STATUSES, true)
            ? $request->query('status')
            : 'empty';                                   // default: yang belum isi

        $logs = DailyLog::with([
                'division:id,name',
                'tasks' => fn ($q) => $q->orderBy('sort_order'),
            ])
            ->where('log_date', $date->toDateString())
            ->get()
            ->keyBy('user_id');

        // karyawan yang wajib isi pada tanggal itu, plus siapa pun yang sudah punya input
        $users = User::with('division:id,name')
            ->where('role', Role::Staff->value)
            ->where(function ($q) use ($date, $isToday, $logs) {
                $q->where(function ($w) use ($date, $isToday) {
                    $w->where('active_from', '<=', $date->toDateString())
                      ->where(fn ($x) => $x->whereNull('active_until')
                                           ->orWhere('active_until', '>=', $date->toDateString()));

                    if ($isToday) {
                        $w->where('is_active', true);
                    }
                })->orWhereIn('id', $logs->keys());
            })
            ->get();

        $rows = $users->map(function (User $user) use ($logs) {
            $log = $logs->get($user->id);

            return [
                'user'        => $user,
                'log'         => $log,
                'state'       => $log === null ? 'empty'
                                 : ($log->status === LogStatus::Off ? 'off' : 'present'),
                'division_id' => $log?->division_id ?? $user->division_id,
                'division'    => $log?->division?->name ?? $user->division?->name ?? '-',
            ];
        });

        // ringkasan keseluruhan (tidak terpengaruh filter)
        $summary = [
            'total'   => $rows->count(),
            'present' => $rows->where('state', 'present')->count(),
            'off'     => $rows->where('state', 'off')->count(),
            'empty'   => $rows->where('state', 'empty')->count(),
        ];

        // daftar divisi di kiri: yang paling banyak belum isi di atas
        $divisions = $rows->groupBy('division_id')
            ->map(fn ($items) => [
                'id'     => $items->first()['division_id'],
                'name'   => $items->first()['division'],
                'total'  => $items->count(),
                'filled' => $items->whereIn('state', ['present', 'off'])->count(),
                'empty'  => $items->where('state', 'empty')->count(),
            ])
            ->sort(fn ($a, $b) => [-$a['empty'], $a['name']] <=> [-$b['empty'], $b['name']])
            ->values();

        // cakupan filter (divisi + cari), dipakai untuk angka di tab
        $scoped = $rows
            ->when($divisionFilter !== '', fn ($c) => $c->filter(
                fn ($r) => (string) $r['division_id'] === $divisionFilter
            ))
            ->when($search !== '', fn ($c) => $c->filter(
                fn ($r) => mb_stripos($r['user']->name, $search) !== false
                        || mb_stripos($r['user']->employee_code, $search) !== false
            ));

        $counts = [
            'empty'   => $scoped->where('state', 'empty')->count(),
            'present' => $scoped->where('state', 'present')->count(),
            'off'     => $scoped->where('state', 'off')->count(),
            'all'     => $scoped->count(),
        ];

        $filtered = $status === 'all' ? $scoped : $scoped->where('state', $status);

        $order  = ['empty' => 0, 'present' => 1, 'off' => 2];
        $sorted = $filtered
            ->sort(fn ($a, $b) => [$order[$a['state']], $a['division'], $a['user']->name]
                              <=> [$order[$b['state']], $b['division'], $b['user']->name])
            ->values();

        $lastPage = max(1, (int) ceil($sorted->count() / $perPage));
        $page     = min(LengthAwarePaginator::resolveCurrentPage(), $lastPage);

        $paginator = new LengthAwarePaginator(
            $sorted->forPage($page, $perPage)->values(),
            $sorted->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        return view('sm.today.index', [
            'date'           => $date,
            'isToday'        => $isToday,
            'summary'        => $summary,
            'divisions'      => $divisions,
            'counts'         => $counts,
            'rows'           => $paginator,
            'search'         => $search,
            'perPage'        => $perPage,
            'perPageOptions' => self::PER_PAGE_OPTIONS,
            'divisionFilter' => $divisionFilter,
            'status'         => $status,
            'prev'           => $date->copy()->subDay()->toDateString(),
            'next'           => $date->copy()->addDay()->toDateString(),
        ]);
    }

    private function parseDate(?string $value): Carbon
    {
        if (! $value) {
            return today();
        }

        try {
            $date = Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
        } catch (\Throwable) {
            return today();
        }

        return $date->gt(today()) ? today() : $date;   // tanggal depan tidak boleh
    }
}