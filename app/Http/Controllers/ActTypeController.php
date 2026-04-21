<?php

namespace App\Http\Controllers;

use App\Models\ActType;
use Illuminate\Http\Request;

class ActTypeController extends Controller
{
    public function index()
    {
        return view('act_types.index');
    }

    public function load(Request $request)
    {
        $draw = $request->input('draw', 1);
        $start = $request->input('start', 0);
        $length = $request->input('length', 25);

        $totalRecords = ActType::active()->count();

        $query = ActType::active();

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
        foreach ($results as $i => $item) {
            $data[] = [
                'id' => $item->id,
                'rowNum' => $start + $i + 1,
                'name' => $item->name,
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
        ]);

        ActType::create($validated);

        return redirect()->route('act-types.index')->with('success', 'Act Type created successfully.');
    }

    public function show(ActType $actType)
    {
        
        return response()->json([
            'id' => $actType->id,
            'name' => $actType->name,
            'created_at' => $actType->created_at?->format('d.m.Y H:i'),
        ]);
    }

    public function edit(ActType $actType)
    {
        return response()->json([
            'id' => $actType->id,
            'name' => $actType->name,
        ]);
    }

    public function update(Request $request, ActType $actType)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $actType->update($validated);

        return redirect()->route('act-types.index')->with('success', 'Act Type updated successfully.');
    }

    public function destroy(ActType $actType)
    {
        $actType->update(['is_deleted' => true]);

        return redirect()->route('act-types.index')->with('success', 'Act Type deleted successfully.');
    }
}