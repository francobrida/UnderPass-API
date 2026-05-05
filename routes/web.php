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
        $log .= "✅ Migraciones ejecutadas.\n";

        $output1 = shell_exec('php ../artisan passport:keys --force 2>&1');
        $output2 = shell_exec('php ../artisan passport:client --personal --name="Laravel Personal Access Client" 2>&1');
        $output3 = shell_exec('php ../artisan passport:client --password --name="Laravel Password Grant Client" 2>&1');
        
        if (strpos($output1, 'Could not open input file') !== false) {
             shell_exec('php artisan passport:keys --force');
             shell_exec('php artisan passport:client --personal --name="Laravel Personal Access Client"');
             shell_exec('php artisan passport:client --password --name="Laravel Password Grant Client"');
        }
        
        $log .= "✅ Passport configurado (comandos de terminal ejecutados).\n";

        Artisan::call('db:seed', ['--force' => true]);
        $log .= "✅ Seeders ejecutados.\n";
        $log .= "\n--- 🎉 Proceso completado con éxito ---\n";
    } catch (\Exception $e) {
        $log .= "\n❌ ERROR CRÍTICO: " . $e->getMessage() . "\n";
        Log::error("Fallo en reset-db: " . $e->getMessage());
    }
    return "<pre>$log</pre>";
});