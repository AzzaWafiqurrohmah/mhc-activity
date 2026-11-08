<?php

namespace App\Http\Controllers\Sm;

use App\LogStatus;
use App\Role;
use App\Http\Controllers\Controller;
use App\Models\DailyLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class TodayController extends Controller
{
    public function index(Request $request)
    {
        $date    = $this->parseDate($request->query('date'));
        $isToday = $date->isToday();

        $logs = DailyLog::with('division:id,name')
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

                    // untuk hari ini, akun yang sudah dinonaktifkan tidak bisa login, jadi tidak ditagih
                    if ($isToday) {
                        $w->where('is_active', true);
                    }
                })->orWhereIn('id', $logs->keys());
            })
            ->get();

        $rows = $users->map(function (User $user) use ($logs) {
            $log = $logs->get($user->id);

            return [
                'user'     => $user,
                'log'      => $log,
                'state'    => $log === null ? 'empty'
                              : ($log->status === LogStatus::Off ? 'off' : 'present'),
                'division' => $log?->division?->name ?? $user->division?->name ?? '-',
            ];
        });

        $order = ['empty' => 0, 'present' => 1, 'off' => 2];

        $groups = $rows
            ->sort(fn ($a, $b) => [$order[$a['state']], $a['user']->name]
                              <=> [$order[$b['state']], $b['user']->name])
            ->groupBy('division')
            ->sortKeys();

        $summary = [
            'total'   => $rows->count(),
            'present' => $rows->where('state', 'present')->count(),
            'off'     => $rows->where('state', 'off')->count(),
            'empty'   => $rows->where('state', 'empty')->count(),
        ];

        return view('sm.today.index', [
            'date'    => $date,
            'isToday' => $isToday,
            'groups'  => $groups,
            'summary' => $summary,
            'prev'    => $date->copy()->subDay()->toDateString(),
            'next'    => $date->copy()->addDay()->toDateString(),
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