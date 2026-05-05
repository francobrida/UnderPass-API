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
        // 3. Generar claves y clientes de Passport usando shell_exec
        // (Passport oculta sus comandos en rutas web, por lo que usamos la terminal directamente)
        $output1 = shell_exec('php ../artisan passport:keys --force 2>&1');
        $output2 = shell_exec('php ../artisan passport:client --personal --name="Laravel Personal Access Client" 2>&1');
        $output3 = shell_exec('php ../artisan passport:client --password --name="Laravel Password Grant Client" 2>&1');
        
        // Si no funciona con '../artisan', probamos con 'artisan' (dependiendo del CWD en Railway)
        if (strpos($output1, 'Could not open input file') !== false) {
             shell_exec('php artisan passport:keys --force');
             shell_exec('php artisan passport:client --personal --name="Laravel Personal Access Client"');
             shell_exec('php artisan passport:client --password --name="Laravel Password Grant Client"');
        }
        
        $log .= "✅ Passport configurado (comandos de terminal ejecutados).\n";

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