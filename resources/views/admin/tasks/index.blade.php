@extends('layouts.app')

@section('title', 'Tugas')

@section('content')
<div
    x-data="{
        open: {{ old('_mode') ? 'true' : 'false' }},
        mode: @js(old('_mode', 'create')),
        action: @js(old('_action', route('admin.tasks.store'))),
        divisionLabel: @js(old('_division_label', '')),
        division_id: @js(old('division_id', '')),
        name: @js(old('name', '')),
        type: @js(old('type', 'routine')),
        create() {
            this.mode = 'create';
            this.action = @js(route('admin.tasks.store'));
            this.divisionLabel = '';
            this.division_id = '';
            this.name = '';
            this.type = 'routine';
            this.open = true;
            this.$nextTick(() => this.$refs.name.focus());
        },
        edit(url, task) {
            this.mode = 'edit';
            this.action = url;
            this.divisionLabel = task.division;
            this.name = task.name;
            this.type = task.type;
            this.open = true;
            this.$nextTick(() => this.$refs.name.focus());
        },
    }"
    @keydown.escape.window="open = false"
>

    <div class="mb-6">
        <h1 class="text-2xl font-semibold">Tugas</h1>
        <p class="text-sm text-slate-500">Daftar tugas yang dicentang karyawan tiap divisi.</p>
    </div>

    <div class="card overflow-hidden"
         x-data="liveTable({
             url: @js(route('admin.tasks.index')),
             search: @js($search),
             perPage: @js($perPage),
             filters: { division: @js($divisionFilter) },
         })">

        {{-- Toolbar: cari + filter divisi (kiri), tambah (kanan) --}}
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 p-4">
            <div class="flex w-full flex-1 flex-wrap items-center gap-3">
                <div class="relative w-full max-w-xs">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                         viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                        <circle cx="9" cy="9" r="6"/><path d="M14 14l4 4"/>
                    </svg>

                    <input type="text" x-model="search" @input.debounce.300ms="load()"
                           placeholder="Cari nama tugas..." autocomplete="off"
                           class="field w-full !pl-10 !pr-8">

                    <button type="button" x-show="search !== ''" x-cloak
                            @click="search = ''; load()"
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-lg leading-none text-slate-400 hover:text-slate-600"
                            aria-label="Hapus pencarian">&times;</button>
                </div>

                <select x-model="filters.division" @change="load()" class="field">
                    <option value="">Semua divisi</option>
                    @foreach ($allDivisions as $division)
                        <option value="{{ $division->id }}">
                            {{ $division->name }}{{ $division->is_active ? '' : ' (nonaktif)' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <button type="button" @click="create()" class="btn btn-brand shrink-0">+ Tambah Tugas</button>
        </div>

        {{-- Area yang diganti lewat AJAX --}}
        <div id="table-area" @click="onPageClick($event)"
             :class="loading ? 'opacity-50' : ''" class="transition-opacity">

            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="w-20 px-5 py-3">Urutan</th>
                        <th class="px-5 py-3">Nama tugas</th>
                        <th class="px-5 py-3">Divisi</th>
                        <th class="px-5 py-3">Tipe</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($tasks as $task)
                        <tr class="{{ $task->is_active ? '' : 'bg-slate-50 text-slate-400' }}">
                            <td class="px-5 py-3">{{ $task->sort_order }}</td>
                            <td class="px-5 py-3 font-medium">{{ $task->name }}</td>
                            <td class="px-5 py-3">{{ $task->division->name }}</td>
                            <td class="px-5 py-3">
                                <span class="badge {{ $task->type === \App\TaskType::Routine ? 'badge-blue' : 'badge-amber' }}">
                                    {{ $task->type->label() }}
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                <span class="badge {{ $task->is_active ? 'badge-green' : 'badge-gray' }}">
                                    {{ $task->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <button type="button" class="btn btn-outline btn-sm"
                                            @click="edit(@js(route('admin.tasks.update', $task)), @js([
                                                'name'     => $task->name,
                                                'type'     => $task->type->value,
                                                'division' => $task->division->name,
                                            ]))">
                                        Edit
                                    </button>

                                    <form method="POST" action="{{ route('admin.tasks.toggle', $task) }}"
                                          onsubmit="return confirm('{{ $task->is_active ? 'Nonaktifkan' : 'Aktifkan' }} tugas {{ $task->name }}?')">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                                class="btn btn-sm {{ $task->is_active ? 'btn-danger' : 'btn-success' }}">
                                            {{ $task->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-center text-slate-500">
                                {{ ($search !== '' || $divisionFilter !== '') ? 'Tidak ada tugas yang cocok.' : 'Belum ada tugas.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="flex flex-col gap-3 border-t border-slate-200 px-5 py-3 text-sm text-slate-500
                        sm:flex-row sm:items-center sm:justify-between">
                <p>Menampilkan {{ $tasks->count() }} dari {{ $tasks->total() }} data</p>

                <div class="flex flex-wrap items-center gap-4">
                    <label class="flex items-center gap-2">
                        Tampilkan
                        <select x-model="perPage" @change="load()" class="field py-1.5">
                            @foreach ($perPageOptions as $n)
                                <option value="{{ $n }}" @selected($perPage === $n)>{{ $n }}</option>
                            @endforeach
                        </select>
                    </label>

                    {{ $tasks->links('pagination.compact') }}
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
                    x-text="mode === 'edit' ? 'Edit Tugas' : 'Tambah Tugas'"></h2>

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
                <input type="hidden" name="_division_label" :value="divisionLabel">

                <div class="space-y-4 px-6 py-5">

                    {{-- Divisi: dipilih saat tambah, terkunci saat edit --}}
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Divisi</label>

                        <select name="division_id" x-model="division_id" x-show="mode === 'create'"
                                :disabled="mode !== 'create'" class="field w-full">
                            <option value="">Pilih divisi</option>
                            @foreach ($activeDivisions as $division)
                                <option value="{{ $division->id }}">{{ $division->name }}</option>
                            @endforeach
                        </select>

                        <div x-show="mode === 'edit'" x-cloak>
                            <div class="field bg-slate-50 text-slate-500" x-text="divisionLabel"></div>
                            <p class="mt-1.5 text-xs text-slate-500">
                                Divisi tidak bisa dipindah. Untuk memindahkan, nonaktifkan tugas ini dan buat baru di divisi lain.
                            </p>
                        </div>

                        @error('division_id')
                            <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="task-name" class="mb-1.5 block text-sm font-medium">Nama tugas</label>
                        <input id="task-name" type="text" name="name" x-model="name" x-ref="name"
                               class="field w-full" placeholder="Contoh: Retur barang">
                        @error('name')
                            <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Tipe</label>
                        <div class="grid grid-cols-2 gap-2">
                            <label class="cursor-pointer rounded-md border px-3 py-2.5 text-sm"
                                   :class="type === 'routine' ? 'border-[var(--brand)] bg-[var(--brand-soft)]' : 'border-slate-300'">
                                <input type="radio" name="type" value="routine" x-model="type" class="sr-only">
                                <span class="block font-medium">Rutin</span>
                                <span class="block text-xs text-slate-500">Dikerjakan hampir tiap hari</span>
                            </label>

                            <label class="cursor-pointer rounded-md border px-3 py-2.5 text-sm"
                                   :class="type === 'incidental' ? 'border-[var(--brand)] bg-[var(--brand-soft)]' : 'border-slate-300'">
                                <input type="radio" name="type" value="incidental" x-model="type" class="sr-only">
                                <span class="block font-medium">Insidental</span>
                                <span class="block text-xs text-slate-500">Muncul karena ada kejadian</span>
                            </label>
                        </div>
                        <p class="mt-1.5 text-xs text-slate-500" x-show="mode === 'edit'" x-cloak>
                            Mengubah tipe juga mengubah grafik periode-periode sebelumnya.
                        </p>
                        @error('type')
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