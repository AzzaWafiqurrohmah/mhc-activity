@extends('layouts.app')

@section('title', 'Input Harian')

@section('content')
@php
    $shiftLabels = ['morning' => 'Pagi', 'afternoon' => 'Siang'];

    $oldStatus  = old('status', $log?->status->value ?? 'present');
    $oldShift   = old('shift', $log?->shift?->value ?? '');
    $checkedIds = array_map('intval', old('task_ids', $log ? $log->tasks->pluck('id')->all() : []));
    $oldNote    = old('other_note', $log?->other_note ?? '');

    $startEditing = $log === null || old('_editing');

    $pct = $totalDays > 0 ? min(100, round($filledDays / $totalDays * 100)) : 0;
@endphp

<div class="mx-auto max-w-3xl"
     x-data="{
        status: @js($oldStatus),
        shift: @js($oldShift),
        taskIds: @js($checkedIds),
        editing: {{ $startEditing ? 'true' : 'false' }},
        saved: {{ session('saved') ? 'true' : 'false' }},
        remaining: {{ $canEdit ? $secondsLeft : 0 }},
        timer: null,
        init() {
            if (this.saved) setTimeout(() => this.saved = false, 2500);

            if (this.remaining > 0) {
                const deadline = Date.now() + this.remaining * 1000;
                this.timer = setInterval(() => {
                    this.remaining = Math.max(0, Math.round((deadline - Date.now()) / 1000));
                    if (this.remaining === 0) {
                        clearInterval(this.timer);
                        // waktu edit habis: muat ulang supaya server menampilkan versi read-only
                        setTimeout(() => window.location.reload(), 1200);
                    }
                }, 500);
            }
        },
        get countdown() {
            const m = Math.floor(this.remaining / 60), s = this.remaining % 60;
            return String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
        },
        has(id) { return this.taskIds.some(v => v == id); },
     }">

    {{-- Notif singkat "Berhasil disimpan" --}}
    <div x-show="saved" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:leave="transition ease-in duration-300"
         x-transition:leave-end="opacity-0"
         class="fixed right-6 top-20 z-50 flex items-center gap-2 rounded-md border border-emerald-200
                bg-emerald-50 px-4 py-2.5 text-sm font-medium text-emerald-800 shadow-lg">
        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.5"
             stroke-linecap="round" stroke-linejoin="round"><path d="M4 10l4 4 8-8"/></svg>
        Berhasil disimpan
    </div>

    {{-- Judul + terisi bulan ini --}}
    <div class="mb-5 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold">Input Harian</h1>
            <p class="text-sm text-slate-500">{{ $today->copy()->locale('id')->translatedFormat('l, d F Y') }}</p>
        </div>

        <div class="w-48">
            <p class="text-sm text-slate-500">
                Terisi bulan ini:
                <span class="font-semibold text-slate-800">{{ $filledDays }}/{{ $totalDays }}</span> hari
            </p>
            <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-slate-200">
                <div class="h-full rounded-full bg-[var(--brand)]" style="width: {{ $pct }}%"></div>
            </div>
        </div>
    </div>

    {{-- ============ FORM (belum isi, atau sedang edit dalam 15 menit) ============ --}}
    @if ($log === null || $canEdit)
        <div class="card" x-show="editing" x-cloak>
            <div class="flex items-center justify-between px-6 py-4">
                <h2 class="text-lg font-semibold">{{ $log ? 'Edit Input Hari Ini' : 'Input Hari Ini' }}</h2>

                @if ($log)
                    <span class="text-sm text-slate-500">
                        Sisa waktu edit
                        <span class="font-mono font-medium text-slate-700" x-text="countdown"></span>
                    </span>
                @endif
            </div>

            <div class="mx-5 border-t border-slate-200"></div>

            <form method="POST" action="{{ $log ? route('staff.logs.update') : route('staff.logs.store') }}">
                @csrf
                @if ($log)
                    @method('PUT')
                    <input type="hidden" name="_editing" value="1">
                @endif

                <div class="space-y-4 px-6 py-5">

                    {{-- Status + Shift sebaris --}}
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium">Status hari ini</label>
                            <div class="grid grid-cols-2 gap-2">
                                <label class="cursor-pointer rounded-md border px-3 py-2 text-center text-sm font-medium"
                                       :class="status === 'present' ? 'border-[var(--brand)] bg-[var(--brand-soft)]' : 'border-slate-300'">
                                    <input type="radio" name="status" value="present" x-model="status" class="sr-only">
                                    Masuk
                                </label>

                                <label class="cursor-pointer rounded-md border px-3 py-2 text-center text-sm font-medium"
                                       :class="status === 'off' ? 'border-[var(--brand)] bg-[var(--brand-soft)]' : 'border-slate-300'">
                                    <input type="radio" name="status" value="off" x-model="status" class="sr-only">
                                    Off
                                </label>
                            </div>
                            @error('status')
                                <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div x-show="status === 'present'" x-cloak>
                            <label class="mb-1.5 block text-sm font-medium">Shift</label>
                            <div class="grid grid-cols-2 gap-2">
                                @foreach ($shiftLabels as $value => $label)
                                    <label class="cursor-pointer rounded-md border px-3 py-2 text-center text-sm font-medium"
                                           :class="shift === '{{ $value }}' ? 'border-[var(--brand)] bg-[var(--brand-soft)]' : 'border-slate-300'">
                                        <input type="radio" name="shift" value="{{ $value }}" x-model="shift"
                                               :disabled="status !== 'present'" class="sr-only">
                                        {{ $label }}
                                    </label>
                                @endforeach
                            </div>
                            @error('shift')
                                <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Off: tidak ada input lain --}}
                    <div x-show="status === 'off'" x-cloak
                         class="rounded-md border border-dashed border-slate-300 bg-slate-50 px-4 py-4 text-center text-sm text-slate-500">
                        Hari ini kamu Off. Tidak ada yang perlu diisi, langsung simpan saja.
                    </div>

                    {{-- Masuk: tugas + keterangan tambahan --}}
                    <div x-show="status === 'present'" x-cloak class="space-y-4">

                        <div>
                            <div class="mb-1.5 flex items-center justify-between">
                                <label class="block text-sm font-medium">Tugas yang dikerjakan</label>
                                <span class="text-xs text-slate-500">Dipilih: <span x-text="taskIds.length"></span></span>
                            </div>

                            @if ($tasks->isEmpty())
                                <p class="rounded-md border border-dashed border-slate-300 bg-slate-50 px-4 py-4 text-sm text-slate-500">
                                    Divisi kamu belum punya tugas aktif. Hubungi admin, atau isi keterangan tambahan di bawah.
                                </p>
                            @else
                                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                    @foreach ($tasks as $task)
                                        <label class="flex cursor-pointer items-center gap-2.5 rounded-md border px-3 py-2 text-sm"
                                               :class="has({{ $task->id }}) ? 'border-[var(--brand)] bg-[var(--brand-soft)]' : 'border-slate-300'">
                                            <input type="checkbox" name="task_ids[]" value="{{ $task->id }}"
                                                   x-model="taskIds" :disabled="status !== 'present'"
                                                   class="h-4 w-4 shrink-0 rounded border-slate-300 accent-[var(--brand)]">
                                            <span>{{ $task->name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            @endif

                            @error('task_ids')
                                <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                            @error('task_ids.*')
                                <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="other-note" class="mb-1.5 block text-sm font-medium">
                                Keterangan tambahan <span class="font-normal text-slate-400">(opsional)</span>
                            </label>
                            <input id="other-note" type="text" name="other_note" value="{{ $oldNote }}" maxlength="200"
                                   :disabled="status !== 'present'" autocomplete="off"
                                   class="field w-full" placeholder="Contoh: bantu bongkar muat barang di gudang">
                            @error('other_note')
                                <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="mx-5 border-t border-slate-200"></div>

                <div class="flex justify-end gap-2 px-6 py-4">
                    @if ($log)
                        <button type="button" class="btn btn-outline" @click="window.location.reload()">Batal</button>
                    @endif
                    <button type="submit" class="btn btn-brand">Simpan</button>
                </div>
            </form>
        </div>
    @endif

    {{-- ============ RINGKASAN HARI INI (setelah simpan / read-only) ============ --}}
    @if ($log)
        <div class="card" x-show="!editing" x-cloak>
            <div class="flex items-center justify-between px-6 py-4">
                <h2 class="text-lg font-semibold">Hari Ini</h2>

                @if ($canEdit)
                    <button type="button" class="btn btn-outline btn-sm" @click="editing = true">
                        Edit
                        <span class="ml-1 font-mono text-slate-500" x-text="countdown"></span>
                    </button>
                @endif
            </div>

            <div class="mx-5 border-t border-slate-200"></div>

            <div class="space-y-3 px-6 py-5">
                @if ($log->status === \App\LogStatus::Off)
                    <div class="flex items-center gap-3">
                        <span class="badge badge-gray">Off</span>
                        <p class="text-sm text-slate-500">Kamu Off hari ini. Tidak ada yang perlu diisi.</p>
                    </div>
                @else
                    <div class="flex items-center gap-2">
                        <span class="badge badge-green">Masuk</span>
                        <span class="badge badge-blue">Shift {{ $shiftLabels[$log->shift->value] ?? '-' }}</span>
                    </div>

                    @if ($log->tasks->isNotEmpty())
                        <div class="flex flex-wrap gap-1.5">
                            @foreach ($log->tasks->sortBy('sort_order') as $task)
                                <span class="inline-flex items-center gap-1.5 rounded bg-slate-100 px-2.5 py-1 text-sm text-slate-700">
                                    <svg class="h-3.5 w-3.5 text-emerald-600" viewBox="0 0 20 20" fill="none"
                                         stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                                         stroke-linejoin="round"><path d="M4 10l4 4 8-8"/></svg>
                                    {{ $task->name }}
                                </span>
                            @endforeach
                        </div>
                    @endif

                    @if ($log->other_note)
                        <p class="text-sm text-slate-600">
                            <span class="font-medium text-slate-500">Keterangan tambahan:</span> {{ $log->other_note }}
                        </p>
                    @endif
                @endif
            </div>
        </div>
    @endif
</div>
@endsection