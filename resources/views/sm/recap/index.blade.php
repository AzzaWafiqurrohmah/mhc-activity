@extends('layouts.app')

@section('title', 'Rekap Nilai')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-semibold">Rekap Nilai</h1>
        <p class="text-sm text-slate-500">Nilai kelengkapan input harian tiap karyawan pada periode terpilih.</p>
    </div>

    <div x-data="liveTable({
             url: @js(route('sm.recap.index')),
             search: @js($search),
             perPage: @js($perPage),
             filters: { division: @js($divisionFilter), sort: @js($sort), from: @js($from), to: @js($to) },
         })">

        {{-- Toolbar --}}
        <div class="card mb-4 space-y-3 p-4">
            <div class="flex flex-wrap items-center gap-2">
                <span class="mr-1 text-sm text-slate-500">Periode:</span>

                @foreach ($presets as $p)
                    <button type="button" class="btn btn-sm"
                            :class="filters.from === @js($p['from']) && filters.to === @js($p['to']) ? 'btn-brand' : 'btn-outline'"
                            @click="filters.from = @js($p['from']); filters.to = @js($p['to']); load()">
                        {{ $p['label'] }}
                    </button>
                @endforeach

                <div class="ml-auto flex items-center gap-2 text-sm text-slate-500">
                    <input type="date" x-model="filters.from" @change="load()" class="field py-1.5">
                    <span>s/d</span>
                    <input type="date" x-model="filters.to" @change="load()" class="field py-1.5">
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <div class="relative w-full max-w-xs">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                         viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                        <circle cx="9" cy="9" r="6"/><path d="M14 14l4 4"/>
                    </svg>

                    <input type="text" x-model="search" @input.debounce.300ms="load()"
                           placeholder="Cari nama atau ID karyawan..." autocomplete="off"
                           class="field w-full pl-9 pr-8">

                    <button type="button" x-show="search !== ''" x-cloak
                            @click="search = ''; load()"
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-lg leading-none text-slate-400 hover:text-slate-600"
                            aria-label="Hapus pencarian">&times;</button>
                </div>

                <select x-model="filters.division" @change="load()" class="field">
                    <option value="">Semua divisi</option>
                    @foreach ($divisions as $division)
                        <option value="{{ $division->id }}">{{ $division->name }}</option>
                    @endforeach
                </select>

                <select x-model="filters.sort" @change="load()" class="field">
                    <option value="low">Nilai terendah dulu</option>
                    <option value="high">Nilai tertinggi dulu</option>
                    <option value="name">Nama A-Z</option>
                </select>
            </div>
        </div>

        {{-- Area yang diganti lewat AJAX --}}
        <div id="table-area" @click="onPageClick($event)"
             :class="loading ? 'opacity-50' : ''" class="transition-opacity">

            @php
                $tone = fn ($s) => $s === null ? 'bg-slate-300'
                    : ($s >= 90 ? 'bg-emerald-500' : ($s >= 70 ? 'bg-amber-500' : 'bg-red-500'));
            @endphp

            {{-- Kartu ringkasan --}}
            <div class="mb-4 grid gap-4 sm:grid-cols-3">
                <div class="card p-4">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Karyawan dinilai</p>
                    <p class="mt-1 text-2xl font-semibold">{{ $summary['count'] }}</p>
                </div>
                <div class="card p-4">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Rata-rata nilai</p>
                    <p class="mt-1 text-2xl font-semibold">
                        {{ $summary['average'] === null ? '-' : number_format($summary['average'], 1, ',', '.') . '%' }}
                    </p>
                </div>
                <div class="card p-4">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Total hari kosong</p>
                    <p class="mt-1 text-2xl font-semibold">{{ $summary['empty'] }}</p>
                </div>
            </div>

            <div class="card overflow-hidden">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Karyawan</th>
                            <th class="px-5 py-3">Divisi</th>
                            <th class="px-5 py-3 text-right">Hari wajib</th>
                            <th class="px-5 py-3 text-right">Masuk</th>
                            <th class="px-5 py-3 text-right">Off</th>
                            <th class="px-5 py-3 text-right">Kosong</th>
                            <th class="w-56 px-5 py-3">Nilai</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($rows as $row)
                            <tr>
                                <td class="px-5 py-3">
                                    <a href="{{ route('sm.recap.show', ['user' => $row['user_id'], 'from' => $from, 'to' => $to]) }}"
   class="font-medium hover:text-[var(--brand)] hover:underline">{{ $row['name'] }}</a>
                                    <p class="text-xs text-slate-500">{{ $row['code'] }}</p>
                                </td>
                                <td class="px-5 py-3">{{ $row['division'] }}</td>
                                <td class="px-5 py-3 text-right">{{ $row['working'] }}</td>
                                <td class="px-5 py-3 text-right">{{ $row['present'] }}</td>
                                <td class="px-5 py-3 text-right text-slate-500">{{ $row['off'] }}</td>
                                <td class="px-5 py-3 text-right {{ $row['empty'] > 0 ? 'font-medium text-red-600' : 'text-slate-400' }}">
                                    {{ $row['empty'] }}
                                </td>
                                <td class="px-5 py-3">
                                    @if ($row['score'] === null)
                                        <span class="text-slate-400">-</span>
                                    @else
                                        <div class="flex items-center gap-3">
                                            <div class="h-2 flex-1 overflow-hidden rounded-full bg-slate-100">
                                                <div class="h-full {{ $tone($row['score']) }}"
                                                     style="width: {{ $row['score'] }}%"></div>
                                            </div>
                                            <span class="w-14 text-right font-medium">
                                                {{ number_format($row['score'], 1, ',', '.') }}%
                                            </span>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-10 text-center text-slate-500">
                                    Tidak ada karyawan pada filter ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="flex flex-col gap-3 border-t border-slate-200 px-5 py-3 text-sm text-slate-500
                            sm:flex-row sm:items-center sm:justify-between">
                    <p>Menampilkan {{ $rows->count() }} dari {{ $rows->total() }} data</p>

                    <div class="flex flex-wrap items-center gap-4">
                        <label class="flex items-center gap-2">
                            Tampilkan
                            <select x-model="perPage" @change="load()" class="field py-1.5">
                                @foreach ($perPageOptions as $n)
                                    <option value="{{ $n }}" @selected($perPage === $n)>{{ $n }}</option>
                                @endforeach
                            </select>
                        </label>

                        {{ $rows->links('pagination.compact') }}
                    </div>
                </div>
            </div>

            <p class="mt-3 text-xs text-slate-500">
                Nilai = hari masuk terisi ÷ (hari wajib − hari off). Hari wajib dihitung sejak akun aktif
                sampai kemarin; hari ini belum dihitung karena belum selesai.
            </p>
        </div>
    </div>
@endsection