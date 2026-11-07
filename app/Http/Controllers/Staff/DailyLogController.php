<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\LogStatus;
use App\Models\DailyLog;
use App\Models\Task;
use App\Models\User;
use App\Shift;
use Carbon\CarbonPeriod;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DailyLogController extends Controller
{
    private const EDIT_WINDOW_MINUTES = 15;
    private const HISTORY_DAYS = 30;

    /** Menu "Input Harian" */
    public function index(Request $request)
    {
        $user = $request->user();
        abort_if(! $user->division_id, 403, 'Akun belum punya divisi. Hubungi admin.');

        $today = today();
        $log   = $this->todayLog($user);

        // sisa waktu edit dihitung server, supaya hitung mundur di browser tidak bergantung jam perangkat
        $secondsLeft = $log
            ? max(0, $log->created_at->copy()->addMinutes(self::EDIT_WINDOW_MINUTES)->timestamp - now()->timestamp)
            : 0;

        $tasks = Task::where('division_id', $user->division_id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'type']);

        [$filledDays, $totalDays] = $this->monthStats($user, $today, $log !== null);

        return view('staff.dashboard', [
            'today'       => $today,
            'log'         => $log,
            'canEdit'     => $log !== null && $secondsLeft > 0,
            'secondsLeft' => $secondsLeft,
            'tasks'       => $tasks,
            'filledDays'  => $filledDays,
            'totalDays'   => $totalDays,
        ]);
    }

    /** Menu "Riwayat" */
    public function history(Request $request)
    {
        $user = $request->user();
        abort_if(! $user->division_id, 403, 'Akun belum punya divisi. Hubungi admin.');

        $today    = today();
        $hasToday = DailyLog::where('user_id', $user->id)
            ->where('log_date', $today->toDateString())
            ->exists();

        [$filledDays, $totalDays] = $this->monthStats($user, $today, $hasToday);

        return view('staff.history', [
            'today'      => $today,
            'filledDays' => $filledDays,
            'totalDays'  => $totalDays,
            'history'    => $this->historyRows($user, $today),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        abort_if(! $user->division_id, 403, 'Akun belum punya divisi. Hubungi admin.');

        if ($this->todayLog($user)) {
            return back()->withErrors(['alert' => 'Input hari ini sudah ada.']);
        }

        $data = $this->validated($request, $user);

        try {
            DB::transaction(function () use ($user, $data) {
                $log = DailyLog::create([
                    'user_id'     => $user->id,
                    'division_id' => $user->division_id, // snapshot divisi saat input
                    'log_date'    => today()->toDateString(),
                    'status'      => $data['status'],
                    'shift'       => $data['shift'],
                    'other_note'  => $data['other_note'],
                ]);

                $log->tasks()->sync($data['task_ids']);
            });
        } catch (UniqueConstraintViolationException) {
            // dobel klik / dua tab
            return back()->withErrors(['alert' => 'Input hari ini sudah ada.']);
        }

        return back()->with('saved', true);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $log  = $this->todayLog($user);

        if (! $log) {
            return back()->withErrors(['alert' => 'Belum ada input hari ini.']);
        }

        if (now()->gte($log->created_at->copy()->addMinutes(self::EDIT_WINDOW_MINUTES))) {
            return back()->withErrors([
                'alert' => 'Waktu edit sudah lewat (' . self::EDIT_WINDOW_MINUTES . ' menit sejak disimpan).',
            ]);
        }

        $data = $this->validated($request, $user);

        DB::transaction(function () use ($log, $data) {
            $log->update([
                'status'     => $data['status'],
                'shift'      => $data['shift'],
                'other_note' => $data['other_note'],
            ]);

            $log->tasks()->sync($data['task_ids']);
        });

        return back()->with('saved', true);
    }

    private function todayLog(User $user): ?DailyLog
    {
        return DailyLog::with('tasks:id,name,sort_order')
            ->where('user_id', $user->id)
            ->where('log_date', today()->toDateString())
            ->first();
    }

    /**
     * "Terisi bulan ini": mulai tanggal 1 (atau tanggal mulai aktif kalau lebih baru) sampai hari ini.
     * Hari ini baru masuk pembagi kalau sudah diisi, karena masih bisa diisi sampai hari berganti.
     *
     * @return array{0:int,1:int} [terisi, total]
     */
    private function monthStats(User $user, $today, bool $filledToday): array
    {
        $monthStart = $today->copy()->startOfMonth();
        $from = $user->active_from->copy()->startOfDay();
        if ($from->lt($monthStart)) {
            $from = $monthStart;
        }

        $total = $from->gt($today) ? 0 : iterator_count(CarbonPeriod::create($from, $today));
        if (! $filledToday && $total > 0) {
            $total--;
        }

        $filled = DailyLog::where('user_id', $user->id)
            ->whereBetween('log_date', [$from->toDateString(), $today->toDateString()])
            ->count();

        return [$filled, $total];
    }

    /**
     * Riwayat dari kemarin mundur 30 hari (tidak lebih lama dari tanggal mulai aktif).
     * Hari tanpa baris di daily_logs = "kosong" (null).
     */
    private function historyRows(User $user, $today): array
    {
        $yesterday = $today->copy()->subDay();

        $start = $today->copy()->subDays(self::HISTORY_DAYS);
        $activeFrom = $user->active_from->copy()->startOfDay();
        if ($start->lt($activeFrom)) {
            $start = $activeFrom;
        }

        if ($start->gt($yesterday)) {
            return [];
        }

        $logs = DailyLog::with('tasks:id,name,sort_order')
            ->where('user_id', $user->id)
            ->whereBetween('log_date', [$start->toDateString(), $yesterday->toDateString()])
            ->get()
            ->keyBy(fn (DailyLog $l) => $l->log_date->toDateString());

        $rows = [];
        for ($d = $yesterday->copy(); $d->gte($start); $d->subDay()) {
            $rows[] = ['date' => $d->copy(), 'log' => $logs->get($d->toDateString())];
        }

        return $rows;
    }

    /**
     * Validasi bersama untuk simpan & edit.
     * Kalau Off: shift, tugas, dan keterangan diabaikan sama sekali (walau dikirim manual).
     */
    private function validated(Request $request, User $user): array
    {
        $present = $request->input('status') === LogStatus::Present->value;

        $validated = $request->validate([
            'status'       => ['required', Rule::enum(LogStatus::class)],
            'shift'        => ['nullable', Rule::requiredIf($present), Rule::enum(Shift::class)],
            'task_ids'     => ['nullable', 'array'],
            'task_ids.*'   => ['integer', Rule::exists('tasks', 'id')
                                ->where('division_id', $user->division_id)
                                ->where('is_active', true)],
            'other_note'   => ['nullable', 'string', 'max:200'],
        ], [
            'shift.required'      => 'Pilih shift: Pagi atau Siang.',
            'task_ids.*.exists'   => 'Ada tugas yang tidak valid. Muat ulang halaman lalu coba lagi.',
            'other_note.max'      => 'Keterangan tambahan maksimal 200 karakter.',
        ], [
            'status'     => 'Status',
            'shift'      => 'Shift',
            'task_ids'   => 'Tugas',
            'other_note' => 'Keterangan tambahan',
        ]);

        if (! $present) {
            return [
                'status'     => LogStatus::Off->value,
                'shift'      => null,
                'other_note' => null,
                'task_ids'   => [],
            ];
        }

        $taskIds = array_values(array_unique(array_map('intval', $validated['task_ids'] ?? [])));

        $note = trim((string) ($validated['other_note'] ?? ''));
        $note = $note === '' ? null : $note;

        // mencegah simpan kosong: minimal satu tugas, atau keterangan tambahan terisi
        if ($taskIds === [] && $note === null) {
            throw ValidationException::withMessages([
                'task_ids' => 'Centang minimal satu tugas, atau isi keterangan tambahan.',
            ]);
        }

        return [
            'status'     => LogStatus::Present->value,
            'shift'      => $validated['shift'],
            'other_note' => $note,
            'task_ids'   => $taskIds,
        ];
    }
}