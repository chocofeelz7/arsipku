<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureActiveUser
{
    public function handle(Request $request, Closure $next)
    {
        if (! $request->user()?->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login')->withErrors(['email' => 'Akun tidak aktif. Hubungi Ketua.']);
        }
        if ($request->user()->must_change_password && ! $request->routeIs('password.*', 'logout')) {
            return redirect()->route('password.edit')->with('notice', 'Ganti kata sandi awal sebelum menggunakan ArsipKU.');
        }
        $response = $next($request);
        $response->headers->set('Cache-Control', 'private, no-store');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        return $response;
    }
}
