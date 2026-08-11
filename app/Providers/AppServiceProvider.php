<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Vouch;
use App\Models\User;
use Laravel\Passport\Passport;
use App\Observers\VouchObserver;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;

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
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }
        
        \Laravel\Passport\Passport::loadKeysFrom(storage_path());
        Vouch::observe(VouchObserver::class);

        RateLimiter::for('login', function ($request) {
            $rawEmail = $request->input('email');
            $email = strtolower(trim(is_string($rawEmail) ? $rawEmail : ''));

            return Limit::perMinute(5)->by($email . '|' . $request->ip());
        });
    }
}
