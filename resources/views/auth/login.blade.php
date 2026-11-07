<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk | {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-800 antialiased">
<div class="grid min-h-screen lg:grid-cols-2">

    {{-- Panel kiri: identitas --}}
    <div class="bg-brand-gradient hidden flex-col justify-between p-12 text-white lg:flex">
        <div class="flex items-center gap-3">
            <div class="grid h-11 w-11 place-items-center rounded-xl bg-white/15 text-lg font-bold">M</div>
            <div>
                <p class="font-semibold leading-tight">Millenium Home Center</p>
                <p class="text-sm text-white/70">Jember</p>
            </div>
        </div>

        <div>
            <h1 class="text-4xl font-semibold leading-tight">Catat aktivitas harian,<br>tanpa ribet.</h1>
            <p class="mt-4 max-w-md text-white/75">
                Isi shift dan tugas yang kamu kerjakan hari ini. Cukup beberapa detik.
            </p>
        </div>

        <p class="text-sm text-white/60">&copy; {{ date('Y') }} Millenium Home Center</p>
    </div>

    {{-- Panel kanan: form --}}
    <div class="flex items-center justify-center p-6 sm:p-12">
        <div class="w-full max-w-sm">

            <div class="mb-8 flex items-center gap-3 lg:hidden">
                <div class="bg-brand-gradient grid h-10 w-10 place-items-center rounded-xl font-bold text-white">M</div>
                <p class="font-semibold">Millenium Home Center</p>
            </div>

            <h2 class="text-2xl font-semibold">Masuk</h2>
            <p class="mt-1 text-sm text-slate-500">Gunakan ID karyawan dan PIN kamu.</p>

            <form method="POST" action="{{ route('login.store') }}" class="mt-8 space-y-5">
                @csrf

                <div>
                    <label for="employee_code" class="mb-1.5 block text-sm font-medium">ID Karyawan</label>
                    <input id="employee_code" name="employee_code" type="text"
                           value="{{ old('employee_code') }}" autofocus autocomplete="off"
                           class="input-brand w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-base uppercase transition"
                           placeholder="contoh: STF001">
                </div>

                <div>
                    <label for="password" class="mb-1.5 block text-sm font-medium">PIN</label>
                    <input id="password" name="password" type="password" inputmode="numeric"
                           autocomplete="off"
                           class="input-brand w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-base tracking-widest transition"
                           placeholder="••••••">
                </div>

                @if ($errors->any())
                    <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        {{ $errors->first() }}
                    </div>
                @endif

                <button type="submit"
                        class="btn-brand w-full rounded-xl px-4 py-3 text-base font-semibold shadow-sm transition">
                    Masuk
                </button>
            </form>

            <p class="mt-6 text-center text-xs text-slate-400">
                Lupa PIN? Hubungi admin.
            </p>
        </div>
    </div>
</div>
</body>
</html>