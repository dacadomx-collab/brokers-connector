<?php
/**
 * ╔══════════════════════════════════════════════════════════════════════╗
 * ║  RESET DE CONTRASEÑA — Super Admin dacadomx@gmail.com               ║
 * ║  ARCHIVO TEMPORAL — ELIMINAR INMEDIATAMENTE DESPUÉS DE USAR         ║
 * ║  Accesible SOLO desde localhost.                                     ║
 * ╚══════════════════════════════════════════════════════════════════════╝
 */

// ── Guardia de seguridad: solo localhost ──────────────────────────────────────
$remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($remoteAddr, ['127.0.0.1', '::1', '0:0:0:0:0:0:0:1'])) {
    http_response_code(403);
    header('Content-Type: text/plain');
    exit('403 Forbidden.');
}

// ── Parsear .env ──────────────────────────────────────────────────────────────
function parseEnvFile(string $path): array
{
    $vars = [];
    if (!file_exists($path)) return $vars;
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) continue;
        [$key, $val] = explode('=', $line, 2);
        $val = trim($val);
        if (strlen($val) >= 2 &&
            (($val[0] === '"' && $val[-1] === '"') || ($val[0] === "'" && $val[-1] === "'"))) {
            $val = substr($val, 1, -1);
        }
        $vars[trim($key)] = $val;
    }
    return $vars;
}

$env    = parseEnvFile(__DIR__ . '/../brokers_new/.env');
$dbHost = $env['DB_HOST']     ?? '';
$dbPort = $env['DB_PORT']     ?? '3306';
$dbName = $env['DB_DATABASE'] ?? '';
$dbUser = $env['DB_USERNAME'] ?? '';
$dbPass = $env['DB_PASSWORD'] ?? '';

// ── Constantes del reset ──────────────────────────────────────────────────────
const TARGET_ID       = 25;
const TARGET_EMAIL    = 'dacadomx@gmail.com';
const NEW_PASSWORD    = 'Gargantua2026!';
const CONFIRM_PARAM   = 'ejecutar';
const CONFIRM_VALUE   = 'RESET_AHORA';

// ── Estado ────────────────────────────────────────────────────────────────────
$confirmed  = ($_GET[CONFIRM_PARAM] ?? '') === CONFIRM_VALUE;
$resetDone  = false;
$resetError = '';
$userBefore = null;
$pdo        = null;

// ── Conectar siempre (para preview + ejecución) ───────────────────────────────
try {
    $pdo = new PDO(
        "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4",
        $dbUser, $dbPass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 8]
    );

    // Leer el usuario objetivo antes de modificar
    $stmt = $pdo->prepare(
        'SELECT id, full_name, email, password, active FROM users WHERE id = ? OR email = ? LIMIT 1'
    );
    $stmt->execute([TARGET_ID, TARGET_EMAIL]);
    $userBefore = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $resetError = 'Error de conexión: ' . preg_replace('/password[=:"\'\s]+\S+/i', 'password=[REDACTED]', $e->getMessage());
}

// ── Ejecutar reset si se confirmó ─────────────────────────────────────────────
if ($confirmed && $pdo && $userBefore && !$resetError) {
    try {
        // password_hash con PASSWORD_BCRYPT cost=10 — idéntico a Laravel Hash::make()
        $newHash = password_hash(NEW_PASSWORD, PASSWORD_BCRYPT, ['cost' => 10]);

        $update = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
        $update->execute([$newHash, (int) $userBefore['id']]);

        if ($update->rowCount() === 1) {
            $resetDone = true;
        } else {
            $resetError = 'El UPDATE no afectó ninguna fila. Revisar el ID.';
        }
    } catch (PDOException $e) {
        $resetError = 'Error en UPDATE: ' . $e->getMessage();
    }
}

// ── URL de confirmación ───────────────────────────────────────────────────────
$confirmUrl = strtok($_SERVER['REQUEST_URI'] ?? '', '?')
    . '?' . CONFIRM_PARAM . '=' . CONFIRM_VALUE;

?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Reset Admin — Brokers Connector</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      background: #0f172a; color: #e2e8f0;
      min-height: 100vh; display: flex;
      align-items: center; justify-content: center;
      padding: 2rem 1rem;
    }
    .wrap  { width: 100%; max-width: 620px; }
    .card  {
      background: #1e293b; border: 1px solid #334155;
      border-radius: 14px; padding: 2rem; margin-bottom: 1rem;
    }
    .card-danger  { border-color: #991b1b; background: #1c0505; }
    .card-success { border-color: #166534; background: #052e16; }
    .card-warning { border-color: #92400e; background: #1c0f02; }
    h1 { font-size: 1.3rem; font-weight: 800; margin-bottom: .25rem; }
    h2 { font-size: .9rem; font-weight: 700; color: #94a3b8;
         text-transform: uppercase; letter-spacing: .06em; margin-bottom: 1rem; }
    table { width: 100%; border-collapse: collapse; font-size: .875rem; }
    td { padding: .45rem .6rem; border-bottom: 1px solid #1e293b; }
    td:first-child { color: #94a3b8; font-weight: 600; width: 45%; }
    td:last-child  { font-family: monospace; color: #e2e8f0; }
    .btn {
      display: inline-block; padding: .75rem 2rem;
      border-radius: 8px; font-weight: 700; font-size: 1rem;
      text-decoration: none; cursor: pointer; margin-top: 1.25rem;
      border: none;
    }
    .btn-danger  { background: #dc2626; color: #fff; }
    .btn-danger:hover  { background: #b91c1c; }
    .big { font-size: 2.5rem; font-weight: 800; margin: .5rem 0; }
    code { background: #0f172a; border: 1px solid #334155;
           border-radius: 4px; padding: .1rem .4rem; font-size: .8rem; }
    .warn-box {
      background: #451a03; border: 1px solid #92400e;
      border-radius: 8px; padding: .875rem 1rem;
      font-size: .8rem; color: #fbbf24; margin-top: 1rem;
    }
  </style>
</head>
<body>
<div class="wrap">

<?php if ($resetError): ?>
  <!-- ERROR ──────────────────────────────────────────────────── -->
  <div class="card card-danger">
    <h1>❌ Error</h1>
    <p style="color:#fca5a5;margin-top:.75rem;"><?= htmlspecialchars($resetError) ?></p>
  </div>

<?php elseif (!$pdo || !$userBefore): ?>
  <!-- SIN CONEXIÓN O USUARIO NO ENCONTRADO ─────────────────── -->
  <div class="card card-danger">
    <h1>❌ <?= !$pdo ? 'Sin conexión a BD' : 'Usuario no encontrado' ?></h1>
    <p style="color:#fca5a5;margin-top:.75rem;">
      <?= !$pdo
          ? 'No se pudo conectar a la base de datos. Verifica las credenciales en <code>.env</code>.'
          : 'No se encontró ningún usuario con ID=' . TARGET_ID . ' o email ' . TARGET_EMAIL . '.' ?>
    </p>
  </div>

<?php elseif ($resetDone): ?>
  <!-- ÉXITO ──────────────────────────────────────────────────── -->
  <div class="card card-success">
    <h2>✅ Reset Completado</h2>
    <div class="big" style="color:#4ade80;">¡Contraseña del Super Admin restablecida con éxito!</div>

    <table style="margin-top:1.25rem;">
      <tr><td>Usuario</td>       <td><?= htmlspecialchars($userBefore['full_name'] ?? '—') ?></td></tr>
      <tr><td>Email</td>         <td><?= htmlspecialchars($userBefore['email'] ?? '—') ?></td></tr>
      <tr><td>ID</td>            <td><?= (int)($userBefore['id'] ?? 0) ?></td></tr>
      <tr><td>Nueva contraseña</td><td><?= htmlspecialchars(NEW_PASSWORD) ?></td></tr>
      <tr><td>Hash generado con</td><td>password_hash() · BCRYPT · cost=10</td></tr>
      <tr><td>Compatible con</td> <td>Laravel Hash::make() — Passport · login nativo</td></tr>
    </table>

    <div class="warn-box" style="margin-top:1.25rem;">
      🔐 Ya puedes iniciar sesión en el panel con estas credenciales.<br><br>
      ⚠ <strong>ELIMINA ESTE ARCHIVO AHORA:</strong><br>
      <code>public_html/reset-admin.php</code>
    </div>
  </div>

<?php else: ?>
  <!-- PANTALLA DE CONFIRMACIÓN ────────────────────────────────── -->
  <div class="card card-warning">
    <h2>⚠ Confirmación Requerida</h2>
    <h1>Reset de Contraseña — Super Admin</h1>
    <p style="color:#d97706;margin-top:.5rem;font-size:.9rem;">
      Esta operación actualizará la contraseña en la base de datos.
      Revisa los datos y confirma antes de proceder.
    </p>
  </div>

  <!-- Preview del usuario a modificar -->
  <div class="card">
    <h2>👤 Usuario objetivo</h2>
    <table>
      <tr><td>ID</td>       <td><?= (int)($userBefore['id'] ?? 0) ?><?php if ((int)($userBefore['id'] ?? 0) !== TARGET_ID): ?> <span style="color:#f87171;">(≠ <?= TARGET_ID ?>)</span><?php endif; ?></td></tr>
      <tr><td>Email</td>    <td><?= htmlspecialchars($userBefore['email'] ?? '—') ?></td></tr>
      <tr><td>Nombre</td>   <td><?= htmlspecialchars($userBefore['full_name'] ?? '—') ?></td></tr>
      <tr><td>Activo</td>   <td><?= $userBefore['active'] ? '✅ Sí' : '❌ No' ?></td></tr>
      <tr><td>Hash actual</td><td style="color:#64748b;"><?= htmlspecialchars(substr($userBefore['password'] ?? '', 0, 22)) ?>… (bcrypt)</td></tr>
    </table>
  </div>

  <div class="card">
    <h2>🔑 Cambio que se aplicará</h2>
    <table>
      <tr><td>Nueva contraseña</td><td style="color:#4ade80;font-weight:700;"><?= htmlspecialchars(NEW_PASSWORD) ?></td></tr>
      <tr><td>Algoritmo</td>       <td>BCRYPT · cost=10 (Laravel-compatible)</td></tr>
      <tr><td>Base de datos</td>   <td><?= htmlspecialchars($dbName) ?></td></tr>
      <tr><td>Host</td>            <td><?= htmlspecialchars($dbHost) ?></td></tr>
    </table>

    <a href="<?= htmlspecialchars($confirmUrl) ?>" class="btn btn-danger">
      🔑 Sí, restablecer contraseña ahora
    </a>
  </div>

<?php endif; ?>

  <p style="text-align:center;color:#334155;font-size:.7rem;margin-top:.5rem;">
    Eliminar: <code style="color:#475569;">public_html/reset-admin.php</code>
  </p>
</div>
</body>
</html>
