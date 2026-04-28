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
      Passport::loadKeysFrom(storage_path());
      
        if (!app()->runningInConsole() && config('app.env') === 'production') {
            try {
                if (Schema::hasTable('users') && \App\Models\User::count() === 0) {
                    Artisan::call('db:seed', ['--force' => true]);
                    Artisan::call('passport:install', ['--force' => true]);
                }
            } catch (\Exception $e) {
            }
        }

        Vouch::observe(VouchObserver::class);
    }
}
