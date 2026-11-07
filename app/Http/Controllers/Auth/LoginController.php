<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function create()
    {
        return view('auth.login');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'employee_code' => ['required', 'string'],
            'password'      => ['required', 'string'],
        ], [], [
            'employee_code' => 'ID karyawan',
            'password'      => 'PIN',
        ]);

        // batasi percobaan: 5x per menit per ID + IP
        $key = Str::lower($data['employee_code']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            throw ValidationException::withMessages([
                'employee_code' => "Terlalu banyak percobaan. Coba lagi dalam {$seconds} detik.",
            ]);
        }

        $ok = Auth::attempt([
            'employee_code' => $data['employee_code'],
            'password'      => $data['password'],
            'is_active'     => true,
        ]);

        if (! $ok) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages([
                'employee_code' => 'ID karyawan atau PIN salah.',
            ]);
        }

        $user = Auth::user();

        // akun yang sudah lewat tanggal berhenti tidak boleh masuk
        if ($user->active_until && $user->active_until->isBefore(today())) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'employee_code' => 'Akun ini sudah tidak aktif.',
            ]);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();

        return redirect()->route($user->role->homeRoute());
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}