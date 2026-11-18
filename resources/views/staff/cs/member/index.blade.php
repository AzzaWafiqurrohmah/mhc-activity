@extends('layouts.app')

@section('title', 'Member')

@section('content')
<div
    x-data="{
        open: {{ old('_mode') ? 'true' : 'false' }},
        importOpen: {{ old('_import') ? 'true' : 'false' }},
        mode: @js(old('_mode', 'create')),
        action: @js(old('_action', route('staff.cs.member.store'))),
        f: {
            member_no:    @js(old('member_no', '')),
            name:         @js(old('name', '')),
            phone:        @js(old('phone', '')),
            nik:          @js(old('nik', '')),
            address:      @js(old('address', '')),
            kelurahan:    @js(old('kelurahan', '')),
            kecamatan:    @js(old('kecamatan', '')),
            occupation:   @js(old('occupation', '')),
            radius_value: @js(old('radius_value', '')),
            radius_unit:  @js(old('radius_unit', 'm')),
            area_type:    @js(old('area_type', 'dalam_kota')),
        },
        blank() {
            return { member_no: '', name: '', phone: '', nik: '', address: '', kelurahan: '', kecamatan: '',
                     occupation: '', radius_value: '', radius_unit: 'm', area_type: 'dalam_kota' };
        },
        create() {
            this.mode = 'create';
            this.action = @js(route('staff.cs.member.store'));
            this.f = this.blank();
            this.open = true;
            this.$nextTick(() => this.$refs.first.focus());
        },
        edit(url, data) {
            this.mode = 'edit';
            this.action = url;
            this.f = { ...this.blank(), ...data };
            this.open = true;
            this.$nextTick(() => this.$refs.first.focus());
        },
    }"
    @keydown.escape.window="open = false"
>

    {{-- Judul --}}
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-[#3B2E1A]">Member</h1>
            <p class="mt-1 text-base font-medium text-[#6B4F2E]">Data member yang diinput CS.</p>
        </div>
        <button type="button" @click="create()" class="btn btn-brand">+ Tambah Member</button>
    </div>

    @if (session('import_result'))
        @php
            $r  = session('import_result');
            $ok = $r['created'] + $r['updated'];
            $n  = fn ($v) => number_format($v, 0, ',', '.');

            if ($r['failed'] > 0 && $ok === 0) {
                $toast = ['type' => 'error', 'title' => 'Impor gagal',
                        'message' => 'Semua ' . $n($r['failed']) . ' baris bermasalah. Lihat detailnya di halaman ini.'];
            } elseif ($r['failed'] > 0) {
                $toast = ['type' => 'warning', 'title' => 'Impor selesai dengan catatan',
                        'message' => $n($ok) . ' data berhasil masuk, ' . $n($r['failed']) . ' baris gagal. Lihat detailnya di halaman ini.'];
            } elseif ($ok === 0) {
                $toast = ['type' => 'warning', 'title' => 'Tidak ada data baru',
                        'message' => 'Semua ' . $n($r['skipped']) . ' data sudah ada di aplikasi, jadi dilewati.'];
            } else {
                $parts = [$n($r['created']) . ' data baru masuk'];
                if ($r['updated']) $parts[] = $n($r['updated']) . ' diperbarui';
                if ($r['skipped']) $parts[] = $n($r['skipped']) . ' dilewati';
                $toast = ['type' => 'success', 'title' => 'Impor berhasil', 'message' => implode(', ', $parts) . '.'];
            }
        @endphp

        <div x-data x-init="window.toast(@js($toast))"></div>

        <div x-data="{ show: true }" x-show="show" class="card mb-4 p-5">
            <div class="flex items-start justify-between gap-4">
                <h2 class="text-lg font-bold text-[#3B2E1A]">Hasil impor</h2>
                <button type="button" @click="show = false"
                        class="text-2xl leading-none text-slate-400 hover:text-slate-600" aria-label="Tutup">&times;</button>
            </div>

            <div class="mt-3 grid gap-3 sm:grid-cols-4">
                <div class="rounded-lg border border-emerald-300 bg-emerald-100 p-4">
                    <p class="text-sm font-semibold text-emerald-800">Baru masuk</p>
                    <p class="mt-1 text-3xl font-bold text-emerald-800">{{ $n($r['created']) }}</p>
                </div>
                <div class="rounded-lg border border-[#E2C47F] bg-[#F0D9A8] p-4">
                    <p class="text-sm font-semibold text-[#6B4F2E]">Diperbarui</p>
                    <p class="mt-1 text-3xl font-bold text-[#4A3420]">{{ $n($r['updated']) }}</p>
                </div>
                <div class="rounded-lg border border-stone-300 bg-stone-200 p-4">
                    <p class="text-sm font-semibold text-stone-700">Dilewati</p>
                    <p class="mt-1 text-3xl font-bold text-stone-700">{{ $n($r['skipped']) }}</p>
                    <p class="text-xs text-stone-600">no member sudah ada</p>
                </div>
                <div class="rounded-lg border p-4 {{ $r['failed'] ? 'border-red-300 bg-red-100' : 'border-slate-300 bg-slate-200' }}">
                    <p class="text-sm font-semibold {{ $r['failed'] ? 'text-red-800' : 'text-slate-600' }}">Gagal</p>
                    <p class="mt-1 text-3xl font-bold {{ $r['failed'] ? 'text-red-800' : 'text-slate-600' }}">{{ $n($r['failed']) }}</p>
                </div>
            </div>

            @if (count($r['unmatched']))
                <p class="mt-4 text-sm text-slate-700">
                    <span class="font-semibold">Nama CS tanpa akun</span> (tetap tersimpan sebagai teks):
                    {{ implode(', ', $r['unmatched']) }}
                </p>
            @endif

            @if (count($r['errors']))
                <div class="mt-4">
                    <p class="text-sm font-semibold text-red-700">Baris yang gagal:</p>
                    <ul class="mt-1 max-h-48 space-y-0.5 overflow-y-auto text-sm text-slate-700">
                        @foreach ($r['errors'] as $e)
                            <li>Baris {{ $e['row'] }}: {{ $e['message'] }}</li>
                        @endforeach
                    </ul>
                    @if ($r['failed'] > count($r['errors']))
                        <p class="mt-1 text-xs text-slate-500">Hanya 30 baris pertama yang ditampilkan.</p>
                    @endif
                </div>
            @endif
        </div>
    @endif

    <div x-data="liveTable({
            url: @js(route('staff.cs.member.index')),
            search: @js($search),
            perPage: @js($perPage),
            filters: {
                kecamatan: @js($filters['kecamatan']), kelurahan: @js($filters['kelurahan']),
                area: @js($filters['area']), cs: @js($filters['cs']),
                from: @js($filters['from']), to: @js($filters['to']),
                sort: @js($sort),
            },
            regions: ['member-filters', 'member-list'],
         })">

        {{-- Cari (di luar area yang diganti, supaya fokus ketikan aman) --}}
<div class="card mb-4 p-4">

    {{-- Cari + Impor + Ekspor --}}
    <div class="flex flex-wrap items-center gap-3">
        <div class="relative min-w-[16rem] flex-1">
            <svg class="pointer-events-none absolute left-3.5 top-1/2 h-[18px] w-[18px] -translate-y-1/2 text-slate-400"
                 viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                <circle cx="9" cy="9" r="6"/><path d="M14 14l4 4"/>
            </svg>

            <input type="text" x-model="search" @input.debounce.300ms="load()" autocomplete="off"
                   placeholder="Cari di semua data: nama, no HP, no KTP, alamat, no member, kelurahan, pekerjaan..."
                   class="field w-full text-base" style="padding-left: 2.6rem; padding-right: 2.2rem">

            <button type="button" x-show="search !== ''" x-cloak @click="search = ''; load()"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-xl leading-none text-slate-400 hover:text-slate-600"
                    aria-label="Hapus pencarian">&times;</button>
        </div>

        <button type="button" class="btn btn-gold" @click="importOpen = true">
            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" stroke-linejoin="round">
                <path d="M10 13V3M6 7l4-4 4 4M3 13v3a1 1 0 001 1h12a1 1 0 001-1v-3"/>
            </svg>
            Impor Excel
        </button>

        <a :href="exportUrl(@js(route('staff.cs.member.export')))"
        @click.prevent="exportFile(@js(route('staff.cs.member.export')))"
        :class="exporting ? 'pointer-events-none opacity-70' : ''"
        class="btn btn-green">
            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round">
                <path d="M10 3v10M6 9l4 4 4-4M3 13v3a1 1 0 001 1h12a1 1 0 001-1v-3"/>
            </svg>
            <span x-text="exporting ? 'Menyiapkan...' : 'Ekspor Excel'">Ekspor Excel</span>
        </a>
    </div>

    {{-- Filter --}}
    @php $me = auth()->user()->name; @endphp

    <div id="member-filters" :class="loading ? 'opacity-60' : ''"
         class="mt-3 space-y-3 transition-opacity">

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
            <select x-model="filters.kecamatan" @change="filters.kelurahan = ''; load()" class="field">
                <option value="">Semua kecamatan</option>
                @foreach ($kecamatans as $name)
                    <option value="{{ $name }}">{{ $name }}</option>
                @endforeach
            </select>

            <select x-model="filters.kelurahan" @change="load()" class="field">
                <option value="">Semua kelurahan</option>
                @foreach ($kelurahans as $name)
                    <option value="{{ $name }}">{{ $name }}</option>
                @endforeach
            </select>

            <select x-model="filters.area" @change="load()" class="field">
                <option value="">Dalam &amp; luar kota</option>
                @foreach ($areaTypes as $type)
                    <option value="{{ $type->value }}">{{ $type->label() }}</option>
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

        {{-- Daftar --}}
        <div class="card overflow-hidden">
            <div id="member-list" @click="onPageClick($event)" :class="loading ? 'opacity-60' : ''"
                 class="transition-opacity">

                {{-- Judul kolom --}}
                <div class="hidden gap-4 border-b border-[#E2C47F] bg-[#F7ECD0] px-5 py-3 text-[13px] font-semibold
                            text-[#6B4F2E] lg:grid lg:grid-cols-[1.7fr_1.1fr_1.5fr_0.9fr_1fr_1fr_auto]">
                    <span>Member</span><span>Kontak</span><span>Alamat</span><span>Pekerjaan</span>
                    <span>Jarak</span>

                    <button type="button" class="flex items-center gap-1 text-left font-semibold hover:text-[#3B2E1A]"
                            title="Klik untuk membalik urutan"
                            @click="filters.sort = filters.sort === 'newest' ? 'oldest' : 'newest'; load()">
                        Diinput
                        <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2"
                            stroke-linecap="round" stroke-linejoin="round">
                            @if ($sort === 'newest') <path d="M5 8l5 5 5-5"/> @else <path d="M5 12l5-5 5 5"/> @endif
                        </svg>
                    </button>

                    <span class="w-16"></span>
                </div>

                <div class="divide-y divide-[#EFE2C2]">
                    @forelse ($members as $member)
                        @php
                            $initials = \Illuminate\Support\Str::of($member->name)->explode(' ')->filter()->take(2)
                                ->map(fn ($w) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($w, 0, 1)))
                                ->implode('');
                        @endphp

                        <div class="grid items-start gap-x-4 gap-y-2 px-5 py-4 transition hover:bg-[#FFF8E6]
                                    lg:grid-cols-[1.25fr_1.1fr_1.6fr_0.9fr_1fr_1fr_auto]">

                            <div class="flex min-w-0 items-center gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-[15px] font-semibold text-[#3B2E1A]">{{ $member->name }}</p>
                                    <p class="font-mono text-[13px] text-slate-500">{{ $member->member_no }}</p>
                                </div>
                            </div>

                            <div class="text-[15px]">
                                <p class="font-medium text-slate-800">{{ $member->phone ?: '-' }}</p>
                                <p class="font-mono text-[13px] text-slate-500">{{ $member->nik ?: '-' }}</p>
                            </div>

                            <div class="min-w-0 text-[15px]">
                                <p class="line-clamp-2 text-slate-800">{{ $member->address }}</p>
                                <p class="text-[13px] text-slate-500">Kel. {{ $member->kelurahan }} · Kec. {{ $member->kecamatan }}</p>
                            </div>

                            <div class="text-[15px] text-slate-700">{{ $member->occupation ?: '-' }}</div>

                            <div class="text-[15px]">
                                <p class="font-medium text-slate-800">{{ $member->radiusLabel() }}</p>
                                <span class="badge {{ $member->area_type === \App\AreaType::Inside ? 'badge-green' : 'badge-amber' }}">
                                    {{ $member->area_type->label() }}
                                </span>
                            </div>

                            <div class="text-[15px]">
                                <p class="font-medium text-slate-800">{{ $member->input_date->format('d/m/Y') }}</p>
                                <p class="text-[13px] text-slate-500">{{ $member->created_by_name }}</p>
                            </div>

                            <div class="lg:w-16 lg:text-right">
                                <button type="button" class="btn btn-outline btn-sm"
                                        @click="edit(@js(route('staff.cs.member.update', $member)), @js([
                                            'member_no'    => $member->member_no,
                                            'name'         => $member->name,
                                            'phone'        => $member->phone,
                                            'nik'          => $member->nik,
                                            'address'      => $member->address,
                                            'kelurahan'    => $member->kelurahan,
                                            'kecamatan'    => $member->kecamatan,
                                            'occupation'   => $member->occupation,
                                            'radius_value' => $member->radiusForm()['value'],
                                            'radius_unit'  => $member->radiusForm()['unit'],
                                            'area_type'    => $member->area_type->value,
                                        ]))">
                                    Edit
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="px-5 py-14 text-center text-base text-slate-500">
                            Tidak ada member yang cocok dengan pencarian atau filter ini.
                        </div>
                    @endforelse
                </div>

                <div class="flex flex-col gap-3 border-t border-[#EFE2C2] bg-[#FBF3E0] px-5 py-3 text-sm text-[#6B4F2E]
                            sm:flex-row sm:items-center sm:justify-between">
                    <p data-total="{{ $members->total() }}" class="font-medium">Menampilkan {{ $members->count() }} dari {{ $members->total() }} data</p>
                    <div class="flex flex-wrap items-center gap-4">
                        <label class="flex items-center gap-2 font-medium">
                            Tampilkan
                            <select x-model="perPage" @change="load()" class="field py-1.5">
                                @foreach ($perPageOptions as $n)
                                    <option value="{{ $n }}" @selected($perPage === $n)>{{ $n }}</option>
                                @endforeach
                            </select>
                        </label>

                        {{ $members->links('pagination.compact') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Saran otomatis untuk kelurahan dan kecamatan --}}
    <datalist id="kecamatan-list">
        @foreach ($kecamatans as $name) <option value="{{ $name }}"></option> @endforeach
    </datalist>
    <datalist id="kelurahan-list">
        @foreach ($allKelurahans as $name) <option value="{{ $name }}"></option> @endforeach
    </datalist>

    <div x-show="importOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/50" @click="importOpen = false"></div>

    <div class="card relative max-h-[92vh] w-full max-w-xl overflow-y-auto p-6 shadow-xl"
         x-data="{ busy: false }" x-transition>

        <h2 class="text-xl font-bold text-[#3B2E1A]">Impor dari Excel</h2>
        <p class="mt-1 text-sm text-slate-600">
            Judul kolom di baris pertama dikenali otomatis. Paling aman memakai
            <a href="{{ route('staff.cs.member.template') }}" class="font-semibold text-[#946A32] underline">template ini</a>.
        </p>

        <div class="mt-4 rounded-md border border-[#E2C47F] bg-[#FBF3E0] p-3 text-[13px] text-[#6B4F2E]">
            <p><span class="font-semibold">Kolom:</span> Tanggal Input, CS yang Input, No Member, Nama, Alamat,
               Kelurahan, Kecamatan, No KTP, No HP, Pekerjaan, Radius, Keterangan.</p>
            <p class="mt-1.5"><span class="font-semibold">Penting:</span> ubah format kolom <b>No Member, No KTP, dan No HP</b>
               di Excel jadi <b>Text</b> sebelum mengisi. Kalau dibiarkan format angka, Excel merusak KTP 16 digit
               dan membuang angka nol di depan HP, dan itu tidak bisa dipulihkan.</p>
            <p class="mt-1.5">Hanya <b>No Member dan Nama</b> yang wajib. Alamat, kelurahan, dan kecamatan yang kosong diisi "-".</p>
        </div>

        <form method="POST" action="{{ route('staff.cs.member.import') }}" enctype="multipart/form-data"
              class="mt-5 space-y-4" @submit="busy = true">
            @csrf
            <input type="hidden" name="_import" value="1">

            <div>
                <label class="mb-1.5 block text-sm font-semibold text-[#6B4F2E]">File Excel</label>
                <input type="file" name="file" accept=".xlsx,.xls,.csv" class="field w-full">
                <p class="mt-1 text-xs text-red-600">{{ $errors->first('file') }}</p>
            </div>

            <div>
                <p class="mb-1.5 text-sm font-semibold text-[#6B4F2E]">Radius yang tidak tertulis satuannya dianggap</p>
                <div class="flex gap-5 text-sm">
                    <label class="flex items-center gap-2">
                        <input type="radio" name="default_unit" value="km" class="accent-[#946A32]"
                               {{ old('default_unit', 'km') === 'km' ? 'checked' : '' }}> Kilometer (km)
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="radio" name="default_unit" value="m" class="accent-[#946A32]"
                               {{ old('default_unit', 'km') === 'm' ? 'checked' : '' }}> Meter (m)
                    </label>
                </div>
            </div>

            <div>
                <p class="mb-1.5 text-sm font-semibold text-[#6B4F2E]">Kalau no member sudah ada di aplikasi</p>
                <div class="space-y-1.5 text-sm">
                    <label class="flex items-center gap-2">
                        <input type="radio" name="on_duplicate" value="skip" class="accent-[#946A32]"
                               {{ old('on_duplicate', 'skip') === 'skip' ? 'checked' : '' }}> Lewati, data yang ada tidak diubah
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="radio" name="on_duplicate" value="update" class="accent-[#946A32]"
                               {{ old('on_duplicate', 'skip') === 'update' ? 'checked' : '' }}> Perbarui dengan isi Excel
                    </label>
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" @click="importOpen = false" class="btn btn-outline">Batal</button>
                <button type="submit" class="btn btn-brand" :disabled="busy">
                    <span x-text="busy ? 'Mengimpor...' : 'Impor'">Impor</span>
                </button>
            </div>
        </form>
    </div>
</div>

    {{-- Modal tambah / edit --}}
    <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/50" @click="open = false"></div>

        <div class="card relative max-h-[92vh] w-full max-w-2xl overflow-y-auto p-6 shadow-xl" x-transition>
            <h2 class="text-xl font-bold text-[#3B2E1A]"
                x-text="mode === 'edit' ? 'Edit Member' : 'Tambah Member'"></h2>

            <form method="POST" :action="action" class="mt-5 space-y-4">
                @csrf
                <input type="hidden" name="_method" value="PUT" :disabled="mode !== 'edit'">
                <input type="hidden" name="_mode" :value="mode">
                <input type="hidden" name="_action" :value="action">

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-[#6B4F2E]">No member</label>
                        <input type="text" name="member_no" x-model="f.member_no" x-ref="first" class="field w-full font-mono" autocomplete="off">
                        <p class="mt-1 text-xs text-red-600">{{ $errors->first('member_no') }}</p>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-[#6B4F2E]">Nama</label>
                        <input type="text" name="name" x-model="f.name" class="field w-full" autocomplete="off">
                        <p class="mt-1 text-xs text-red-600">{{ $errors->first('name') }}</p>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-[#6B4F2E]">No HP</label>
                        <input type="text" name="phone" x-model="f.phone" inputmode="tel" class="field w-full" autocomplete="off">
                        <p class="mt-1 text-xs text-red-600">{{ $errors->first('phone') }}</p>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-[#6B4F2E]">No KTP</label>
                        <input type="text" name="nik" x-model="f.nik" inputmode="numeric" class="field w-full font-mono" autocomplete="off">
                        <p class="mt-1 text-xs text-red-600">{{ $errors->first('nik') }}</p>
                    </div>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-[#6B4F2E]">Alamat</label>
                    <textarea name="address" x-model="f.address" rows="2" class="field w-full"></textarea>
                    <p class="mt-1 text-xs text-red-600">{{ $errors->first('address') }}</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-[#6B4F2E]">Kelurahan</label>
                        <input type="text" name="kelurahan" x-model="f.kelurahan" list="kelurahan-list" class="field w-full" autocomplete="off">
                        <p class="mt-1 text-xs text-red-600">{{ $errors->first('kelurahan') }}</p>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-[#6B4F2E]">Kecamatan</label>
                        <input type="text" name="kecamatan" x-model="f.kecamatan" list="kecamatan-list" class="field w-full" autocomplete="off">
                        <p class="mt-1 text-xs text-red-600">{{ $errors->first('kecamatan') }}</p>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-[#6B4F2E]">Pekerjaan</label>
                        <input type="text" name="occupation" x-model="f.occupation" class="field w-full" autocomplete="off">
                        <p class="mt-1 text-xs text-red-600">{{ $errors->first('occupation') }}</p>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-[#6B4F2E]">Radius ke MHC</label>
                        <div class="flex gap-2">
                            <input type="number" name="radius_value" x-model="f.radius_value" min="0" step="any"
                                   class="field w-full" placeholder="0">
                            <select name="radius_unit" x-model="f.radius_unit" class="field w-24">
                                <option value="m">m</option>
                                <option value="km">km</option>
                            </select>
                        </div>
                        <p class="mt-1 text-xs text-red-600">{{ $errors->first('radius_value') ?: $errors->first('radius_unit') }}</p>
                    </div>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-[#6B4F2E]">Keterangan wilayah</label>
                    <div class="grid grid-cols-2 gap-2">
                        @foreach ($areaTypes as $type)
                            <label class="cursor-pointer rounded-md border px-3 py-2.5 text-center text-sm font-semibold"
                                   :class="f.area_type === '{{ $type->value }}'
                                           ? 'border-[var(--brand)] bg-[var(--brand-soft)] text-[#4A3420]'
                                           : 'border-slate-300 text-slate-600'">
                                <input type="radio" name="area_type" value="{{ $type->value }}" x-model="f.area_type" class="sr-only">
                                {{ $type->label() }}
                            </label>
                        @endforeach
                    </div>
                    <p class="mt-1 text-xs text-red-600">{{ $errors->first('area_type') }}</p>
                </div>

                <p class="text-xs text-slate-500" x-show="mode === 'create'">
                    Tanggal input dan nama CS terisi otomatis dari akun yang sedang login.
                </p>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="open = false" class="btn btn-outline">Batal</button>
                    <button type="submit" class="btn btn-brand">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection