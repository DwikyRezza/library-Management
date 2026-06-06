<?php

namespace App\Http\Controllers;

use App\Http\Requests\MemberLoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MemberAuthController extends Controller
{
    public function create(): View
    {
        return view('auth.member-login');
    }

    public function store(MemberLoginRequest $request): RedirectResponse
    {
        $credentials = $request->validated();
        $field = filter_var($credentials['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        if (! Auth::guard('member')->attempt([
            $field => $credentials['login'],
            'password' => $credentials['password'],
            'rejected' => false,
        ], (bool) ($credentials['remember'] ?? false))) {
            throw ValidationException::withMessages([
                'login' => 'Login gagal. Periksa username/email, password, dan status membership.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('books.search'));
    }

    public function destroy(): RedirectResponse
    {
        Auth::guard('member')->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('home')->with('success', 'Anda telah keluar dari akun member.');
    }
}
