@php
    $user = auth()->user();
    $modules = \App\Support\Modules::forUser($user);

    // inisial untuk avatar: huruf pertama dari dua kata pertama nama
    $initials = \Illuminate\Support\Str::of($user->name)
        ->explode(' ')
        ->filter()
        ->take(2)
        ->map(fn ($w) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($w, 0, 1)))
        ->implode('');

    $menus = match ($user->role) {
        \App\Role::Admin => [
            ['Akun',   'admin.users.index',     'admin.users.*'],
            ['Divisi', 'admin.divisions.index', 'admin.divisions.*'],
            ['Tugas',  'admin.tasks.index',     'admin.tasks.*'],
            ['Produk', 'admin.products.index',  'admin.products.*'],
        ],
        \App\Role::Staff => [
            ['Aktivitas Harian', 'staff.dashboard', 'staff.dashboard'],
            ['Riwayat',     'staff.history',   'staff.history'],
        ],
        \App\Role::Sm => [
            ['Hari Ini',      'sm.dashboard',   'sm.dashboard'],
            ['Log Aktivitas', 'sm.logs.index',  'sm.logs.*'],
            ['Rekap Nilai',   'sm.recap.index', 'sm.recap.*'],
        ],
        default => [],
    };
@endphp


<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') | {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-page text-slate-800 antialiased">

<header class="header-accent border-b border-slate-200 bg-white">
    <div class="mx-auto flex h-14 max-w-6xl items-center justify-between px-6">

        {{-- Kiri: logo --}}
        <div class="flex items-center gap-3">
            <div class="bg-brand-gradient grid h-9 w-9 place-items-center rounded-md font-bold text-white">M</div>
            <div class="hidden sm:block">
                <p class="text-sm font-semibold leading-tight">Millenium Home Center</p>
                <p class="text-xs text-slate-500">Activity Tracker</p>
            </div>
        </div>

        {{-- Kanan: menu, lalu user --}}
        <div class="flex h-full items-center gap-8">
            <nav class="flex h-full items-stretch gap-6">
                @foreach ($menus as [$label, $route, $pattern])
                    @if (Route::has($route))
                        <a href="{{ route($route) }}"
                           class="nav-link {{ request()->routeIs($pattern) ? 'nav-link-active' : '' }}">
                            {{ $label }}
                        </a>
                    @endif
                @endforeach
                @if (count($modules))
                    @php
                        $reportActive = collect($modules)->contains(fn ($m) => request()->routeIs($m['pattern']));
                    @endphp

                    <div class="relative flex h-full items-stretch"
                        x-data="{ open: false }"
                        @click.outside="open = false"
                        @keydown.escape.window="open = false">

                        <button type="button" @click="open = !open"
                                class="nav-link gap-1 {{ $reportActive ? 'nav-link-active' : '' }}">
                            Report
                            <svg class="h-4 w-4 transition" :class="open ? 'rotate-180' : ''" viewBox="0 0 20 20"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M5 8l5 5 5-5"/>
                            </svg>
                        </button>

                        <div x-show="open" x-cloak x-transition.origin.top
                            class="absolute left-0 top-full z-40 mt-1 w-64 rounded-lg border border-slate-200 bg-white py-1.5 shadow-lg">
                            @foreach ($modules as $m)
                                <a href="{{ route($m['route']) }}"
                                class="block px-4 py-2.5 text-sm font-medium
                                        {{ request()->routeIs($m['pattern'])
                                                ? 'bg-[var(--brand-soft)] text-[var(--brand-dark)]'
                                                : 'text-slate-700 hover:bg-slate-50' }}">
                                    {{ $m['label'] }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </nav>

            {{-- User: nama + avatar inisial, klik untuk buka menu --}}
            <div class="relative border-l border-slate-200 pl-6"
                 x-data="{ menu: false }"
                 @click.outside="menu = false"
                 @keydown.escape.window="menu = false">

                <button type="button" @click="menu = !menu"
                        class="flex items-center gap-3 rounded-md py-1 pl-2 pr-1 transition-colors hover:bg-slate-50"
                        :aria-expanded="menu" aria-haspopup="true">
                    <span class="text-right leading-tight">
                        <span class="block text-sm font-medium">{{ $user->name }}</span>
                        <span class="block text-xs text-slate-500">
                            {{ $user->role->label() }}@if ($user->division) · {{ $user->division->name }}@endif
                        </span>
                    </span>

                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full border border-[var(--brand)]
                                 bg-[var(--brand-soft)] text-sm font-semibold text-[var(--brand)]">
                        {{ $initials }}
                    </span>

                    <svg class="h-4 w-4 text-slate-400 transition-transform" :class="menu ? 'rotate-180' : ''"
                         viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round"><path d="M5 8l5 5 5-5"/></svg>
                </button>

                <div x-show="menu" x-cloak x-transition.origin.top.right
                     class="card absolute right-0 top-full z-40 mt-2 w-44 overflow-hidden py-1 shadow-lg">
                    <button type="button"
                            @click="menu = false; $dispatch('open-pin')"
                            class="block w-full px-4 py-2 text-left text-sm text-slate-700 hover:bg-slate-50">
                        Ubah PIN
                    </button>

                    <div class="my-1 border-t border-slate-100"></div>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                                class="block w-full px-4 py-2 text-left text-sm text-slate-700 hover:bg-red-50 hover:text-red-600">
                            Keluar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>

<main class="mx-auto max-w-6xl px-6 py-8">
    @if (session('success'))
    <div x-data x-init="window.toast({ type: 'success', title: 'Berhasil', message: @js(session('success')) })"></div>
@endif

@error('alert')
    <div x-data x-init="window.toast({ type: 'error', title: 'Tidak bisa diproses', message: @js($message) })"></div>
@enderror

    @yield('content')
</main>

{{-- Modal ubah PIN (semua role). Error memakai bag "pin" supaya tidak bentrok dengan form lain. --}}
<div x-data="{
        open: {{ $errors->getBag('pin')->any() ? 'true' : 'false' }},
        init() {
            this.$watch('open', v => { if (v) this.$nextTick(() => this.$refs.current.focus()); });
        },
        close() {
            this.open = false;
            this.$refs.form.reset();
        },
     }"
     @open-pin.window="open = true"
     @keydown.escape.window="if (open) close()"
     x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">

    <div class="absolute inset-0 bg-slate-900/50" @click="close()"></div>

    <div class="card relative w-full max-w-sm shadow-xl" x-transition>
        <div class="flex items-center justify-between px-6 py-4">
            <h2 class="text-lg font-semibold">Ubah PIN</h2>

            <button type="button" @click="close()" aria-label="Tutup"
                    class="text-slate-400 transition-colors hover:text-red-600">
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round"><path d="M5 5l10 10M15 5L5 15"/></svg>
            </button>
        </div>

        <div class="mx-5 border-t border-slate-200"></div>

        <form method="POST" action="{{ route('pin.update') }}" x-ref="form">
            @csrf
            @method('PUT')

            <div class="space-y-4 px-6 py-5">
                <div>
                    <label for="pin-current" class="mb-1.5 block text-sm font-medium">PIN lama</label>
                    <input id="pin-current" type="password" name="current_password" x-ref="current"
                           inputmode="numeric" maxlength="6" autocomplete="current-password"
                           @input="$event.target.value = $event.target.value.replace(/\D/g, '')"
                           class="field w-full font-mono tracking-widest" placeholder="••••••">
                    @error('current_password', 'pin')
                        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="pin-new" class="mb-1.5 block text-sm font-medium">PIN baru (6 angka)</label>
                    <input id="pin-new" type="password" name="password"
                           inputmode="numeric" maxlength="6" autocomplete="new-password"
                           @input="$event.target.value = $event.target.value.replace(/\D/g, '')"
                           class="field w-full font-mono tracking-widest" placeholder="••••••">
                    @error('password', 'pin')
                        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="pin-confirm" class="mb-1.5 block text-sm font-medium">Ulangi PIN baru</label>
                    <input id="pin-confirm" type="password" name="password_confirmation"
                           inputmode="numeric" maxlength="6" autocomplete="new-password"
                           @input="$event.target.value = $event.target.value.replace(/\D/g, '')"
                           class="field w-full font-mono tracking-widest" placeholder="••••••">
                </div>

                <p class="text-xs text-slate-500">
                    Setelah diubah, hanya kamu yang tahu PIN ini. Kalau lupa, minta admin untuk mereset.
                </p>
            </div>

            <div class="mx-5 border-t border-slate-200"></div>

            <div class="flex justify-end gap-2 px-6 py-4">
                <button type="button" @click="close()" class="btn btn-outline">Batal</button>
                <button type="submit" class="btn btn-brand">Simpan</button>
            </div>
        </form>
    </div>
</div>

<div x-data class="pointer-events-none fixed right-4 top-4 z-[60] flex w-[calc(100%-2rem)] max-w-sm flex-col gap-3">
    <template x-for="t in $store.toasts.items" :key="t.id">
        <div class="toast-in pointer-events-auto relative overflow-hidden rounded-lg border bg-white shadow-lg"
             :class="{
                'border-emerald-300': t.type === 'success',
                'border-amber-300':   t.type === 'warning',
                'border-red-300':     t.type === 'error',
                'border-[#E2C47F]':   t.type === 'loading'
             }">

            <div class="flex items-start gap-3 p-4">
                <span class="mt-0.5 grid h-9 w-9 shrink-0 place-items-center rounded-full"
                      :class="{
                        'bg-emerald-100 text-emerald-600': t.type === 'success',
                        'bg-amber-100 text-amber-600':     t.type === 'warning',
                        'bg-red-100 text-red-600':         t.type === 'error',
                        'bg-[#F0D9A8] text-[#946A32]':     t.type === 'loading'
                      }">
                    <svg x-show="t.type === 'success'" class="h-5 w-5" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M5 12.5l4.5 4.5L19 7.5"/>
                    </svg>
                    <svg x-show="t.type === 'warning'" class="h-5 w-5" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 8v5M12 16.5h.01"/><path d="M10.3 4.2L2.8 17a2 2 0 001.7 3h15a2 2 0 001.7-3L13.7 4.2a2 2 0 00-3.4 0z"/>
                    </svg>
                    <svg x-show="t.type === 'error'" class="h-5 w-5" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M6 6l12 12M18 6L6 18"/>
                    </svg>
                    <svg x-show="t.type === 'loading'" class="h-5 w-5 animate-spin" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2.4" stroke-linecap="round">
                        <path d="M12 3a9 9 0 019 9" />
                    </svg>
                </span>

                <div class="min-w-0 flex-1">
                    <p class="text-[15px] font-semibold text-[#3B2E1A]" x-text="t.title"></p>
                    <p class="mt-0.5 text-sm text-slate-600" x-text="t.message"></p>
                </div>

                <button type="button" @click="$store.toasts.remove(t.id)"
                        class="text-xl leading-none text-slate-400 hover:text-slate-600" aria-label="Tutup">&times;</button>
            </div>

            {{-- garis hitung mundur --}}
            <div x-show="t.type !== 'loading'" class="toast-bar h-1"
                 :class="{
                    'bg-emerald-400': t.type === 'success',
                    'bg-amber-400':   t.type === 'warning',
                    'bg-red-400':     t.type === 'error'
                 }"></div>
        </div>
    </template>
</div>

</body>
</html>