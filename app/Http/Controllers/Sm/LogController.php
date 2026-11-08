<?php

namespace App\Http\Controllers\Sm;

use App\Http\Controllers\Controller;
use App\Models\DailyLog;
use App\Models\Division;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class LogController extends Controller
{
    private const PER_PAGE_OPTIONS = [5, 10, 25, 50];

    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page');
        $perPage = in_array($perPage, self::PER_PAGE_OPTIONS, true) ? $perPage : 10;

        $search = trim((string) $request->query('q'));

        $divisionFilter = (string) $request->query('division', '');
        $divisionFilter = ctype_digit($divisionFilter) ? $divisionFilter : '';

        // default: 7 hari terakhir (termasuk hari ini)
        $from = $this->parseDate($request->query('from'), today()->subDays(6));
        $to   = $this->parseDate($request->query('to'), today());

        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        $logs = DailyLog::with([
                'user:id,name,employee_code',
                'division:id,name',
                'tasks' => fn ($q) => $q->orderBy('sort_order'),
            ])
            ->whereBetween('log_date', [$from->toDateString(), $to->toDateString()])
            ->when($divisionFilter !== '', fn ($q) => $q->where('division_id', $divisionFilter))
            ->when($search !== '', fn ($q) => $q->whereHas('user', fn ($u) => $u->where(
                fn ($w) => $w->where('name', 'like', "%{$search}%")
                             ->orWhere('employee_code', 'like', "%{$search}%")
            )))
            ->orderByDesc('log_date')
            ->orderBy(User::select('name')->whereColumn('users.id', 'daily_logs.user_id'))
            ->paginate($perPage)
            ->withQueryString();

        return view('sm.logs.index', [
            'logs'           => $logs,
            'search'         => $search,
            'perPage'        => $perPage,
            'perPageOptions' => self::PER_PAGE_OPTIONS,
            'divisionFilter' => $divisionFilter,
            'from'           => $from->toDateString(),
            'to'             => $to->toDateString(),
            'divisions'      => Division::orderBy('name')->get(['id', 'name']),
        ]);
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
}