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
        if (class_exists('Laravel\Passport\Passport')) {
            $passport = 'Laravel\Passport\Passport';
            $passport::ignoreCryptoKeys();
            $passport::personalAccessClientId(1);
        }

        Vouch::observe(VouchObserver::class);
    }
}
