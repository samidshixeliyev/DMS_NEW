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
        $executors = Executor::with('department')->active()->get();
        return view('users.index', compact('executors'));
    }

    public function load(Request $request)
    {
        $draw   = $request->input('draw', 1);
        $start  = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 25);
        $search = trim($request->input('search.value', ''));

        $query = User::with(['executor.department', 'department'])->active();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('surname', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('user_role', 'like', "%{$search}%");
            });
        }

        $total    = User::active()->count();
        $filtered = (clone $query)->count();

        $users = $query->orderBy('id', 'desc')->skip($start)->take($length)->get();

        $roleMap = ['admin' => 'Admin', 'manager' => 'Menecer', 'user' => 'İstifadəçi', 'executor' => 'İcraçı'];

        $data = $users->map(fn($u) => [
            'id'           => $u->id,
            'name'         => $u->name,
            'surname'      => $u->surname,
            'username'     => $u->username,
            'user_role'    => $u->user_role,
            'roleLabel'    => $roleMap[$u->user_role] ?? $u->user_role,
            'executor'     => $u->executor?->name,
            'executorDept' => $u->executor?->department?->name,
            'department'   => $u->department?->name,
            'isSelf'       => $u->id === auth()->id(),
        ]);

        return response()->json([
            'draw'            => (int) $draw,
            'recordsTotal'    => $total,
            'recordsFiltered' => $filtered,
            'data'            => $data,
        ]);
    }

    public function checkUsername(Request $request)
    {
        $username  = $request->input('username', '');
        $excludeId = $request->input('exclude_id');

        $query = User::where('username', $username)->where('is_deleted', false);
        if ($excludeId) {
            $query->where('id', '!=', (int) $excludeId);
        }

        return response()->json(['exists' => $query->exists()]);
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
            'department_id' => $request->input('user_role') === 'manager'
                ? 'required|exists:departments,id'
                : 'nullable|exists:departments,id',
        ]);

        $validated['password'] = Hash::make($validated['password']);

        // executor_id is only meaningful for executor and manager roles
        if (!in_array($validated['user_role'], ['executor', 'manager'])) {
            $validated['executor_id'] = null;
        }

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
            'department_id' => $request->input('user_role') === 'manager'
                ? 'required|exists:departments,id'
                : 'nullable|exists:departments,id',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        // executor_id is only meaningful for executor and manager roles
        if (!in_array($validated['user_role'], ['executor', 'manager'])) {
            $validated['executor_id'] = null;
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