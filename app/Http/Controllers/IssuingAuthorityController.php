<?php

namespace App\Http\Controllers;

use App\Models\IssuingAuthority;
use Illuminate\Http\Request;

class IssuingAuthorityController extends Controller
{
    public function index()
    {
        $issuingAuthorities = IssuingAuthority::active()->paginate(20);
        return view('issuing_authorities.index', compact('issuingAuthorities'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        IssuingAuthority::create($validated);

        return redirect()->route('issuing-authorities.index')->with('success', 'Qəbul edən orqan uğurla yaradıldı.');
    }

    public function show(IssuingAuthority $issuingAuthority)
    {
        return response()->json([
            'id' => $issuingAuthority->id,
            'name' => $issuingAuthority->name,
            'created_at' => $issuingAuthority->created_at?->format('d.m.Y H:i'),
        ]);
    }

    public function edit(IssuingAuthority $issuingAuthority)
    {
        return response()->json([
            'id' => $issuingAuthority->id,
            'name' => $issuingAuthority->name,
        ]);
    }

    public function update(Request $request, IssuingAuthority $issuingAuthority)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $issuingAuthority->update($validated);

        return redirect()->route('issuing-authorities.index')->with('success', 'Qəbul edən orqan uğurla yeniləndi.');
    }

    public function destroy(IssuingAuthority $issuingAuthority)
    {
        $issuingAuthority->update(['is_deleted' => true]);

        return redirect()->route('issuing-authorities.index')->with('success', 'Qəbul edən orqan uğurla silindi.');
    }
}
