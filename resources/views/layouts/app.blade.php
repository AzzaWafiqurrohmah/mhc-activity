@php
    $user = auth()->user();

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
        ],
        \App\Role::Staff => [
            ['Input Harian', 'staff.dashboard', 'staff.dashboard'],
            ['Riwayat',      'staff.history',   'staff.history'],
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
        <div class="mb-6 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    {{-- hanya untuk pesan umum (key "alert"); error validasi tampil di dalam modal --}}
    @error('alert')
        <div class="mb-6 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ $message }}
        </div>
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

</body>
</html>