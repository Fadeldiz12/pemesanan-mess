<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Bawaan scaffold Laravel cuma cek 'api/*' - tapi project ini TIDAK
        // PUNYA routes/api.php sama sekali, sementara banyak halaman (form
        // approve/reject/skip-stage, dll di peminjaman-mess, dan lain-lain)
        // memanggil endpoint di routes/web.php lewat fetch() dengan Accept:
        // application/json (lihat *.blade.php). Tanpa expectsJson() di sini,
        // exception seperti ValidationException dari $request->validate()
        // selalu di-redirect (bukan JSON) untuk request-request itu, walau
        // sudah eksplisit minta JSON - fetch() lalu diam-diam MENGIKUTI
        // redirect itu dan menerima halaman HTML utuh, bukan pesan error.
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
