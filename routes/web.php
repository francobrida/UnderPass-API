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

Route::get('/reset-db-production', function () {
    // Seguridad (descomenta esto en producción real para que nadie borre tu BD)
    // if (request('token') !== '123456789') { return "No autorizado"; }
    $log = "--- Inicia: Limpieza y Sembrado en Producción ---\n";
    
    try {
        // 1. Borrar todas las tablas a la fuerza (ignora Foreign Keys temporales)
        Artisan::call('db:wipe', ['--force' => true]);
        $log .= "✅ Tablas borradas correctamente.\n";
        // 2. Ejecutar las migraciones
        Artisan::call('migrate', ['--force' => true]);
        $log .= "✅ Migraciones ejecutadas.\n";
        // 3. Instalar Passport (Crucial para generar clientes y claves de encriptación)
        Artisan::call('passport:install', ['--force' => true]);
        $log .= "✅ Passport configurado.\n";
        // 4. Ejecutar los seeders (usuarios de prueba, eventos, etc.)
        Artisan::call('db:seed', ['--force' => true]);
        $log .= "✅ Seeders ejecutados.\n";
        $log .= "\n--- 🎉 Proceso completado con éxito ---\n";
    } catch (\Exception $e) {
        $log .= "\n❌ ERROR CRÍTICO: " . $e->getMessage() . "\n";
        Log::error("Fallo en reset-db: " . $e->getMessage());
    }
    return "<pre>$log</pre>";
});