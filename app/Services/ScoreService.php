<?php

namespace App\Services;

use App\LogStatus;
use App\Role;
use App\Models\DailyLog;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ScoreService
{
    /**
     * Hitung nilai tiap staff untuk satu periode.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function forPeriod(Carbon $from, Carbon $to, ?int $divisionId = null, string $search = ''): Collection
    {
        $from = $from->copy()->startOfDay();

        // hari ini belum selesai, jadi batas hitung maksimal kemarin
        $cutoff = today()->subDay();
        $end    = $to->copy()->startOfDay();
        if ($end->gt($cutoff)) {
            $end = $cutoff->copy();
        }

        // staff yang masa aktifnya beririsan dengan periode
        $users = User::with('division:id,name')
            ->where('role', Role::Staff->value)
            ->where('active_from', '<=', $to->toDateString())
            ->where(fn ($q) => $q->whereNull('active_until')
                                 ->orWhere('active_until', '>=', $from->toDateString()))
            ->when($divisionId, fn ($q) => $q->where('division_id', $divisionId))
            ->when($search !== '', fn ($q) => $q->where(
                fn ($w) => $w->where('name', 'like', "%{$search}%")
                             ->orWhere('employee_code', 'like', "%{$search}%")
            ))
            ->get();

        // jumlah masuk dan off per karyawan, hanya yang jatuh di masa aktifnya
        $counts = DailyLog::query()
            ->join('users', 'users.id', '=', 'daily_logs.user_id')
            ->whereIn('daily_logs.user_id', $users->pluck('id'))
            ->whereBetween('daily_logs.log_date', [$from->toDateString(), $end->toDateString()])
            ->whereColumn('daily_logs.log_date', '>=', 'users.active_from')
            ->where(fn ($q) => $q->whereNull('users.active_until')
                                 ->orWhereColumn('daily_logs.log_date', '<=', 'users.active_until'))
            ->selectRaw("daily_logs.user_id,
                SUM(CASE WHEN daily_logs.status = 'present' THEN 1 ELSE 0 END) AS present_days,
                SUM(CASE WHEN daily_logs.status = 'off' THEN 1 ELSE 0 END) AS off_days")
            ->groupBy('daily_logs.user_id')
            ->get()
            ->keyBy('user_id');

        return $users->map(function (User $user) use ($from, $end, $counts) {
            $start  = $user->active_from->copy()->startOfDay()->max($from);
            $finish = $user->active_until
                ? $user->active_until->copy()->startOfDay()->min($end)
                : $end->copy();

            $working = $start->lte($finish) ? (int) $start->diffInDays($finish) + 1 : 0;

            $row     = $counts->get($user->id);
            $present = min((int) ($row->present_days ?? 0), $working);
            $off     = min((int) ($row->off_days ?? 0), $working - $present);
            $empty   = max(0, $working - $present - $off);

            $denominator = $working - $off;
            $score = $denominator > 0
                ? round(min(100, $present / $denominator * 100), 1)
                : null;

            return [
                'user_id'  => $user->id,
                'name'     => $user->name,
                'code'     => $user->employee_code,
                'division' => $user->division?->name ?? '-',
                'working'  => $working,
                'present'  => $present,
                'off'      => $off,
                'empty'    => $empty,
                'score'    => $score,
            ];
        })->values();
    }

    /**
 * Rincian per hari untuk satu karyawan.
 * Aturannya sama dengan forPeriod(), jadi angkanya pasti sinkron dengan Rekap.
 */
public function detailFor(User $user, Carbon $from, Carbon $to): array
{
    $from   = $from->copy()->startOfDay();
    $to     = $to->copy()->startOfDay();
    $cutoff = today()->subDay();               // hari ini belum dihitung

    $activeFrom  = $user->active_from->copy()->startOfDay();
    $activeUntil = $user->active_until?->copy()->startOfDay();

    $logs = DailyLog::where('user_id', $user->id)
        ->whereBetween('log_date', [$from->toDateString(), $to->toDateString()])
        ->get()
        ->keyBy(fn ($log) => $log->log_date->toDateString());

    $days  = [];
    $count = ['present' => 0, 'off' => 0, 'empty' => 0];

    for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
        $key = $d->toDateString();

        $counted = $d->gte($activeFrom)
            && ($activeUntil === null || $d->lte($activeUntil))
            && $d->lte($cutoff);

        if (! $counted) {
            $days[$key] = 'na';                // di luar masa aktif atau belum selesai
            continue;
        }

        $log   = $logs->get($key);
        $state = $log === null ? 'empty'
               : ($log->status === LogStatus::Off ? 'off' : 'present');

        $days[$key] = $state;
        $count[$state]++;
    }

    $working     = array_sum($count);
    $denominator = $working - $count['off'];

    return [
        'days'    => $days,
        'working' => $working,
        'present' => $count['present'],
        'off'     => $count['off'],
        'empty'   => $count['empty'],
        'score'   => $denominator > 0 ? round($count['present'] / $denominator * 100, 1) : null,
    ];
}

/** Tugas yang paling sering dicentang karyawan pada periode itu. */
public function topTasks(User $user, Carbon $from, Carbon $to, int $limit = 5): Collection
{
    return DB::table('daily_log_task')
        ->join('daily_logs', 'daily_logs.id', '=', 'daily_log_task.daily_log_id')
        ->join('tasks', 'tasks.id', '=', 'daily_log_task.task_id')
        ->where('daily_logs.user_id', $user->id)
        ->whereBetween('daily_logs.log_date', [$from->toDateString(), $to->toDateString()])
        ->groupBy('tasks.id', 'tasks.name', 'tasks.type')
        ->orderByDesc('total')
        ->orderBy('tasks.name')
        ->limit($limit)
        ->select('tasks.name', 'tasks.type', DB::raw('COUNT(*) as total'))
        ->get();
}
}