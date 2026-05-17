<?php
/**
 * ╔══════════════════════════════════════════════════════════════════════╗
 * ║  TEST DE FUEGO — Diagnóstico de Entorno Brokers Connector           ║
 * ║  ARCHIVO TEMPORAL — ELIMINAR DESPUÉS DE VERIFICAR                   ║
 * ║  No subir a git. No dejar en producción.                            ║
 * ╚══════════════════════════════════════════════════════════════════════╝
 */

// ── Guardia de seguridad: solo accesible desde localhost ─────────────────────
$remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($remoteAddr, ['127.0.0.1', '::1', '0:0:0:0:0:0:0:1'])) {
    http_response_code(403);
    header('Content-Type: text/plain');
    exit('403 Forbidden — Este script de diagnóstico solo corre desde localhost.');
}

// ── Parsear .env (fuente única de verdad — sin credenciales hardcodeadas) ────
function parseEnvFile(string $path): array
{
    $vars = [];
    if (!file_exists($path)) {
        return $vars;
    }
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') {
            continue;
        }
        if (strpos($line, '=') === false) {
            continue;
        }
        [$key, $val] = explode('=', $line, 2);
        // Remover comillas dobles o simples envolventes
        $val = trim($val);
        if (
            (strlen($val) >= 2 && $val[0] === '"'  && $val[-1] === '"') ||
            (strlen($val) >= 2 && $val[0] === "'"  && $val[-1] === "'")
        ) {
            $val = substr($val, 1, -1);
        }
        $vars[trim($key)] = $val;
    }
    return $vars;
}

// El .env está un nivel arriba de public_html/, dentro de brokers_new/
$envPath = __DIR__ . '/../brokers_new/.env';
$env     = parseEnvFile($envPath);

$dbHost = $env['DB_HOST']     ?? '(no encontrado)';
$dbPort = $env['DB_PORT']     ?? '3306';
$dbName = $env['DB_DATABASE'] ?? '(no encontrado)';
$dbUser = $env['DB_USERNAME'] ?? '(no encontrado)';
$dbPass = $env['DB_PASSWORD'] ?? '';

// ── Variables de resultado ────────────────────────────────────────────────────
$connStatus   = false;
$connError    = '';
$activeDb     = '';
$user1        = null;
$user2        = null;
$user1Found   = false;
$user2Found   = false;
$user1Match   = false;
$user2Match   = false;

// ── 1. Test de conexión PDO ───────────────────────────────────────────────────
try {
    $pdo = new PDO(
        "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT            => 8,
        ]
    );

    $connStatus = true;
    $activeDb   = $pdo->query('SELECT DATABASE()')->fetchColumn();

    // ── 2. Usuario A: leolage@acadep.com / acadep01 ───────────────────────────
    $stmt = $pdo->prepare('SELECT id, email, password FROM users WHERE email = ? LIMIT 1');
    $stmt->execute(['leolage@acadep.com']);
    $user1 = $stmt->fetch();

    if ($user1) {
        $user1Found = true;
        $user1Match = password_verify('acadep01', $user1['password']);
    }

    // ── 3. Usuario B: dacadomx@gmail.com (id=25) / Gargantua2026! ────────────
    $stmt2 = $pdo->prepare('SELECT id, email, password FROM users WHERE email = ? LIMIT 1');
    $stmt2->execute(['dacadomx@gmail.com']);
    $user2 = $stmt2->fetch();

    if ($user2) {
        $user2Found = true;
        $user2Match = password_verify('Gargantua2026!', $user2['password']);
    }

} catch (PDOException $e) {
    $connStatus = false;
    $connError  = $e->getMessage();
    // Sanitizar: no exponer contraseña si aparece en el mensaje de error
    $connError = preg_replace('/password[=:"\'\s]+\S+/i', 'password=[REDACTED]', $connError);
}

// ── Helpers de render ─────────────────────────────────────────────────────────
function badge(bool $ok, string $yes = 'OK', string $no = 'FALLO'): string
{
    $color = $ok ? '#16a34a' : '#dc2626';
    $bg    = $ok ? '#dcfce7' : '#fee2e2';
    $text  = $ok ? $yes : $no;
    return "<span style='background:{$bg};color:{$color};padding:.25rem .75rem;"
         . "border-radius:999px;font-weight:700;font-size:.8rem;'>{$text}</span>";
}

function row(string $label, string $value, bool $ok = true): string
{
    $icon = $ok ? '✅' : '❌';
    return "<tr>
      <td style='padding:.5rem .75rem;color:#374151;font-weight:600;'>{$icon} {$label}</td>
      <td style='padding:.5rem .75rem;color:#111827;font-family:monospace;'>" . htmlspecialchars($value) . "</td>
    </tr>";
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Test de Fuego — Brokers Connector</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      background: #0f172a;
      color: #e2e8f0;
      padding: 2rem 1rem;
      min-height: 100vh;
    }
    .wrap  { max-width: 780px; margin: 0 auto; }
    .card  {
      background: #1e293b;
      border: 1px solid #334155;
      border-radius: 12px;
      padding: 1.5rem;
      margin-bottom: 1.25rem;
    }
    .card-title {
      font-size: 1rem;
      font-weight: 700;
      color: #94a3b8;
      text-transform: uppercase;
      letter-spacing: .08em;
      margin-bottom: 1rem;
      padding-bottom: .5rem;
      border-bottom: 1px solid #334155;
    }
    table { width: 100%; border-collapse: collapse; }
    td    { border-bottom: 1px solid #1e293b; }
    tr:last-child td { border-bottom: none; }
    .big-status {
      font-size: 2rem;
      font-weight: 800;
      margin-bottom: .25rem;
    }
    .warning-box {
      background: #451a03;
      border: 1px solid #92400e;
      border-radius: 8px;
      padding: 1rem 1.25rem;
      font-size: .85rem;
      color: #fbbf24;
      margin-bottom: 1.5rem;
    }
    .env-pill {
      display: inline-block;
      background: #0f172a;
      border: 1px solid #334155;
      border-radius: 6px;
      padding: .1rem .5rem;
      font-family: monospace;
      font-size: .8rem;
      color: #7dd3fc;
    }
    .success-card { border-color: #166534; background: #052e16; }
    .error-card   { border-color: #991b1b; background: #2d0a0a; }
  </style>
</head>
<body>
<div class="wrap">

  <h1 style="font-size:1.5rem;font-weight:800;margin-bottom:.25rem;">
    🔥 Test de Fuego — Diagnóstico de Entorno
  </h1>
  <p style="color:#64748b;margin-bottom:1.5rem;">
    Brokers Connector · <?= date('Y-m-d H:i:s') ?>
  </p>

  <div class="warning-box">
    ⚠ <strong>ARCHIVO TEMPORAL.</strong>
    Eliminar <code>public_html/test-db.php</code> inmediatamente después de verificar.
    No subir a git. No dejar accesible en producción.
  </div>

  <!-- ── SECCIÓN A: Configuración leída del .env ──────────────────────── -->
  <div class="card">
    <div class="card-title">📄 Sección A — Configuración leída del .env</div>
    <p style="font-size:.8rem;color:#64748b;margin-bottom:.75rem;">
      Fuente: <code><?= htmlspecialchars(realpath($envPath) ?: $envPath) ?></code>
    </p>
    <table>
      <?= row('DB_HOST',     $dbHost) ?>
      <?= row('DB_PORT',     $dbPort) ?>
      <?= row('DB_DATABASE', $dbName, $dbName === 'tourfindycom_newbrokers_db') ?>
      <?= row('DB_USERNAME', $dbUser, $dbUser === 'tourfindycom_newbrokers') ?>
      <?= row('DB_PASSWORD', str_repeat('●', min(12, strlen($dbPass))) . ' (' . strlen($dbPass) . ' chars)') ?>
    </table>
    <div style="margin-top:.75rem;">
      <?php if ($dbName !== 'tourfindycom_newbrokers_db'): ?>
        <span style="color:#f87171;font-size:.85rem;">
          ❌ DB_DATABASE incorrecto — esperado: <span class="env-pill">tourfindycom_newbrokers_db</span>
        </span>
      <?php else: ?>
        <span style="color:#4ade80;font-size:.85rem;">✅ DB_DATABASE apunta al servidor remoto correcto.</span>
      <?php endif; ?>
    </div>
  </div>

  <!-- ── SECCIÓN A (cont): Estado de conexión PDO ─────────────────────── -->
  <div class="card <?= $connStatus ? 'success-card' : 'error-card' ?>">
    <div class="card-title">🔌 Sección A — Estado de Conexión PDO</div>
    <div class="big-status" style="color: <?= $connStatus ? '#4ade80' : '#f87171' ?>">
      <?= $connStatus ? '✅ CONEXIÓN EXITOSA' : '❌ CONEXIÓN FALLIDA' ?>
    </div>

    <?php if ($connStatus): ?>
      <table style="margin-top:.75rem;">
        <?= row('Base de datos activa (SELECT DATABASE())', $activeDb, $activeDb === 'tourfindycom_newbrokers_db') ?>
        <?= row('Host confirmado', $dbHost) ?>
      </table>
      <?php if ($activeDb !== 'tourfindycom_newbrokers_db'): ?>
        <p style="color:#f87171;margin-top:.75rem;font-size:.875rem;">
          ⚠ La DB activa es <strong><?= htmlspecialchars($activeDb) ?></strong>,
          se esperaba <strong>tourfindycom_newbrokers_db</strong>. Revisar configuración.
        </p>
      <?php endif; ?>
    <?php else: ?>
      <p style="color:#fca5a5;margin-top:.75rem;font-size:.875rem;">
        <strong>Error PDO:</strong> <?= htmlspecialchars($connError) ?>
      </p>
      <div style="margin-top:1rem;padding:1rem;background:#1c0a0a;border-radius:8px;font-size:.8rem;color:#f97316;">
        <strong>Pasos para resolver:</strong><br>
        1. Verifica que <code>newbrokers.tourfindy.com</code> acepta conexiones MySQL remotas.<br>
        2. En cPanel → <strong>Remote MySQL</strong>: añade tu IP local
           (ver <a href="https://whatismyip.com" target="_blank" style="color:#7dd3fc;">whatismyip.com</a>).<br>
        3. Confirma usuario/contraseña en <code>knowledge/info.txt</code>.
      </div>
    <?php endif; ?>
  </div>

  <!-- ── SECCIÓN B: Usuario leolage@acadep.com ────────────────────────── -->
  <div class="card <?= (!$connStatus) ? '' : ($user1Match ? 'success-card' : 'error-card') ?>">
    <div class="card-title">👤 Sección B — leolage@acadep.com / acadep01</div>

    <?php if (!$connStatus): ?>
      <p style="color:#64748b;">Sin conexión — prueba no ejecutada.</p>
    <?php elseif (!$user1Found): ?>
      <div class="big-status" style="color:#f87171;">❌ USUARIO NO ENCONTRADO</div>
      <p style="color:#fca5a5;margin-top:.5rem;font-size:.875rem;">
        No existe un registro con email <strong>leolage@acadep.com</strong> en la tabla users.
      </p>
    <?php else: ?>
      <table style="margin-bottom:.75rem;">
        <?= row('ID en base de datos',  (string) ($user1['id'] ?? '—')) ?>
        <?= row('Email encontrado',      $user1['email'] ?? '—') ?>
        <?= row('Hash almacenado',       substr($user1['password'] ?? '', 0, 20) . '… (bcrypt)') ?>
        <?= row('Contraseña "acadep01"', $user1Match ? 'COINCIDE' : 'NO COINCIDE', $user1Match) ?>
      </table>
      <div class="big-status" style="color: <?= $user1Match ? '#4ade80' : '#f87171' ?>">
        <?= $user1Match ? '✅ VERIFICADO' : '❌ CONTRASEÑA INCORRECTA' ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- ── SECCIÓN C: Usuario dacadomx@gmail.com ────────────────────────── -->
  <div class="card <?= (!$connStatus) ? '' : ($user2Match ? 'success-card' : 'error-card') ?>">
    <div class="card-title">🔑 Sección C — dacadomx@gmail.com (Super Admin, id=25) / Gargantua2026!</div>

    <?php if (!$connStatus): ?>
      <p style="color:#64748b;">Sin conexión — prueba no ejecutada.</p>
    <?php elseif (!$user2Found): ?>
      <div class="big-status" style="color:#f87171;">❌ USUARIO NO ENCONTRADO</div>
      <p style="color:#fca5a5;margin-top:.5rem;font-size:.875rem;">
        No existe un registro con email <strong>dacadomx@gmail.com</strong> en la tabla users.
      </p>
    <?php else: ?>
      <table style="margin-bottom:.75rem;">
        <?= row('ID en base de datos',         (string) ($user2['id'] ?? '—'), (int)($user2['id'] ?? 0) === 25) ?>
        <?= row('Email encontrado',              $user2['email'] ?? '—') ?>
        <?= row('Hash almacenado',               substr($user2['password'] ?? '', 0, 20) . '… (bcrypt)') ?>
        <?= row('Contraseña "Gargantua2026!"',   $user2Match ? 'COINCIDE' : 'NO COINCIDE', $user2Match) ?>
      </table>
      <?php if ((int)($user2['id'] ?? 0) !== 25): ?>
        <p style="color:#fbbf24;font-size:.8rem;margin-bottom:.5rem;">
          ⚠ El id encontrado es <?= (int)($user2['id'] ?? 0) ?> — se esperaba id=25.
        </p>
      <?php endif; ?>
      <div class="big-status" style="color: <?= $user2Match ? '#4ade80' : '#f87171' ?>">
        <?= $user2Match ? '✅ VERIFICADO' : '❌ CONTRASEÑA INCORRECTA' ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- ── SECCIÓN D: Resumen ejecutivo ─────────────────────────────────── -->
  <div class="card" style="border-color:#7c3aed;background:#1a0533;">
    <div class="card-title" style="color:#c4b5fd;">⚡ Sección D — Resumen Ejecutivo</div>
    <table>
      <?= row('Conexión a tourfindycom_newbrokers_db', $connStatus ? 'EXITOSA' : 'FALLIDA', $connStatus) ?>
      <?= row('DB activa = tourfindycom_newbrokers_db', $activeDb === 'tourfindycom_newbrokers_db' ? 'SÍ' : 'NO', $activeDb === 'tourfindycom_newbrokers_db') ?>
      <?= row('leolage@acadep.com existe',             $user1Found ? 'SÍ' : 'NO', $user1Found) ?>
      <?= row('leolage@acadep.com / acadep01 válido',  $user1Match ? 'SÍ' : 'NO', $user1Match) ?>
      <?= row('dacadomx@gmail.com existe',             $user2Found ? 'SÍ' : 'NO', $user2Found) ?>
      <?= row('dacadomx / Gargantua2026! válido',      $user2Match ? 'SÍ' : 'NO', $user2Match) ?>
    </table>

    <?php
    $allGreen = $connStatus
        && $activeDb === 'tourfindycom_newbrokers_db'
        && $user1Match
        && $user2Match;
    ?>

    <div style="margin-top:1.25rem;padding:1rem;border-radius:8px;
                background:<?= $allGreen ? '#052e16' : '#2d0a0a' ?>;
                border:1px solid <?= $allGreen ? '#166534' : '#991b1b' ?>;">
      <?php if ($allGreen): ?>
        <p style="color:#4ade80;font-size:1.1rem;font-weight:800;">
          🟢 ENTORNO LISTO — Todos los checks pasaron. Sistema operativo en BD remota.
        </p>
      <?php else: ?>
        <p style="color:#f87171;font-size:1.1rem;font-weight:800;">
          🔴 HAY CHECKS FALLIDOS — Revisar las secciones en rojo arriba.
        </p>
      <?php endif; ?>
    </div>
  </div>

  <p style="text-align:center;color:#334155;font-size:.75rem;margin-top:1rem;">
    ⚠ Elimina este archivo una vez verificado:
    <code style="color:#64748b;">public_html/test-db.php</code>
  </p>

</div>
</body>
</html>
