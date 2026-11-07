<?php

namespace App\Http\Controllers\Admin;

use App\TaskType;
use App\Http\Controllers\Controller;
use App\Models\Division;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    private const PER_PAGE_OPTIONS = [5, 10, 25, 50];

    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page');
        $perPage = in_array($perPage, self::PER_PAGE_OPTIONS, true) ? $perPage : 10;

        $search = trim((string) $request->query('q'));

        $divisionFilter = (string) $request->query('division', '');
        $divisionFilter = ctype_digit($divisionFilter) ? $divisionFilter : '';

        $tasks = Task::with('division')
            ->when($search !== '', fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->when($divisionFilter !== '', fn ($q) => $q->where('division_id', $divisionFilter))
            ->orderBy(Division::select('name')->whereColumn('divisions.id', 'tasks.division_id'))
            ->orderByDesc('is_active')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.tasks.index', [
            'tasks'            => $tasks,
            'search'           => $search,
            'perPage'          => $perPage,
            'perPageOptions'   => self::PER_PAGE_OPTIONS,
            'divisionFilter'   => $divisionFilter,
            'allDivisions'     => Division::orderBy('name')->get(['id', 'name', 'is_active']),
            'activeDivisions'  => Division::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'division_id' => ['required', Rule::exists('divisions', 'id')->where('is_active', true)],
            'name'        => ['required', 'string', 'max:100',
                              Rule::unique('tasks', 'name')->where('division_id', $request->input('division_id'))],
            'type'        => ['required', Rule::enum(TaskType::class)],
            'sort_order'  => ['nullable', 'integer', 'min:1', 'max:999'],
        ], [], $this->labels());

        // urutan kosong = taruh paling akhir di divisinya
        $data['sort_order'] ??= (Task::where('division_id', $data['division_id'])->max('sort_order') ?? 0) + 1;

        Task::create($data);

        return back()->with('success', 'Tugas berhasil ditambahkan.');
    }

    public function update(Request $request, Task $task)
    {
        // division_id sengaja tidak boleh diubah di sini
        $data = $request->validate([
            'name'       => ['required', 'string', 'max:100',
                             Rule::unique('tasks', 'name')
                                 ->where('division_id', $task->division_id)
                                 ->ignore($task->id)],
            'type'       => ['required', Rule::enum(TaskType::class)],
            'sort_order' => ['nullable', 'integer', 'min:1', 'max:999'],
        ], [], $this->labels());

        $data['sort_order'] ??= $task->sort_order;

        $task->update($data);

        return back()->with('success', 'Tugas berhasil diperbarui.');
    }

    public function toggle(Task $task)
    {
        if (! $task->is_active && ! $task->division->is_active) {
            return back()->withErrors([
                'alert' => "Divisi {$task->division->name} sedang nonaktif. Aktifkan divisinya dulu.",
            ]);
        }

        $task->update(['is_active' => ! $task->is_active]);

        return back()->with('success', $task->is_active
            ? "Tugas {$task->name} diaktifkan."
            : "Tugas {$task->name} dinonaktifkan.");
    }

    private function labels(): array
    {
        return [
            'division_id' => 'Divisi',
            'name'        => 'Nama tugas',
            'type'        => 'Tipe',
            'sort_order'  => 'Urutan',
        ];
    }
}