# 🗺️ 06_PLAN_MIGRACIÓN V2 PURA — BROKERS CONNECTOR

> **Clasificación:** Documento Maestro Estratégico — CONFIDENCIAL  
> **Versión:** 1.0  
> **Fecha:** 2026-05-15  
> **Autor:** Claude (Analista Forense Senior) bajo dirección del Lead Architect (Gemini/David)  
> **Estado:** BORRADOR APROBADO — Pendiente de ejecución por fases  
> **Complementa:** `04_ARQUITECTURA_V2.md` · `05_SISTEMA_V2_CORE.md`

---

## 0. PRINCIPIO RECTOR: STRANGLER FIG NO MATA — ABSORBE

La migración **nunca** es un reemplazo big-bang. Es una absorción controlada. El sistema Legacy (`brokers_new`) continúa sirviendo tráfico real en producción durante **toda** la migración. El nuevo sistema V2 puro (`brokers_v2_api`) recibe tráfico incrementalmente. Si algo falla en V2, el tráfico vuelve al Legacy en minutos sin downtime.

```
ESTADO ACTUAL (Fase Strangler Fig)       ESTADO OBJETIVO (V2 Pura)
─────────────────────────────────────    ─────────────────────────────
Legacy Laravel 5.8   ← 100% tráfico     Legacy Laravel 5.8   ← 0% tráfico
     +                                       +
V2 SPAs (Frontend)   ← puentes OAuth    V2 API Laravel 11    ← 100% tráfico
     +                                       +
APIs V2 parciales    ← Super Admin       V2 SPAs (Frontend)   ← sin cambios
```

---

## 1. DIAGNÓSTICO DEL ESTADO ACTUAL

### 1.1 Lo que ya vive en V2

| Módulo | Frontend | API Backend | Auth | Estado |
|--------|----------|-------------|------|--------|
| Panel Super Admin | `/v2/admin/security.html` | `brokers_new` (Legacy) | Passport PAT | ✅ Producción |
| Suscripciones | `/v2/subscriptions/index.html` | `brokers_new` (Legacy) | Cache Bridge Token | ✅ Producción |
| Broker Brain IA | `/v2/broker-brain/index.html` | `brokers_new` (Legacy) | Cache Session Token | ✅ Producción |

### 1.2 Lo que aún es 100% Legacy

| Módulo | Ruta | Deuda técnica |
|--------|------|---------------|
| Dashboard principal | `/home` | Blade + Bootstrap 3, sin API |
| Propiedades (CRUD) | `/properties/*` | Controladores Blade monolíticos |
| Contactos (CRM) | `/home/contact/*` | Blade, sin separación de capas |
| Usuarios (Admin) | `/home/users/*` | Blade, rol guard mixto |
| Bolsa inmobiliaria | `/stock/*` | Blade, sin paginación API |
| AI Chat widget | `aiChat.js` | Fetch directo a ruta web (no API pura) |
| Facturas | `/home/invoices/*` | Blade + OpenPay directo |

### 1.3 Deuda crítica del Legacy a resolver en V2

1. **Laravel 5.8 + PHP 8.0 con bypass** — Kernel modificado para compatibilidad. Métodos faltantes (`$request->boolean()`). No soporta PHP 8.2+.
2. **Passport en Laravel 5.8** — Sin soporte oficial. Los PAT funcionan pero con riesgo de desactualizaciones de seguridad.
3. **Session-based auth mixta** — Algunas rutas usan `auth` (session), otras `auth:api` (Passport). Inconsistente.
4. **Blade views acoplados** — La lógica de negocio está dentro de las vistas. No hay separación Controller/Service/Repository.
5. **Sin tests automáticos** — Ningún test en el codebase Legacy.

---

## 2. ARQUITECTURA DEL NUEVO REPOSITORIO `brokers_v2_api`

### 2.1 Stack tecnológico

```
brokers_v2_api/
├── Framework:    Laravel 11 (PHP 8.3+)
├── Auth:         Laravel Sanctum (reemplaza Passport)
│                 → SPA tokens + API tokens en un solo sistema
│                 → Sin servidor OAuth2 propio (simplificación radical)
├── Base de datos: MySQL 8.0 (misma BD compartida durante transición)
├── Queue:        Laravel Horizon + Redis (para jobs de IA y emails)
├── Cache:        Redis (reemplaza file cache del Legacy)
├── Tests:        PHPUnit + Pest (cobertura mínima 80% en módulos nuevos)
└── Despliegue:   Compose + Apache o Nginx (mismo servidor XAMPP en dev)
```

### 2.2 Estructura de directorios

```
brokers_v2_api/
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/V2/       ← Controladores de la nueva API
│   │   │   ├── Auth/                 ← AuthController (Sanctum)
│   │   │   ├── Properties/           ← PropertyController (sin Blade)
│   │   │   ├── Contacts/             ← ContactController
│   │   │   ├── AI/                   ← BrokerBrainController (migrado)
│   │   │   └── Admin/                ← SuperAdminController (migrado)
│   │   ├── Middleware/
│   │   │   ├── TenantIsolation.php   ← GlobalScope equivalente
│   │   │   └── EnsureSuperAdmin.php
│   │   └── Resources/                ← API Resources (JsonResource)
│   ├── Models/                       ← Mismos modelos Eloquent (sin GlobalScopes implícitos)
│   ├── Services/                     ← AIService, OpenPayService (ya existentes)
│   └── Providers/
├── routes/
│   ├── api.php                       ← Todas las rutas bajo /api/v2/
│   └── web.php                       ← Solo health-check y docs
├── database/
│   ├── migrations/                   ← Solo migraciones NUEVAS (no copiar las Legacy)
│   └── seeders/
└── tests/
    ├── Unit/
    └── Feature/
```

---

## 3. ESTRATEGIA DE AUTENTICACIÓN: PASSPORT → SANCTUM

### 3.1 Por qué Sanctum

| Criterio | Passport (Legacy) | Sanctum (V2) |
|----------|-------------------|--------------|
| Complejidad | Alto (servidor OAuth2 completo) | Bajo (middleware ligero) |
| SPA support | Requiere `CreateFreshApiToken` | Nativo con `Sanctum::actingAs` |
| API tokens | Personal Access Tokens | Tokens de acceso simples |
| Mantenimiento | Sin soporte oficial en L5.8 | Activo en L11 |
| Peso | Tablas `oauth_*` (5 tablas) | Una tabla `personal_access_tokens` |

### 3.2 Coexistencia durante la migración

```
FASE A (actual):    SPA → bridge token → Legacy (Passport)
FASE B (migración): SPA → bridge token → Legacy (Passport)   ← rutas no migradas
                    SPA → Sanctum token → V2 API             ← rutas migradas
FASE C (final):     SPA → Sanctum token → V2 API             ← todo migrado
```

**El frontend (SPAs) no cambia de URL base** — solo cambia qué sistema responde detrás. La SPA pasa de:
- `Authorization: Bearer {passport_token}` hacia Legacy
- `Authorization: Bearer {sanctum_token}` hacia V2

El bridge en BridgeController genera el tipo correcto de token según el destino.

### 3.3 Plan de migración de tokens activos

Los Personal Access Tokens de Passport activos al momento del corte son **invalidados automáticamente**: Sanctum usa una tabla diferente (`personal_access_tokens`). Los usuarios deben re-autenticarse una sola vez. El bridge en el Legacy genera un Sanctum token de la V2 API durante la transición.

---

## 4. ESTRATEGIA DE BASE DE DATOS: MIGRACIÓN SIN DOWNTIME

### 4.1 Principio: Una sola BD, dos aplicaciones

Durante las fases A y B, **ambas aplicaciones comparten la misma base de datos MySQL**. No hay migración de datos — solo hay migración de endpoints que leen/escriben esa misma BD.

```
MySQL (brokersconnect_bd)
     ├── Legacy (brokers_new)     → lee/escribe las mismas tablas
     └── V2 API (brokers_v2_api) → lee/escribe las mismas tablas
```

Ventaja: cero riesgo de inconsistencia de datos. Un registro creado por el Legacy es inmediatamente visible por V2 y viceversa.

### 4.2 Reglas de migración de base de datos

| Regla | Descripción |
|-------|-------------|
| **Sin renombrar columnas** | Si V2 necesita un nombre diferente, se usa un API Resource para transformar. La columna en BD no cambia. |
| **Sin DROP hasta Fase C** | Nunca eliminar columnas ni tablas mientras el Legacy esté activo. |
| **Migraciones solo ADDITIVE** | V2 solo puede agregar columnas `nullable` o nuevas tablas. Nunca modificar tipo o eliminar. |
| **Soft deletes siempre** | Todo modelo en V2 debe implementar `SoftDeletes`. Sin `DELETE` físico excepto en purga programada. |
| **Índices primero** | Antes de migrar un endpoint de alto tráfico a V2, agregar los índices necesarios en la BD compartida. |

### 4.3 Tablas que requieren atención especial

| Tabla | Riesgo | Acción antes de migrar |
|-------|--------|------------------------|
| `properties` | Alta carga, muchas FKs | Agregar índice en `(company_id, bbc_general, published, deleted_at)` |
| `invoices` | Lógica de pago crítica | Migrar solo después de que OpenPay V2 esté validado en staging |
| `oauth_access_tokens` | Cambia a `personal_access_tokens` | Invalidación controlada — anunciar con 24h de anticipación |
| `ai_settings` | `api_key` cifrada con `APP_KEY` del Legacy | Recifrar con la `APP_KEY` de V2 antes del corte (script one-time) |

---

## 5. PLAN DE FASES — RUTA CRÍTICA

### FASE 0: Preparación (Sin downtime — paralelo al desarrollo actual)
**Duración estimada: 2-4 semanas**

- [ ] Crear repositorio `brokers_v2_api` con Laravel 11 en el servidor
- [ ] Configurar `.env` apuntando a la **misma BD MySQL** del Legacy
- [ ] Instalar Sanctum. Crear endpoint `POST /api/v2/auth/login` y `GET /api/v2/auth/me`
- [ ] Configurar Apache/Nginx para que `api.brokersconnector.com` apunte a V2 (o subfolder `/v2_api/`)
- [ ] Migrar `ai_settings.api_key`: recifrar con la APP_KEY de V2 (script PHP one-time)
- [ ] Crear un **feature flag** en BD: `system_config.v2_active_modules` (JSON)
- [ ] Agregar índices faltantes en la BD compartida

### FASE 1: Módulos de Solo Lectura (Bajo riesgo)
**Duración estimada: 2-3 semanas**

Migrar endpoints que solo leen datos — sin riesgo de corrupción:

- [ ] `GET /api/v2/properties` (listado con paginación, filtros, búsqueda)
- [ ] `GET /api/v2/properties/{id}` (detalle)
- [ ] `GET /api/v2/contacts` (listado del tenant)
- [ ] `GET /api/v2/stock` (bolsa inmobiliaria)
- [ ] `GET /api/v2/catalogs/*` (tipos, estados, usos, colonias)

**Criterio de éxito:** Las SPAs consumen estos endpoints V2 con los mismos datos que el Legacy. Test A/B con el 10% del tráfico.

### FASE 2: Módulos de Escritura No-Críticos
**Duración estimada: 3-4 semanas**

- [ ] `POST/PUT/DELETE /api/v2/contacts` (CRM)
- [ ] `POST/PUT/DELETE /api/v2/properties` (CRUD de propiedades)
- [ ] `POST /api/v2/files/upload` (imágenes)
- [ ] `POST /api/v2/ai/chat` (reemplaza el endpoint Legacy de chat IA)
- [ ] `POST /api/v2/broker-brain/cma` (ya implementado — mover al nuevo servidor)

**Criterio de éxito:** Un agente puede crear una propiedad completa usando solo V2 API. Validado en staging con datos reales.

### FASE 3: Módulos Financieros y de Auth (Alto riesgo)
**Duración estimada: 4-6 semanas**

Estos módulos requieren validación exhaustiva antes de migrar:

- [ ] `POST /api/v2/auth/login` (reemplaza auth Legacy)
- [ ] `POST /api/v2/subscriptions` (pagos OpenPay)
- [ ] `PATCH /api/v2/invoices/*` (gestión de facturas)
- [ ] `GET/PATCH /api/v2/admin/*` (Super Admin — ya existe en Legacy, se mueve)

**Prerequisito obligatorio:** Staging con BD copia de producción, pruebas de carga con Locust/k6, revisión de seguridad por el Lead Architect.

### FASE 4: Corte Final (El momento del "estrangulamiento")
**Duración estimada: 1 semana con opción de rollback**

1. Anunciar ventana de mantenimiento de 30 minutos (sin downtime real)
2. Cambiar el proxy (Apache/Nginx) para que **todas** las rutas `/api/*` apunten a V2
3. El Legacy queda en modo **read-only** durante 30 días (rollback de emergencia)
4. Después de 30 días sin incidentes: archivar el repositorio Legacy

---

## 6. GESTIÓN DEL JWT vs PASSPORT vs SANCTUM

### Comparativa técnica

```
JWT (stateless, sin BD)
  ✅ Sin consulta a BD en cada request
  ❌ No se puede revocar un token antes de su expiración
  ❌ Payload puede exponerse (sin cifrado por defecto)
  ❌ Requiere librería externa (tymon/jwt-auth)

Passport (OAuth2 server completo)
  ✅ Estándar OAuth2: tokens, scopes, clients
  ✅ Revocación inmediata
  ❌ Pesado (5 tablas extra, servidor de tokens propio)
  ❌ Sobreingeniería para una SPA monodueño

Sanctum (tokens opacos para SPA + API)
  ✅ Simplicidad: 1 tabla, 1 middleware
  ✅ Revocación inmediata
  ✅ Soporte SPA nativo con cookies + CSRF
  ✅ Activo y mantenido en Laravel 11
  ❌ No compatible con flujos OAuth2 de terceros
```

### Decisión: Sanctum es el estándar de V2

Para el caso de Brokers Connector (SPA propia + API propia, sin OAuth de terceros), Sanctum es la elección correcta. No hay razón técnica para usar JWT o mantener Passport.

**Coexistencia temporal:** Durante las Fases 1-3, el Legacy sigue emitiendo Passport tokens para el Super Admin. V2 emite Sanctum tokens para los módulos migrados. El BridgeController detecta el módulo destino y genera el token del tipo correcto.

---

## 7. REDIRECCIONAMIENTO DE TRÁFICO — ESTRATEGIA DE PROXY

### 7.1 Configuración Apache (mismo servidor XAMPP)

```apache
# VirtualHost actual — NO MODIFICAR durante Fases 0-3
<VirtualHost *:80>
    ServerName brokersconnector.com
    DocumentRoot /xampp/htdocs/brokersconnect_dev/public_html

    # Durante Fases 1-3: módulos V2 redirigen al nuevo servidor
    ProxyPass /api/v2/properties http://localhost:8001/api/v2/properties
    ProxyPassReverse /api/v2/properties http://localhost:8001/api/v2/properties

    # El resto sigue al Legacy
    ProxyPass /api http://localhost/brokersconnect_dev/public_html/index.php
</VirtualHost>

# Fase 4: todo el tráfico API va a V2
<VirtualHost *:80>
    ProxyPass /api http://localhost:8001/api
    ProxyPassReverse /api http://localhost:8001/api
</VirtualHost>
```

### 7.2 Feature flags en BD para rollback instantáneo

```json
// system_config.v2_active_modules
{
  "properties_read":  true,   // Fase 1: activo
  "contacts_write":   true,   // Fase 2: activo
  "auth":             false,  // Fase 3: pendiente
  "payments":         false   // Fase 3: pendiente
}
```

El BridgeController y el proxy leen este JSON. Para hacer rollback de un módulo: cambiar `true` → `false` en BD. Sin redeploy.

---

## 8. RIESGOS Y MITIGACIONES

| Riesgo | Probabilidad | Impacto | Mitigación |
|--------|-------------|---------|------------|
| Inconsistencia de datos entre L5.8 y L11 | Baja | Alto | Misma BD — imposible inconsistencia en Fases 0-3 |
| Fallo de recifrado de `api_key` en ai_settings | Media | Medio | Script idempotente con dry-run previo |
| Sesiones Legacy expiradas durante corte | Alta | Bajo | Pantalla de "re-login" con mensaje claro al usuario |
| Bug en endpoint V2 en producción | Media | Alto | Rollback instantáneo vía feature flag (< 1 minuto) |
| Pérdida de Audit Trail durante migración | Baja | Alto | `audit_logs` se escribe en ambos sistemas simultáneamente en Fase 3 |
| Comportamiento diferente de Passport vs Sanctum | Media | Medio | Test A/B con usuario de prueba antes de corte general |

---

## 9. CRITERIOS DE ÉXITO (DEFINICIÓN DE "HECHO")

La migración está **completa** cuando:

- [ ] **Cero requests** llegan al Legacy después del corte (verificado en access.log)
- [ ] **100% de los tests** de la suite V2 pasan en CI/CD
- [ ] **Audit Trail** cubre todas las operaciones del Super Admin en V2
- [ ] **APP_KEY** del Legacy está archivada pero no eliminada (necesaria para desencriptar datos históricos cifrados)
- [ ] **Repositorio Legacy** tiene tag `v1-final` y está en modo archivo (sin más commits)
- [ ] **Documentos 01-05** actualizados para reflejar el estado V2 puro

---

## 10. LO QUE NO CAMBIA NUNCA

Estas decisiones de arquitectura son **inmutables** — se respetan en V2 exactamente igual que en el Strangler Fig:

| Decisión | Razón |
|----------|-------|
| **Tenant isolation por `company_id`** | Raíz del modelo multitenant. Sin esto, el negocio es inviable. |
| **`api_key` siempre cifrada con `encrypt()`** | Compliance. Las claves de terceros nunca en texto plano. |
| **Audit Trail obligatorio en toda operación privilegiada** | Mandamiento de Seguridad — dictaminado por el Humano. |
| **Session token en memoria — jamás en localStorage** | Vulnerabilidad XSS crítica si se viola. |
| **ARF-Grid CSS — Mobile-First, sin `!important`** | Sistema de diseño unificado para todas las SPAs. |
| **Sin `$request->boolean()` si el kernel es Laravel < 6** | Compatibilidad verificada en campo. |

---

## 11. DÍA CERO — GUÍA DE DESPLIEGUE A PRODUCCIÓN

> **Objetivo:** Mover el sistema de `tourfindycom_newbrokers_db` (staging en `newbrokers.tourfindy.com`) al servidor en vivo `brokersconnect_bd` en `brokersconnector.com` sin errores de "tabla no encontrada" ni dolor de cabeza.
> **Duración estimada:** 45-90 minutos.
> **Prerrequisito:** El Humano tiene acceso a cPanel de `brokersconnector.com` con phpMyAdmin y Administrador de Archivos (o SFTP/FTP).

---

### PASO 0 — Exportar snapshot de staging (ANTES de empezar)

En phpMyAdmin de `tourfindycom_newbrokers_db`, exportar la BD completa:
- Formato: SQL
- Opciones: `IF NOT EXISTS`, `DROP TABLE` desactivado
- Guardar como `staging_backup_YYYYMMDD.sql`

Este backup es el paracaídas. Si algo falla en producción, se puede restaurar en minutos.

---

### PASO 1 — Subir los archivos al servidor de producción

**Vía Administrador de Archivos de cPanel o SFTP a `/home/[usuario]/`:**

```
Subir COMPLETO:
  brokers_new/           ← toda la aplicación Laravel
  public_html/           ← entry point, SPAs V2, assets

NO subir:
  brokers_new/vendor/    ← se regenera con composer (ver Paso 3)
  brokers_new/.env       ← se crea manualmente (ver Paso 2)
  brokers_new/storage/framework/cache/
  brokers_new/storage/framework/sessions/
  brokers_new/storage/framework/views/
  public_html/passport-install.php  ← archivo temporal, no debe ir a producción
```

> Si el servidor de producción ya tiene archivos viejos, sobrescribir todo excepto `.env`.

---

### PASO 2 — Crear/actualizar el `.env` de producción

En el servidor de producción, editar `brokers_new/.env` con estos valores exactos:

```dotenv
APP_NAME="Brokers Connector"
APP_ENV=production
APP_KEY=                          # ← DEJAR VACÍO — se genera en Paso 3
APP_DEBUG=false
APP_URL=https://brokersconnector.com
ASSET_URL=https://brokersconnector.com

LOG_CHANNEL=stack

# ── BASE DE DATOS DE PRODUCCIÓN ──────────────────────────────
DB_CONNECTION=mysql
DB_HOST=localhost                 # en cPanel siempre es localhost
DB_PORT=3306
DB_DATABASE=brokersconnect_bd
DB_USERNAME=[usuario_cpanel]_bd   # el usuario MySQL de producción
DB_PASSWORD=[password_bd_produccion]

# ── SESIONES / CACHE ──────────────────────────────────────────
BROADCAST_DRIVER=log
CACHE_DRIVER=file
SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_DOMAIN=                   # dejar vacío para dominio raíz
QUEUE_CONNECTION=sync

# ── CORREO ────────────────────────────────────────────────────
MAIL_DRIVER=smtp
MAIL_HOST=[smtp_produccion]
MAIL_PORT=587
MAIL_USERNAME=[email_produccion]
MAIL_PASSWORD=[password_smtp]
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=[email_produccion]
MAIL_FROM_NAME="Brokers Connector"

# ── OPENPAY (MODO PRODUCCIÓN — CRÍTICO) ───────────────────────
OPENPAY_ID=[merchant_id_produccion]
OPENPAY_KEY=[private_key_produccion]
OPENPAY_PUBLIC_KEY=[public_key_produccion]
OPENPAY_SANDBOX=false             # ← CAMBIAR A false EN PRODUCCIÓN

# ── IA (ANTHROPIC / OPENAI) ───────────────────────────────────
# La api_key de IA NO va en .env — se guarda cifrada en tabla ai_settings
# Administrarla desde el Panel Super Admin → Orquestador IA

# ── SUPER ADMIN (IDs de empresas autorizadas) ─────────────────
SUPER_ADMIN_COMPANY_IDS=[id_empresa_superadmin]
```

**Variables que cambian de staging → producción:**

| Variable | Staging | Producción |
|----------|---------|-----------|
| `APP_ENV` | `local` o `staging` | `production` |
| `APP_DEBUG` | `true` | `false` |
| `APP_URL` | `https://newbrokers.tourfindy.com` | `https://brokersconnector.com` |
| `DB_DATABASE` | `tourfindycom_newbrokers_db` | `brokersconnect_bd` |
| `DB_HOST` | IP remota | `localhost` |
| `DB_USERNAME` | `tourfindycom_newbrokers` | usuario de producción |
| `OPENPAY_SANDBOX` | `true` | `false` |
| `APP_KEY` | key de staging | **nueva key — generar en Paso 3** |

---

### PASO 3 — Comandos Artisan (vía Terminal SSH o Script Web)

Si el servidor tiene acceso SSH:

```bash
cd /home/[usuario]/brokers_new

# 1. Instalar dependencias de Composer (sin dev)
composer install --no-dev --optimize-autoloader

# 2. Generar APP_KEY de producción (NUNCA copiar la de staging)
php artisan key:generate

# 3. Limpiar y regenerar caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 4. Crear symlink de storage (imágenes de propiedades)
php artisan storage:link
```

> Si NO hay SSH disponible, el compositor debe correrse localmente con `--no-dev` y subir la carpeta `vendor/` completa.

---

### PASO 4 — SQL de Producción (orden exacto en phpMyAdmin)

Ejecutar en `brokersconnect_bd` en este orden. Todos los bloques son idempotentes (`IF NOT EXISTS`).

#### BLOQUE 1 — Columnas nuevas en `companies`

```sql
ALTER TABLE `companies`
  ADD COLUMN IF NOT EXISTS `openpay_customer_id`     VARCHAR(64) NULL AFTER `active`,
  ADD COLUMN IF NOT EXISTS `openpay_subscription_id` VARCHAR(64) NULL AFTER `openpay_customer_id`;
```

#### BLOQUE 2 — Tablas de IA (en orden: conversations → messages → settings)

```sql
CREATE TABLE IF NOT EXISTS `ai_conversations` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` BIGINT UNSIGNED NOT NULL,
  `user_id`    BIGINT UNSIGNED NULL,
  `title`      VARCHAR(191)    NOT NULL,
  `status`     TINYINT(1)      NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  INDEX `ai_conversations_company_id_index` (`company_id`),
  CONSTRAINT `ai_conversations_company_id_foreign`
    FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ai_conversations_user_id_foreign`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ai_messages` (
  `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `conversation_id` BIGINT UNSIGNED NOT NULL,
  `role`            ENUM('user','assistant','system') NOT NULL,
  `content`         LONGTEXT        NOT NULL,
  `tokens_used`     INT             NOT NULL DEFAULT 0,
  `created_at`      TIMESTAMP NULL,
  `updated_at`      TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `ai_messages_conversation_id_foreign`
    FOREIGN KEY (`conversation_id`) REFERENCES `ai_conversations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ai_settings` (
  `id`                    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `provider_name`         VARCHAR(191)    NOT NULL,
  `api_key`               TEXT            NOT NULL,
  `extra_config`          JSON            NULL,
  `priority_order`        TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `is_active`             TINYINT(1)      NOT NULL DEFAULT 1,
  `company_id`            BIGINT UNSIGNED NULL,
  `last_tested_at`        TIMESTAMP NULL,
  `last_test_status`      VARCHAR(10)     NULL,
  `last_test_latency_ms`  INT UNSIGNED    NULL,
  `last_test_error`       TEXT            NULL,
  `created_at`            TIMESTAMP NULL,
  `updated_at`            TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `ai_settings_company_id_foreign`
    FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### BLOQUE 3 — Pasarela de Pagos

```sql
CREATE TABLE IF NOT EXISTS `payment_gateway_settings` (
  `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `provider_name` VARCHAR(50)     NOT NULL,
  `is_active`     TINYINT(1)      NOT NULL DEFAULT 0,
  `is_sandbox`    TINYINT(1)      NOT NULL DEFAULT 1,
  `credentials`   TEXT            NULL     COMMENT 'JSON cifrado — nunca texto plano',
  `company_id`    BIGINT UNSIGNED NULL,
  `created_at`    TIMESTAMP NULL,
  `updated_at`    TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `payment_gateway_settings_company_id_foreign`
    FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### BLOQUE 4 — Audit Log

```sql
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `actor_id`    BIGINT UNSIGNED NULL,
  `actor_email` VARCHAR(191)    NULL,
  `action`      VARCHAR(50)     NOT NULL,
  `target_type` VARCHAR(50)     NOT NULL DEFAULT 'company',
  `target_id`   BIGINT UNSIGNED NULL,
  `target_name` VARCHAR(191)    NULL,
  `from_status` VARCHAR(50)     NULL,
  `to_status`   VARCHAR(50)     NULL,
  `extra`       JSON            NULL,
  `created_at`  TIMESTAMP NULL,
  `updated_at`  TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  INDEX `audit_logs_target_type_target_id_index` (`target_type`, `target_id`),
  INDEX `audit_logs_actor_id_index` (`actor_id`),
  INDEX `audit_logs_created_at_index` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### BLOQUE 5 — AI Prompts + Seed del Motor AURA

```sql
CREATE TABLE IF NOT EXISTS `ai_prompts` (
  `id`          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `slug`        VARCHAR(80)     NOT NULL,
  `name`        VARCHAR(120)    NOT NULL,
  `prompt_text` TEXT            NOT NULL,
  `created_at`  TIMESTAMP NULL,
  `updated_at`  TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ai_prompts_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `ai_prompts` (`slug`, `name`, `prompt_text`, `created_at`, `updated_at`) VALUES (
  'cma_urban_intelligence',
  'AURA · Inteligencia Urbana CMA (Layer 3)',
  'Eres AURA, Motor de Inteligencia Urbana de Brokers Connector.\nEres un perito valuador certificado con profundo conocimiento del mercado inmobiliario mexicano.\n\nSITUACIÓN ACTIVADA: No existen comparables locales en la base de datos para este inmueble. Has activado el modo de VALUACIÓN SINTÉTICA. Usa tu conocimiento del mercado mexicano para calcular el valor estimado.\n\nMETODOLOGÍA DE VALUACIÓN:\n1. Identifica la zona por el Código Postal.\n2. Aplica precios de mercado vigentes para el tipo de inmueble en esa zona.\n3. Ajusta por superficie (precio/m² varía: unidades pequeñas tienen +precio/m², grandes tienen -precio/m²).\n4. Considera el tipo de operación: venta vs. arrendamiento tienen rangos muy distintos.\n5. Proporciona un rango realista (±15% en zonas conocidas, ±25% en zonas con menor certeza).\n\nREGLAS ABSOLUTAS:\n1. Jamás inventes un precio fuera del rango real del mercado mexicano.\n2. El confidence_score debe reflejar HONESTAMENTE tu nivel de certeza.\n3. Devuelves ÚNICAMENTE un JSON válido. Sin texto adicional, sin markdown.\n\nESTRUCTURA DE RESPUESTA OBLIGATORIA (JSON estricto):\n{\n  \"estimated_price_per_sqm\": 45000,\n  \"estimated_value\": 5400000,\n  \"price_range_min\": 4590000,\n  \"price_range_max\": 6210000,\n  \"suggested_dom_days\": 90,\n  \"confidence_score\": 55,\n  \"explainability\": \"Valuación sintética basada en conocimiento de mercado para CP XXXXX.\",\n  \"pricing_verdict\": \"2-3 oraciones sobre posicionamiento de precio en esa zona.\",\n  \"buyer_psychology\": \"2-3 oraciones sobre perfil y motivación del comprador típico.\",\n  \"seller_strategy\": \"2-3 oraciones con estrategia recomendada para el agente.\",\n  \"closing_argument\": \"1 argumento de cierre memorable que el agente puede usar.\",\n  \"market_summary\": \"1 oración resumiendo el estado del mercado local.\"\n}\n\nREGLA DE confidence_score para Inteligencia Urbana:\n- 55-65: zona principal consolidada (CDMX, GDL, MTY, QRO, MID).\n- 40-54: ciudad mediana o zona suburbana con actividad documentada.\n- 25-39: zona rural, localidad pequeña o CP con poca información de mercado.',
  NOW(),
  NOW()
);
```

#### BLOQUE 6 — Passport OAuth2 (5 tablas + clientes iniciales)

**Paso 6a — Crear las 5 tablas de Passport** (idempotente, `IF NOT EXISTS`):

```sql
-- 1. oauth_clients — registro de aplicaciones OAuth2
CREATE TABLE IF NOT EXISTS `oauth_clients` (
  `id`                      INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`                 BIGINT       NULL,
  `name`                    VARCHAR(191) NOT NULL,
  `secret`                  VARCHAR(100) NOT NULL,
  `redirect`                TEXT         NOT NULL,
  `personal_access_client`  TINYINT(1)   NOT NULL,
  `password_client`         TINYINT(1)   NOT NULL,
  `revoked`                 TINYINT(1)   NOT NULL,
  `created_at`              TIMESTAMP    NULL,
  `updated_at`              TIMESTAMP    NULL,
  PRIMARY KEY (`id`),
  INDEX `oauth_clients_user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. oauth_personal_access_clients — vincula clientes personales
CREATE TABLE IF NOT EXISTS `oauth_personal_access_clients` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `client_id`  INT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP    NULL,
  `updated_at` TIMESTAMP    NULL,
  PRIMARY KEY (`id`),
  INDEX `oauth_personal_access_clients_client_id_index` (`client_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. oauth_auth_codes — códigos de autorización (flujo Authorization Code)
CREATE TABLE IF NOT EXISTS `oauth_auth_codes` (
  `id`         VARCHAR(100) NOT NULL,
  `user_id`    BIGINT       NOT NULL,
  `client_id`  INT UNSIGNED NOT NULL,
  `scopes`     TEXT         NULL,
  `revoked`    TINYINT(1)   NOT NULL,
  `expires_at` DATETIME     NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. oauth_access_tokens — tokens de acceso activos
CREATE TABLE IF NOT EXISTS `oauth_access_tokens` (
  `id`         VARCHAR(100) NOT NULL,
  `user_id`    BIGINT       NULL,
  `client_id`  INT UNSIGNED NOT NULL,
  `name`       VARCHAR(191) NULL,
  `scopes`     TEXT         NULL,
  `revoked`    TINYINT(1)   NOT NULL,
  `created_at` TIMESTAMP    NULL,
  `updated_at` TIMESTAMP    NULL,
  `expires_at` DATETIME     NULL,
  PRIMARY KEY (`id`),
  INDEX `oauth_access_tokens_user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. oauth_refresh_tokens — tokens de refresco
CREATE TABLE IF NOT EXISTS `oauth_refresh_tokens` (
  `id`              VARCHAR(100) NOT NULL,
  `access_token_id` VARCHAR(100) NOT NULL,
  `revoked`         TINYINT(1)   NOT NULL,
  `expires_at`      DATETIME     NULL,
  PRIMARY KEY (`id`),
  INDEX `oauth_refresh_tokens_access_token_id_index` (`access_token_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Paso 6b — Inicializar los clientes OAuth2**

Los clientes de Passport requieren secretos aleatorios generados por Laravel. Usar **uno** de estos métodos (en orden de preferencia):

**Opción A — Vía Script Web** (si no hay SSH):
1. Subir `public_html/passport-install.php` (ya está en el repositorio)
2. Acceder desde el navegador: `https://brokersconnector.com/passport-install.php`
3. Verificar que diga "Passport configurado correctamente"
4. **ELIMINAR el archivo inmediatamente** — es un vector de seguridad

**Opción B — Vía SSH**:
```bash
cd /home/[usuario]/brokers_new
php artisan passport:install
```

**Opción C — SQL manual de emergencia** (solo si A y B fallan):

> ADVERTENCIA: Reemplazar `[SECRET_40_CHARS]` con un string aleatorio de 40 caracteres. Generar en: `openssl rand -base64 30 | tr -d '/+=' | head -c 40`

```sql
-- Personal Access Client
INSERT INTO `oauth_clients`
  (`user_id`,`name`,`secret`,`redirect`,`personal_access_client`,`password_client`,`revoked`,`created_at`,`updated_at`)
VALUES
  (NULL, 'Brokers Connector Personal Access Client', '[SECRET_40_CHARS]',
   'http://localhost', 1, 0, 0, NOW(), NOW());

-- Vincular en oauth_personal_access_clients
INSERT INTO `oauth_personal_access_clients` (`client_id`, `created_at`, `updated_at`)
SELECT id, NOW(), NOW() FROM `oauth_clients`
WHERE `personal_access_client` = 1 AND `revoked` = 0
ORDER BY id DESC LIMIT 1;

-- Password Grant Client
INSERT INTO `oauth_clients`
  (`user_id`,`name`,`secret`,`redirect`,`personal_access_client`,`password_client`,`revoked`,`created_at`,`updated_at`)
VALUES
  (NULL, 'Brokers Connector Password Grant Client', '[OTRO_SECRET_40_CHARS]',
   'http://localhost', 0, 1, 0, NOW(), NOW());
```

#### BLOQUE 7 — Registrar migraciones en la tabla `migrations`

Esto evita que `php artisan migrate` intente re-ejecutar scripts ya aplicados manualmente:

```sql
INSERT IGNORE INTO `migrations` (`migration`, `batch`) VALUES
  ('2026_04_27_000001_create_ai_conversations_table',              1),
  ('2026_04_27_000002_create_ai_messages_table',                   1),
  ('2026_04_28_000001_add_openpay_customer_id_to_companies_table', 1),
  ('2026_04_28_000002_add_openpay_subscription_id_to_companies_table', 1),
  ('2026_05_04_000001_create_ai_settings_table',                   1),
  ('2026_05_05_000001_seed_super_admin_role',                       1),
  ('2026_05_06_000001_create_payment_gateway_settings_table',      1),
  ('2026_05_13_203448_create_audit_logs_table',                    1),
  ('2026_05_16_000001_add_ping_columns_to_ai_settings_table',      1),
  ('2026_05_16_000002_create_ai_prompts_table',                    1);
```

---

### PASO 5 — Configurar el Super Admin en producción

1. En phpMyAdmin de `brokersconnect_bd`, encontrar el ID del usuario super admin.
2. En el `.env` de producción, actualizar `SUPER_ADMIN_COMPANY_IDS` con el `company_id` correcto.
3. Verificar que el usuario tenga el rol `super_admin` en la tabla `model_has_roles`.

---

### PASO 6 — Configurar la API Key de IA en producción

La `api_key` del proveedor IA **nunca va en `.env`** — se administra cifrada desde el Panel:

1. Login como Super Admin en `https://brokersconnector.com`
2. Panel Super Admin → Orquestador IA → Proveedores
3. Agregar el proveedor (ej. Anthropic / Claude) con la API Key de producción
4. Activar y hacer ping para verificar conectividad

---

### PASO 7 — Verificación post-despliegue (Checklist de vuelo)

```
[ ] https://brokersconnector.com/login carga sin errores (HTTP 200)
[ ] Login con usuario real funciona correctamente
[ ] Panel Super Admin accesible → https://brokersconnector.com/home/v2/admin-bridge
[ ] Broker Brain IA accesible → Menú lateral → Broker Brain IA
[ ] Pasarela de Pagos sin error 1146 (tabla payment_gateway_settings existe)
[ ] Suscripciones → botón de pago funciona en modo PRODUCCIÓN (no sandbox)
[ ] Crear una propiedad de prueba → se guarda correctamente
[ ] Audit Log registra la operación del Super Admin
[ ] storage/app/public symlink existe (imágenes de propiedades visibles)
[ ] APP_DEBUG=false en .env (nunca exponer stack traces en producción)
```

---

### ROLLBACK DE EMERGENCIA

Si algo falla después del corte:

1. Restaurar el backup `staging_backup_YYYYMMDD.sql` en la BD
2. Reapuntar el DNS (o el `.env`) de vuelta a `tourfindycom_newbrokers_db`
3. El sistema regresa a staging en < 5 minutos

**Tiempo máximo de rollback:** 5-10 minutos si el backup fue tomado en el Paso 0.
