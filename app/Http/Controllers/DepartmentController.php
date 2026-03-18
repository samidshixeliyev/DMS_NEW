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
        $draw = $request->input('draw', 1);
        $start = $request->input('start', 0);
        $length = $request->input('length', 25);

        $totalRecords = Department::active()->count();

        $query = Department::active();

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
                'id' => $dept->id,
                'rowNum' => $start + $i + 1,
                'name' => $dept->name,
            ];
        }

        return response()->json([
            'draw' => (int) $draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate(['name' => 'required|string|max:255']);
        Department::create($validated);
        return redirect()->route('departments.index')->with('success', 'Department created successfully.');
    }

    public function show(Department $department)
    {
        return response()->json([
            'id' => $department->id,
            'name' => $department->name,
            'created_at' => $department->created_at?->format('d.m.Y H:i'),
        ]);
    }

    public function edit(Department $department)
    {
        return response()->json([
            'id' => $department->id,
            'name' => $department->name,
        ]);
    }

    public function update(Request $request, Department $department)
    {
        $validated = $request->validate(['name' => 'required|string|max:255']);
        $department->update($validated);
        return redirect()->route('departments.index')->with('success', 'Department updated successfully.');
    }

    public function destroy(Department $department)
    {
        $department->update(['is_deleted' => true]);
        return redirect()->route('departments.index')->with('success', 'Department deleted successfully.');
    }
}