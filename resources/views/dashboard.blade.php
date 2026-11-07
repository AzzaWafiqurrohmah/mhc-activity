@extends('layouts.app')

@section('title', $title)

@section('content')
    <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-12 text-center">
        <p class="text-sm text-slate-500">Login berhasil sebagai</p>
        <h1 class="mt-1 text-2xl font-semibold">{{ $title }}</h1>
        <p class="mt-2 text-slate-500">Halaman ini masih kosong. Cuma untuk memastikan login dan role sudah benar.</p>
    </div>
@endsection