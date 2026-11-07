@extends('layouts.app')

@section('title', 'Divisi')

@section('content')
<div
    x-data="{
        open: {{ old('_mode') ? 'true' : 'false' }},
        mode: @js(old('_mode', 'create')),
        action: @js(old('_action', route('admin.divisions.store'))),
        name: @js(old('name', '')),
        create() {
            this.mode = 'create';
            this.action = @js(route('admin.divisions.store'));
            this.name = '';
            this.open = true;
            this.$nextTick(() => this.$refs.name.focus());
        },
        edit(url, name) {
            this.mode = 'edit';
            this.action = url;
            this.name = name;
            this.open = true;
            this.$nextTick(() => this.$refs.name.focus());
        },
    }"
    @keydown.escape.window="open = false"
>

    <div class="mb-6">
        <h1 class="text-2xl font-semibold">Divisi</h1>
        <p class="text-sm text-slate-500">Kelola daftar divisi toko.</p>
    </div>

    <div class="card overflow-hidden"
         x-data="liveTable({ url: @js(route('admin.divisions.index')), search: @js($search), perPage: @js($perPage) })">

        {{-- Toolbar: cari (kiri), tambah (kanan) --}}
        <div class="flex items-center justify-between gap-3 border-b border-slate-200 p-4">
            <div class="relative w-full max-w-xs">
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                     viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <circle cx="9" cy="9" r="6"/><path d="M14 14l4 4"/>
                </svg>

                <input type="text" x-model="search" @input.debounce.300ms="load()"
                       placeholder="Cari nama divisi..." autocomplete="off"
                       class="field w-full !pl-10 !pr-8">

                <button type="button" x-show="search !== ''" x-cloak
                        @click="search = ''; load()"
                        class="absolute right-2 top-1/2 -translate-y-1/2 text-lg leading-none text-slate-400 hover:text-slate-600"
                        aria-label="Hapus pencarian">&times;</button>
            </div>

            <button type="button" @click="create()" class="btn btn-brand shrink-0">+ Tambah Divisi</button>
        </div>

        {{-- Area yang diganti saat cari / ganti halaman / ganti jumlah --}}
        <div id="table-area" @click="onPageClick($event)"
             :class="loading ? 'opacity-50' : ''" class="transition-opacity">

            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Nama</th>
                        <th class="px-5 py-3">Staff aktif</th>
                        <th class="px-5 py-3">Tugas aktif</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($divisions as $division)
                        <tr class="{{ $division->is_active ? '' : 'bg-slate-50 text-slate-400' }}">
                            <td class="px-5 py-3 font-medium">{{ $division->name }}</td>
                            <td class="px-5 py-3">{{ $division->users_count }}</td>
                            <td class="px-5 py-3">{{ $division->tasks_count }}</td>
                            <td class="px-5 py-3">
                                <span class="badge {{ $division->is_active ? 'badge-green' : 'badge-gray' }}">
                                    {{ $division->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <button type="button" class="btn btn-outline btn-sm"
                                            @click="edit(@js(route('admin.divisions.update', $division)), @js($division->name))">
                                        Edit
                                    </button>

                                    <form method="POST" action="{{ route('admin.divisions.toggle', $division) }}"
                                          onsubmit="return confirm('{{ $division->is_active ? 'Nonaktifkan' : 'Aktifkan' }} divisi {{ $division->name }}?')">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                                class="btn btn-sm {{ $division->is_active ? 'btn-danger' : 'btn-success' }}">
                                            {{ $division->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-10 text-center text-slate-500">
                                {{ $search !== '' ? 'Tidak ada divisi yang cocok.' : 'Belum ada divisi.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            {{-- Footer: info (kiri), jumlah per halaman + pagination (kanan) --}}
            <div class="flex flex-col gap-3 border-t border-slate-200 px-5 py-3 text-sm text-slate-500
                        sm:flex-row sm:items-center sm:justify-between">
                <p>Menampilkan {{ $divisions->count() }} dari {{ $divisions->total() }} data</p>

                <div class="flex flex-wrap items-center gap-4">
                    <label class="flex items-center gap-2">
                        Tampilkan
                        <select x-model="perPage" @change="load()" class="field py-1.5">
                            @foreach ($perPageOptions as $n)
                                <option value="{{ $n }}" @selected($perPage === $n)>{{ $n }}</option>
                            @endforeach
                        </select>
                    </label>

                    {{ $divisions->links('pagination.compact') }}
                </div>
            </div>
        </div>
    </div>

    {{-- Modal tambah / edit --}}
    <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/50" @click="open = false"></div>

        <div class="card relative w-full max-w-md shadow-xl" x-transition>
            {{-- Header: judul + tombol silang --}}
            <div class="flex items-center justify-between px-6 py-4">
                <h2 class="text-lg font-semibold"
                    x-text="mode === 'edit' ? 'Edit Divisi' : 'Tambah Divisi'"></h2>

                <button type="button" @click="open = false" aria-label="Tutup"
                        class="text-slate-400 transition-colors hover:text-red-600">
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor"
                         stroke-width="2" stroke-linecap="round"><path d="M5 5l10 10M15 5L5 15"/></svg>
                </button>
            </div>

            <div class="mx-5 border-t border-slate-200"></div>

            <form method="POST" :action="action">
                @csrf
                <input type="hidden" name="_method" value="PUT" :disabled="mode !== 'edit'">
                <input type="hidden" name="_mode" :value="mode">
                <input type="hidden" name="_action" :value="action">

                <div class="space-y-4 px-6 py-5">
                    <div>
                        <label for="name" class="mb-1.5 block text-sm font-medium">Nama divisi</label>
                        <input id="name" type="text" name="name" x-model="name" x-ref="name"
                               class="field w-full" placeholder="Contoh: Kasir">
                        @error('name')
                            <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mx-5 border-t border-slate-200"></div>

                <div class="flex justify-end gap-2 px-6 py-4">
                    <button type="button" @click="open = false" class="btn btn-outline">Batal</button>
                    <button type="submit" class="btn btn-brand">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection