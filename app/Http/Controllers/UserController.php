<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Executor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with(['executor.department', 'department'])->active()->orderBy('id', 'desc')->paginate(20);
        $executors = Executor::with('department')->active()->get();
        return view('users.index', compact('users', 'executors'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'surname' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username',
            'password' => 'required|string|min:6|confirmed',
            'user_role' => 'required|in:admin,manager,user,executor',
            'executor_id' => 'nullable|exists:executors,id',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        $validated['password'] = Hash::make($validated['password']);

        User::create($validated);

        return redirect()->route('users.index')->with('success', 'İstifadəçi uğurla yaradıldı.');
    }

    public function show(User $user)
    {
        $user->load('executor.department', 'department');
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'surname' => $user->surname,
            'username' => $user->username,
            'user_role' => $user->user_role,
            'executor_name' => $user->executor?->name,
            'executor_department' => $user->executor?->department?->name,
            'department_name' => $user->department?->name,
            'created_at' => $user->created_at?->format('d.m.Y H:i'),
        ]);
    }

    public function edit(User $user)
    {
        $executors = Executor::with('department')->active()->get();
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'surname' => $user->surname,
            'username' => $user->username,
            'user_role' => $user->user_role,
            'executor_id' => $user->executor_id,
            'department_id' => $user->department_id,
            'departments' => \App\Models\Department::active()->get(),
            'executors' => $executors,
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'surname' => 'required|string|max:255',
            'username' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:6|confirmed',
            'user_role' => 'required|in:admin,manager,user,executor',
            'executor_id' => 'nullable|exists:executors,id',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return redirect()->route('users.index')->with('success', 'İstifadəçi uğurla yeniləndi.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return redirect()->route('users.index')->with('error', 'Özünüzü silə bilməzsiniz.');
        }

        $user->update(['is_deleted' => true]);

        return redirect()->route('users.index')->with('success', 'İstifadəçi uğurla silindi.');
    }

    public function changePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ], [
            'current_password.required' => 'Cari şifrə mütləqdir.',
            'password.required' => 'Yeni şifrə mütləqdir.',
            'password.min' => 'Yeni şifrə ən azı 6 simvol olmalıdır.',
            'password.confirmed' => 'Şifrə təkrarı uyğun gəlmir.',
        ]);

        $user = auth()->user();

        if (!Hash::check($validated['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => 'Cari şifrə yanlışdır.']);
        }

        $user->update(['password' => Hash::make($validated['password'])]);

        return back()->with('success', 'Şifrəniz uğurla dəyişdirildi.');
    }
}