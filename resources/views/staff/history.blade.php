@extends('layouts.app')

@section('title', 'Riwayat')

@section('content')
@php
    $shiftLabels = ['morning' => 'Pagi', 'afternoon' => 'Siang'];
    $pct = $totalDays > 0 ? min(100, round($filledDays / $totalDays * 100)) : 0;

    // data ringkas untuk hitung ringkasan & filter di browser (tanpa reload)
    $rowsMeta = collect($history)->map(fn ($r, $i) => [
        'i' => $i + 1,
        's' => $r['log'] === null
            ? 'empty'
            : ($r['log']->status === \App\LogStatus::Off ? 'off' : 'present'),
    ])->values()->all();
@endphp

<div class="mx-auto max-w-4xl"
     x-data="{
        range: 7,
        status: 'all',
        rows: @js($rowsMeta),
        count(s) { return this.rows.filter(r => r.i <= this.range && (s === 'all' || r.s === s)).length; },
        show(i, s) { return i <= this.range && (this.status === 'all' || this.status === s); },
     }">

    {{-- Judul + terisi bulan ini --}}
    <div class="mb-5 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold">Riwayat</h1>
            <p class="text-sm text-slate-500">Isian harian kamu sampai kemarin.</p>
        </div>

        <div class="w-48">
            <p class="text-sm text-slate-500">
                Terisi bulan ini:
                <span class="font-semibold text-slate-800">{{ $filledDays }}/{{ $totalDays }}</span> hari
            </p>
            <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-slate-200">
                <div class="h-full rounded-full bg-[var(--brand)]" style="width: {{ $pct }}%"></div>
            </div>
        </div>
    </div>

    <div class="card overflow-hidden">

        {{-- Header: judul + pilihan rentang hari --}}
        <div class="flex items-center justify-between px-6 py-4">
            <h2 class="text-lg font-semibold">
                <span x-text="range"></span> hari terakhir
            </h2>

            <div class="inline-flex rounded-md border border-slate-300 p-0.5 text-sm">
                @foreach ([7, 14, 30] as $n)
                    <button type="button" @click="range = {{ $n }}"
                            :class="range === {{ $n }} ? 'bg-[var(--brand-soft)] font-medium text-slate-800' : 'text-slate-500'"
                            class="rounded px-3 py-1">{{ $n }} hari</button>
                @endforeach
            </div>
        </div>

        {{-- Ringkasan: sekaligus jadi filter status --}}
        <div class="grid grid-cols-2 divide-x divide-slate-200 border-t border-slate-200 sm:grid-cols-4">
            <button type="button" @click="status = 'all'"
                    :class="status === 'all' ? 'bg-[var(--brand-soft)]' : 'hover:bg-slate-50'"
                    class="px-5 py-3 text-left transition-colors">
                <p class="text-2xl font-semibold" x-text="count('all')"></p>
                <p class="mt-0.5 text-xs text-slate-500">Semua hari</p>
            </button>

            <button type="button" @click="status = 'present'"
                    :class="status === 'present' ? 'bg-[var(--brand-soft)]' : 'hover:bg-slate-50'"
                    class="px-5 py-3 text-left transition-colors">
                <p class="text-2xl font-semibold text-emerald-600" x-text="count('present')"></p>
                <p class="mt-0.5 flex items-center gap-1.5 text-xs text-slate-500">
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span> Masuk
                </p>
            </button>

            <button type="button" @click="status = 'off'"
                    :class="status === 'off' ? 'bg-[var(--brand-soft)]' : 'hover:bg-slate-50'"
                    class="px-5 py-3 text-left transition-colors">
                <p class="text-2xl font-semibold text-slate-500" x-text="count('off')"></p>
                <p class="mt-0.5 flex items-center gap-1.5 text-xs text-slate-500">
                    <span class="h-2 w-2 rounded-full bg-slate-400"></span> Off
                </p>
            </button>

            <button type="button" @click="status = 'empty'"
                    :class="status === 'empty' ? 'bg-[var(--brand-soft)]' : 'hover:bg-slate-50'"
                    class="px-5 py-3 text-left transition-colors">
                <p class="text-2xl font-semibold"
                   :class="count('empty') > 0 ? 'text-red-600' : 'text-slate-400'" x-text="count('empty')"></p>
                <p class="mt-0.5 flex items-center gap-1.5 text-xs text-slate-500">
                    <span class="h-2 w-2 rounded-full bg-red-500"></span> Kosong
                </p>
            </button>
        </div>

        {{-- Tabel --}}
        <div class="overflow-x-auto border-t border-slate-200">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="w-36 px-5 py-3">Tanggal</th>
                        <th class="w-36 px-5 py-3">Status</th>
                        <th class="px-5 py-3">Tugas</th>
                        <th class="px-5 py-3">Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($history as $row)
                        @php
                            $h = $row['log'];
                            $key = $h === null ? 'empty' : ($h->status === \App\LogStatus::Off ? 'off' : 'present');
                        @endphp

                        <tr x-show="show({{ $loop->iteration }}, '{{ $key }}')" x-cloak
                            class="border-t border-slate-100 align-top
                                   {{ $key === 'empty' ? 'bg-red-50/70' : ($key === 'off' ? 'bg-slate-50/70' : '') }}">

                            <td class="px-5 py-3 {{ $key === 'empty' ? 'text-red-700' : '' }}">
                                <p class="font-medium">{{ $row['date']->copy()->locale('id')->translatedFormat('D, d M') }}</p>
                            </td>

                            @if ($key === 'present')
                                <td class="px-5 py-3">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <span class="badge badge-green">Masuk</span>
                                        <span class="badge badge-blue">{{ $shiftLabels[$h->shift->value] ?? '-' }}</span>
                                    </div>
                                </td>

                                <td class="px-5 py-3">
                                    @if ($h->tasks->isNotEmpty())
                                        <div class="flex flex-wrap gap-1.5">
                                            @foreach ($h->tasks->sortBy('sort_order') as $task)
                                                <span class="rounded bg-slate-100 px-2 py-0.5 text-xs text-slate-700">{{ $task->name }}</span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-slate-400">-</span>
                                    @endif
                                </td>

                                <td class="px-5 py-3 text-slate-600">
                                    {{ $h->other_note ?: '-' }}
                                </td>
                            @elseif ($key === 'off')
                                <td class="px-5 py-3">
                                    <span class="badge badge-gray">Off</span>
                                </td>
                                <td colspan="2" class="px-5 py-3 text-slate-500">Libur</td>
                            @else
                                <td class="px-5 py-3">
                                    <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-700">
                                        Kosong
                                    </span>
                                </td>
                                <td colspan="2" class="px-5 py-3 text-red-700">Kamu lupa mengisi hari ini.</td>
                            @endif
                        </tr>
                    @endforeach

                    {{-- Kosong: belum ada riwayat / filter tidak menemukan apa-apa --}}
                    <tr x-show="count(status) === 0" x-cloak class="border-t border-slate-100">
                        <td colspan="4" class="px-5 py-10 text-center text-slate-500"
                            x-text="status === 'all' ? 'Belum ada riwayat.' : 'Tidak ada hari dengan status ini.'"></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection