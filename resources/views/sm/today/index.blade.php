@extends('layouts.app')

@section('title', 'Hari Ini')

@section('content')
<div @if ($isToday) x-data="autoRefresh({ url: @js(route('sm.dashboard')) })" @endif>

    {{-- Judul + pemilih tanggal --}}
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-[#3B2E1A]">
                {{ $isToday ? 'Hari Ini' : 'Kondisi Harian' }}
            </h1>
            <p class="mt-1 text-base font-medium text-[#6B4F2E]">{{ $date->translatedFormat('l, d F Y') }}</p>
            @if ($isToday)
                <p class="mt-1 text-[13px] text-[#7A6444]">
                    Diperbarui otomatis tiap 1 menit · terakhir <span class="font-semibold" x-text="updated"></span>
                </p>
            @endif
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('sm.dashboard', ['date' => $prev]) }}" class="btn btn-outline px-3.5" aria-label="Hari sebelumnya">&lsaquo;</a>

            <input type="date" value="{{ $date->toDateString() }}" max="{{ today()->toDateString() }}"
                   onchange="if (this.value) window.location = '{{ route('sm.dashboard') }}?date=' + this.value"
                   class="field">

            @if ($isToday)
                <span class="btn btn-outline pointer-events-none px-3.5 opacity-40" aria-label="Hari berikutnya">&rsaquo;</span>
            @else
                <a href="{{ route('sm.dashboard', ['date' => $next]) }}" class="btn btn-outline px-3.5" aria-label="Hari berikutnya">&rsaquo;</a>
                <a href="{{ route('sm.dashboard') }}" class="btn btn-brand">Hari ini</a>
            @endif
        </div>
    </div>

    <div id="today-area">
        @php
            $emptyLabel = $isToday ? 'Belum isi' : 'Kosong';
            $filled     = $summary['present'] + $summary['off'];
            $pct        = $summary['total'] > 0 ? round($filled / $summary['total'] * 100) : 0;

            // warna kartu "belum isi / kosong" mengikuti kondisi
            if ($summary['empty'] === 0) {
                $emptyCard = 'border-slate-300 bg-slate-200';
                $emptyText = 'text-slate-600';
                $emptyIcon = 'text-slate-500';
            } elseif ($isToday) {
                $emptyCard = 'border-amber-300 bg-amber-100';
                $emptyText = 'text-amber-800';
                $emptyIcon = 'text-amber-600';
            } else {
                $emptyCard = 'border-red-300 bg-red-100';
                $emptyText = 'text-red-800';
                $emptyIcon = 'text-red-600';
            }
        @endphp

        {{-- Kartu ringkasan --}}
        <div class="mb-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">

            <div class="rounded-[10px] border border-[#E2C47F] bg-[#F0D9A8] p-5 shadow-sm">
                <div class="flex items-start justify-between">
                    <p class="text-sm font-semibold text-[#6B4F2E]">Wajib isi</p>
                    <span class="grid h-9 w-9 place-items-center rounded-lg bg-white/60 text-[#946A32]">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                             stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c0-3.6 2.9-5.5 6.5-5.5s6.5 1.9 6.5 5.5"/>
                            <path d="M16 4.6a3.5 3.5 0 0 1 0 6.8M18 14.8c2 .6 3.5 2.2 3.5 5.2"/>
                        </svg>
                    </span>
                </div>
                <p class="mt-3 text-4xl font-bold text-[#4A3420]">{{ $summary['total'] }}</p>
                <p class="mt-0.5 text-[13px] font-medium text-[#6B4F2E]/80">karyawan</p>
            </div>

            <div class="rounded-[10px] border border-emerald-300 bg-emerald-100 p-5 shadow-sm">
                <div class="flex items-start justify-between">
                    <p class="text-sm font-semibold text-emerald-800">Masuk</p>
                    <span class="grid h-9 w-9 place-items-center rounded-lg bg-white/60 text-emerald-600">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                             stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="9"/><path d="M8 12.5l3 3 5-6"/>
                        </svg>
                    </span>
                </div>
                <p class="mt-3 text-4xl font-bold text-emerald-800">{{ $summary['present'] }}</p>
                <p class="mt-0.5 text-[13px] font-medium text-emerald-800/80">sudah mengisi</p>
            </div>

            <div class="rounded-[10px] border border-stone-300 bg-stone-200 p-5 shadow-sm">
                <div class="flex items-start justify-between">
                    <p class="text-sm font-semibold text-stone-700">Off</p>
                    <span class="grid h-9 w-9 place-items-center rounded-lg bg-white/60 text-stone-500">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                             stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 14.5A8 8 0 0 1 9.5 4a8 8 0 1 0 10.5 10.5z"/>
                        </svg>
                    </span>
                </div>
                <p class="mt-3 text-4xl font-bold text-stone-700">{{ $summary['off'] }}</p>
                <p class="mt-0.5 text-[13px] font-medium text-stone-600">libur</p>
            </div>

            <div class="rounded-[10px] border {{ $emptyCard }} p-5 shadow-sm">
                <div class="flex items-start justify-between">
                    <p class="text-sm font-semibold {{ $emptyText }}">{{ $emptyLabel }}</p>
                    <span class="grid h-9 w-9 place-items-center rounded-lg bg-white/60 {{ $emptyIcon }}">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                             stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>
                        </svg>
                    </span>
                </div>
                <p class="mt-3 text-4xl font-bold {{ $emptyText }}">{{ $summary['empty'] }}</p>
                <p class="mt-0.5 text-[13px] font-medium {{ $emptyText }} opacity-80">
                    {{ $isToday ? 'masih bisa mengisi' : 'tidak mengisi' }}
                </p>
            </div>
        </div>

        {{-- Progres keseluruhan --}}
        <div class="card mb-6 p-5">
            <div class="mb-3 flex items-end justify-between gap-4">
                <p class="text-base font-semibold text-[#4A3420]">
                    {{ $filled }} dari {{ $summary['total'] }} karyawan sudah mengisi
                </p>
                <p class="text-2xl font-bold text-emerald-600">{{ $pct }}%</p>
            </div>
            <div class="h-3 overflow-hidden rounded-full bg-[#EADDBB]">
                <div class="h-full rounded-full bg-emerald-500" style="width: {{ $pct }}%"></div>
            </div>
        </div>

        {{-- Per divisi --}}
        @forelse ($groups as $divisionName => $items)
            @php
                $divFilled = $items->whereIn('state', ['present', 'off'])->count();
                $divTotal  = $items->count();
            @endphp

            <div class="card mb-5 overflow-hidden">
                <div class="flex items-center justify-between gap-4 border-b border-[#E2C47F]
                            bg-gradient-to-r from-[#EDD399] to-[#F6E5BF] px-5 py-3.5">
                    <h2 class="text-lg font-bold text-[#4A3420]">{{ $divisionName }}</h2>
                    <div class="flex items-center gap-3 text-sm font-semibold text-[#6B4F2E]">
                        <span>{{ $divFilled }}/{{ $divTotal }} sudah isi</span>
                        <div class="h-2 w-28 overflow-hidden rounded-full bg-white/70">
                            <div class="h-full rounded-full bg-emerald-500"
                                 style="width: {{ round($divFilled / $divTotal * 100) }}%"></div>
                        </div>
                    </div>
                </div>

                <table class="w-full text-left text-[15px]">
                    <thead class="bg-[#F7ECD0] text-[13px] font-semibold uppercase tracking-wide text-[#6B4F2E]">
                        <tr>
                            <th class="px-5 py-3">Karyawan</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3">Diisi</th>
                            <th class="px-5 py-3">Tugas dikerjakan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#EFE2C2]">
                        @foreach ($items as $item)
                            @php
                                $log = $item['log'];
                                $rowTone = $item['state'] === 'empty'
                                    ? ($isToday ? 'bg-amber-100/80' : 'bg-red-100/70')
                                    : 'odd:bg-white even:bg-[#FFF9EA] hover:bg-[#FFF3D6]';
                            @endphp

                            <tr class="align-top {{ $rowTone }}">
                                <td class="px-5 py-3.5">
                                    <p class="font-semibold text-[#3B2E1A]">{{ $item['user']->name }}</p>
                                    <p class="text-[13px] text-slate-500">{{ $item['user']->employee_code }}</p>
                                </td>

                                <td class="whitespace-nowrap px-5 py-3.5">
                                    @if ($item['state'] === 'present')
                                        <span class="badge badge-green">Masuk</span>
                                        <span class="ml-1 text-sm font-medium text-slate-600">{{ $log->shift?->label() }}</span>
                                    @elseif ($item['state'] === 'off')
                                        <span class="badge badge-gray">Off</span>
                                    @else
                                        <span class="badge {{ $isToday ? 'badge-amber' : 'badge-red' }}">{{ $emptyLabel }}</span>
                                    @endif
                                </td>

                                <td class="whitespace-nowrap px-5 py-3.5 font-medium text-slate-600">
                                    {{ $log ? $log->created_at->format('H:i') : '-' }}
                                </td>

                                <td class="px-5 py-3.5">
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
                                            <p class="mt-1.5 text-sm italic text-slate-600">Lainnya: {{ $log->other_note }}</p>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @empty
            <div class="card p-10 text-center text-base text-slate-500">
                Tidak ada karyawan yang wajib mengisi pada tanggal ini.
            </div>
        @endforelse
    </div>
</div>
@endsection