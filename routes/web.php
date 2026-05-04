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
        $log = "";

        
        Artisan::call('optimize:clear');
        $log .= "Caché limpiada correctamente.\n";

       
        Artisan::call('package:discover');
        $log .= "Paquetes redescubiertos.\n";

       
        $commands = Artisan::all();
        $passportExists = false;
        foreach ($commands as $name => $command) {
            if (str_contains($name, 'passport')) {
                $passportExists = true;
                $log .= "Comando detectado: $name\n";
            }
        }

        if ($passportExists) {
            
            Artisan::call('passport:install', ['--force' => true]);
            $log .= "Passport instalado con éxito.\n";
        } else {
            $log .= "ERROR: El comando sigue sin existir. Intentando registrar manual...\n";
            
            $app = app();
            $app->register(\Laravel\Passport\PassportServiceProvider::class);
            Artisan::call('passport:install', ['--force' => true]);
            $log .= "Passport instalado vía registro manual.\n";
        }

        return "<pre>$log</pre>";

    } catch (\Exception $e) {
        return "Error: " . $e->getMessage() . "\n\nTraza:\n" . $e->getTraceAsString();
    }
});
