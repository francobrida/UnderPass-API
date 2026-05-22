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
    if (request('token') !== '123456789') { return "No autorizado"; }
    $log = "--- Inicia: Limpieza y Sembrado en Producción ---\n";
    
    try {
        Artisan::call('db:wipe', ['--force' => true]);
        $log .= "✅ Tablas borradas correctamente.\n";
  
        Artisan::call('migrate', ['--force' => true]);
        $log .= "✅ Migraciones ejecutadas (incluyendo Passport si fue publicado).\n";

        Artisan::call('passport:client', ['--personal' => true, '--name' => 'Laravel Personal Access Client']);
        Artisan::call('passport:client', ['--password' => true, '--name' => 'Laravel Password Grant Client']);
        Artisan::call('passport:keys', ['--force' => true]);
        $log .= "✅ Passport configurado (comandos Artisan directos).\n";

        Artisan::call('db:seed', ['--force' => true]);
        $log .= "✅ Seeders ejecutados (incluye Géneros y los 3 TEST EVENTS).\n";
        
        $log .= "\n--- 🎉 Proceso completado con éxito ---\n";
    } catch (\Exception $e) {
        $log .= "\n❌ ERROR CRÍTICO: " . $e->getMessage() . "\n";
        Log::error("Fallo en reset-db: " . $e->getMessage());
    }
    return "<pre>$log</pre>";
});