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
    // 1. Verificación de seguridad opcional (puedes usar un parámetro en la URL)
    // if (request('token') !== 'un-token-secreto-aqui') { return "No autorizado"; }

    $log = "--- Inicia: Wipe, Migrate y Seed en Producción ---\n";
    
    try {
        // Ejecutamos el comando 'fresh' que elimina tablas y recrea el esquema.
        // El parámetro '--seed' ejecuta automáticamente el DatabaseSeeder.
        $exitCode = Artisan::call('migrate:fresh', [
            '--force' => true,
            '--seed' => true
        ]);

        if ($exitCode === 0) {
            $log .= "✅ Proceso completado con éxito.\n";
            $log .= "--- Salida del sistema: ---\n";
            $log .= Artisan::output();
        } else {
            $log .= "⚠️ El comando terminó con un código de salida inesperado: $exitCode\n";
        }

    } catch (\Exception $e) {
        $log .= "❌ ERROR CRÍTICO: " . $e->getMessage() . "\n";
        // Opcional: Registrar el error en los logs de Laravel para debug
        Log::error("Fallo en reset-db: " . $e->getMessage());
    }

    return "<pre>$log</pre>";
});