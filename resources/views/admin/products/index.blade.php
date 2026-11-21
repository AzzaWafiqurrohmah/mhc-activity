@extends('layouts.app')

@section('title', 'Produk')

@section('content')
<div
    x-data="{
        open: {{ old('_mode') ? 'true' : 'false' }},
        importOpen: {{ old('_import') ? 'true' : 'false' }},
        mode: @js(old('_mode', 'create')),
        action: @js(old('_action', route('admin.products.store'))),
        barcode: @js(old('barcode', '')),
        name: @js(old('name', '')),
        division_id: @js(old('division_id') ?? ''),
        create() {
            this.mode = 'create';
            this.action = @js(route('admin.products.store'));
            this.barcode = '';
            this.name = '';
            this.division_id = '';
            this.open = true;
            this.$nextTick(() => this.$refs.barcode.focus());
        },
        edit(url, product) {
            this.mode = 'edit';
            this.action = url;
            this.barcode = product.barcode;
            this.name = product.name;
            this.division_id = product.division_id;
            this.open = true;
            this.$nextTick(() => this.$refs.barcode.focus());
        },
    }"
    @keydown.escape.window="open = false"
>

    <div class="mb-6">
        <h1 class="text-2xl font-semibold">Produk</h1>
        <p class="text-sm text-slate-500">Daftar barang yang tersedia di toko</p>
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
                        'message' => 'Semua ' . $n($r['skipped']) . ' barcode sudah ada di aplikasi, jadi dilewati.'];
            } else {
                $parts = [$n($r['created']) . ' produk baru masuk'];
                if ($r['updated']) $parts[] = $n($r['updated']) . ' diperbarui';
                if ($r['skipped']) $parts[] = $n($r['skipped']) . ' dilewati';
                $toast = ['type' => 'success', 'title' => 'Impor berhasil', 'message' => implode(', ', $parts) . '.'];
            }
        @endphp

        <div x-data x-init="window.toast(@js($toast))"></div>

        <div x-data="{ show: true }" x-show="show" class="card mb-4 p-5">
            <div class="flex items-start justify-between gap-4">
                <h2 class="text-lg font-semibold">Hasil impor</h2>
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
                    <p class="text-xs text-stone-600">barcode sudah ada</p>
                </div>
                <div class="rounded-lg border p-4 {{ $r['failed'] ? 'border-red-300 bg-red-100' : 'border-slate-300 bg-slate-200' }}">
                    <p class="text-sm font-semibold {{ $r['failed'] ? 'text-red-800' : 'text-slate-600' }}">Gagal</p>
                    <p class="mt-1 text-3xl font-bold {{ $r['failed'] ? 'text-red-800' : 'text-slate-600' }}">{{ $n($r['failed']) }}</p>
                </div>
            </div>

            @if (count($r['unmatched']))
                <div class="mt-4 text-sm text-slate-700">
                    <p class="font-semibold text-amber-700">Divisi tidak dikenali (produknya tetap diproses, divisinya tidak diisi):</p>
                    <ul class="mt-1 space-y-0.5">
                        @foreach ($r['unmatched'] as $u)
                            <li>
                                "{{ $u['name'] }}" di {{ $n($u['count']) }} baris
                                @if ($u['hint']) <span class="text-slate-500">({{ $u['hint'] }})</span> @endif
                            </li>
                        @endforeach
                    </ul>
                    <p class="mt-1 text-xs text-slate-500">
                        Betulkan tulisannya di Excel atau tambahkan divisinya di menu Divisi, lalu impor lagi dengan pilihan "Perbarui".
                    </p>
                </div>
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

    <div class="card overflow-hidden"
         x-data="liveTable({
             url: @js(route('admin.products.index')),
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
                           placeholder="Cari barcode atau nama produk..." autocomplete="off"
                           class="field w-full !pl-10 !pr-8">

                    <button type="button" x-show="search !== ''" x-cloak
                            @click="search = ''; load()"
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-lg leading-none text-slate-400 hover:text-slate-600"
                            aria-label="Hapus pencarian">&times;</button>
                </div>

                <select x-model="filters.division" @change="load()" class="field">
                    <option value="">Semua divisi</option>
                    <option value="none">Tanpa divisi</option>
                    @foreach ($allDivisions as $division)
                        <option value="{{ $division->id }}">
                            {{ $division->name }}{{ $division->is_active ? '' : ' (nonaktif)' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex shrink-0 flex-wrap items-center gap-2">
                <button type="button" class="btn btn-gold" @click="importOpen = true">
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M10 13V3M6 7l4-4 4 4M3 13v3a1 1 0 001 1h12a1 1 0 001-1v-3"/>
                    </svg>
                    Impor Excel
                </button>

                <a :href="exportUrl(@js(route('admin.products.export')))"
                @click.prevent="exportFile(@js(route('admin.products.export')))"
                :class="exporting ? 'pointer-events-none opacity-70' : ''" class="btn btn-green">
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M10 3v10M6 9l4 4 4-4M3 13v3a1 1 0 001 1h12a1 1 0 001-1v-3"/>
                    </svg>
                    <span x-text="exporting ? 'Menyiapkan...' : 'Ekspor Excel'">Ekspor Excel</span>
                </a>

                <button type="button" @click="create()" class="btn btn-brand">+ Tambah Produk</button>
            </div>
        </div>

        {{-- Area yang diganti lewat AJAX --}}
        <div id="table-area" @click="onPageClick($event)"
             :class="loading ? 'opacity-50' : ''" class="transition-opacity">

            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Barcode</th>
                        <th class="px-5 py-3">Nama produk</th>
                        <th class="px-5 py-3">Divisi</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($products as $product)
                        <tr class="{{ $product->is_active ? '' : 'bg-slate-50 text-slate-400' }}">
                            <td class="px-5 py-3 font-mono text-[13px]">{{ $product->barcode }}</td>
                            <td class="px-5 py-3 font-medium">{{ $product->name }}</td>
                            <td class="px-5 py-3">{{ $product->division?->name ?? '-' }}</td>
                            <td class="px-5 py-3">
                                <span class="badge {{ $product->is_active ? 'badge-green' : 'badge-gray' }}">
                                    {{ $product->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <button type="button" class="btn btn-outline btn-sm"
                                            @click="edit(@js(route('admin.products.update', $product)), @js([
                                                'barcode'     => $product->barcode,
                                                'name'        => $product->name,
                                                'division_id' => $product->division_id ? (string) $product->division_id : '',
                                            ]))">
                                        Edit
                                    </button>

                                    <form method="POST" action="{{ route('admin.products.toggle', $product) }}"
                                          onsubmit="return confirm('{{ $product->is_active ? 'Nonaktifkan' : 'Aktifkan' }} produk {{ addslashes($product->name) }}?')">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                                class="btn btn-sm {{ $product->is_active ? 'btn-danger' : 'btn-success' }}">
                                            {{ $product->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-10 text-center text-slate-500">
                                {{ ($search !== '' || $divisionFilter !== '') ? 'Tidak ada produk yang cocok.' : 'Belum ada produk.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="flex flex-col gap-3 border-t border-slate-200 px-5 py-3 text-sm text-slate-500
                        sm:flex-row sm:items-center sm:justify-between">
                <p data-total="{{ $products->total() }}">Menampilkan {{ $products->count() }} dari {{ $products->total() }} data</p>

                <div class="flex flex-wrap items-center gap-4">
                    <label class="flex items-center gap-2">
                        Tampilkan
                        <select x-model="perPage" @change="load()" class="field py-1.5">
                            @foreach ($perPageOptions as $n)
                                <option value="{{ $n }}" @selected($perPage === $n)>{{ $n }}</option>
                            @endforeach
                        </select>
                    </label>

                    {{ $products->links('pagination.compact') }}
                </div>
            </div>
        </div>
    </div>

    {{-- Modal tambah / edit --}}
    <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/50" @click="open = false"></div>

        <div class="card relative w-full max-w-md shadow-xl" x-transition>
            <div class="flex items-center justify-between px-6 py-4">
                <h2 class="text-lg font-semibold"
                    x-text="mode === 'edit' ? 'Edit Produk' : 'Tambah Produk'"></h2>

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
                        <label for="product-barcode" class="mb-1.5 block text-sm font-medium">Barcode</label>
                        {{-- Mesin scan mengirim Enter di akhir: pindah ke kolom nama, jangan kirim form --}}
                        <input id="product-barcode" type="text" name="barcode" x-model="barcode" x-ref="barcode"
                               @keydown.enter.prevent="$refs.name.focus()"
                               class="field w-full font-mono" placeholder="ketik barcode" autocomplete="off">
                        <p class="mt-1.5 text-xs text-slate-500">Harus unik.</p>
                        @error('barcode')
                            <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="product-name" class="mb-1.5 block text-sm font-medium">Nama produk</label>
                        <input id="product-name" type="text" name="name" x-model="name" x-ref="name"
                               class="field w-full" placeholder="Contoh: Keramik Lantai Roman 40x40 Putih" autocomplete="off">
                        @error('name')
                            <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Divisi <span class="font-normal text-slate-400">(opsional)</span></label>
                        <select name="division_id" x-model="division_id" class="field w-full">
                            <option value="">Tanpa divisi</option>
                            @foreach ($allDivisions as $division)
                                <option value="{{ $division->id }}">
                                    {{ $division->name }}{{ $division->is_active ? '' : ' (nonaktif)' }}
                                </option>
                            @endforeach
                        </select>
                        @error('division_id')
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

    {{-- Modal impor --}}
<div x-show="importOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/50" @click="importOpen = false"></div>

    <div class="card relative max-h-[92vh] w-full max-w-xl overflow-y-auto p-6 shadow-xl"
         x-data="{ busy: false }" x-transition>

        <h2 class="text-lg font-semibold">Impor produk dari Excel</h2>
        <p class="mt-1 text-sm text-slate-600">
            Judul kolom di baris pertama dikenali otomatis. Paling aman memakai
            <a href="{{ route('admin.products.template') }}" class="font-semibold text-[var(--brand)] underline">template ini</a>.
        </p>

        <div class="mt-4 rounded-md border border-[#E2C47F] bg-[#FBF3E0] p-3 text-[13px] text-[#6B4F2E]">
            <p><span class="font-semibold">Kolom:</span> Barcode, Nama Produk, Divisi.</p>
            <p class="mt-1.5"><span class="font-semibold">Penting:</span> ubah format kolom <b>Barcode</b> di Excel jadi <b>Text</b>
               sebelum mengisi (template sudah begitu). Kalau dibiarkan format angka, Excel mengubah barcode 13 digit jadi
               bentuk ilmiah dan membuang angka nol di depannya, dan itu tidak bisa dipulihkan.</p>
            <p class="mt-1.5"><b>Divisi</b> ditulis sesuai nama divisi (huruf besar-kecil tidak masalah) dan boleh dikosongkan.
               Nama yang tidak dikenali tidak membatalkan baris. Produknya tetap diproses tanpa divisi dan dilaporkan setelah impor.</p>
        </div>

        <form method="POST" action="{{ route('admin.products.import') }}" enctype="multipart/form-data"
              class="mt-5 space-y-4" @submit="busy = true">
            @csrf
            <input type="hidden" name="_import" value="1">

            <div>
                <label class="mb-1.5 block text-sm font-medium">File Excel</label>
                <input type="file" name="file" accept=".xlsx,.xls,.csv" class="field w-full">
                <p class="mt-1 text-xs text-red-600">{{ $errors->first('file') }}</p>
            </div>

            <div>
                <p class="mb-1.5 text-sm font-medium">Kalau barcode sudah ada di aplikasi</p>
                <div class="space-y-1.5 text-sm">
                    <label class="flex items-center gap-2">
                        <input type="radio" name="on_duplicate" value="skip" class="accent-[#946A32]"
                               {{ old('on_duplicate', 'skip') === 'skip' ? 'checked' : '' }}> Lewati, data yang ada tidak diubah
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="radio" name="on_duplicate" value="update" class="accent-[#946A32]"
                               {{ old('on_duplicate', 'skip') === 'update' ? 'checked' : '' }}> Perbarui nama dan divisi dengan isi Excel
                    </label>
                    <p class="pl-6 text-xs text-slate-500">Divisi yang kosong atau tidak dikenali di Excel tidak menghapus divisi yang sudah terisi.</p>
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
</div>

@endsection