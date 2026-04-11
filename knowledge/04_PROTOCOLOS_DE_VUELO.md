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

- [ ] **Variables de entorno de OpenPay:** `OPENPAY_ID`, `OPENPAY_KEY_SECRET`, `OPENPAY_PRODUCTION` deben estar en `.env`. Solo `openPay_payment()` las usa correctamente; los otros métodos de pago (`openPay_paynet`, `openPay_spei`) tienen las keys hardcodeadas (deuda técnica documentada).
- [ ] **Modo Sandbox vs Producción:** `Openpay::setProductionMode(env('OPENPAY_PRODUCTION'))`. En desarrollo, `OPENPAY_PRODUCTION=false`. Cambiar a `true` solo en producción real.
- [ ] **Webhook `/api/invoice/paynet/pay`:** Es un endpoint público sin autenticación. Debe estar en la whitelist de IPs de OpenPay en el servidor de producción.

---

### 📧 EMAIL (SENDGRID)

- [ ] **API Key de SendGrid en `.env`:** La key `SG.LFNHt9yHSqOhintBn8ToTw...` está **hardcodeada** en `PropertyController` y `CompanyController`. En cualquier código NUEVO, usar `config('services.sendgrid.key')` o `env('SENDGRID_KEY')` y moverla al `.env`.
- [ ] **From address:** Los correos se envían desde `propiedades@brokersconnector.com` y `correos@brokersconnector.com`. Verificar que estos dominios están verificados en SendGrid antes de enviar en producción.

---

### 🏢 MULTITENANCY

- [ ] **Siempre filtrar por `company_id`:** Toda query a `properties`, `contacts`, `users`, `invoices` dentro del panel web DEBE incluir `where('company_id', auth()->user()->company_id)` o usar el scope `allMyProperties()` del modelo `User`.
- [ ] **`company` puede ser NULL:** Un usuario recién registrado puede no tener `company_id` asignado. El middleware `Company` redirige a `/home` en ese caso. Las queries que asumen `auth()->user()->company` no nula pueden lanzar errores — verificar siempre.

---

### 🗂️ RUTAS Y ESTRUCTURA

- [ ] **Ruta `/generar-codex` — ELIMINAR ANTES DE PRODUCCIÓN:** Presente en `routes/web.php`. Expone el schema completo de la BD sin autenticación. Es una violación del Mandamiento #8.
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