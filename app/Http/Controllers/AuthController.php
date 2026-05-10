<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('legal-acts.index');
        }
        return response()->view('auth.login')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        // Only allow active users
        if (Auth::attempt(array_merge($credentials, ['is_deleted' => false]), $request->boolean('remember'))) {
            $request->session()->regenerate();
            ActivityLog::record(ActivityLog::ACTION_LOGIN, 'Sistemə daxil oldu');
            return redirect()->intended(route('legal-acts.index'));
        }

        return back()->withErrors([
            'username' => 'İstifadəçi adı və ya şifrə yanlışdır.',
        ])->onlyInput('username');
    }

    public function logout(Request $request)
    {
        ActivityLog::record(ActivityLog::ACTION_LOGOUT, 'Sistemdən çıxdı');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
