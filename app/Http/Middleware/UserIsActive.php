<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class UserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->status !== 'Aktif') {
            Auth::logout();
            $request->session()->invalidate();

            return redirect()->route('login')->withErrors(['username' => 'Akun Anda tidak aktif. Silakan hubungi Super Admin.']);
        }

        return $next($request);
    }
}
