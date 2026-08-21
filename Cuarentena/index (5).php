<?php

/**
 * BROKERS CONNECTOR - CAPA DE PRESENTACIÓN (CARA PÚBLICA)
 * Regla de ORO - Arquitectura Dual
 */

define('LARAVEL_START', microtime(true));

/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
| Conecta la Cara Pública (/public_html) con el Cerebro (/brokers)
*/

require __DIR__.'/../brokers/vendor/autoload.php';

/*
|--------------------------------------------------------------------------
| Turn On The Lights
|--------------------------------------------------------------------------
| Inicializa el contenedor IoC de Laravel
*/

$app = require_once __DIR__.'/../brokers/bootstrap/app.php';

/*
|--------------------------------------------------------------------------
| Run The Application
|--------------------------------------------------------------------------
*/

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

$response->send();

$kernel->terminate($request, $response);