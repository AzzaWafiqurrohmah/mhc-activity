@extends('layouts.app')

@section('title', $user->name)

@section('content')
    @php
        $score = $detail['score'];
        $tone  = $score === null ? 'text-slate-400'
               : ($score >= 90 ? 'text-emerald-600' : ($score >= 70 ? 'text-amber-600' : 'text-red-600'));

        $cell = [
            'present' => 'bg-emerald-100 text-emerald-800',
            'off'     => 'bg-slate-200 text-slate-600',
            'empty'   => 'bg-red-100 font-semibold text-red-700',
            'na'      => 'bg-slate-50 text-slate-400',
        ];
        $stateLabel = ['present' => 'Masuk', 'off' => 'Off', 'empty' => 'Kosong', 'na' => 'Tidak dihitung'];
    @endphp

    {{-- Judul --}}
    <div class="mb-6">
        <a href="{{ route('sm.recap.index', ['from' => $from, 'to' => $to]) }}"
           class="text-sm text-slate-500 hover:text-slate-800">&larr; Kembali ke Rekap Nilai</a>

        <div class="mt-2 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold">{{ $user->name }}</h1>
                <p class="text-sm text-slate-500">
                    {{ $user->employee_code }} · {{ $user->division?->name ?? '-' }}
                </p>
            </div>
        </div>
    </div>

    {{-- Pemilih periode --}}
    <div class="card mb-4 flex flex-wrap items-center gap-2 p-4">
        <span class="mr-1 text-sm text-slate-500">Periode:</span>

        @foreach ($presets as $p)
            <a href="{{ route('sm.recap.show', ['user' => $user, 'from' => $p['from'], 'to' => $p['to']]) }}"
               class="btn btn-sm {{ $from === $p['from'] && $to === $p['to'] ? 'btn-brand' : 'btn-outline' }}">
                {{ $p['label'] }}
            </a>
        @endforeach

        <form method="GET" action="{{ route('sm.recap.show', $user) }}"
              class="ml-auto flex items-center gap-2 text-sm text-slate-500">
            <input type="date" name="from" value="{{ $from }}" onchange="this.form.submit()" class="field py-1.5">
            <span>s/d</span>
            <input type="date" name="to" value="{{ $to }}" max="{{ today()->toDateString() }}"
                   onchange="this.form.submit()" class="field py-1.5">
        </form>
    </div>

    {{-- Kartu ringkasan --}}
    <div class="mb-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <div class="card p-4">
            <p class="text-xs uppercase tracking-wide text-slate-500">Nilai</p>
            <p class="mt-1 text-2xl font-semibold {{ $tone }}">
                {{ $score === null ? '-' : number_format($score, 1, ',', '.') . '%' }}
            </p>
        </div>
        <div class="card p-4">
            <p class="text-xs uppercase tracking-wide text-slate-500">Hari wajib</p>
            <p class="mt-1 text-2xl font-semibold">{{ $detail['working'] }}</p>
        </div>
        <div class="card p-4">
            <p class="text-xs uppercase tracking-wide text-slate-500">Masuk</p>
            <p class="mt-1 text-2xl font-semibold text-emerald-600">{{ $detail['present'] }}</p>
        </div>
        <div class="card p-4">
            <p class="text-xs uppercase tracking-wide text-slate-500">Off</p>
            <p class="mt-1 text-2xl font-semibold text-slate-600">{{ $detail['off'] }}</p>
        </div>
        <div class="card p-4">
            <p class="text-xs uppercase tracking-wide text-slate-500">Kosong</p>
            <p class="mt-1 text-2xl font-semibold {{ $detail['empty'] > 0 ? 'text-red-600' : 'text-slate-400' }}">
                {{ $detail['empty'] }}
            </p>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-3">

        {{-- Kalender --}}
        <div class="card p-5 lg:col-span-2">
            <h2 class="mb-4 font-semibold">Kalender kehadiran input</h2>

            @foreach ($months as $m)
                <div class="mb-6 last:mb-0">
                    <p class="mb-2 text-sm font-semibold">{{ $m['label'] }}</p>

                    <div class="grid grid-cols-7 gap-1 text-center text-xs">
                        @foreach (['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $h)
                            <div class="py-1 text-slate-400">{{ $h }}</div>
                        @endforeach

                        @for ($i = 0; $i < $m['offset']; $i++)
                            <div></div>
                        @endfor

                        @for ($n = 1; $n <= $m['length']; $n++)
                            @php
                                $date  = $m['first']->copy()->day($n)->toDateString();
                                $state = $detail['days'][$date] ?? 'out';
                            @endphp

                            <div class="rounded-md py-2
                                        {{ $state === 'out' ? 'text-slate-300' : $cell[$state] }}
                                        {{ $date === today()->toDateString() ? 'ring-2 ring-[var(--brand)]' : '' }}"
                                 @if ($state !== 'out') title="{{ $stateLabel[$state] }}" @endif>
                                {{ $n }}
                            </div>
                        @endfor
                    </div>
                </div>
            @endforeach

            <div class="mt-5 flex flex-wrap gap-4 border-t border-slate-100 pt-4 text-xs text-slate-500">
                <span class="flex items-center gap-1.5"><i class="h-3 w-3 rounded bg-emerald-100"></i> Masuk</span>
                <span class="flex items-center gap-1.5"><i class="h-3 w-3 rounded bg-slate-200"></i> Off</span>
                <span class="flex items-center gap-1.5"><i class="h-3 w-3 rounded bg-red-100"></i> Kosong</span>
                <span class="flex items-center gap-1.5"><i class="h-3 w-3 rounded border border-slate-200 bg-slate-50"></i> Tidak dihitung (hari ini, belum aktif, atau di luar periode)</span>
            </div>
        </div>

        {{-- Tugas paling sering --}}
        <div class="card p-5">
            <h2 class="font-semibold">Tugas paling sering</h2>
            <p class="mb-4 text-xs text-slate-500">Berapa hari tugas itu dicentang, dari {{ $detail['present'] }} hari masuk.</p>

            @forelse ($tasks as $task)
                @php
                    $pct = $detail['present'] > 0 ? min(100, round($task->total / $detail['present'] * 100)) : 0;
                    $isRoutine = $task->type === \App\TaskType::Routine->value;
                @endphp

                <div class="mb-4 last:mb-0">
                    <div class="mb-1 flex items-center justify-between gap-2 text-sm">
                        <span class="font-medium">{{ $task->name }}</span>
                        <span class="shrink-0 text-slate-500">{{ $task->total }} hari · {{ $pct }}%</span>
                    </div>
                    <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full {{ $isRoutine ? 'bg-[var(--brand)]' : 'bg-amber-500' }}"
                             style="width: {{ $pct }}%"></div>
                    </div>
                    <span class="mt-1 inline-block badge {{ $isRoutine ? 'badge-blue' : 'badge-amber' }}">
                        {{ $isRoutine ? 'Rutin' : 'Insidental' }}
                    </span>
                </div>
            @empty
                <p class="py-6 text-center text-sm text-slate-500">Belum ada tugas yang dicentang pada periode ini.</p>
            @endforelse

            @if ($otherCount > 0)
                <p class="mt-4 border-t border-slate-100 pt-3 text-xs text-slate-500">
                    Mengisi "Lainnya" sebanyak {{ $otherCount }} kali. Isinya bisa dilihat di Log Aktivitas.
                </p>
            @endif
        </div>
    </div>

    {{-- Daftar tanggal --}}
    <div class="mt-4 grid gap-4 md:grid-cols-2">
        <div class="card p-5">
            <h2 class="mb-3 font-semibold">Tanggal off <span class="font-normal text-slate-500">({{ $offDates->count() }})</span></h2>
            @if ($offDates->isEmpty())
                <p class="text-sm text-slate-500">Tidak ada.</p>
            @else
                <div class="flex flex-wrap gap-1.5">
                    @foreach ($offDates as $d)
                        <span class="badge badge-gray">{{ $d }}</span>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="card p-5">
            <h2 class="mb-3 font-semibold">Tanggal tidak mengisi <span class="font-normal text-slate-500">({{ $emptyDates->count() }})</span></h2>
            @if ($emptyDates->isEmpty())
                <p class="text-sm text-slate-500">Tidak ada, semua hari wajib sudah terisi.</p>
            @else
                <div class="flex flex-wrap gap-1.5">
                    @foreach ($emptyDates as $d)
                        <span class="badge badge-red">{{ $d }}</span>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endsection