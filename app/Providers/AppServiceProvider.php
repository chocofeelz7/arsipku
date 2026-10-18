<?php

namespace App\Providers;

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
        \Illuminate\Support\Facades\RateLimiter::for('login', function (\Illuminate\Http\Request $request) {
            return [\Illuminate\Cache\RateLimiting\Limit::perMinute(5)->by(hash('sha256', mb_strtolower((string) $request->input('email')).'|'.$request->ip())), \Illuminate\Cache\RateLimiting\Limit::perMinute(30)->by($request->ip())];
        });
        \Illuminate\Support\Facades\View::composer('layouts.app', function ($view) {
            $user = auth()->user();
            $view->with('unreadCount', $user ? \App\Models\InboxMessage::where('user_id', $user->id)->whereNull('read_at')->whereHas('document', fn ($q) => $q->visibleTo($user))->count() : 0);
        });
    }
}
