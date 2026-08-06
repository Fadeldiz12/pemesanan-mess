<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceChangePassword
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->force_change_password && !$request->routeIs('password.*', 'logout')) {
            return redirect()->route('password.edit')->with('warning', 'Silakan ganti password terlebih dahulu.');
        }

        return $next($request);
    }
}
