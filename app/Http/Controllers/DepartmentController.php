<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index()
    {
        return view('departments.index');
    }

    public function load(Request $request)
    {
        $draw   = $request->input('draw', 1);
        $start  = $request->input('start', 0);
        $length = $request->input('length', 25);

        $totalRecords = Department::active()->count();

        $query = Department::active()->with('parent');

        $search = $request->input('search.value');
        if ($search) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        $filteredRecords = (clone $query)->count();

        $orderCol = (int) $request->input('order.0.column', 0);
        $orderDir = $request->input('order.0.dir', 'asc') === 'asc' ? 'asc' : 'desc';
        match ($orderCol) {
            0 => $query->orderBy('id', $orderDir),
            1 => $query->orderBy('name', $orderDir),
            default => $query->orderBy('id', 'desc'),
        };

        $results = $query->skip($start)->take($length)->get();

        $data = [];
        foreach ($results as $i => $dept) {
            $data[] = [
                'id'         => $dept->id,
                'rowNum'     => $start + $i + 1,
                'name'       => $dept->name,
                'parent'     => $dept->parent?->name ?? '-',
                'can_assign' => $dept->can_assign
                    ? '<span class="badge bg-success">Bəli</span>'
                    : '<span class="badge bg-secondary">Xeyr</span>',
            ];
        }

        return response()->json([
            'draw'            => (int) $draw,
            'recordsTotal'    => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data'            => $data,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'parent_id' => 'nullable|exists:departments,id',
            'can_assign' => 'nullable|boolean',
        ]);

        $validated['can_assign'] = $request->boolean('can_assign');

        Department::create($validated);

        return redirect()->route('departments.index')->with('success', 'İdarə uğurla yaradıldı.');
    }

    public function show(Department $department)
    {
        $department->load('parent');

        return response()->json([
            'id'          => $department->id,
            'name'        => $department->name,
            'parent_id'   => $department->parent_id,
            'parent_name' => $department->parent?->name,
            'can_assign'  => (bool) $department->can_assign,
            'created_at'  => $department->created_at?->format('d.m.Y H:i'),
        ]);
    }

    public function edit(Department $department)
    {
        $allDepts = Department::active()
            ->where('id', '!=', $department->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json([
            'id'             => $department->id,
            'name'           => $department->name,
            'parent_id'      => $department->parent_id,
            'can_assign'     => (bool) $department->can_assign,
            'all_departments' => $allDepts,
        ]);
    }

    public function update(Request $request, Department $department)
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'parent_id' => [
                'nullable',
                'exists:departments,id',
                function ($attribute, $value, $fail) use ($department) {
                    if ($value === null) return;
                    $descendants = Department::descendantIdsOf($department->id);
                    if (in_array((int) $value, $descendants)) {
                        $fail('Bir idarə öz alt-idarəsinə tabe ola bilməz (dövri əlaqə).');
                    }
                },
            ],
            'can_assign' => 'nullable|boolean',
        ]);

        $validated['can_assign'] = $request->boolean('can_assign');

        $department->update($validated);

        return redirect()->route('departments.index')->with('success', 'İdarə uğurla yeniləndi.');
    }

    public function destroy(Department $department)
    {
        $department->update(['is_deleted' => true]);

        return redirect()->route('departments.index')->with('success', 'İdarə uğurla silindi.');
    }
}
