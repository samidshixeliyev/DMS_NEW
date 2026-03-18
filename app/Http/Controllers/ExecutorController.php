<?php

namespace App\Http\Controllers;

use App\Models\Executor;
use App\Models\Department;
use Illuminate\Http\Request;

class ExecutorController extends Controller
{
    public function index()
    {
        $departments = Department::active()->get();
        return view('executors.index', compact('departments'));
    }

    public function load(Request $request)
    {
        $draw = $request->input('draw', 1);
        $start = $request->input('start', 0);
        $length = $request->input('length', 25);

        $totalRecords = Executor::active()->count();

        $query = Executor::with('department')->active();

        $search = $request->input('search.value');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('position', 'like', '%' . $search . '%')
                  ->orWhereHas('department', fn($dq) => $dq->where('name', 'like', '%' . $search . '%'));
            });
        }

        $filteredRecords = (clone $query)->count();

        $orderCol = (int) $request->input('order.0.column', 0);
        $orderDir = $request->input('order.0.dir', 'asc') === 'asc' ? 'asc' : 'desc';
        match ($orderCol) {
            0 => $query->orderBy('id', $orderDir),
            1 => $query->orderBy('name', $orderDir),
            2 => $query->orderBy('position', $orderDir),
            default => $query->orderBy('id', 'desc'),
        };

        $results = $query->skip($start)->take($length)->get();

        $data = [];
        foreach ($results as $i => $item) {
            $data[] = [
                'id' => $item->id,
                'rowNum' => $start + $i + 1,
                'name' => $item->name,
                'position' => $item->position ?? '-',
                'department' => $item->department?->name ?? '-',
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
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'position' => 'required|string|max:255',
            'department_id' => 'required|exists:departments,id',
        ]);

        Executor::create($validated);

        return redirect()->route('executors.index')->with('success', 'Executor created successfully.');
    }

    public function show(Executor $executor)
    {
        $executor->load('department');
        return response()->json([
            'id' => $executor->id,
            'name' => $executor->name,
            'position' => $executor->position,
            'department' => $executor->department?->name,
            'created_at' => $executor->created_at?->format('d.m.Y H:i'),
        ]);
    }

    public function edit(Executor $executor)
    {
        $departments = Department::active()->get();
        return response()->json([
            'id' => $executor->id,
            'name' => $executor->name,
            'position' => $executor->position,
            'department_id' => $executor->department_id,
            'departments' => $departments,
        ]);
    }

    public function update(Request $request, Executor $executor)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'position' => 'required|string|max:255',
            'department_id' => 'required|exists:departments,id',
        ]);

        $executor->update($validated);

        return redirect()->route('executors.index')->with('success', 'Executor updated successfully.');
    }

    public function destroy(Executor $executor)
    {
        $executor->update(['is_deleted' => true]);

        return redirect()->route('executors.index')->with('success', 'Executor deleted successfully.');
    }
}