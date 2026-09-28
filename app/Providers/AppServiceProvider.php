<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        // Data lonceng notifikasi di topbar (lihat partials/navbar.blade.php)
        // dibagikan lewat composer supaya tidak perlu di-compact() manual di
        // SETIAP controller yang render layout ini - partial ini muncul di
        // hampir semua halaman ter-autentikasi.
        View::composer('partials.navbar', function ($view) {
            $user = auth()->user();

            $view->with([
                'unreadNotificationsCount' => $user?->unreadNotifications()->count() ?? 0,
                'recentNotifications' => $user?->notifications()->latest()->limit(8)->get() ?? collect(),
            ]);
        });
    }
}