<?php

namespace App\Http\Middleware;

use App\Support\AccessMatrix;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAccess
{
    public function handle(Request $request, Closure $next, string $menuKey, string $action = 'read'): Response
    {
        abort_unless(AccessMatrix::can($menuKey, $action, $request->user()), 403, 'Anda tidak memiliki akses ke halaman ini.');

        return $next($request);
    }
}
