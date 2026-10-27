<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class BoothAuthController extends Controller
{
    public function show(): View|RedirectResponse
    {
        if (Auth::check()) {
            // /login selalu ke halaman scan (bukan admin) untuk kedua role.
            return redirect()->route('booth.scan');
        }

        return view('booth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['username' => 'Username atau password salah.'])
                ->onlyInput('username');
        }

        $request->session()->regenerate();

        // Login bilik (operator maupun scanner) selalu mendarat di /bilik.
        // Akses /admin tetap lewat /admin/login (canAccessPanel menolak scanner).
        return redirect()->intended(route('booth.scan'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
