@extends('layouts.app')

@section('title', 'Log Aktivitas')

@section('content')
@php
    // inisial untuk avatar karyawan
    $initials = fn (string $name) => \Illuminate\Support\Str::of($name)
        ->explode(' ')
        ->filter()
        ->take(2)
        ->map(fn ($w) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($w, 0, 1)))
        ->implode('');

    $prevDate = null;
@endphp

{{-- helper tanggal untuk tombol rentang cepat (dipakai komponen di dalamnya) --}}
<div x-data="{
        iso(offset = 0) {
            const d = new Date();
            d.setDate(d.getDate() + offset);
            return d.toLocaleDateString('sv-SE');
        },
        monthStart() {
            const d = new Date();
            d.setDate(1);
            return d.toLocaleDateString('sv-SE');
        },
     }">

    <div class="mb-6">
        <h1 class="text-2xl font-semibold">Log Aktivitas</h1>
        <p class="text-sm text-slate-500">Seluruh input harian karyawan, terbaru di atas.</p>
    </div>

    <div class="card overflow-hidden"
         x-data="liveTable({
             url: @js(route('sm.logs.index')),
             search: @js($search),
             perPage: @js($perPage),
             filters: { division: @js($divisionFilter), from: @js($from), to: @js($to) },
         })">

        {{-- Toolbar baris 1: cari, divisi, rentang tanggal --}}
        <div class="flex flex-wrap items-center gap-3 px-4 pb-3 pt-4">
            <div class="relative min-w-[16rem] max-w-md flex-1">
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                     viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <circle cx="9" cy="9" r="6"/><path d="M14 14l4 4"/>
                </svg>

                <input type="text" x-model="search" @input.debounce.300ms="load()"
                       placeholder="Cari nama atau ID karyawan..." autocomplete="off"
                       class="field w-full !pl-10 !pr-8">

                <button type="button" x-show="search !== ''" x-cloak
                        @click="search = ''; load()"
                        class="absolute right-2 top-1/2 -translate-y-1/2 text-lg leading-none text-slate-400 hover:text-slate-600"
                        aria-label="Hapus pencarian">&times;</button>
            </div>

            <select x-model="filters.division" @change="load()" class="field w-48">
                <option value="">Semua divisi</option>
                @foreach ($divisions as $division)
                    <option value="{{ $division->id }}">{{ $division->name }}</option>
                @endforeach
            </select>

            {{-- Rentang tanggal: satu kotak, dua input di dalamnya --}}
            <div class="flex h-[38px] shrink-0 items-center rounded-md border border-slate-300 bg-white pl-3 pr-1
                        text-sm text-slate-400 transition-shadow
                        focus-within:border-[var(--brand)] focus-within:ring-2 focus-within:ring-[var(--brand-soft)]">
                <svg class="mr-1.5 h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="none" stroke="currentColor"
                     stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="4.5" width="14" height="12" rx="2"/>
                    <path d="M3 8.5h14M7 3v3M13 3v3"/>
                </svg>

                <input type="date" x-model="filters.from" @change="load()" aria-label="Dari tanggal"
                       class="h-full w-[8.5rem] border-0 bg-transparent px-1 text-sm text-slate-700
                              focus:outline-none focus:ring-0">
                <span class="px-0.5">&ndash;</span>
                <input type="date" x-model="filters.to" @change="load()" aria-label="Sampai tanggal"
                       class="h-full w-[8.5rem] border-0 bg-transparent px-1 text-sm text-slate-700
                              focus:outline-none focus:ring-0">
            </div>
        </div>

        {{-- Toolbar baris 2: rentang cepat, reset, legenda --}}
        <div class="flex flex-wrap items-center gap-2 border-b border-slate-200 px-4 pb-4 text-sm">
            <span class="mr-1 text-xs font-medium uppercase tracking-wide text-slate-400">Cepat</span>

            <button type="button"
                    @click="filters.from = iso(); filters.to = iso(); load()"
                    :class="filters.from === iso() && filters.to === iso()
                        ? 'border-[var(--brand)] bg-[var(--brand-soft)] text-slate-800'
                        : 'border-slate-300 text-slate-600 hover:bg-slate-50'"
                    class="rounded-full border px-3 py-1 text-xs font-medium transition-colors">Hari ini</button>

            <button type="button"
                    @click="filters.from = iso(-6); filters.to = iso(); load()"
                    :class="filters.from === iso(-6) && filters.to === iso()
                        ? 'border-[var(--brand)] bg-[var(--brand-soft)] text-slate-800'
                        : 'border-slate-300 text-slate-600 hover:bg-slate-50'"
                    class="rounded-full border px-3 py-1 text-xs font-medium transition-colors">7 hari terakhir</button>

            <button type="button"
                    @click="filters.from = monthStart(); filters.to = iso(); load()"
                    :class="filters.from === monthStart() && filters.to === iso()
                        ? 'border-[var(--brand)] bg-[var(--brand-soft)] text-slate-800'
                        : 'border-slate-300 text-slate-600 hover:bg-slate-50'"
                    class="rounded-full border px-3 py-1 text-xs font-medium transition-colors">Bulan ini</button>

            <button type="button"
                    x-show="search !== '' || filters.division !== '' || filters.from !== '' || filters.to !== ''" x-cloak
                    @click="search = ''; filters.division = ''; filters.from = ''; filters.to = ''; load()"
                    class="ml-1 text-xs font-medium text-slate-500 underline-offset-2 hover:text-red-600 hover:underline">
                Reset filter
            </button>

            {{-- Legenda tipe tugas --}}
            <div class="ml-auto flex items-center gap-4 text-xs text-slate-500">
                <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-blue-500"></span> Rutin</span>
                <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-amber-500"></span> Insidental</span>
            </div>
        </div>

        {{-- Area yang diganti lewat AJAX --}}
        <div id="table-area" @click="onPageClick($event)"
             :class="loading ? 'opacity-50' : ''" class="transition-opacity">

            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Karyawan</th>
                        <th class="px-5 py-3">Divisi</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">Tugas dikerjakan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        @php
                            $date = $log->log_date->copy()->locale('id');
                            $dateKey = $log->log_date->toDateString();
                            $isOff = $log->status === \App\LogStatus::Off;
                        @endphp

                        {{-- Pemisah per tanggal --}}
                        @if ($dateKey !== $prevDate)
                            <tr class="border-t border-slate-200 bg-slate-50/80 first:border-t-0">
                                <td colspan="4" class="px-5 py-2">
                                    <div class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                                        <svg class="h-4 w-4 text-slate-400" viewBox="0 0 20 20" fill="none"
                                             stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                                             stroke-linejoin="round">
                                            <rect x="3" y="4.5" width="14" height="12" rx="2"/>
                                            <path d="M3 8.5h14M7 3v3M13 3v3"/>
                                        </svg>
                                        {{ $date->translatedFormat('l, d F Y') }}

                                        @if ($log->log_date->isToday())
                                            <span class="badge badge-green">Hari ini</span>
                                        @elseif ($log->log_date->isYesterday())
                                            <span class="badge badge-gray">Kemarin</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @php $prevDate = $dateKey; @endphp
                        @endif

                        <tr class="border-t border-slate-100 align-top transition-colors hover:bg-slate-50/60
                                   {{ $isOff ? 'bg-slate-50/50' : '' }}">

                            {{-- Karyawan --}}
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-slate-100
                                                 text-xs font-semibold text-slate-600">
                                        {{ $initials($log->user->name) }}
                                    </span>
                                    <div class="min-w-0">
                                        <p class="truncate font-medium">{{ $log->user->name }}</p>
                                        <p class="text-xs text-slate-500">{{ $log->user->employee_code }}</p>
                                    </div>
                                </div>
                            </td>

                            <td class="px-5 py-3 text-slate-600">{{ $log->division->name }}</td>

                            {{-- Status --}}
                            <td class="whitespace-nowrap px-5 py-3">
                                @if ($isOff)
                                    <span class="badge badge-gray">Off</span>
                                @else
                                    <div class="flex items-center gap-1.5">
                                        <span class="badge badge-green">Masuk</span>
                                        @if ($log->shift)
                                            <span class="badge badge-blue">{{ $log->shift->label() }}</span>
                                        @endif
                                    </div>
                                @endif
                            </td>

                            {{-- Tugas --}}
                            <td class="px-5 py-3">
                                @if ($isOff)
                                    <span class="text-slate-400">Libur</span>
                                @elseif ($log->tasks->isEmpty() && ! $log->other_note)
                                    <span class="text-slate-400">-</span>
                                @else
                                    @if ($log->tasks->isNotEmpty())
                                        <div class="flex flex-wrap gap-1.5">
                                            @foreach ($log->tasks as $task)
                                                <span class="inline-flex items-center gap-1.5 rounded bg-slate-100 px-2 py-0.5 text-xs text-slate-700">
                                                    <span class="h-1.5 w-1.5 shrink-0 rounded-full
                                                        {{ $task->type === \App\TaskType::Routine ? 'bg-blue-500' : 'bg-amber-500' }}"></span>
                                                    {{ $task->name }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif

                                    @if ($log->other_note)
                                        <p class="{{ $log->tasks->isNotEmpty() ? 'mt-2' : '' }} flex items-start gap-1.5 text-xs text-slate-500">
                                            <svg class="mt-px h-3.5 w-3.5 shrink-0 text-slate-400" viewBox="0 0 20 20" fill="none"
                                                 stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                                                 stroke-linejoin="round">
                                                <path d="M4 4h12v9H8l-4 3V4z"/>
                                            </svg>
                                            <span><span class="font-medium">Keterangan:</span> {{ $log->other_note }}</span>
                                        </p>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-16 text-center">
                                <svg class="mx-auto h-10 w-10 text-slate-300" viewBox="0 0 20 20" fill="none"
                                     stroke="currentColor" stroke-width="1.2" stroke-linecap="round"
                                     stroke-linejoin="round">
                                    <rect x="3" y="4.5" width="14" height="12" rx="2"/>
                                    <path d="M3 8.5h14M7 3v3M13 3v3"/>
                                </svg>
                                <p class="mt-3 font-medium text-slate-600">Tidak ada data pada filter ini</p>
                                <p class="mt-1 text-sm text-slate-500">Coba ubah rentang tanggal, divisi, atau kata pencarian.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="flex flex-col gap-3 border-t border-slate-200 px-5 py-3 text-sm text-slate-500
                        sm:flex-row sm:items-center sm:justify-between">
                <p>Menampilkan {{ $logs->count() }} dari {{ $logs->total() }} data</p>

                <div class="flex flex-wrap items-center gap-4">
                    <label class="flex items-center gap-2">
                        Tampilkan
                        <select x-model="perPage" @change="load()" class="field py-1.5">
                            @foreach ($perPageOptions as $n)
                                <option value="{{ $n }}" @selected($perPage === $n)>{{ $n }}</option>
                            @endforeach
                        </select>
                    </label>

                    {{ $logs->links('pagination.compact') }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection