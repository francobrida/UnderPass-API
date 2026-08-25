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

$assertResetAuthorized = function (bool $requiresProdFlag = false) {
    $token = config('app.reset_db_token');
    abort_if(! is_string($token) || $token === '', 404);

    $provided = request('token');
    abort_if(! is_string($provided), 404);

    abort_if(! hash_equals($token, $provided), 404);

    // ponytail: token travels in a GET query string (logs/history/Referer);
    // real fix is POST + signed URL, deferred as out of scope for this hotfix.
    abort_if($requiresProdFlag && ! config('app.allow_prod_db_reset'), 404);
};

Route::get('/reset-db-demo', function () use ($assertResetAuthorized) {
    $assertResetAuthorized();
    $log = "--- Inicia: Limpieza y Sembrado en ENTORNO DEMO ---\n";
    
    try {
        ini_set('display_errors', 1);
        error_reporting(E_ALL);

        Artisan::call('db:wipe', ['--force' => true]);
        $log .= "✅ Tablas borradas correctamente.\n";
  
        Artisan::call('migrate', ['--force' => true]);
        $log .= "✅ Migraciones ejecutadas (incluyendo Passport si fue publicado).\n";

        $clientRepository = app(\Laravel\Passport\ClientRepository::class);
        $clientRepository->createPersonalAccessGrantClient('Laravel Personal Access Client', 'users');
        $clientRepository->createPasswordGrantClient('Laravel Password Grant Client', 'users', true);

        $publicKeyPath = \Laravel\Passport\Passport::keyPath('oauth-public.key');
        $privateKeyPath = \Laravel\Passport\Passport::keyPath('oauth-private.key');
        $key = \phpseclib3\Crypt\RSA::createKey(4096);
        file_put_contents($publicKeyPath, (string) $key->getPublicKey());
        file_put_contents($privateKeyPath, (string) $key);
        if (! windows_os()) {
            chmod($publicKeyPath, 0660);
            chmod($privateKeyPath, 0600);
        }
        $log .= "✅ Passport configurado (clientes creados y llaves generadas directamente).\n";

        Artisan::call('db:seed', ['--force' => true]);
        $log .= "✅ Seeders DEMO ejecutados (incluye Géneros, Usuarios de prueba y Eventos).\n";
        
        $log .= "\n--- 🎉 Proceso DEMO completado con éxito ---\n";
    } catch (\Throwable $e) {
        $log .= "\n❌ ERROR CRÍTICO: " . $e->getMessage() . " en " . $e->getFile() . " línea " . $e->getLine() . "\n";
        try {
            Log::error("Fallo en reset-db-demo: " . $e->getMessage());
        } catch (\Throwable $logError) {
            $log .= "\n❌ Además, falló el Log: " . $logError->getMessage() . "\n";
        }
    }
    return "<pre>$log</pre>";
});

Route::get('/reset-db-prod', function () use ($assertResetAuthorized) {
    $assertResetAuthorized(true);
    $log = "--- Inicia: Limpieza y Sembrado en ENTORNO PRODUCCIÓN ---\n";
    
    try {
        Artisan::call('db:wipe', ['--force' => true]);
        $log .= "✅ Tablas borradas correctamente.\n";
  
        Artisan::call('migrate', ['--force' => true]);
        $log .= "✅ Migraciones ejecutadas.\n";

        $clientRepository = app(\Laravel\Passport\ClientRepository::class);
        $clientRepository->createPersonalAccessGrantClient('Laravel Personal Access Client', 'users');
        $clientRepository->createPasswordGrantClient('Laravel Password Grant Client', 'users', true);

        $publicKeyPath = \Laravel\Passport\Passport::keyPath('oauth-public.key');
        $privateKeyPath = \Laravel\Passport\Passport::keyPath('oauth-private.key');
        $key = \phpseclib3\Crypt\RSA::createKey(4096);
        file_put_contents($publicKeyPath, (string) $key->getPublicKey());
        file_put_contents($privateKeyPath, (string) $key);
        if (! windows_os()) {
            chmod($publicKeyPath, 0660);
            chmod($privateKeyPath, 0600);
        }
        $log .= "✅ Passport configurado (clientes creados y llaves generadas directamente).\n";

        Artisan::call('db:seed', ['--class' => 'ProductionSeeder', '--force' => true]);
        $log .= "✅ Seeder de PRODUCCIÓN ejecutado (Solo Géneros y Admin).\n";
        
        $log .= "\n--- 🎉 Proceso PRODUCCIÓN completado con éxito ---\n";
    } catch (\Exception $e) {
        $log .= "\n❌ ERROR CRÍTICO: " . $e->getMessage() . "\n";
        Log::error("Fallo en reset-db-prod: " . $e->getMessage());
    }
    return "<pre>$log</pre>";
});