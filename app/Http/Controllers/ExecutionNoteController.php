<?php

namespace App\Http\Controllers;

use App\Models\ExecutionNote;
use Illuminate\Http\Request;

class ExecutionNoteController extends Controller
{
    public function index()
    {
        return view('execution_notes.index');
    }

    public function load(Request $request)
    {
        $draw = $request->input('draw', 1);
        $start = $request->input('start', 0);
        $length = $request->input('length', 25);

        $totalRecords = ExecutionNote::active()->count();

        $query = ExecutionNote::active();

        $search = $request->input('search.value');
        if ($search) {
            $query->where('note', 'like', '%' . $search . '%');
        }

        $filteredRecords = (clone $query)->count();

        $orderCol = (int) $request->input('order.0.column', 0);
        $orderDir = $request->input('order.0.dir', 'asc') === 'asc' ? 'asc' : 'desc';
        match ($orderCol) {
            0 => $query->orderBy('id', $orderDir),
            1 => $query->orderBy('note', $orderDir),
            default => $query->orderBy('id', 'desc'),
        };

        $results = $query->skip($start)->take($length)->get();

        $data = [];
        foreach ($results as $i => $item) {
            $data[] = [
                'id' => $item->id,
                'rowNum' => $start + $i + 1,
                'note' => \Illuminate\Support\Str::limit($item->note, 100),
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
            'note' => 'required|string',
        ]);

        ExecutionNote::create($validated);

        return redirect()->route('execution-notes.index')->with('success', 'Execution Note created successfully.');
    }

    public function show(ExecutionNote $executionNote)
    {
        return response()->json([
            'id' => $executionNote->id,
            'note' => $executionNote->note,
            'created_at' => $executionNote->created_at?->format('d.m.Y H:i'),
        ]);
    }

    public function edit(ExecutionNote $executionNote)
    {
        return response()->json([
            'id' => $executionNote->id,
            'note' => $executionNote->note,
        ]);
    }

    public function update(Request $request, ExecutionNote $executionNote)
    {
        $validated = $request->validate([
            'note' => 'required|string',
        ]);

        $executionNote->update($validated);

        return redirect()->route('execution-notes.index')->with('success', 'Execution Note updated successfully.');
    }

    public function destroy(ExecutionNote $executionNote)
    {
        $executionNote->update(['is_deleted' => true]);

        return redirect()->route('execution-notes.index')->with('success', 'Execution Note deleted successfully.');
    }
}