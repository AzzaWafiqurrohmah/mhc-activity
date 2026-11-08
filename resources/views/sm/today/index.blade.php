@extends('layouts.app')

@section('title', 'Hari Ini')

@section('content')
<div @if ($isToday) x-data="autoRefresh({ url: @js(route('sm.dashboard')) })" @endif>

    {{-- Judul + pemilih tanggal --}}
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold">
                {{ $isToday ? 'Hari Ini' : 'Kondisi Harian' }}
            </h1>
            <p class="text-sm text-slate-500">{{ $date->translatedFormat('l, d F Y') }}</p>
            @if ($isToday)
                <p class="mt-1 text-xs text-slate-400">
                    Diperbarui otomatis tiap 1 menit · terakhir <span x-text="updated"></span>
                </p>
            @endif
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('sm.dashboard', ['date' => $prev]) }}" class="btn btn-outline btn-sm" aria-label="Hari sebelumnya">&lsaquo;</a>

            <input type="date" value="{{ $date->toDateString() }}" max="{{ today()->toDateString() }}"
                   onchange="if (this.value) window.location = '{{ route('sm.dashboard') }}?date=' + this.value"
                   class="field py-1.5">

            @if ($isToday)
                <span class="btn btn-outline btn-sm pointer-events-none opacity-40" aria-label="Hari berikutnya">&rsaquo;</span>
            @else
                <a href="{{ route('sm.dashboard', ['date' => $next]) }}" class="btn btn-outline btn-sm" aria-label="Hari berikutnya">&rsaquo;</a>
                <a href="{{ route('sm.dashboard') }}" class="btn btn-brand btn-sm">Hari ini</a>
            @endif
        </div>
    </div>

    <div id="today-area">
        @php
            $emptyLabel = $isToday ? 'Belum isi' : 'Kosong';
            $filled     = $summary['present'] + $summary['off'];
            $pct        = $summary['total'] > 0 ? round($filled / $summary['total'] * 100) : 0;
        @endphp

        {{-- Kartu ringkasan --}}
        <div class="mb-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="card p-4">
                <p class="text-xs uppercase tracking-wide text-slate-500">Wajib isi</p>
                <p class="mt-1 text-2xl font-semibold">{{ $summary['total'] }}</p>
            </div>
            <div class="card p-4">
                <p class="text-xs uppercase tracking-wide text-slate-500">Masuk</p>
                <p class="mt-1 text-2xl font-semibold text-emerald-600">{{ $summary['present'] }}</p>
            </div>
            <div class="card p-4">
                <p class="text-xs uppercase tracking-wide text-slate-500">Off</p>
                <p class="mt-1 text-2xl font-semibold text-slate-600">{{ $summary['off'] }}</p>
            </div>
            <div class="card p-4">
                <p class="text-xs uppercase tracking-wide text-slate-500">{{ $emptyLabel }}</p>
                <p class="mt-1 text-2xl font-semibold {{ $summary['empty'] > 0 ? ($isToday ? 'text-amber-600' : 'text-red-600') : 'text-slate-400' }}">
                    {{ $summary['empty'] }}
                </p>
            </div>
        </div>

        {{-- Progres keseluruhan --}}
        <div class="card mb-6 p-4">
            <div class="mb-2 flex items-center justify-between text-sm">
                <span class="text-slate-600">{{ $filled }} dari {{ $summary['total'] }} karyawan sudah mengisi</span>
                <span class="font-medium">{{ $pct }}%</span>
            </div>
            <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                <div class="h-full bg-emerald-500" style="width: {{ $pct }}%"></div>
            </div>
        </div>

        {{-- Per divisi --}}
        @forelse ($groups as $divisionName => $items)
            @php
                $divFilled = $items->whereIn('state', ['present', 'off'])->count();
                $divTotal  = $items->count();
            @endphp

            <div class="card mb-4 overflow-hidden">
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-3">
                    <h2 class="font-semibold">{{ $divisionName }}</h2>
                    <div class="flex items-center gap-3 text-sm text-slate-500">
                        <span>{{ $divFilled }}/{{ $divTotal }} sudah isi</span>
                        <div class="h-1.5 w-24 overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full bg-emerald-500" style="width: {{ round($divFilled / $divTotal * 100) }}%"></div>
                        </div>
                    </div>
                </div>

                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-2.5">Karyawan</th>
                            <th class="px-5 py-2.5">Status</th>
                            <th class="px-5 py-2.5">Diisi</th>
                            <th class="px-5 py-2.5">Tugas dikerjakan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($items as $item)
                            @php $log = $item['log']; @endphp

                            <tr class="align-top {{ $item['state'] === 'empty' && ! $isToday ? 'bg-red-50/50' : '' }}">
                                <td class="px-5 py-3">
                                    <p class="font-medium">{{ $item['user']->name }}</p>
                                    <p class="text-xs text-slate-500">{{ $item['user']->employee_code }}</p>
                                </td>

                                <td class="whitespace-nowrap px-5 py-3">
                                    @if ($item['state'] === 'present')
                                        <span class="badge badge-green">Masuk</span>
                                        <span class="ml-1 text-xs text-slate-500">{{ $log->shift?->label() }}</span>
                                    @elseif ($item['state'] === 'off')
                                        <span class="badge badge-gray">Off</span>
                                    @else
                                        <span class="badge {{ $isToday ? 'badge-amber' : 'badge-red' }}">{{ $emptyLabel }}</span>
                                    @endif
                                </td>

                                <td class="whitespace-nowrap px-5 py-3 text-slate-500">
                                    {{ $log ? $log->created_at->format('H:i') : '-' }}
                                </td>

                                <td class="px-5 py-3">
                                    @if (! $log || ($log->tasks->isEmpty() && ! $log->other_note))
                                        <span class="text-slate-400">-</span>
                                    @else
                                        <div class="flex flex-wrap gap-1.5">
                                            @foreach ($log->tasks as $task)
                                                <span class="badge {{ $task->type === \App\TaskType::Routine ? 'badge-blue' : 'badge-amber' }}">
                                                    {{ $task->name }}
                                                </span>
                                            @endforeach
                                        </div>

                                        @if ($log->other_note)
                                            <p class="mt-1.5 text-xs italic text-slate-500">Lainnya: {{ $log->other_note }}</p>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @empty
            <div class="card p-10 text-center text-slate-500">
                Tidak ada karyawan yang wajib mengisi pada tanggal ini.
            </div>
        @endforelse
    </div>
</div>
@endsection