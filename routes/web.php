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
    $log = "--- Chequeo de Integridad de Passport ---\n";

    if (class_exists(\Laravel\Passport\PassportServiceProvider::class)) {
        $log .= "✅ La clase PassportServiceProvider existe en el servidor.\n";
    } else {
        $log .= "❌ ERROR: La clase no existe. El paquete no se instaló en el vendor de producción.\n";
    }

    try {
        app()->register(\Laravel\Passport\PassportServiceProvider::class);
        $log .= "✅ Registro manual completado.\n";
        
        \Illuminate\Support\Facades\Artisan::call('optimize:clear');
        
        // Ejecutamos el comando directamente por su nombre de clase si Artisan no lo mapeó
        $exitCode = \Illuminate\Support\Facades\Artisan::call('passport:install', ['--force' => true]);
        $log .= "✅ passport:install ejecutado (Código: $exitCode).\n";
        $log .= "Salida: " . \Illuminate\Support\Facades\Artisan::output();
    } catch (\Exception $e) {
        $log .= "❌ Fallo en ejecución: " . $e->getMessage() . "\n";
    }

    return "<pre>$log</pre>";
});

Route::get('/seed-db', function () {
    $log = "--- Seeding Database in Production ---\n";
    try {
        \Illuminate\Support\Facades\Artisan::call('db:seed', ['--force' => true]);
        $log .= "✅ Database successfully seeded in production!\n";
        $log .= \Illuminate\Support\Facades\Artisan::output();
    } catch (\Exception $e) {
        $log .= "❌ Fallo en ejecución: " . $e->getMessage() . "\n";
    }

    return "<pre>$log</pre>";
});