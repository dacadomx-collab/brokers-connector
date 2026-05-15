# 📘 05_SISTEMA_V2_CORE — BIBLIA TÉCNICA DEL SISTEMA V2

> **Clasificación:** Documento Maestro de Referencia  
> **Versión:** 1.0  
> **Fecha:** 2026-05-13  
> **Autor:** Claude (Agente de Ejecución Senior) bajo supervisión del Lead Architect  
> **Complementa:** `04_ARQUITECTURA_V2.md` (visión) · este documento es la implementación real

---

## 1. ARQUITECTURA GENERAL — STRANGLER FIG EN PRODUCCIÓN

El sistema opera como **arquitectura híbrida bidireccional**. No es un monolito, tampoco un microservicio puro. Es una transición controlada:

```
┌─────────────────────────────────────────────────────────────────┐
│  USUARIO FINAL                                                   │
│  Browser / App Móvil                                             │
└────────────┬────────────────────────────────────────────────────┘
             │ HTTPS
┌────────────▼────────────────────────────────────────────────────┐
│  CAPA DE ENTRADA (Apache / XAMPP)                               │
│                                                                  │
│  /brokers_new/public/     ← Legacy Laravel 5.8 (PHP 8.4)       │
│  /public_html/newbrokers/ ← SPA Vanilla JS (V2 Frontend)       │
└────────────┬───────────────────────┬────────────────────────────┘
             │                       │
  ┌──────────▼──────────┐   ┌───────▼────────────────┐
  │  LEGACY BACKEND     │   │  V2 FRONTEND (SPA)     │
  │  Laravel 5.8        │   │  Vanilla JS / HTML5    │
  │  Blade + Bootstrap  │   │  /v2/admin/security.html│
  │  Session PHP        │   │  /v2/subscriptions/    │
  │  Spatie Roles       │   │  CSS Variables (ARF)   │
  └──────────┬──────────┘   └───────┬────────────────┘
             │                      │
  ┌──────────▼──────────────────────▼──────────────────┐
  │  API LAYER — Laravel Passport (OAuth2)             │
  │  /api/v2/admin/*    → SuperAdminController         │
  │  /api/v2/my-company/user-quota → UserQuotaController│
  │  /api/auth/login    → AuthController (Passport)    │
  │  Middleware: auth:api + role:super_admin           │
  └─────────────────────────────────────────────────────┘

EL PUENTE (Bridge Pattern):
  GET /home/v2/admin-bridge  →  BridgeController::adminBridge()
  ↓ Genera Personal Access Token (Passport)
  ↓ Redirige a /v2/admin/security.html?access_token=<TOKEN>
  ↓ SPA valida con GET /api/v2/admin/users (auth:api + role:super_admin)
```

### Reglas de Convivencia

| Regla | Descripción |
|-------|-------------|
| **El Legacy NO llama al V2 directamente** | Solo genera un token y redirige. Sin acoplamiento de código. |
| **El V2 NO usa sesión PHP** | Solo Bearer Token (Passport OAuth2). Stateless. |
| **El Bridge es de un solo sentido** | Legacy → V2. Nunca V2 → Legacy (excepto logout redirect). |
| **CORS controlado** | El V2 API aplica middleware `Cors` para orígenes autorizados. |

---

## 2. REGLAS DE NEGOCIO MONETIZADAS — LEY 1+1

### Plan Base: $850 MXN / mes

```
┌─────────────────────────────────────────┐
│  PLAN BASE — $850 MXN/mes              │
│                                         │
│  ✅ 1 usuario Admin                    │
│  ✅ 1 usuario Agente                   │
│  ─────────────────────────────────────  │
│  Subtotal: 2 usuarios INCLUIDOS        │
└─────────────────────────────────────────┘
```

### Escalabilidad: $50 MXN por usuario adicional / mes

```
Usuarios activos | Cargo adicional | Total mensual
──────────────── | ─────────────── | ─────────────
2 (base)         | $0              | $850 MXN
3                | $50             | $900 MXN
4                | $100            | $950 MXN
5                | $150            | $1,000 MXN
N                | (N-2) × $50     | $850 + (N-2)×$50 MXN
```

### Validación en el Backend

**Endpoint de consulta (Admin autenticado):**
```
GET /api/v2/my-company/user-quota
Authorization: Bearer <passport_token>

Respuesta:
{
  "requires_charge": true | false,
  "active_users": 3,
  "base_included": 2,
  "extra_users": 1,
  "charge_amount": 50,
  "currency": "MXN"
}
```

**Validación complementaria (Super Admin):**
```
GET /api/v2/admin/companies/{id}/validate-user-quota
Authorization: Bearer <super_admin_token>
```

### Dónde Vive la Lógica

| Archivo | Responsabilidad |
|---------|----------------|
| `app/Http/Controllers/Api/UserQuotaController.php` | Endpoint de consulta de cuota para el Admin de la empresa |
| `app/Http/Controllers/Api/SuperAdminController.php::validateUserQuota()` | Consulta de cuota desde el panel Super Admin |
| `UserController::create()` | Valida que la empresa tenga `package != 1` para más de 1 usuario |

> **PENDIENTE DE CONECTAR:** La respuesta del endpoint `user-quota` debe ser consumida por el frontend antes de mostrar el formulario de creación de usuarios. Mostrar modal de confirmación de cargo si `requires_charge = true`.

---

## 3. PANEL SUPER ADMIN V2 — MÓDULOS ACTIVOS

### 3.1 Acceso y Seguridad

| Capa | Mecanismo | Descripción |
|------|-----------|-------------|
| **Ruta de entrada** | `GET /home/v2/admin-bridge` | Middleware: `auth` + `role:super_admin` |
| **Token** | Laravel Passport Personal Access Token | Se genera en cada acceso, no tiene expiración fija |
| **API** | `auth:api` + `role:super_admin` + `throttle:30,1` | Triple guardia en cada endpoint |
| **ACADEP** | `env('ACADEP_COMPANY_ID')` | Empresa protegida contra eliminación accidental |
| **URL limpiada** | `window.history.replaceState()` en el JS | El access_token no queda en el historial del browser |

**¿Quién tiene acceso?**  
Solo usuarios con rol `super_admin`. Por defecto, solo la empresa **ACADEP** tiene usuarios con este rol. Otras empresas pueden ser autorizadas manualmente mediante `php artisan tinker` o desde el propio panel (toggle-role endpoint).

### 3.2 Tabs del Panel

| Tab | ID HTML | Carga inicial | API |
|-----|---------|---------------|-----|
| Administradores | `panel-users` | ✅ Al cargar el panel | `GET /api/v2/admin/users` |
| Orquestador IA | `panel-ai` | Lazy (al hacer clic) | `GET /api/v2/admin/ai-settings` |
| Pasarelas de Pago | `panel-payments` | Lazy | `GET /api/v2/admin/payment-gateways` |
| **Empresas** | `panel-companies` | Lazy | `GET /api/v2/admin/companies` |
| **Audit Trail** | `panel-audit` | Lazy | `GET /api/v2/admin/audit-logs` |

### 3.3 Módulo Empresas

**Funcionalidades:**
- Tabla paginada (20/página) con búsqueda por nombre o email
- Columnas ordenables por: ID, Empresa, Email, Plan, Estatus, Último Pago, Vence
- Sorting server-side con whitelist de columnas (seguro contra SQL injection)
- Indicador visual ▲/▼ en el header activo (CSS `aria-sort`)
- Acciones: **Suspender/Activar** (PATCH toggle-status) y **Eliminar** (DELETE)
- Modal de confirmación antes de cualquier acción destructiva
- Badge de estatus: `Activa` (verde) · `Vencida` (amarillo) · `Suspendida` (rojo)

**Endpoints:**
```
GET    /api/v2/admin/companies?sort_by=name&sort_dir=asc&search=X&page=1
PATCH  /api/v2/admin/companies/{id}/toggle-status
DELETE /api/v2/admin/companies/{id}
GET    /api/v2/admin/companies/{id}/validate-user-quota
```

### 3.4 Módulo Orquestador IA

**Funcionalidades:**
- Escalera de failover visual (proveedores activos ordenados por prioridad)
- Formulario de alta/edición: `provider_name`, `api_key` (cifrada), `priority_order`, `is_active`, `extra_config` (JSON)
- Tabla con todos los proveedores, toggles inline de activo/inactivo, botones Editar y Eliminar
- `api_key` NUNCA se expone en el frontend — solo `api_key_masked` (ej. `••••••••4o3a`)

**Compatibilidad Laravel 5.8:**  
`$request->boolean()` no existe en L5.8. Se usa `filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN)` en toda la capa API.

**Endpoints:**
```
GET    /api/v2/admin/ai-settings
POST   /api/v2/admin/ai-settings
PUT    /api/v2/admin/ai-settings/{id}
DELETE /api/v2/admin/ai-settings/{id}
PATCH  /api/v2/admin/ai-settings/{id}/toggle
```

### 3.5 Audit Trail

**REGLA INQUEBRANTABLE:**  
Todo endpoint de escritura del `SuperAdminController` **DEBE** llamar a `$this->writeAuditLog()` antes de retornar éxito. Sin log = la operación no está completa. Esta regla aplica a: empresas, usuarios, proveedores IA y cualquier módulo futuro.

**REGLA DE SEGURIDAD DEL LOG:**  
JAMÁS registrar `api_key`, contraseñas, tokens ni credenciales en el campo `extra` ni en ningún campo de `audit_logs`, ni en texto plano ni cifrado.

**Funcionalidades del panel:**
- Tabla paginada (50/página) con todas las acciones del Super Admin
- Columnas: #, Fecha/Hora, Super Admin (email), Acción, Target, Estado Anterior, Estado Nuevo
- Botón **"⬇ Exportar PDF"** — genera PDF landscape A4 con jsPDF 2.5.1 UMD
- Las entradas se escriben automáticamente vía `writeAuditLog()` en cada acción

**Implementación del helper `writeAuditLog()` en `SuperAdminController`:**
```php
private function writeAuditLog(array $params): void
{
    // Escribe en audit_logs con los campos correctos del schema.
    // Envuelto en try/catch: un fallo de log NO debe bloquear la operación.
    DB::table('audit_logs')->insert([
        'actor_id'    => $actor->id,
        'actor_email' => $actor->email,
        'action'      => $params['action'],      // código del catálogo
        'target_type' => $params['target_type'],
        'target_id'   => $params['target_id'],
        'target_name' => mb_substr($params['target_name'] ?? '', 0, 191),
        'from_status' => mb_substr($params['from_status'] ?? '', 0, 50),
        'to_status'   => mb_substr($params['to_status'] ?? '', 0, 50),
        'extra'       => json_encode($params['extra'] ?? null),
        'created_at'  => now(),
        'updated_at'  => now(),
    ]);
}
```

**Tabla `audit_logs` (schema real — migration 2026_05_13_create_audit_logs_table):**
```sql
id            BIGINT PK AUTO_INCREMENT
actor_id      BIGINT NULL           -- ID del super_admin que actuó
actor_email   VARCHAR(191) NULL     -- Email snapshot (evita JOIN)
action        VARCHAR(50)           -- Código del catálogo (ver abajo)
target_type   VARCHAR(50) DEFAULT 'company'  -- Tabla afectada
target_id     BIGINT NULL           -- ID del registro afectado
target_name   VARCHAR(191) NULL     -- Nombre legible del target
from_status   VARCHAR(50) NULL      -- Estado anterior
to_status     VARCHAR(50) NULL      -- Estado posterior
extra         JSON NULL             -- Detalles adicionales (SIN credenciales)
created_at    TIMESTAMP
updated_at    TIMESTAMP

INDEX (target_type, target_id)
INDEX actor_id
INDEX created_at
```

**Catálogo de códigos `action` (exhaustivo y obligatorio):**

| Código | Módulo | Descripción |
|--------|--------|-------------|
| `activate` | Empresas | Empresa activada |
| `suspend` | Empresas | Empresa suspendida |
| `delete` | Empresas | Empresa eliminada lógicamente |
| `update` | Empresas | Datos editados |
| `subscription_override` | Empresas | Pago manual / ajuste financiero |
| `toggle_role` | Usuarios | Rol promovido o degradado |
| `reset_password` | Usuarios | Contraseña temporal generada |
| `CREATE_AI_SETTING` | Orquestador IA | Proveedor creado |
| `UPDATE_AI_SETTING` | Orquestador IA | Proveedor editado |
| `DELETE_AI_SETTING` | Orquestador IA | Proveedor eliminado |
| `TOGGLE_AI_SETTING` | Orquestador IA | Estado activo/inactivo cambiado |

> **Para módulos futuros:** agregar el nuevo código a esta tabla Y al mapa `ACTION_LABEL` en `security.js` antes de desplegar.

---

## 4. SEGURIDAD — IMPLEMENTACIONES ACTIVAS (2026-05-13)

### 4.1 Global Scopes de Tenant (Mandamiento #2)

| Scope | Archivo | Efecto |
|-------|---------|--------|
| `TenantUserScope` | `app/Http/Scopes/TenantUserScope.php` | Filtra `users.company_id = auth()->user()->company_id` en TODA query al modelo `User` |
| `TenantCompanyScope` | `app/Http/Scopes/TenantCompanyScope.php` | Filtra `companies.id = auth()->user()->company_id` en TODA query al modelo `Company` |

**Exenciones automáticas:**
- Si el usuario NO está autenticado → scope no se aplica (rutas públicas)
- Si el usuario tiene rol `super_admin` → scope no se aplica (necesita ver todos los tenants)
- Si el scope ya se está ejecutando → flag estático `$resolving` previene recursión infinita durante el login

**Para queries que requieren acceso cross-tenant:**
```php
Company::withoutGlobalScopes()->find($id);
User::withoutGlobalScope(TenantUserScope::class)->where(...)->get();
```

### 4.2 Bloqueo de Escalada de Privilegios

**`UserController.php`:**
```php
private const FORBIDDEN_ROLES = ['Admin', 'super_admin'];

// En create() y update():
if (in_array($request->user_a, self::FORBIDDEN_ROLES, true)) {
    abort(403, 'No está permitido asignar ese rol desde este panel.');
}
```

### 4.3 Rutas Protegidas por Rol (web.php)

```php
Route::middleware(['auth', 'company', 'companyPayment'])->group(function () {
    // Rutas accesibles por cualquier usuario autenticado (Admin + Agente):
    // properties/*, contacts/*, stock/*, perfil, chat IA

    Route::middleware(['role:Admin'])->group(function () {
        // Rutas EXCLUSIVAS de Admin — Agentes reciben 403 automático:
        // /home/users/*
        // /home/settings/account
        // /home/website
        // /home/invoices/*
        // /home/plans
    });
});
```

---

## 5. STATUS ACTUAL — CORTE 2026-05-13

### ✅ IMPLEMENTADO Y ACTIVO

| Componente | Estado |
|-----------|--------|
| Bridge Legacy → V2 (BridgeController) | Funcional |
| Passport OAuth2 para API V2 | Funcional |
| Panel Super Admin — Tab Administradores | Funcional |
| Panel Super Admin — Tab Orquestador IA | Funcional |
| Panel Super Admin — Tab Pasarelas de Pago | Funcional |
| Panel Super Admin — Tab **Empresas** con sorting | **Nuevo — 2026-05-13** |
| Panel Super Admin — Tab **Audit Trail** + PDF | **Nuevo — 2026-05-13** |
| TenantUserScope + TenantCompanyScope | **Nuevo — 2026-05-13** |
| Bloqueo de roles FORBIDDEN en UserController | **Nuevo — 2026-05-13** |
| Middleware `role:Admin` en rutas sensibles | **Nuevo — 2026-05-13** |
| API `user-quota` (monetización 1+1) | **Nuevo — 2026-05-13** |
| Migración `audit_logs` table | Creada — pendiente `php artisan migrate` |

### ⚠️ PENDIENTE DE CONECTAR (NO ROMPE NADA — solo falta la integración frontend)

| Tarea | Prioridad |
|-------|-----------|
| Consumir `/api/v2/my-company/user-quota` desde el formulario de creación de usuarios (mostrar modal de confirmación de cargo $50 MXN antes de crear) | ALTA |
| Ejecutar `php artisan migrate` para crear tabla `audit_logs` | BLOQUEANTE para el Audit Trail |
| Configurar `ACADEP_COMPANY_ID=<id_real>` en `.env` | ALTA |
| Agregar `role:Admin` como guardia en el Agente al intentar crear usuarios (actualmente el botón en el sidemenu está oculto pero la ruta ya tiene el middleware) | COMPLETADO |

### ❌ NO INICIADO (Backlog V2)

| Módulo | Descripción |
|--------|-------------|
| `brokers_v2_api/` | Repositorio del Backend V2 puro (Laravel 10+ aislado) — aún no creado |
| Sistema de notificaciones en tiempo real | WebSocket / SSE para alertas de vencimiento |
| Onboarding V2 de empresa nueva | Flujo completo en SPA: plan → pago → activación |
| Módulo de reportes por empresa | Dashboard de métricas por tenant |

---

## 6. VARIABLES DE ENTORNO REQUERIDAS POR V2

```env
# Bridge / Frontend
V2_FRONTEND_BASE=http://localhost/brokersconnect_dev/public_html/newbrokers
V2_API_BASE=http://localhost/brokersconnect_dev/brokers_new/public

# Restricción ACADEP (ID de la empresa ACADEP en la BD)
ACADEP_COMPANY_ID=<id_de_acadep>
```

---

## 7. FLUJO COMPLETO DE UNA ACCIÓN SUPER ADMIN (Referencia)

```
Super Admin navega a /home/v2/admin-bridge
  └─► BridgeController::adminBridge()
      └─► Verifica role:super_admin (middleware web)
      └─► Genera Personal Access Token (Passport)
      └─► redirect('/v2/admin/security.html?access_token=TOKEN')

SPA carga security.html
  └─► security.js boot()
      └─► Lee access_token de URLSearchParams
      └─► Limpia URL (replaceState)
      └─► GET /api/v2/admin/users → valida token + role
      └─► Si OK → showScreen('main')

Admin hace clic en "Empresas"
  └─► activateTab('tab-companies')
  └─► loadCompanies(1)
      └─► GET /api/v2/admin/companies?sort_by=id&sort_dir=asc
      └─► SuperAdminController::listCompanies()
          └─► Company::withoutGlobalScopes() — cross-tenant
          └─► Aplica whitelist de sort_by
          └─► JOIN con invoices (último pago)
          └─► Retorna JSON paginado con status_label
      └─► renderCompanies(data)
      └─► updateSortIcons()

Admin hace clic en "Suspender empresa XYZ"
  └─► coToggle(id, name, currentActive) → abre modal
  └─► Admin confirma
      └─► PATCH /api/v2/admin/companies/{id}/toggle-status
      └─► toggleCompanyStatus()
          └─► company.active = !previous
          └─► DB::table('audit_logs').insert(...)
          └─► Retorna JSON con nuevo estado
      └─► showToast('Empresa suspendida.')
      └─► loadCompanies() — recarga tabla

Admin exporta Audit Trail a PDF
  └─► $('audit-export-pdf').click
  └─► new jsPDF({ orientation: 'landscape' })
  └─► doc.autoTable(cols, rows)
  └─► doc.save('audit-trail-2026-05-13.pdf')
```

---

*Documento generado por Claude Code (Agente Ejecutor Senior) — Brokers Connector V2*  
*Mantener actualizado en cada sesión de desarrollo*
