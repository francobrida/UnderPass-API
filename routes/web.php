<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'app' => 'UnderPass API',
        'version' => '1.0.0',
        'status' => 'Online',
        'docs' => url('/docs'),
        'author' => 'Franco Bridarolli'
    ]);
});

// debug to make passport work on railway
Route::get('/debug-db', function () {
    try {
        $log = "";

        Artisan::call('vendor:publish', ['--tag' => 'passport-migrations']);
        $log .= "1. Migraciones de Passport publicadas.\n";

       
        Artisan::call('migrate', ['--force' => true]);
        $log .= "2. Migraciones ejecutadas: " . Artisan::output() . "\n";

        Artisan::call('passport:install', ['--force' => true]);
        $log .= "3. Passport Install: " . Artisan::output() . "\n";

        return "<pre>$log</pre>";
    } catch (\Exception $e) {
        return "Error: " . $e->getMessage();
    }
});