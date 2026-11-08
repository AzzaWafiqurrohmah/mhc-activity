<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk | {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        .login-bg   { background: linear-gradient(135deg, #FBF1DF 0%, #F3DDB4 55%, #E8C88E 100%); }
        .blob       { position: absolute; border-radius: 9999px; filter: blur(70px); pointer-events: none; }

        .login-logo { background: linear-gradient(135deg, #DDB87F, #B98A4E); color: #fff; }

        .login-input {
            width: 100%; background: #fff; border: 1px solid #E2D3B8; border-radius: 8px;
            padding: .72rem .9rem .72rem 2.6rem; font-size: 15px; color: #4A3420;
            transition: border-color .15s, box-shadow .15s;
        }
        .login-input::placeholder { color: #BFAE92; }
        .login-input:focus { outline: none; border-color: #B98A4E; box-shadow: 0 0 0 4px rgba(185, 138, 78, .2); }
        .login-input-pin { padding-right: 2.8rem; }

        .login-btn {
            width: 100%; border-radius: 8px; padding: .78rem 1rem; font-size: 15px; font-weight: 600;
            background: linear-gradient(135deg, #E0B873, #C99A55); color: #3D2A12;
            transition: filter .15s, box-shadow .15s;
        }
        .login-btn:hover:not(:disabled) { filter: brightness(1.04); box-shadow: 0 10px 22px -10px rgba(185, 138, 78, .9); }
        .login-btn:disabled { opacity: .75; cursor: wait; }
    </style>
</head>

<body class="login-bg relative min-h-screen overflow-hidden antialiased">

    {{-- Hiasan latar --}}
    <div class="blob" style="top:-120px; left:-100px; width:420px; height:420px; background:rgba(214,170,105,.5)"></div>
    <div class="blob" style="bottom:-140px; right:-80px; width:460px; height:460px; background:rgba(196,146,92,.4)"></div>
    <div class="blob" style="top:38%; left:58%; width:260px; height:260px; background:rgba(255,248,235,.8)"></div>

    <main class="relative z-10 flex min-h-screen items-center justify-center p-5">
        <div class="w-full max-w-[420px]">

            {{-- Logo --}}
            <div class="mb-7 flex flex-col items-center text-center">
                <div class="login-logo grid h-14 w-14 place-items-center rounded-[10px] text-3xl font-extrabold shadow-sm">M</div>
                <p class="mt-3.5 text-xl font-bold tracking-tight text-[#4A3420]">Millenium Home Center</p>
                <p class="mt-1 text-[13px] font-semibold uppercase tracking-[.2em] text-[#94805E]">Jember</p>
            </div>

            {{-- Kartu --}}
            <div class="rounded-[10px] border border-[#EADBC0]/80 bg-white/75 p-8 shadow-[0_24px_60px_-24px_rgba(140,95,40,.45)] backdrop-blur-xl">

                <h1 class="text-[26px] font-bold tracking-tight text-[#4A3420]">Selamat Datang</h1>
                <p class="mt-1.5 text-sm font-medium text-[#7A6444]">Masuk untuk mengisi aktivitas harianmu.</p>

                <form method="POST" action="{{ route('login.store') }}" class="mt-7 space-y-5"
                      x-data="{ show: false, loading: false }"
                      x-init="window.addEventListener('pageshow', () => loading = false)"
                      @submit="loading = true">
                    @csrf

                    {{-- ID karyawan --}}
                    <div>
                        <label for="employee_code" class="mb-1.5 block text-[13px] font-medium text-[#6B4F2E]">ID Karyawan</label>
                        <div class="relative">
                            <svg class="pointer-events-none absolute left-3.5 top-1/2 h-[18px] w-[18px] -translate-y-1/2 text-[#BFA880]"
                                 viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                                 stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-6 8-6s8 2 8 6"/>
                            </svg>
                            <input id="employee_code" name="employee_code" type="text"
                                   value="{{ old('employee_code') }}" autofocus autocomplete="off"
                                   placeholder="Contoh: STF001"
                                   class="login-input uppercase placeholder:normal-case">
                        </div>
                    </div>

                    {{-- PIN --}}
                    <div>
                        <label for="password" class="mb-1.5 block text-[13px] font-medium text-[#6B4F2E]">PIN</label>
                        <div class="relative">
                            <svg class="pointer-events-none absolute left-3.5 top-1/2 h-[18px] w-[18px] -translate-y-1/2 text-[#BFA880]"
                                 viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                                 stroke-linecap="round" stroke-linejoin="round">
                                <rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>
                            </svg>

                            <input id="password" name="password" type="password" :type="show ? 'text' : 'password'"
                                   inputmode="numeric" autocomplete="off" placeholder="Masukkan PIN"
                                   class="login-input login-input-pin">

                            <button type="button" @click="show = !show" tabindex="-1"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 rounded-md p-2 text-[#B49C74] hover:text-[#6B4F2E]"
                                    :aria-label="show ? 'Sembunyikan PIN' : 'Tampilkan PIN'">
                                <svg x-show="!show" class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none"
                                     stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>
                                </svg>
                                <svg x-show="show" x-cloak class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none"
                                     stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M3 3l18 18"/><path d="M10.6 6.1A10 10 0 0 1 12 6c6.4 0 10 6 10 6a17 17 0 0 1-3.2 3.9M6.4 7.6A17 17 0 0 0 2 12s3.6 6 10 6a9.7 9.7 0 0 0 4-.9"/>
                                    <path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Pesan error --}}
                    @if ($errors->any())
                        <div class="flex items-start gap-2 rounded-lg border border-red-200 bg-red-50/80 px-3.5 py-3 text-[13px] text-red-700">
                            <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/>
                            </svg>
                            <span>{{ $errors->first() }}</span>
                        </div>
                    @endif

                    <button type="submit" class="login-btn" :disabled="loading">
                        <span x-text="loading ? 'Memproses...' : 'Masuk'">Masuk</span>
                    </button>
                </form>

                <p class="mt-5 text-center text-xs text-[#A6926F]">Lupa PIN? Hubungi admin.</p>
            </div>

            <p class="mt-6 text-center text-xs text-[#A6926F]">&copy; {{ date('Y') }} Millenium Home Center</p>
        </div>
    </main>
</body>
</html>