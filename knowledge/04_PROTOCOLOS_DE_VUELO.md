# 🧪 PROTOCOLOS DE VUELO (CHECKLISTS DE CALIDAD)

## 🤖 DIRECTRIZ DE AGENTE AUTÓNOMO (VS CODE)
La IA (Claude) actúa como un Agente Integrado con permisos de lectura/escritura en el sistema de archivos.
- **PROHIBIDO:** Entregar bloques de código largos para que el humano los copie y pegue manualmente.
- **OBLIGATORIO:** La IA debe buscar, abrir, editar, guardar y verificar los archivos directamente usando sus herramientas de entorno.
- **FLUJO DE TRABAJO:** El Arquitecto (Humano + Gemini) define la estrategia y la arquitectura. La IA ejecuta el código directamente en los archivos, hace las pruebas locales necesarias y reporta un "Informe de Operación" detallado al terminar.

## 🛫 PRE-CODE CHECKLIST (OBLIGATORIO)
Antes de generar código, la IA debe confirmar:
- [ ] ¿Las variables están registradas en el Codex?
- [ ] ¿El endpoint respeta el Contrato de API?
- [ ] ¿El diseño propuesto es Mobile-First?
- [ ] ¿Existe una Regla de Piedra que afecte esta lógica?

## 🛡️ FOUNDATION CHECK (ARRANQUE DE PROYECTO)
Al iniciar un proyecto desde cero, la IA debe preguntar y confirmar:
- [ ] ¿Están creados los archivos de Fundación (`.env`, `.env.example`, `.htaccess`, `conexion.php`)?
- [ ] ¿El `.htaccess` tiene las reglas de bloqueo de carpetas ocultas y enrutamiento limpio?
- [ ] ¿El `.gitignore` está configurado para proteger el `.env` real?

## 🔒 SYSTEM IMMUTABILITY CHECK
- [ ] ¿Estoy intentando crear una tabla o campo nuevo sin permiso? (DETENERSE SI ES SÍ).
- [ ] ¿Estoy intentando "optimizar" algo que altera el Codex? (DETENERSE SI ES SÍ).

## 🛬 POST-CODE VALIDATION (AUTO-AUDITORÍA)
Antes de entregar el código al usuario:
- [ ] **Limpieza:** ¿Eliminé variables e imports no usados? (Dead Code).
- [ ] **Naming:** ¿Back es snake_case y Front es camelCase?
- [ ] **Seguridad:** ¿Sanitice inputs y protegí contra tipos erróneos (NaN/Null)?
- [ ] **Consistencia:** ¿Usé sinónimos prohibidos o me apegué al Codex?

## ✅ POST-IMPLEMENTACIÓN (DOCUMENTACIÓN VIVA)
Después de que el usuario confirme que un componente (Frontend o Backend) funciona sin errores, la IA debe proponer la actualización obligatoria del Codex y los registros del proyecto.
- [ ] **Codex Actualizado:** ¿Se registró la nueva tabla, variable o componente en el `02_SYSTEM_CODEX_REGISTRY.md`?
- [ ] **Contrato Verificado:** ¿El endpoint documentado en `03_CONTRATOS_API_Y_LOGICA.md` coincide 100% con el código final?
- [ ] **Cierre de Hito:** ¿Se informó al Arquitecto sobre el estado final y los archivos tocados?

---

## 🗄️ PROTOCOLO DE BASE DE DATOS — ENTORNO HÍBRIDO (REGLA INMUTABLE)

> Registrado el 2026-05-16 tras incidente de entorno. Aplica a TODAS las sesiones futuras.

### ❌ PROHIBIDO
```
php artisan migrate
php artisan db:seed
php artisan migrate:fresh
```
El CLI de PHP local NO está configurado para conectarse a la base de datos del servidor remoto (testing/producción). Ejecutar estos comandos localmente produce errores de conexión o, peor, modifica una BD local vacía sin efecto en el servidor real.

### ✅ PROCESO OBLIGATORIO para toda modificación de BD

| Paso | Quién | Qué |
|---|---|---|
| 1 | IA (Claude) | Crea el archivo de migración Laravel (para historial en git) |
| 2 | IA (Claude) | Genera el **script SQL puro** (CREATE/ALTER/INSERT) en el Informe de Operación |
| 3 | Humano | Copia el SQL y lo ejecuta en **phpMyAdmin del servidor remoto** |
| 4 | Humano | Confirma ejecución exitosa antes de probar el código |

### 📦 LO QUE ENTREGA LA IA en cada migración

```
✅ Archivo .php en database/migrations/ → para historial git y futura ejecución artisan
✅ SQL puro en el Informe de Operación  → para ejecución inmediata en phpMyAdmin
✅ SQL de rollback (DROP/ALTER reverso) → en comentario, por si hay que revertir
❌ NUNCA instrucciones "ejecuta php artisan migrate" como paso de deploy
❌ NUNCA asumir que el servidor local tiene acceso a la BD remota
```

### 🔑 Credenciales de la BD Remota (Servidor Testing/Producción)

| Variable | Valor |
|---|---|
| `DB_CONNECTION` | `mysql` |
| `DB_HOST` | `newbrokers.tourfindy.com` |
| `DB_PORT` | `3306` |
| `DB_DATABASE` | `tourfindycom_newbrokers_db` |
| `DB_USERNAME` | `tourfindycom_newbrokers` |
| `DB_PASSWORD` | ver `knowledge/info.txt` |

> Para que el XAMPP local se conecte al MySQL remoto, el IP del equipo local debe
> estar en la lista de Remote MySQL en el cPanel de tourfindy.com.
> Si la conexión falla con `Access denied`, verificar ese paso primero.

### 🔖 Migraciones Históricas — Estado de Ejecución en Servidor Remoto

| Archivo | Estado | SQL incluido en |
|---|---|---|
| `2026_05_16_000001_add_ping_columns_to_ai_settings_table.php` | ✅ SQL entregado | Informe sesión 2026-05-16 |
| `2026_05_16_000002_create_ai_prompts_table.php` | ✅ SQL entregado | Informe sesión 2026-05-16 |
| Script Maestro Synaptic Core™ (ai_prompts modular + ai_prompt_versions + audit_logs) | ✅ SQL entregado | Informe sesión 2026-05-16 |

---

## 🛠️ PRE-FLIGHT CHECKS TÉCNICOS (BROKERS CONNECTOR — HALLAZGOS DE AUDITORÍA)

> Verificaciones específicas del entorno antes de desarrollar o desplegar. Fecha: 2026-04-10.

---

### ☁️ STORAGE & UPLOADS

- [ ] **Permisos de carpeta `public/`:** Verificar que `public/companies/` tiene `chmod 0755` y es escribible por el servidor web (Apache/Nginx). Sin esto, `$file->move()` lanza una excepción silenciosa.
- [ ] **NO usar `Storage::disk()`:** Este proyecto NO usa el sistema de Storage de Laravel. Los archivos van directamente a `public_path()`. Usar `Storage::link()` o `Storage::disk('public')` romperá las rutas de imágenes.
- [ ] **Intervention Image instalado:** El procesamiento de thumbnails requiere `intervention/image`. Verificar que está en `vendor/` y que GD o Imagick está habilitado en `php.ini`.
- [ ] **Extensión `.jfif`:** Las imágenes `.jfif` NO generan thumbnail (bypass hardcoded en `FilePropertyController`). Se guarda el `src` original como `thumbnail`. Comportamiento esperado — no es un bug.

---

### 🔑 AUTENTICACIÓN Y TOKENS

- [ ] **Laravel Passport migrado:** Verificar que las tablas `oauth_*` existen en la BD. Si no, ejecutar `php artisan passport:install` antes de cualquier llamada a `POST /api/auth/login`.
- [ ] **Personal Access Client:** El login API usa `$user->createToken('Personal Access Token')`. Requiere que exista un "Personal Access Client" en `oauth_clients`. Se crea con `passport:install`.
- [ ] **Guard correcto por ruta:** API routes → `auth:api`. Web routes → `auth`. Si se confunden, el middleware devuelve 401 o redirige inesperadamente.

---

### 💳 PAGOS (OPENPAY)

- [x] **Variables de entorno de OpenPay:** `OPENPAY_ID`, `OPENPAY_KEY_SECRET`, `OPENPAY_PRODUCTION`, `OPENPAY_SANDBOX_ID`, `OPENPAY_SANDBOX_KEY` viven en `.env`. ✅ **RESUELTO** (2026-07-07): `openPay_payment()`, `openPay_paynet()` y `openPay_spei()` usan `App\Services\OpenPayService`, que centraliza la lectura de credenciales desde `.env`. Ya no hay keys hardcodeadas.
- [ ] **Modo Sandbox vs Producción:** `Openpay::setProductionMode(env('OPENPAY_PRODUCTION'))`. En desarrollo, `OPENPAY_PRODUCTION=false`. Cambiar a `true` solo en producción real.
- [ ] **Webhook `/api/invoice/paynet/pay`:** Es un endpoint público sin autenticación. Debe estar en la whitelist de IPs de OpenPay en el servidor de producción.

---

### 📧 EMAIL (SENDGRID)

- [x] **API Key de SendGrid en `.env`:** ✅ **RESUELTO** (2026-07-07): las 3 llamadas en `PropertyController.php` y la de `CompanyController.php` usan `env('SENDGRID_API_KEY')`. La key vive únicamente en `.env`. En código NUEVO, seguir usando `env('SENDGRID_API_KEY')`.
- [ ] **From address:** Los correos se envían desde `propiedades@brokersconnector.com` y `correos@brokersconnector.com`. Verificar que estos dominios están verificados en SendGrid antes de enviar en producción.

---

### 🏢 MULTITENANCY

- [ ] **Siempre filtrar por `company_id`:** Toda query a `properties`, `contacts`, `users`, `invoices` dentro del panel web DEBE incluir `where('company_id', auth()->user()->company_id)` o usar el scope `allMyProperties()` del modelo `User`.
- [ ] **`company` puede ser NULL:** Un usuario recién registrado puede no tener `company_id` asignado. El middleware `Company` redirige a `/home` en ese caso. Las queries que asumen `auth()->user()->company` no nula pueden lanzar errores — verificar siempre.

---

### 🗂️ RUTAS Y ESTRUCTURA

- [x] **Ruta `/generar-codex`:** ✅ **RESUELTO** — eliminada en commit `776c1ff` (2026-04-27, Fase 7.4). Verificado 2026-07-07: no existe en `web.php` ni `api.php`.
- [ ] **`AuthController::register()` tiene `dd($request)`:** El registro de usuarios vía API está inutilizado intencionalmente (commented out en `routes/api.php`). Si se reactiva, eliminar el `dd()` primero.
- [ ] **Feeds de portales son públicos:** `/propiedades-com/feed`, `/lgi/feed`, `/doomos/feed`, `/lamudi/feed`, `/casafy/feed` no tienen middleware. Son intencionalmente públicos para los scrapers de portales.

---

### 📦 DEPENDENCIAS CLAVE A VERIFICAR

| Paquete | Uso | Verificación |
|---|---|---|
| `barryvdh/laravel-dompdf` | Generación de PDF de propiedades | `PDF::loadView()` en `PropertyController` |
| `intervention/image` | Thumbnails de imágenes | `Image::make()` en `FilePropertyController` |
| `spatie/laravel-permission` | Sistema de roles RBAC | Tablas `roles`, `model_has_roles` en BD |
| `laravel/passport` | Autenticación API Bearer Token | Tablas `oauth_*` en BD |
| `openpay/openpay` | Pasarela de pagos | Variables `OPENPAY_*` en `.env` |
| `sendgrid/sendgrid` | Envío de emails transaccionales | API Key en `.env` (actualmente hardcodeada) |