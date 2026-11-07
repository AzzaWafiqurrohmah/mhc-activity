<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Division;
use App\Models\User;
use App\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    private const PER_PAGE_OPTIONS = [5, 10, 25, 50];

    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page');
        $perPage = in_array($perPage, self::PER_PAGE_OPTIONS, true) ? $perPage : 10;

        $search = trim((string) $request->query('q'));

        $roleFilter = (string) $request->query('role', '');
        $roleFilter = Role::tryFrom($roleFilter) ? $roleFilter : '';

        $divisionFilter = (string) $request->query('division', '');
        $divisionFilter = ctype_digit($divisionFilter) ? $divisionFilter : '';

        // filter divisi hanya berlaku kalau role yang dipilih Staff
        if ($roleFilter !== Role::Staff->value) {
            $divisionFilter = '';
        }

        $users = User::with('division')
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('employee_code', 'like', "%{$search}%");
            }))
            ->when($roleFilter !== '', fn ($q) => $q->where('role', $roleFilter))
            ->when($divisionFilter !== '', fn ($q) => $q->where('division_id', $divisionFilter))
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.users.index', [
            'users'          => $users,
            'search'         => $search,
            'perPage'        => $perPage,
            'perPageOptions' => self::PER_PAGE_OPTIONS,
            'roleFilter'     => $roleFilter,
            'divisionFilter' => $divisionFilter,
            'roles'          => Role::cases(),
            'allDivisions'   => Division::orderBy('name')->get(['id', 'name', 'is_active']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'          => ['required', 'string', 'max:100'],
            'employee_code' => ['required', 'string', 'max:30', 'regex:/^[A-Za-z0-9._-]+$/',
                                Rule::unique('users', 'employee_code')],
            'role'          => ['required', Rule::enum(Role::class)],
            'division_id'   => [Rule::requiredIf($request->input('role') === Role::Staff->value),
                                'nullable',
                                Rule::exists('divisions', 'id')->where('is_active', true)],
            'pin'           => ['required', 'digits:6'],
            'active_from'   => ['required', 'date'],
        ], $this->messages(), $this->labels());

        $user = new User();
        $user->forceFill([
            'name'          => $data['name'],
            'employee_code' => $data['employee_code'],
            'role'          => $data['role'],
            // hanya staff yang terikat divisi
            'division_id'   => $data['role'] === Role::Staff->value ? $data['division_id'] : null,
            'password'      => Hash::make($data['pin']),
            'active_from'   => $data['active_from'],
            'active_until'  => null,
            'is_active'     => true,
        ])->save();

        return back()->with('success', 'Akun berhasil ditambahkan.');
    }

    public function update(Request $request, User $user)
    {
        $isSelf = $user->is($request->user());

        // admin tidak boleh mengubah role dirinya sendiri
        if ($isSelf) {
            $request->merge(['role' => $user->role->value]);
        }

        // employee_code sengaja tidak boleh diubah di sini
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:100'],
            'role'        => ['required', Rule::enum(Role::class)],
            'division_id' => [Rule::requiredIf($request->input('role') === Role::Staff->value),
                              'nullable',
                              // divisi nonaktif hanya boleh dipertahankan, tidak boleh dipilih baru
                              Rule::exists('divisions', 'id')->where(fn ($q) => $q
                                  ->where('is_active', true)
                                  ->orWhere('id', $user->division_id))],
            'pin'         => ['nullable', 'digits:6'],
            'active_from' => ['required', 'date'],
        ], $this->messages(), $this->labels());

        $attributes = [
            'name'        => $data['name'],
            'role'        => $data['role'],
            'division_id' => $data['role'] === Role::Staff->value ? $data['division_id'] : null,
            'active_from' => $data['active_from'],
        ];

        // PIN kosong = tidak diubah
        if (! empty($data['pin'])) {
            $attributes['password'] = Hash::make($data['pin']);
        }

        $user->forceFill($attributes)->save();

        return back()->with('success', 'Akun berhasil diperbarui.');
    }

    public function activate(User $user)
    {
        if ($user->role === Role::Staff && $user->division && ! $user->division->is_active) {
            return back()->withErrors([
                'alert' => "Divisi {$user->division->name} sedang nonaktif. Aktifkan divisinya dulu.",
            ]);
        }

        $user->forceFill(['is_active' => true, 'active_until' => null])->save();

        return back()->with('success', "Akun {$user->name} diaktifkan.");
    }

    public function deactivate(Request $request, User $user)
    {
        if ($user->is($request->user())) {
            return back()->withErrors(['alert' => 'Anda tidak bisa menonaktifkan akun sendiri.']);
        }

        $validator = Validator::make($request->all(), [
            'active_until' => ['required', 'date', 'after_or_equal:' . $user->active_from->toDateString()],
        ], [
            'active_until.after_or_equal' => 'Tanggal berhenti tidak boleh sebelum tanggal mulai aktif ('
                . $user->active_from->format('d/m/Y') . ').',
        ], ['active_until' => 'Tanggal berhenti']);

        if ($validator->fails()) {
            return back()->withErrors(['alert' => $validator->errors()->first()]);
        }

        $user->forceFill([
            'is_active'    => false,
            'active_until' => $validator->validated()['active_until'],
        ])->save();

        return back()->with('success', "Akun {$user->name} dinonaktifkan.");
    }

    private function labels(): array
    {
        return [
            'name'          => 'Nama',
            'employee_code' => 'ID karyawan',
            'role'          => 'Role',
            'division_id'   => 'Divisi',
            'pin'           => 'PIN',
            'active_from'   => 'Tanggal mulai aktif',
        ];
    }

    private function messages(): array
    {
        return [
            'pin.digits'          => 'PIN harus tepat 6 angka.',
            'employee_code.regex' => 'ID karyawan hanya boleh huruf, angka, titik, strip, dan garis bawah.',
        ];
    }
}