@extends('layouts.app')

@section('title', $title)

@section('content')
    <div class="mb-6">
        <h1 class="text-3xl font-bold tracking-tight text-[#3B2E1A]">{{ $title }}</h1>
        <p class="mt-1 text-base font-medium text-[#6B4F2E]">Report divisi {{ auth()->user()->division?->name }}</p>
    </div>

    <div class="card p-12 text-center">
        <p class="text-lg font-semibold text-slate-700">Halaman ini masih kosong</p>
        <p class="mt-1 text-slate-500">Form dan daftar report akan ditambahkan setelah kolomnya ditetapkan.</p>
    </div>
@endsection