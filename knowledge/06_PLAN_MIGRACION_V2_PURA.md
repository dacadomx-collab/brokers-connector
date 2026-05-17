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
