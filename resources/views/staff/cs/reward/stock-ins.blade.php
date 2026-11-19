@extends('layouts.app')

@section('title', 'Hadiah')

@section('content')
<div class="mb-6">
    <h1 class="text-3xl font-bold tracking-tight text-[#3B2E1A]">Hadiah</h1>
    <p class="mt-1 text-base font-medium text-[#6B4F2E]">Riwayat stok masuk dan koreksi.</p>
</div>

@include('staff.cs.reward._tabs')

<div x-data="liveTable({
        url: @js(route('staff.cs.reward.stock-ins.index')),
        search: @js($search),
        perPage: @js($perPage),
        filters: { brand: @js($filters['brand']), type: @js($filters['type']), from: @js($filters['from']), to: @js($filters['to']), sort: @js($sort) },
        regions: ['reward-list'],
     })">

    <div class="card mb-4 p-4">
        <div class="flex flex-wrap items-center gap-3">
            <div class="relative min-w-[16rem] flex-1">
                <svg class="pointer-events-none absolute left-3.5 top-1/2 h-[18px] w-[18px] -translate-y-1/2 text-slate-400"
                     viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <circle cx="9" cy="9" r="6"/><path d="M14 14l4 4"/>
                </svg>
                <input type="text" x-model="search" @input.debounce.300ms="load()" autocomplete="off"
                       placeholder="Cari brand, jenis, keterangan, atau nama penginput..."
                       class="field w-full text-base" style="padding-left: 2.6rem">
            </div>

            <a :href="exportUrl(@js(route('staff.cs.reward.stock-ins.export')))"
               @click.prevent="exportFile(@js(route('staff.cs.reward.stock-ins.export')))"
               :class="exporting ? 'pointer-events-none opacity-70' : ''" class="btn btn-green">
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2"
                     stroke-linecap="round" stroke-linejoin="round">
                    <path d="M10 3v10M6 9l4 4 4-4M3 13v3a1 1 0 001 1h12a1 1 0 001-1v-3"/>
                </svg>
                <span x-text="exporting ? 'Menyiapkan...' : 'Ekspor Excel'">Ekspor Excel</span>
            </a>
        </div>

        <div class="mt-3 flex flex-wrap items-center gap-2">
            <select x-model="filters.brand" @change="load()" class="field">
                <option value="">Semua brand</option>
                @foreach ($brands as $name)
                    <option value="{{ $name }}">{{ $name }}</option>
                @endforeach
            </select>

            <select x-model="filters.type" @change="load()" class="field">
                <option value="">Semua jenis</option>
                @foreach ($types as $name)
                    <option value="{{ $name }}">{{ $name }}</option>
                @endforeach
            </select>

            <div class="flex items-center gap-2 text-sm text-slate-500">
                <input type="date" x-model="filters.from" @change="load()" class="field py-2">
                <span>s/d</span>
                <input type="date" x-model="filters.to" @change="load()" class="field py-2">
            </div>

            <button type="button" class="btn btn-outline"
                    @click="search = ''; Object.keys(filters).forEach(k => filters[k] = (k === 'sort' ? 'newest' : '')); load()">
                Reset
            </button>
        </div>
    </div>

    <div class="card overflow-hidden">
        <div id="reward-list" @click="onPageClick($event)" :class="loading ? 'opacity-60' : ''" class="transition-opacity">

            <div class="hidden gap-4 border-b border-[#E2C47F] bg-[#F7ECD0] px-5 py-3 text-[13px] font-semibold
                        text-[#6B4F2E] lg:grid lg:grid-cols-[1fr_2fr_1fr_2fr_1.2fr]">
                <button type="button" class="flex items-center gap-1 text-left font-semibold hover:text-[#3B2E1A]"
                        title="Klik untuk membalik urutan"
                        @click="filters.sort = filters.sort === 'newest' ? 'oldest' : 'newest'; load()">
                    Tanggal
                    <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2"
                         stroke-linecap="round" stroke-linejoin="round">
                        @if ($sort === 'newest') <path d="M5 8l5 5 5-5"/> @else <path d="M5 12l5-5 5 5"/> @endif
                    </svg>
                </button>
                <span>Hadiah</span><span>Jumlah</span><span>Keterangan</span><span>Diinput oleh</span>
            </div>

            <div class="divide-y divide-[#EFE2C2]">
                @forelse ($entries as $entry)
                    <div class="grid items-start gap-x-4 gap-y-2 px-5 py-4 transition hover:bg-[#FFF8E6]
                                lg:grid-cols-[1fr_2fr_1fr_2fr_1.2fr]">
                        <p class="text-[15px] font-medium text-slate-800">{{ $entry->received_on->format('d/m/Y') }}</p>
                        <div class="text-[15px]">
                            <p class="font-semibold text-[#3B2E1A]">{{ $entry->reward->brand }}</p>
                            <p class="text-[13px] text-slate-500">{{ $entry->reward->type }}</p>
                        </div>
                        <p class="text-lg font-bold {{ $entry->quantity > 0 ? 'text-emerald-700' : 'text-red-700' }}">
                            {{ $entry->quantity > 0 ? '+' : '' }}{{ number_format($entry->quantity, 0, ',', '.') }}
                        </p>
                        <p class="text-[15px] text-slate-700">{{ $entry->notes ?: '-' }}</p>
                        <p class="text-[15px] text-slate-700">{{ $entry->created_by_name }}</p>
                    </div>
                @empty
                    <div class="px-5 py-14 text-center text-base text-slate-500">
                        Tidak ada riwayat yang cocok dengan pencarian atau filter ini.
                    </div>
                @endforelse
            </div>

            <div class="flex flex-col gap-3 border-t border-[#EFE2C2] bg-[#FBF3E0] px-5 py-3 text-sm text-[#6B4F2E]
                        sm:flex-row sm:items-center sm:justify-between">
                <p data-total="{{ $entries->total() }}" class="font-medium">Menampilkan {{ $entries->count() }} dari {{ $entries->total() }} data</p>
                <div class="flex flex-wrap items-center gap-4">
                    <label class="flex items-center gap-2 font-medium">
                        Tampilkan
                        <select x-model="perPage" @change="load()" class="field py-1.5">
                            @foreach ($perPageOptions as $n)
                                <option value="{{ $n }}" @selected($perPage === $n)>{{ $n }}</option>
                            @endforeach
                        </select>
                    </label>
                    {{ $entries->links('pagination.compact') }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection