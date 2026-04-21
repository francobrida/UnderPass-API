<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Vouch;
use App\Observers\VouchObserver;
use Illuminate\Support\Facades\URL;

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
    public function boot()
    {
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        Vouch::observe(VouchObserver::class);
    }
}
