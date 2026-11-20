@extends('layouts.app')

@section('title', 'Hadiah')

@section('content')
@php
    $m    = old('_modal');
    $pick = fn (string $modal, string $key, $default = '') => $m === $modal ? old($key, $default) : $default;
@endphp

<div
    x-data="{
        modal: @js($m),
        editAction: @js($m === 'edit' ? old('_action', '') : ''),
        label: @js($m === 'restock' ? old('_label', '') : ''),
        createForm:  { brand: @js($pick('create', 'brand')), type: @js($pick('create', 'type')),
                    opening_stock: @js($pick('create', 'opening_stock')), min_stock: @js($pick('create', 'min_stock', '5')) },
        editForm:    { brand: @js($pick('edit', 'brand')), type: @js($pick('edit', 'type')),
                    min_stock: @js($pick('edit', 'min_stock')) },
        restockForm: { reward_id: @js($pick('restock', 'reward_id')), received_on: @js($pick('restock', 'received_on', $today)),
                       quantity: @js($pick('restock', 'quantity')), notes: @js($pick('restock', 'notes')) },
        openCreate() {
            this.createForm = { brand: '', type: '', opening_stock: '', min_stock: '5' };
            this.modal = 'create';
            this.$nextTick(() => this.$refs.createFirst.focus());
        },
        openEdit(action, data) {
            this.editAction = action;
            this.editForm = { ...data };
            this.modal = 'edit';
            this.$nextTick(() => this.$refs.editFirst.focus());
        },
        openRestock(id, label) {
            this.label = label;
            this.restockForm = { reward_id: id, received_on: @js($today), quantity: '', notes: '' };
            this.modal = 'restock';
            this.$nextTick(() => this.$refs.restockFirst.focus());
        },
    }"
    @keydown.escape.window="modal = null"
>

    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-[#3B2E1A]">Hadiah</h1>
            <p class="mt-1 text-base font-medium text-[#6B4F2E]">Stok hadiah untuk member, barang masuk, dan barang keluar.</p>
        </div>
        <button type="button" @click="openCreate()" class="btn btn-brand">+ Hadiah Baru</button>
    </div>

    @include('staff.cs.reward._tabs')

    {{-- Ringkasan --}}
    <div class="mb-4 grid gap-3 sm:grid-cols-3">
        <div class="card p-5">
            <p class="text-sm font-semibold text-[#6B4F2E]">Total jenis hadiah</p>
            <p class="mt-1 text-3xl font-bold text-[#3B2E1A]">{{ number_format($summary['kinds'], 0, ',', '.') }}</p>
        </div>
        <div class="card p-5">
            <p class="text-sm font-semibold text-[#6B4F2E]">Total stok</p>
            <p class="mt-1 text-3xl font-bold text-[#3B2E1A]">{{ number_format($summary['stock'], 0, ',', '.') }}</p>
        </div>
        <div class="rounded-md border p-5 {{ $summary['restock'] ? 'border-red-300 bg-red-100' : 'border-[#E6D3AC] bg-white' }}">
            <p class="text-sm font-semibold {{ $summary['restock'] ? 'text-red-800' : 'text-[#6B4F2E]' }}">Perlu restok</p>
            <p class="mt-1 text-3xl font-bold {{ $summary['restock'] ? 'text-red-800' : 'text-[#3B2E1A]' }}">{{ number_format($summary['restock'], 0, ',', '.') }}</p>
            <p class="text-xs {{ $summary['restock'] ? 'text-red-700' : 'text-slate-500' }}">stok menipis atau habis</p>
        </div>
    </div>

    <div x-data="liveTable({
            url: @js(route('staff.cs.reward.index')),
            search: @js($search),
            perPage: @js($perPage),
            filters: {},
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
                    placeholder="Cari brand atau jenis hadiah..."
                    class="field w-full text-base" style="padding-left: 2.6rem">
            </div>

            <a :href="exportUrl(@js(route('staff.cs.reward.export')))"
            @click.prevent="exportFile(@js(route('staff.cs.reward.export')))"
            :class="exporting ? 'pointer-events-none opacity-70' : ''" class="btn btn-green">
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M10 3v10M6 9l4 4 4-4M3 13v3a1 1 0 001 1h12a1 1 0 001-1v-3"/>
                </svg>
                <span x-text="exporting ? 'Menyiapkan...' : 'Ekspor Excel'">Ekspor Excel</span>
            </a>
        </div>
    </div>

        <div class="card overflow-hidden">
<div id="reward-list" :class="loading ? 'opacity-60' : ''" class="transition-opacity">

    <div class="hidden gap-4 border-b border-[#E2C47F] bg-[#F7ECD0] px-5 py-3 text-[13px] font-semibold
                text-[#6B4F2E] lg:grid lg:grid-cols-[1.4fr_1.4fr_0.8fr_0.8fr_1fr_14rem]">
        <span>Brand</span><span>Jenis</span><span>Stok</span><span>Minimum</span><span>Status</span><span></span>
    </div>

    <div class="divide-y divide-[#EFE2C2]">
        @forelse ($rewards as $reward)
            <div class="grid items-center gap-x-4 gap-y-2 px-5 py-4 transition hover:bg-[#FFF8E6]
                        lg:grid-cols-[1.4fr_1.4fr_0.8fr_0.8fr_1fr_14rem]">
                <p class="text-[15px] font-semibold text-[#3B2E1A]">{{ $reward->brand }}</p>
                <p class="text-[15px] text-slate-800">{{ $reward->type }}</p>
                <p class="text-2xl font-extrabold text-[#3B2E1A]">{{ number_format($reward->stock, 0, ',', '.') }}</p>
                <p class="text-[15px] text-slate-500">{{ number_format($reward->min_stock, 0, ',', '.') }}</p>
                <div><span class="badge {{ $reward->status()->badge() }}">{{ $reward->status()->label() }}</span></div>
                <div class="flex gap-2 lg:justify-end">
                    <button type="button" class="btn btn-gold btn-sm"
                            @click="openRestock(@js($reward->id), @js($reward->label()))">Tambah Stok</button>
                    <button type="button" class="btn btn-outline btn-sm"
                            @click="openEdit(@js(route('staff.cs.reward.update', $reward)), @js(['brand' => $reward->brand, 'type' => $reward->type, 'min_stock' => $reward->min_stock]))">Edit</button>
                </div>
            </div>
        @empty
            <div class="px-5 py-14 text-center text-base text-slate-500">
                Belum ada hadiah yang cocok. Klik "+ Hadiah Baru" untuk menambahkan.
            </div>
        @endforelse
    </div>

    <div class="flex flex-col gap-3 border-t border-[#EFE2C2] bg-[#FBF3E0] px-5 py-3 text-sm text-[#6B4F2E]
                sm:flex-row sm:items-center sm:justify-between">
        <p data-total="{{ $rewards->total() }}" class="font-medium">Menampilkan {{ $rewards->count() }} dari {{ $rewards->total() }} data</p>
        <div class="flex flex-wrap items-center gap-4">
            <label class="flex items-center gap-2 font-medium">
                Tampilkan
                <select x-model="perPage" @change="load()" class="field py-1.5">
                    @foreach ($perPageOptions as $n)
                        <option value="{{ $n }}" @selected($perPage === $n)>{{ $n }}</option>
                    @endforeach
                </select>
            </label>
            {{ $rewards->links('pagination.compact') }}
        </div>
    </div>

    <div id="reward-list" @click="onPageClick($event)" :class="loading ? 'opacity-60' : ''" class="transition-opacity">
</div>
        </div>
    </div>

    <datalist id="reward-brand-list">
        @foreach ($brands as $name) <option value="{{ $name }}"></option> @endforeach
    </datalist>

    {{-- Modal: hadiah baru --}}
    <div x-show="modal === 'create'" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/50" @click="modal = null"></div>
        <div class="card relative w-full max-w-lg p-6 shadow-xl" x-transition>
            <h2 class="text-xl font-bold text-[#3B2E1A]">Hadiah Baru</h2>

            <form method="POST" action="{{ route('staff.cs.reward.store') }}" class="mt-5 space-y-4">
                @csrf
                <input type="hidden" name="_modal" value="create">

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-[#6B4F2E]">Brand</label>
                        <input type="text" name="brand" x-model="createForm.brand" x-ref="createFirst"
                               list="reward-brand-list" class="field w-full" autocomplete="off">
                        @if ($m === 'create') <p class="mt-1 text-xs text-red-600">{{ $errors->first('brand') }}</p> @endif
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-[#6B4F2E]">Jenis</label>
                        <input type="text" name="type" x-model="createForm.type" class="field w-full" autocomplete="off">
                        @if ($m === 'create') <p class="mt-1 text-xs text-red-600">{{ $errors->first('type') }}</p> @endif
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-[#6B4F2E]">Stok awal</label>
                        <input type="number" name="opening_stock" x-model="createForm.opening_stock" min="0" step="1"
                            class="field w-full" placeholder="0">
                        <p class="mt-1 text-xs text-slate-500">Opsional. Dicatat sebagai stok masuk pertama.</p>
                        @if ($m === 'create') <p class="mt-1 text-xs text-red-600">{{ $errors->first('opening_stock') }}</p> @endif
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-[#6B4F2E]">Stok minimum</label>
                        <input type="number" name="min_stock" x-model="createForm.min_stock" min="0" step="1" class="field w-full">
                        <p class="mt-1 text-xs text-slate-500">Menipis kalau stok sama dengan atau di bawah angka ini.</p>
                        @if ($m === 'create') <p class="mt-1 text-xs text-red-600">{{ $errors->first('min_stock') }}</p> @endif
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="modal = null" class="btn btn-outline">Batal</button>
                    <button type="submit" class="btn btn-brand">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal: edit --}}
    <div x-show="modal === 'edit'" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/50" @click="modal = null"></div>
        <div class="card relative w-full max-w-lg p-6 shadow-xl" x-transition>
            <h2 class="text-xl font-bold text-[#3B2E1A]">Edit Hadiah</h2>

            <form method="POST" :action="editAction" class="mt-5 space-y-4">
                @csrf
                <input type="hidden" name="_method" value="PUT">
                <input type="hidden" name="_modal" value="edit">
                <input type="hidden" name="_action" :value="editAction">

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-[#6B4F2E]">Brand</label>
                        <input type="text" name="brand" x-model="editForm.brand" x-ref="editFirst"
                               list="reward-brand-list" class="field w-full" autocomplete="off">
                        @if ($m === 'edit') <p class="mt-1 text-xs text-red-600">{{ $errors->first('brand') }}</p> @endif
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-[#6B4F2E]">Jenis</label>
                        <input type="text" name="type" x-model="editForm.type" class="field w-full" autocomplete="off">
                        @if ($m === 'edit') <p class="mt-1 text-xs text-red-600">{{ $errors->first('type') }}</p> @endif
                    </div>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-[#6B4F2E]">Stok minimum</label>
                    <input type="number" name="min_stock" x-model="editForm.min_stock" min="0" step="1" class="field w-full">
                    <p class="mt-1 text-xs text-slate-500">
                        Status "Menipis" muncul kalau stok sama dengan atau di bawah angka ini. Status "Habis" kalau stok 0.
                    </p>
                    @if ($m === 'edit') <p class="mt-1 text-xs text-red-600">{{ $errors->first('min_stock') }}</p> @endif
                </div>

                <p class="text-xs text-slate-500">
                    Stok tidak diubah di sini. Pakai "Tambah Stok" atau catat di tab Pengeluaran supaya riwayatnya tetap cocok.
                </p>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="modal = null" class="btn btn-outline">Batal</button>
                    <button type="submit" class="btn btn-brand">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal: tambah stok --}}
    <div x-show="modal === 'restock'" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/50" @click="modal = null"></div>
        <div class="card relative w-full max-w-lg p-6 shadow-xl" x-transition>
            <h2 class="text-xl font-bold text-[#3B2E1A]">Tambah Stok</h2>
            <p class="mt-1 text-sm font-semibold text-[#946A32]" x-text="label"></p>

            <form method="POST" action="{{ route('staff.cs.reward.stock-ins.store') }}" class="mt-5 space-y-4">
                @csrf
                <input type="hidden" name="_modal" value="restock">
                <input type="hidden" name="_label" :value="label">
                <input type="hidden" name="reward_id" :value="restockForm.reward_id">

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-[#6B4F2E]">Tanggal masuk</label>
                        <input type="date" name="received_on" x-model="restockForm.received_on" max="{{ $today }}" class="field w-full">
                        @if ($m === 'restock') <p class="mt-1 text-xs text-red-600">{{ $errors->first('received_on') }}</p> @endif
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-[#6B4F2E]">Jumlah</label>
                        <input type="number" name="quantity" x-model="restockForm.quantity" step="1"
                               x-ref="restockFirst" class="field w-full" placeholder="0">
                        @if ($m === 'restock') <p class="mt-1 text-xs text-red-600">{{ $errors->first('quantity') }}</p> @endif
                    </div>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-[#6B4F2E]">Keterangan</label>
                    <input type="text" name="notes" x-model="restockForm.notes" class="field w-full" autocomplete="off">
                    <p class="mt-1 text-xs text-slate-500">
                        Salah input sebelumnya? Isi jumlah dengan tanda minus (misalnya -3) dan tulis alasannya di sini.
                    </p>
                    @if ($m === 'restock') <p class="mt-1 text-xs text-red-600">{{ $errors->first('notes') ?: $errors->first('reward_id') }}</p> @endif
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="modal = null" class="btn btn-outline">Batal</button>
                    <button type="submit" class="btn btn-brand">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection