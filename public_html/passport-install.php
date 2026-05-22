<?php
/**
 * ╔══════════════════════════════════════════════════════════════════════╗
 * ║  PASSPORT INSTALL — Inicialización de OAuth2 Personal Access Client ║
 * ║  ARCHIVO TEMPORAL — ELIMINAR INMEDIATAMENTE DESPUÉS DE USAR         ║
 * ╚══════════════════════════════════════════════════════════════════════╝
 *
 * Solución: RuntimeException "Personal access client not found."
 * Causa   : passport:install nunca se ejecutó contra tourfindycom_newbrokers_db.
 * Fix     : Este script bootstrapea Laravel y crea el cliente directamente
 *           desde el contexto web — sin necesidad de acceso CLI/artisan.
 */

// ── Guardia de seguridad: solo localhost ──────────────────────────────────────
if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1', '0:0:0:0:0:0:0:1'])) {
    http_response_code(403);
    header('Content-Type: text/plain');
    exit('403 Forbidden — Solo accesible desde localhost.');
}

// ── Bootstrap de Laravel ──────────────────────────────────────────────────────
define('LARAVEL_START', microtime(true));

require __DIR__ . '/../brokers_new/vendor/autoload.php';

$app = require_once __DIR__ . '/../brokers_new/bootstrap/app.php';

// Kernel de consola (necesario para Artisan::call desde web)
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\Client as OAuthClient;
use Laravel\Passport\PersonalAccessClient;

// ── Leer estado actual de la BD ───────────────────────────────────────────────
$steps   = [];
$hasError = false;

try {
    $activeDb = DB::selectOne('SELECT DATABASE() AS db')->db;
} catch (\Throwable $e) {
    $activeDb = 'ERROR: ' . $e->getMessage();
    $hasError = true;
}

// Clientes existentes antes de cualquier acción
try {
    $existingPersonal  = OAuthClient::where('personal_access_client', 1)->where('revoked', 0)->get();
    $existingPassword  = OAuthClient::where('password_client', 1)->where('revoked', 0)->get();
    $totalClients      = OAuthClient::count();
    $totalPac          = PersonalAccessClient::count();
} catch (\Throwable $e) {
    $existingPersonal = collect();
    $existingPassword = collect();
    $totalClients     = 0;
    $totalPac         = 0;
    $hasError         = true;
    $steps[]          = ['label' => 'Leer oauth_clients', 'ok' => false, 'output' => $e->getMessage()];
}

$alreadyInstalled = $existingPersonal->count() > 0;

// ── Ejecutar solo si no existe ya el Personal Access Client ──────────────────
if (!$alreadyInstalled && !$hasError) {

    // Paso 1: Crear Personal Access Client
    try {
        $exit1 = Artisan::call('passport:client', [
            '--personal' => true,
            '--name'     => 'Brokers Connector Personal Access Client',
        ]);
        $out1 = trim(Artisan::output());
        $steps[] = [
            'label'  => 'passport:client --personal',
            'ok'     => $exit1 === 0,
            'output' => $out1 ?: ($exit1 === 0 ? 'Completado sin output.' : 'Error (exit ' . $exit1 . ')'),
        ];
    } catch (\Throwable $e) {
        $steps[]  = ['label' => 'passport:client --personal', 'ok' => false, 'output' => $e->getMessage()];
        $hasError = true;
    }

    // Paso 2: Crear Password Grant Client (necesario para auth/login API)
    if (!$hasError) {
        try {
            $exit2 = Artisan::call('passport:client', [
                '--password' => true,
                '--name'     => 'Brokers Connector Password Grant Client',
            ]);
            $out2 = trim(Artisan::output());
            $steps[] = [
                'label'  => 'passport:client --password',
                'ok'     => $exit2 === 0,
                'output' => $out2 ?: ($exit2 === 0 ? 'Completado sin output.' : 'Error (exit ' . $exit2 . ')'),
            ];
        } catch (\Throwable $e) {
            $steps[] = ['label' => 'passport:client --password', 'ok' => false, 'output' => $e->getMessage()];
        }
    }
}

// ── Leer estado DESPUÉS de la acción ─────────────────────────────────────────
try {
    $finalPersonal = OAuthClient::where('personal_access_client', 1)->where('revoked', 0)->first();
    $finalPassword = OAuthClient::where('password_client', 1)->where('revoked', 0)->first();
    $finalPac      = PersonalAccessClient::first();
} catch (\Throwable $e) {
    $finalPersonal = null;
    $finalPassword = null;
    $finalPac      = null;
}

$success = $finalPersonal && $finalPac;

?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Passport Install — Brokers Connector</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      background: #0f172a; color: #e2e8f0;
      min-height: 100vh; padding: 2rem 1rem;
    }
    .wrap { max-width: 760px; margin: 0 auto; }
    .card {
      background: #1e293b; border: 1px solid #334155;
      border-radius: 12px; padding: 1.5rem; margin-bottom: 1rem;
    }
    .card-ok      { border-color: #166534; background: #052e16; }
    .card-error   { border-color: #991b1b; background: #1c0505; }
    .card-info    { border-color: #1e40af; background: #0c1a3b; }
    .card-warning { border-color: #92400e; background: #1c0f02; }
    h1 { font-size: 1.4rem; font-weight: 800; }
    h2 { font-size: .8rem; font-weight: 700; color: #94a3b8;
         text-transform: uppercase; letter-spacing: .06em; margin-bottom: .875rem; }
    .big { font-size: 2rem; font-weight: 800; margin: .5rem 0; }
    table { width: 100%; border-collapse: collapse; font-size: .875rem; }
    td { padding: .45rem .6rem; border-bottom: 1px solid #1e293b; }
    td:first-child { color: #94a3b8; font-weight: 600; width: 42%; }
    code {
      background: #0f172a; border: 1px solid #334155;
      border-radius: 4px; padding: .1rem .4rem;
      font-size: .8rem; word-break: break-all;
    }
    .step-row { display: flex; gap: .75rem; align-items: flex-start;
                padding: .75rem; border-radius: 8px; margin-bottom: .5rem; }
    .step-ok    { background: rgba(22,101,52,.2); }
    .step-error { background: rgba(153,27,27,.2); }
    .step-icon  { font-size: 1.25rem; flex-shrink: 0; }
    .step-label { font-weight: 700; font-size: .875rem; }
    .step-out   { font-size: .75rem; color: #94a3b8; font-family: monospace;
                  margin-top: .25rem; white-space: pre-wrap; }
    .warn-box {
      background: #451a03; border: 1px solid #92400e;
      border-radius: 8px; padding: .875rem 1rem;
      font-size: .8rem; color: #fbbf24; margin-top: 1rem;
    }
  </style>
</head>
<body>
<div class="wrap">

  <h1 style="margin-bottom:.25rem;">🔑 Passport Install — OAuth2 Setup</h1>
  <p style="color:#64748b;margin-bottom:1.5rem;">Brokers Connector · <?= date('Y-m-d H:i:s') ?></p>

  <!-- ── Estado de BD ──────────────────────────────────────────────────── -->
  <div class="card card-info">
    <h2>🗄 Base de Datos Activa</h2>
    <table>
      <tr><td>DB activa (SELECT DATABASE())</td>
          <td style="color:<?= $activeDb === 'tourfindycom_newbrokers_db' ? '#4ade80' : '#f87171' ?>;">
            <?= htmlspecialchars($activeDb) ?>
            <?= $activeDb === 'tourfindycom_newbrokers_db' ? ' ✅' : ' ⚠ Verificar .env' ?>
          </td></tr>
      <tr><td>oauth_clients (total antes)</td><td><?= (int)$totalClients ?></td></tr>
      <tr><td>oauth_personal_access_clients</td><td><?= (int)$totalPac ?></td></tr>
      <tr><td>Personal Access Clients activos</td><td><?= (int)$existingPersonal->count() ?></td></tr>
    </table>
  </div>

  <!-- ── Ya instalado / Pasos ejecutados ───────────────────────────────── -->
  <?php if ($alreadyInstalled): ?>
    <div class="card card-info">
      <h2>ℹ Ya existía</h2>
      <p style="color:#93c5fd;">El Personal Access Client ya estaba registrado en la BD. No se creó uno nuevo.</p>
    </div>
  <?php elseif (!empty($steps)): ?>
    <div class="card">
      <h2>⚙ Pasos Ejecutados</h2>
      <?php foreach ($steps as $step): ?>
        <div class="step-row <?= $step['ok'] ? 'step-ok' : 'step-error' ?>">
          <span class="step-icon"><?= $step['ok'] ? '✅' : '❌' ?></span>
          <div>
            <div class="step-label"><code><?= htmlspecialchars($step['label']) ?></code></div>
            <?php if (!empty($step['output'])): ?>
              <div class="step-out"><?= htmlspecialchars($step['output']) ?></div>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <!-- ── Estado final ───────────────────────────────────────────────────── -->
  <div class="card <?= $success ? 'card-ok' : 'card-error' ?>">
    <h2><?= $success ? '✅ Resultado Final' : '❌ Resultado Final' ?></h2>
    <div class="big" style="color:<?= $success ? '#4ade80' : '#f87171' ?>">
      <?= $success ? 'Passport configurado correctamente.' : 'Configuración incompleta — ver errores arriba.' ?>
    </div>

    <?php if ($finalPersonal): ?>
      <table style="margin-top:1rem;">
        <tr><td>Personal Access Client ID</td>
            <td style="color:#4ade80;font-weight:700;"><?= (int)$finalPersonal->id ?></td></tr>
        <tr><td>Nombre</td>
            <td><?= htmlspecialchars($finalPersonal->name) ?></td></tr>
        <tr><td>Secret</td>
            <td><code><?= htmlspecialchars(substr($finalPersonal->secret, 0, 8)) ?>…(oculto)</code></td></tr>
        <tr><td>personal_access_client</td>
            <td><?= $finalPersonal->personal_access_client ? '✅ 1' : '❌ 0' ?></td></tr>
        <tr><td>revoked</td>
            <td><?= $finalPersonal->revoked ? '⚠ 1 (revocado!)' : '✅ 0 (activo)' ?></td></tr>
        <?php if ($finalPac): ?>
        <tr><td>oauth_personal_access_clients.id</td>
            <td style="color:#4ade80;"><?= (int)$finalPac->id ?> ✅</td></tr>
        <?php else: ?>
        <tr><td>oauth_personal_access_clients</td>
            <td style="color:#f87171;">⚠ Sin registro vinculado</td></tr>
        <?php endif; ?>
      </table>
    <?php endif; ?>

    <?php if ($finalPassword): ?>
      <table style="margin-top:.75rem;">
        <tr><td>Password Grant Client ID</td>
            <td><?= (int)$finalPassword->id ?></td></tr>
        <tr><td>Nombre</td>
            <td><?= htmlspecialchars($finalPassword->name) ?></td></tr>
      </table>
    <?php endif; ?>
  </div>

  <?php if ($success): ?>
    <div class="warn-box">
      🎉 <strong>Listo.</strong> El Panel Super Admin V2 debería funcionar ahora.<br><br>
      Prueba el flujo: Login → Panel → V2 Admin.<br><br>
      ⚠ <strong>ELIMINA ESTE ARCHIVO:</strong> <code>public_html/passport-install.php</code>
    </div>
  <?php elseif (!$hasError && !$finalPersonal): ?>
    <div class="warn-box" style="border-color:#991b1b;background:#1c0505;color:#fca5a5;">
      ❌ No se pudo crear el cliente. Usa el SQL de respaldo en phpMyAdmin (ver Informe de Operación).
    </div>
  <?php endif; ?>

</div>
</body>
</html>
