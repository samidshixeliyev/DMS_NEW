<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::active()->paginate(20);
        return view('categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        Category::create($validated);

        return redirect()->route('categories.index')->with('success', 'Kateqoriya uğurla yaradıldı.');
    }

    public function show(Category $category)
    {
        return response()->json([
            'id' => $category->id,
            'name' => $category->name,
            'created_at' => $category->created_at?->format('d.m.Y H:i'),
        ]);
    }

    public function edit(Category $category)
    {
        return response()->json([
            'id' => $category->id,
            'name' => $category->name,
        ]);
    }

    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $category->update($validated);

        return redirect()->route('categories.index')->with('success', 'Kateqoriya uğurla yeniləndi.');
    }

    public function destroy(Category $category)
    {
        $category->update(['is_deleted' => true]);

        return redirect()->route('categories.index')->with('success', 'Kateqoriya uğurla silindi.');
    }
}
