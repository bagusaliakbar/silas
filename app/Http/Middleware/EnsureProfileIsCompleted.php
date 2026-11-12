<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProfileIsCompleted
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isMahasiswa() && ! $user->hasCompletedProfile()) {
            // Allow profile edit, update, logout, and verification routes
            if ($request->routeIs('profile.*') || $request->routeIs('logout') || $request->routeIs('verification.*')) {
                return $next($request);
            }

            return redirect()->route('profile.edit')
                ->with('warning', 'Harap lengkapi biodata akademik Anda terlebih dahulu sebelum dapat mengakses portal dan mengajukan surat.');
        }

        return $next($request);
    }
}
