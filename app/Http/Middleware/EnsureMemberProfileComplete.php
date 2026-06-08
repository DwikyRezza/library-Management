<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMemberProfileComplete
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $member = auth('member')->user();

        if (
            $member &&
            $member->isProfileIncomplete() &&
            ! $request->routeIs('member.profile') &&
            ! $request->routeIs('member.profile.update') &&
            ! $request->routeIs('member.logout')
        ) {
            return redirect()
                ->route('member.profile')
                ->with('warning', 'Lengkapi profil Anda terlebih dahulu sebelum melanjutkan.');
        }

        return $next($request);
    }
}
