@php
    $user = auth()->user();

    $menus = match ($user->role) {
        \App\Role::Admin => [
            ['Akun',   'admin.users.index',     'admin.users.*'],
            ['Divisi', 'admin.divisions.index', 'admin.divisions.*'],
            ['Tugas',  'admin.tasks.index',     'admin.tasks.*'],
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
<body class="min-h-screen bg-slate-50 text-slate-800 antialiased">

<header class="border-b border-slate-200 bg-white">
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

            <div class="flex items-center gap-4 border-l border-slate-200 pl-6">
                <div class="text-right leading-tight">
                    <p class="text-sm font-medium">{{ $user->name }}</p>
                    <p class="text-xs text-slate-500">
                        {{ $user->role->label() }}@if ($user->division) · {{ $user->division->name }}@endif
                    </p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-sm font-medium text-slate-500 hover:text-red-600">Keluar</button>
                </form>
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

</body>
</html>