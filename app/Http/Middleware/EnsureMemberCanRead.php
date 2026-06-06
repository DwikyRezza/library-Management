<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureMemberCanRead
{
    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        $member = Auth::guard('member')->user();

        if (! $member || $member->rejected) {
            Auth::guard('member')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('member.login')
                ->withErrors(['login' => 'Membership Anda tidak memiliki akses membaca.']);
        }

        return $next($request);
    }
}
