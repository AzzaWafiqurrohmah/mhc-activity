<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Division;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DivisionController extends Controller
{
    private const PER_PAGE_OPTIONS = [5, 10, 25, 50];

    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page');
        $perPage = in_array($perPage, self::PER_PAGE_OPTIONS, true) ? $perPage : 10;

        $search = trim((string) $request->query('q'));

        $divisions = Division::withCount([
                'users as users_count' => fn ($q) => $q->where('is_active', true),
                'tasks as tasks_count' => fn ($q) => $q->where('is_active', true),
            ])
            ->when($search !== '', fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.divisions.index', [
            'divisions' => $divisions,
            'search'    => $search,
            'perPage'   => $perPage,
            'perPageOptions' => self::PER_PAGE_OPTIONS,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:divisions,name'],
        ], [], ['name' => 'Nama divisi']);

        Division::create($data);

        return back()->with('success', 'Divisi berhasil ditambahkan.');
    }

    public function update(Request $request, Division $division)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100',
                       Rule::unique('divisions', 'name')->ignore($division->id)],
        ], [], ['name' => 'Nama divisi']);

        $division->update($data);

        return back()->with('success', 'Divisi berhasil diperbarui.');
    }

    public function toggle(Division $division)
    {
        if ($division->is_active && $division->users()->where('is_active', true)->exists()) {
            return back()->withErrors([
                'alert' => "Divisi {$division->name} masih punya staff aktif. "
                         . 'Pindahkan atau nonaktifkan stafnya dulu.',
            ]);
        }

        $division->update(['is_active' => ! $division->is_active]);

        return back()->with('success', $division->is_active
            ? "Divisi {$division->name} diaktifkan."
            : "Divisi {$division->name} dinonaktifkan.");
    }
}