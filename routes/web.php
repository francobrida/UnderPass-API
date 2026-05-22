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

Route::get('/reset-db-demo', function () {
    if (request('token') !== '123456789') { return "No autorizado"; }
    $log = "--- Inicia: Limpieza y Sembrado en ENTORNO DEMO ---\n";
    
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
        $log .= "✅ Seeders DEMO ejecutados (incluye Géneros, Usuarios de prueba y Eventos).\n";
        
        $log .= "\n--- 🎉 Proceso DEMO completado con éxito ---\n";
    } catch (\Exception $e) {
        $log .= "\n❌ ERROR CRÍTICO: " . $e->getMessage() . "\n";
        Log::error("Fallo en reset-db-demo: " . $e->getMessage());
    }
    return "<pre>$log</pre>";
});

Route::get('/reset-db-prod', function () {
    if (request('token') !== '123456789') { return "No autorizado"; }
    $log = "--- Inicia: Limpieza y Sembrado en ENTORNO PRODUCCIÓN ---\n";
    
    try {
        Artisan::call('db:wipe', ['--force' => true]);
        $log .= "✅ Tablas borradas correctamente.\n";
  
        Artisan::call('migrate', ['--force' => true]);
        $log .= "✅ Migraciones ejecutadas.\n";

        Artisan::call('passport:client', ['--personal' => true, '--name' => 'Laravel Personal Access Client']);
        Artisan::call('passport:client', ['--password' => true, '--name' => 'Laravel Password Grant Client']);
        Artisan::call('passport:keys', ['--force' => true]);
        $log .= "✅ Passport configurado.\n";

        Artisan::call('db:seed', ['--class' => 'ProductionSeeder', '--force' => true]);
        $log .= "✅ Seeder de PRODUCCIÓN ejecutado (Solo Géneros y Admin).\n";
        
        $log .= "\n--- 🎉 Proceso PRODUCCIÓN completado con éxito ---\n";
    } catch (\Exception $e) {
        $log .= "\n❌ ERROR CRÍTICO: " . $e->getMessage() . "\n";
        Log::error("Fallo en reset-db-prod: " . $e->getMessage());
    }
    return "<pre>$log</pre>";
});