<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AnnouncementController extends Controller
{
    public function index()
    {
        $announcements = Announcement::with('creator')->orderByDesc('created_at')->get();
        return view('announcements.index', compact('announcements'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'     => 'required|string|max:255',
            'message'   => 'required|string|max:3000',
            'is_active' => 'nullable|boolean',
        ], [
            'title.required'   => 'Başlıq mütləqdir.',
            'message.required' => 'Mətn mütləqdir.',
        ]);

        Announcement::create([
            'title'      => $validated['title'],
            'message'    => $validated['message'],
            'is_active'  => $request->boolean('is_active', true),
            'created_by' => auth()->id(),
        ]);

        Cache::forget('active_announcements');

        return redirect()->route('announcements.index')->with('success', 'Elan uğurla yaradıldı.');
    }

    public function update(Request $request, Announcement $announcement)
    {
        $validated = $request->validate([
            'title'     => 'required|string|max:255',
            'message'   => 'required|string|max:3000',
            'is_active' => 'nullable|boolean',
        ]);

        $announcement->update([
            'title'     => $validated['title'],
            'message'   => $validated['message'],
            'is_active' => $request->boolean('is_active'),
        ]);

        Cache::forget('active_announcements');

        return redirect()->route('announcements.index')->with('success', 'Elan uğurla yeniləndi.');
    }

    public function destroy(Announcement $announcement)
    {
        $announcement->delete();
        Cache::forget('active_announcements');
        return redirect()->route('announcements.index')->with('success', 'Elan silindi.');
    }

    public function toggle(Announcement $announcement)
    {
        $announcement->update(['is_active' => !$announcement->is_active]);
        Cache::forget('active_announcements');
        return response()->json(['is_active' => $announcement->is_active]);
    }
}
