@extends('layouts.app')

@section('title', 'Akun')

@section('content')
<div
    x-data="{
        open: {{ old('_mode') ? 'true' : 'false' }},
        mode: @js(old('_mode', 'create')),
        action: @js(old('_action', route('admin.users.store'))),
        codeLabel: @js(old('_code_label', '')),
        isSelf: {{ old('_self') ? 'true' : 'false' }},
        name: @js(old('name', '')),
        employee_code: @js(old('employee_code', '')),
        role: @js(old('role', 'staff')),
        division_id: @js(old('division_id', '')),
        pin: @js(old('pin', '')),
        active_from: @js(old('active_from', today()->toDateString())),

        off: { open: false, action: '', name: '', date: @js(today()->toDateString()) },

        create() {
            this.mode = 'create';
            this.action = @js(route('admin.users.store'));
            this.codeLabel = '';
            this.isSelf = false;
            this.name = '';
            this.employee_code = '';
            this.role = 'staff';
            this.division_id = '';
            this.pin = '';
            this.active_from = @js(today()->toDateString());
            this.open = true;
            this.$nextTick(() => this.$refs.name.focus());
        },
        edit(url, user) {
            this.mode = 'edit';
            this.action = url;
            this.codeLabel = user.employee_code;
            this.isSelf = user.is_self;
            this.name = user.name;
            this.role = user.role;
            this.division_id = user.division_id ?? '';
            this.pin = '';
            this.active_from = user.active_from;
            this.open = true;
            this.$nextTick(() => this.$refs.name.focus());
        },
        randomPin() {
            this.pin = String(100000 + (crypto.getRandomValues(new Uint32Array(1))[0] % 900000));
        },
        deactivate(url, name) {
            this.off.action = url;
            this.off.name = name;
            this.off.date = @js(today()->toDateString());
            this.off.open = true;
        },
    }"
    @keydown.escape.window="open = false; off.open = false"
>

    <div class="mb-6">
        <h1 class="text-2xl font-semibold">Akun</h1>
        <p class="text-sm text-slate-500">Kelola akun staff, store manager, dan admin.</p>
    </div>

    <div class="card overflow-hidden"
         x-data="liveTable({
             url: @js(route('admin.users.index')),
             search: @js($search),
             perPage: @js($perPage),
             filters: { role: @js($roleFilter), division: @js($divisionFilter) },
         })">

        {{-- Toolbar: cari + filter role & divisi (kiri), tambah (kanan) --}}
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 p-4">
            <div class="flex w-full flex-1 flex-wrap items-center gap-3">
                <div class="relative w-full max-w-xs">
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

                <select x-model="filters.role"
                        @change="if (filters.role !== 'staff') filters.division = ''; load()"
                        class="field">
                    <option value="">Semua role</option>
                    @foreach ($roles as $r)
                        <option value="{{ $r->value }}">{{ $r->label() }}</option>
                    @endforeach
                </select>

                <select x-model="filters.division" @change="load()"
                        :disabled="filters.role !== 'staff'"
                        :title="filters.role !== 'staff' ? 'Pilih role Staff untuk memfilter divisi' : ''"
                        :class="filters.role !== 'staff' ? 'cursor-not-allowed opacity-50' : ''"
                        class="field">
                    <option value="">Semua divisi</option>
                    @foreach ($allDivisions as $division)
                        <option value="{{ $division->id }}">
                            {{ $division->name }}{{ $division->is_active ? '' : ' (nonaktif)' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <button type="button" @click="create()" class="btn btn-brand shrink-0">+ Tambah Akun</button>
        </div>

        {{-- Area yang diganti lewat AJAX --}}
        <div id="table-area" @click="onPageClick($event)"
             :class="loading ? 'opacity-50' : ''" class="transition-opacity">

            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Nama</th>
                        <th class="px-5 py-3">ID karyawan</th>
                        <th class="px-5 py-3">Role</th>
                        <th class="px-5 py-3">Divisi</th>
                        <th class="px-5 py-3">Aktif sejak</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($users as $user)
                        @php $isSelf = $user->id === auth()->id(); @endphp
                        <tr class="{{ $user->is_active ? '' : 'bg-slate-50 text-slate-400' }}">
                            <td class="px-5 py-3 font-medium">
                                {{ $user->name }}
                                @if ($isSelf)
                                    <span class="ml-1 text-xs font-normal text-slate-400">(Anda)</span>
                                @endif
                            </td>
                            <td class="px-5 py-3">{{ $user->employee_code }}</td>
                            <td class="px-5 py-3">
                                <span class="badge {{ match ($user->role) {
                                    \App\Role::Staff => 'badge-blue',
                                    \App\Role::Sm    => 'badge-amber',
                                    default          => 'badge-gray',
                                } }}">
                                    {{ $user->role->label() }}
                                </span>
                            </td>
                            <td class="px-5 py-3">{{ $user->division?->name ?? '-' }}</td>
                            <td class="px-5 py-3">{{ $user->active_from->format('d/m/Y') }}</td>
                            <td class="px-5 py-3">
                                <span class="badge {{ $user->is_active ? 'badge-green' : 'badge-gray' }}">
                                    {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                                @if (! $user->is_active && $user->active_until)
                                    <span class="ml-1 text-xs">berhenti {{ $user->active_until->format('d/m/Y') }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <button type="button" class="btn btn-outline btn-sm"
                                            @click="edit(@js(route('admin.users.update', $user)), @js([
                                                'name'          => $user->name,
                                                'employee_code' => $user->employee_code,
                                                'role'          => $user->role->value,
                                                'division_id'   => $user->division_id,
                                                'active_from'   => $user->active_from->toDateString(),
                                                'is_self'       => $isSelf,
                                            ]))">
                                        Edit
                                    </button>

                                    @if ($user->is_active)
                                        @unless ($isSelf)
                                            <button type="button" class="btn btn-danger btn-sm"
                                                    @click="deactivate(@js(route('admin.users.deactivate', $user)), @js($user->name))">
                                                Nonaktifkan
                                            </button>
                                        @endunless
                                    @else
                                        <form method="POST" action="{{ route('admin.users.activate', $user) }}"
                                              onsubmit="return confirm('Aktifkan akun {{ $user->name }}?')">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-success btn-sm">Aktifkan</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-10 text-center text-slate-500">
                                {{ ($search !== '' || $roleFilter !== '' || $divisionFilter !== '') ? 'Tidak ada akun yang cocok.' : 'Belum ada akun.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="flex flex-col gap-3 border-t border-slate-200 px-5 py-3 text-sm text-slate-500
                        sm:flex-row sm:items-center sm:justify-between">
                <p>Menampilkan {{ $users->count() }} dari {{ $users->total() }} data</p>

                <div class="flex flex-wrap items-center gap-4">
                    <label class="flex items-center gap-2">
                        Tampilkan
                        <select x-model="perPage" @change="load()" class="field py-1.5">
                            @foreach ($perPageOptions as $n)
                                <option value="{{ $n }}" @selected($perPage === $n)>{{ $n }}</option>
                            @endforeach
                        </select>
                    </label>

                    {{ $users->links('pagination.compact') }}
                </div>
            </div>
        </div>
    </div>

    {{-- Modal tambah / edit --}}
    <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/50" @click="open = false"></div>

        <div class="card relative w-full max-w-2xl shadow-xl" x-transition>
            {{-- Header: judul + tombol silang --}}
            <div class="flex items-center justify-between px-6 py-4">
                <h2 class="text-lg font-semibold"
                    x-text="mode === 'edit' ? 'Edit Akun' : 'Tambah Akun'"></h2>

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
                <input type="hidden" name="_code_label" :value="codeLabel">
                <input type="hidden" name="_self" :value="isSelf ? 1 : ''">

                <div class="grid grid-cols-1 gap-x-4 gap-y-4 px-6 py-5 sm:grid-cols-2">

                <div>
                    <label for="user-name" class="mb-1.5 block text-sm font-medium">Nama</label>
                    <input id="user-name" type="text" name="name" x-model="name" x-ref="name"
                           class="field w-full" placeholder="Nama lengkap">
                    @error('name')
                        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- ID karyawan: diketik saat tambah, terkunci saat edit --}}
                <div>
                    <label for="user-code" class="mb-1.5 block text-sm font-medium">ID karyawan</label>

                    <input id="user-code" type="text" name="employee_code" x-model="employee_code"
                           x-show="mode === 'create'" :disabled="mode !== 'create'"
                           class="field w-full" placeholder="Contoh: EMP001" autocomplete="off">

                    <div x-show="mode === 'edit'" x-cloak>
                        <div class="field bg-slate-50 text-slate-500" x-text="codeLabel"></div>
                        <p class="mt-1.5 text-xs text-slate-500">ID karyawan tidak bisa diubah.</p>
                    </div>

                    @error('employee_code')
                        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="user-role" class="mb-1.5 block text-sm font-medium">Role</label>
                    <select id="user-role" name="role" x-model="role" :disabled="isSelf" class="field w-full">
                        @foreach ($roles as $r)
                            <option value="{{ $r->value }}">{{ $r->label() }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1.5 text-xs text-slate-500" x-show="isSelf" x-cloak>
                        Role akun Anda sendiri tidak bisa diubah.
                    </p>
                    @error('role')
                        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Divisi hanya untuk staff: tetap tampil tapi disable untuk role lain --}}
                <div x-effect="if (role !== 'staff') division_id = ''">
                    <label for="user-division" class="mb-1.5 block text-sm font-medium">Divisi</label>
                    <select id="user-division" name="division_id" x-model="division_id"
                            :disabled="role !== 'staff'"
                            :class="role !== 'staff' ? 'cursor-not-allowed bg-slate-50 opacity-60' : ''"
                            class="field w-full">
                        <option value="">Pilih divisi</option>
                        @foreach ($allDivisions as $division)
                            <option value="{{ $division->id }}">
                                {{ $division->name }}{{ $division->is_active ? '' : ' (nonaktif)' }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1.5 text-xs text-slate-500" x-show="role !== 'staff'" x-cloak>
                        Divisi hanya untuk role Staff.
                    </p>
                    <p class="mt-1.5 text-xs text-slate-500" x-show="mode === 'edit' && role === 'staff'" x-cloak>
                        Memindahkan divisi tidak mengubah riwayat centang yang sudah ada.
                    </p>
                    @error('division_id')
                        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="user-pin" class="mb-1.5 block text-sm font-medium">PIN (6 angka)</label>
                    <div class="flex gap-2">
                        <input id="user-pin" type="text" name="pin" x-model="pin"
                               inputmode="numeric" maxlength="6" autocomplete="off"
                               @input="pin = pin.replace(/\D/g, '')"
                               class="field w-full font-mono tracking-widest"
                               :placeholder="mode === 'edit' ? 'Kosongkan jika tidak diubah' : '______'">
                        <button type="button" class="btn btn-outline shrink-0" @click="randomPin()">Acak</button>
                    </div>
                    <p class="mt-1.5 text-xs text-slate-500" x-show="mode === 'edit'" x-cloak>
                        Isi untuk reset PIN. Catat dulu, PIN tidak bisa dilihat lagi setelah disimpan.
                    </p>
                    @error('pin')
                        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="user-from" class="mb-1.5 block text-sm font-medium">Aktif sejak</label>
                    <input id="user-from" type="date" name="active_from" x-model="active_from" class="field w-full">
                    @error('active_from')
                        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                </div>{{-- /body --}}

                <div class="mx-5 border-t border-slate-200"></div>

                <div class="flex justify-end gap-2 px-6 py-4">
                    <button type="button" @click="open = false" class="btn btn-outline">Batal</button>
                    <button type="submit" class="btn btn-brand">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal nonaktifkan: isi tanggal berhenti --}}
    <div x-show="off.open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/50" @click="off.open = false"></div>

        <div class="card relative w-full max-w-sm shadow-xl" x-transition>
            <div class="flex items-center justify-between px-6 py-4">
                <h2 class="text-lg font-semibold">Nonaktifkan akun</h2>

                <button type="button" @click="off.open = false" aria-label="Tutup"
                        class="text-slate-400 transition-colors hover:text-red-600">
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor"
                         stroke-width="2" stroke-linecap="round"><path d="M5 5l10 10M15 5L5 15"/></svg>
                </button>
            </div>

            <div class="mx-5 border-t border-slate-200"></div>

            <form method="POST" :action="off.action">
                @csrf
                @method('PATCH')

                <div class="space-y-4 px-6 py-5">
                <p class="text-sm text-slate-500">
                    <span class="font-medium text-slate-700" x-text="off.name"></span>
                    tidak akan bisa login lagi.
                </p>

                <div>
                    <label for="off-date" class="mb-1.5 block text-sm font-medium">Tanggal berhenti</label>
                    <input id="off-date" type="date" name="active_until" x-model="off.date" class="field w-full">
                </div>

                </div>{{-- /body --}}

                <div class="mx-5 border-t border-slate-200"></div>

                <div class="flex justify-end gap-2 px-6 py-4">
                    <button type="button" @click="off.open = false" class="btn btn-outline">Batal</button>
                    <button type="submit" class="btn btn-danger">Nonaktifkan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection