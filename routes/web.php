<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;

Route::get('/', function () {
    return response()->json([
        'app' => 'UnderPass API',
        'version' => '1.0.0',
        'status' => 'Online',
        'docs' => url('/docs'),
        'author' => 'Franco Bridarolli'
    ]);
});
