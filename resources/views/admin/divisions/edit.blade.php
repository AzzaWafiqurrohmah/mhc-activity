@extends('layouts.app')

@section('title', 'Edit Divisi')

@section('content')
    <div class="mx-auto max-w-lg">
        <h1 class="mb-6 text-2xl font-semibold">Edit Divisi</h1>

        <form method="POST" action="{{ route('admin.divisions.update', $division) }}"
              class="space-y-5 rounded-2xl border border-slate-200 bg-white p-6">
            @csrf
            @method('PUT')

            <div>
                <label for="name" class="mb-1.5 block text-sm font-medium">Nama divisi</label>
                <input id="name" type="text" name="name" value="{{ old('name', $division->name) }}"
                       class="input-brand w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('admin.divisions.index') }}"
                   class="rounded-xl border border-slate-300 px-5 py-2.5 text-sm font-medium hover:bg-slate-100">
                    Batal
                </a>
                <button type="submit" class="btn-brand rounded-xl px-5 py-2.5 text-sm font-semibold">
                    Simpan
                </button>
            </div>
        </form>
    </div>
@endsection