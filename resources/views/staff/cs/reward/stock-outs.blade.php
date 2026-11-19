@extends('layouts.app')

@section('title', 'Hadiah')

@section('content')
@php
    $m  = old('_modal');
    $me = auth()->user()->name;
    $pick = fn (string $key, $default = '') => $m === 'out' ? old($key, $default) : $default;
@endphp

<div
    x-data="{
        open: @js($m === 'out'),
        found: null,
        looking: false,
        lookupUrl: @js(route('staff.cs.reward.members.lookup')),
        f: {
            given_on:       @js($pick('given_on', $today)),
            receipt_no:     @js($pick('receipt_no')),
            member_no:      @js($pick('member_no')),
            member_name:    @js($pick('member_name')),
            phone:          @js($pick('phone')),
            purchase_total: @js($pick('purchase_total')),
            reward_id:      @js($pick('reward_id')),
            quantity:       @js($pick('quantity')),
            notes:          @js($pick('notes')),
        },
        openForm() {
            this.f = { given_on: @js($today), receipt_no: '', member_no: '', member_name: '', phone: '',
                       purchase_total: '', reward_id: '', quantity: '', notes: '' };
            this.found = null;
            this.open = true;
            this.$nextTick(() => this.$refs.first.focus());
        },
        async lookup() {
            const no = this.f.member_no.trim();
            const wasFound = this.found === true;

            if (no === '') {
                this.found = null;
                if (wasFound) { this.f.member_name = ''; this.f.phone = ''; }
                return;
            }

            this.looking = true;
            try {
                const res = await fetch(this.lookupUrl + '?no=' + encodeURIComponent(no), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                });
                const data = await res.json();
                this.found = data.found;

                if (data.found) {
                    this.f.member_name = data.name;
                    this.f.phone = data.phone ?? '';
                } else if (wasFound) {
                    this.f.member_name = '';
                    this.f.phone = '';
                }
            } catch (e) {
                console.error(e);
                this.found = null;
            } finally {
                this.looking = false;
            }
        },
    }"
    @keydown.escape.window="open = false"
>

    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-[#3B2E1A]">Hadiah</h1>
            <p class="mt-1 text-base font-medium text-[#6B4F2E]">Laporan hadiah yang diberikan ke member.</p>
        </div>
        <button type="button" @click="openForm()" class="btn btn-brand">+ Catat Pengeluaran</button>
    </div>

    @include('staff.cs.reward._tabs')

    <div x-data="liveTable({
            url: @js(route('staff.cs.reward.stock-outs.index')),
            search: @js($search),
            perPage: @js($perPage),
            filters: {
                brand: @js($filters['brand']), type: @js($filters['type']), cs: @js($filters['cs']),
                from: @js($filters['from']), to: @js($filters['to']),
                sort: @js($sort),
            },
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
                           placeholder="Cari no struk, nama, no member, no HP, hadiah, keterangan..."
                           class="field w-full text-base" style="padding-left: 2.6rem">
                </div>

                <a :href="exportUrl(@js(route('staff.cs.reward.stock-outs.export')))"
                   @click.prevent="exportFile(@js(route('staff.cs.reward.stock-outs.export')))"
                   :class="exporting ? 'pointer-events-none opacity-70' : ''" class="btn btn-green">
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round">
                        <path d="M10 3v10M6 9l4 4 4-4M3 13v3a1 1 0 001 1h12a1 1 0 001-1v-3"/>
                    </svg>
                    <span x-text="exporting ? 'Menyiapkan...' : 'Ekspor Excel'">Ekspor Excel</span>
                </a>
            </div>

            <div class="mt-3 space-y-3">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="mr-1 text-sm font-semibold text-[#6B4F2E]">Cepat:</span>

                    <button type="button" class="btn btn-sm"
                            :class="filters.from === @js($today) && filters.to === @js($today) ? 'btn-brand' : 'btn-outline'"
                            @click="filters.from = (filters.from === @js($today) && filters.to === @js($today)) ? '' : @js($today); filters.to = filters.from; load()">
                        Hari ini
                    </button>

                    <button type="button" class="btn btn-sm"
                            :class="filters.cs === @js($me) ? 'btn-brand' : 'btn-outline'"
                            @click="filters.cs = filters.cs === @js($me) ? '' : @js($me); load()">
                        Input saya
                    </button>
                </div>

                <div class="flex flex-wrap items-center gap-2">
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

                    <select x-model="filters.cs" @change="load()" class="field">
                        <option value="">Semua CS</option>
                        @foreach ($csNames as $name)
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
        </div>

        <div class="card overflow-hidden">
            <div id="reward-list" @click="onPageClick($event)" :class="loading ? 'opacity-60' : ''" class="transition-opacity">

                <div class="hidden gap-4 border-b border-[#E2C47F] bg-[#F7ECD0] px-5 py-3 text-[13px] font-semibold
                            text-[#6B4F2E] lg:grid lg:grid-cols-[1fr_1.5fr_1fr_1.4fr_1.4fr_1fr]">
                    <button type="button" class="flex items-center gap-1 text-left font-semibold hover:text-[#3B2E1A]"
                            title="Klik untuk membalik urutan"
                            @click="filters.sort = filters.sort === 'newest' ? 'oldest' : 'newest'; load()">
                        Tanggal / No Struk
                        <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2"
                             stroke-linecap="round" stroke-linejoin="round">
                            @if ($sort === 'newest') <path d="M5 8l5 5 5-5"/> @else <path d="M5 12l5-5 5 5"/> @endif
                        </svg>
                    </button>
                    <span>Member</span><span>Total Belanja</span><span>Hadiah</span><span>Keterangan</span><span>Diinput oleh</span>
                </div>

                <div class="divide-y divide-[#EFE2C2]">
                    @forelse ($entries as $out)
                        <div class="grid items-start gap-x-4 gap-y-2 px-5 py-4 transition hover:bg-[#FFF8E6]
                                    lg:grid-cols-[1fr_1.5fr_1fr_1.4fr_1.4fr_1fr]">
                            <div class="text-[15px]">
                                <p class="font-medium text-slate-800">{{ $out->given_on->format('d/m/Y') }}</p>
                                <p class="font-mono text-[13px] text-slate-500">{{ $out->receipt_no }}</p>
                            </div>
                            <div class="min-w-0 text-[15px]">
                                <p class="truncate font-semibold text-[#3B2E1A]">{{ $out->member_name }}</p>
                                <p class="text-[13px] text-slate-500">
                                    <span class="font-mono">{{ $out->member_no ?: '-' }}</span> · {{ $out->phone ?: '-' }}
                                </p>
                            </div>
                            <p class="text-[15px] font-medium text-slate-800">Rp {{ number_format($out->purchase_total, 0, ',', '.') }}</p>
                            <div class="text-[15px]">
                                <p class="font-semibold text-[#3B2E1A]">{{ $out->reward->label() }}</p>
                                <p class="text-[13px] text-slate-500">Jumlah: <b>{{ number_format($out->quantity, 0, ',', '.') }}</b></p>
                            </div>
                            <p class="text-[15px] text-slate-700">{{ $out->notes ?: '-' }}</p>
                            <p class="text-[15px] text-slate-700">{{ $out->created_by_name }}</p>
                        </div>
                    @empty
                        <div class="px-5 py-14 text-center text-base text-slate-500">
                            Tidak ada pengeluaran yang cocok dengan pencarian atau filter ini.
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

    {{-- Modal: catat pengeluaran --}}
    <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/50" @click="open = false"></div>

        <div class="card relative max-h-[92vh] w-full max-w-2xl overflow-y-auto p-6 shadow-xl" x-transition>
            <h2 class="text-xl font-bold text-[#3B2E1A]">Catat Pengeluaran Hadiah</h2>

            <form method="POST" action="{{ route('staff.cs.reward.stock-outs.store') }}" class="mt-5 space-y-4">
                @csrf
                <input type="hidden" name="_modal" value="out">

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-[#6B4F2E]">Tanggal</label>
                        <input type="date" name="given_on" x-model="f.given_on" max="{{ $today }}" class="field w-full">
                        <p class="mt-1 text-xs text-red-600">{{ $errors->first('given_on') }}</p>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-[#6B4F2E]">No struk</label>
                        <input type="text" name="receipt_no" x-model="f.receipt_no" x-ref="first" class="field w-full font-mono" autocomplete="off">
                        <p class="mt-1 text-xs text-red-600">{{ $errors->first('receipt_no') }}</p>
                    </div>
                </div>

                <div class="rounded-md border border-[#E2C47F] bg-[#FBF3E0] p-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="mb-1.5 block text-sm font-semibold text-[#6B4F2E]">No member</label>
                            <input type="text" name="member_no" x-model="f.member_no" @input.debounce.400ms="lookup()"
                                   class="field w-full font-mono" autocomplete="off">
                            <p class="mt-1 text-xs"
                               :class="found === true ? 'text-emerald-700' : 'text-slate-500'"
                               x-show="looking || found !== null" x-cloak>
                                <span x-show="looking">Mencari di data Member...</span>
                                <span x-show="!looking && found === true">Ditemukan. Nama dan HP terisi dari data Member.</span>
                                <span x-show="!looking && found === false">Tidak ada di data Member. Isi nama dan HP manual.</span>
                            </p>
                            <p class="mt-1 text-xs text-red-600">{{ $errors->first('member_no') }}</p>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-semibold text-[#6B4F2E]">Nama</label>
                            <input type="text" name="member_name" x-model="f.member_name" class="field w-full" autocomplete="off">
                            <p class="mt-1 text-xs text-red-600">{{ $errors->first('member_name') }}</p>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-semibold text-[#6B4F2E]">No HP</label>
                            <input type="text" name="phone" x-model="f.phone" inputmode="tel" class="field w-full" autocomplete="off">
                            <p class="mt-1 text-xs text-red-600">{{ $errors->first('phone') }}</p>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-[#6B4F2E]">Total belanja (Rp)</label>
                    <input type="text" name="purchase_total" x-model="f.purchase_total" inputmode="numeric" class="field w-full"
                           @input="f.purchase_total = f.purchase_total.replace(/\D/g, '')" autocomplete="off">
                    <p class="mt-1 text-xs text-red-600">{{ $errors->first('purchase_total') }}</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-[1fr_8rem]">
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-[#6B4F2E]">Hadiah</label>
                        <select name="reward_id" x-model="f.reward_id" class="field w-full">
                            <option value="">Pilih hadiah</option>
                            @foreach ($rewards as $reward)
                                <option value="{{ $reward->id }}" @disabled($reward->stock <= 0)>
                                    {{ $reward->label() }} (stok {{ $reward->stock }})
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-red-600">{{ $errors->first('reward_id') }}</p>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-[#6B4F2E]">Jumlah</label>
                        <input type="number" name="quantity" x-model="f.quantity" min="1" step="1" class="field w-full" placeholder="1">
                        <p class="mt-1 text-xs text-red-600">{{ $errors->first('quantity') }}</p>
                    </div>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-[#6B4F2E]">Keterangan</label>
                    <input type="text" name="notes" x-model="f.notes" class="field w-full" autocomplete="off"
                           placeholder="Contoh: hadiah dari acc store manager">
                    <p class="mt-1 text-xs text-red-600">{{ $errors->first('notes') }}</p>
                </div>

                <p class="text-xs text-slate-500">Nama CS terisi otomatis dari akun yang sedang login.</p>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="open = false" class="btn btn-outline">Batal</button>
                    <button type="submit" class="btn btn-brand">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection