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
        
        Artisan::call('migrate', ['--force' => true]);
        $output = Artisan::output();

        Artisan::call('passport:install', ['--force' => true]);
        $output .= "\nPassport Install: " . Artisan::output();

        return "<pre>$output</pre>";
    } catch (\Exception $e) {
        return "Error: " . $e->getMessage();
    }
});
