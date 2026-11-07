<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class PinController extends Controller
{
    /**
     * Ubah PIN sendiri (semua role). Lupa PIN tetap lewat reset oleh admin.
     * Nama field memakai password / current_password / password_confirmation
     * supaya otomatis tidak ikut tersimpan di old input (dontFlash bawaan Laravel).
     */
    public function update(Request $request)
    {
        $data = $request->validateWithBag('pin', [
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'digits:6', 'confirmed', 'different:current_password'],
        ], [
            'current_password.current_password' => 'PIN lama salah.',
            'password.digits'                   => 'PIN baru harus tepat 6 angka.',
            'password.confirmed'                => 'Konfirmasi PIN baru tidak cocok.',
            'password.different'                => 'PIN baru tidak boleh sama dengan PIN lama.',
        ], [
            'current_password' => 'PIN lama',
            'password'         => 'PIN baru',
        ]);

        $request->user()->forceFill([
            'password' => Hash::make($data['password']),
        ])->save();

        return back()->with('success', 'PIN berhasil diubah.');
    }
}