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
        $log = "--- Forzando Registro de Passport ---\n";

        // 1. Registro manual del Service Provider (Esto activa el comando si el discovery falló)
        app()->register(\Laravel\Passport\PassportServiceProvider::class);
        $log .= "Provider registrado manualmente.\n";

        // 2. Limpieza de optimización
        Artisan::call('optimize:clear');
        $log .= "Caché de optimización purgada.\n";

        // 3. Ejecutar la instalación con salida directa
        Artisan::call('passport:install', ['--force' => true]);
        $log .= "Resultado Passport Install:\n" . Artisan::output();

        return "<pre>$log</pre>";
    } catch (\Exception $e) {
        return "Error en el fix: " . $e->getMessage() . "\n\n" . $e->getTraceAsString();
    }
});