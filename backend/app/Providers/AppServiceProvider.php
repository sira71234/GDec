<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

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
        // Plusieurs appareils peuvent se connecter en même temps depuis le même
        // réseau (donc la même IP publique). La limite précédente (5/minute)
        // bloquait l'équipe entière dès quelques tentatives. On garde une
        // protection anti-force brute, mais suffisamment large pour un usage
        // simultané normal.
        RateLimiter::for('admin-login', function (Request $request): Limit {
            return Limit::perMinute(15)->by($request->ip());
        });
    }
}
