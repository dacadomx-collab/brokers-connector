# 🔗 08. NODO FEDERADO ACADEP AURA — ARQUITECTURA DE CONEXIÓN HÍBRIDA LAN/WAN

> **Estatus:** Operativo — Hito Fase 1 Cerrado  
> **Validado:** 2026-06-05 — Handshake SUCCESS ✓ · HTTP 401 → Hash Bcrypt resuelto  
> **Lead Architect:** David / Gemini | **Agente Ejecutor:** Claude Sonnet 4.6  
> **Módulo:** AcadepAuraService · AiAnalyticsController · Panel Super Admin V2

---

## 🤖 PROTOCOLO DE SUBORDINACIÓN Y AUDITORÍA DE TOKENS (PulseScout ↔ AURA Kernel)

> **Ley origen:** Mandamiento #15 — Gobernanza de Agentes Federados (ver `01_LEY_Y_MANDAMIENTOS.md`)  
> **Aplicación:** Todo agente federado del ecosistema V2, incluyendo `PulseScout IA`.

### Principio rector

`PulseScout IA` **no posee autonomía de conexión**. Es un agente de interfaz que captura el estímulo del bróker y lo delega íntegramente al Kernel AURA para su procesamiento, auditoría y facturación. El agente nunca conoce el proveedor de IA que resuelve la solicitud — esa decisión es exclusiva del Kernel.

### Flujo de toma de decisiones (5 pasos)

```
┌──────────────────────────────────────────────────────────────────────┐
│  PASO 1 — PulseScout UI intercepta el prompt del bróker              │
│                                                                      │
│  El agente recibe el estímulo multimodal del usuario                 │
│  (texto libre, audio transcrito, imagen en Base64, contexto doc).    │
│  NO toma ninguna decisión de enrutamiento.                           │
│  Empaqueta el payload y lo envía al Kernel AURA vía API interna.     │
└───────────────────────────────┬──────────────────────────────────────┘
                                │ POST /api/v2/analytics/query
                                ▼
┌──────────────────────────────────────────────────────────────────────┐
│  PASO 2 — Kernel AURA recibe la solicitud                            │
│  (AiAnalyticsController@query)                                       │
│                                                                      │
│  · Valida el session_token del bróker (Cache, TTL 30 min)            │
│  · Extrae company_id con Tenant Lock Absoluto                        │
│  · Consolida métricas reales del tenant (gatherTenantMetrics)        │
│  · Construye el system prompt enriquecido con el Codex verificado    │
└───────────────────────────────┬──────────────────────────────────────┘
                                │
                                ▼
┌──────────────────────────────────────────────────────────────────────┐
│  PASO 3 — AURA audita el Ledger y verifica el estado de proveedores  │
│                                                                      │
│  · Consulta el estado de AcadepAuraService::isConfigured()           │
│  · Evalúa la escalera de failover dinámica en ai_settings            │
│  · Detecta el estado de cada API Key comercial (activa / caída)      │
│  · Toma la decisión de enrutamiento — SIN intervención del agente    │
└───────────────────────────────┬──────────────────────────────────────┘
                                │
                                ▼
┌──────────────────────────────────────────────────────────────────────┐
│  PASO 4 — AURA ejecuta el bypass instantáneo (Escenario de colapso)  │
│                                                                      │
│  Condición: API Keys comerciales inválidas / agotadas / inaccesibles │
│  Decisión: AURA desactiva la Capa B y enruta directo a ACADEP Linux  │
│                                                                      │
│  Capa A — AcadepAuraService::dispatch()                              │
│    ├─ A1: LAN  (192.168.x.x)  → connect_timeout 1.5s                │
│    └─ A2: WAN  (IPv6 Global)  → connect_timeout 5s (failover)       │
│                                                                      │
│  El Daemon Go de ACADEP valida X-AURA-KEY con:                       │
│    bcrypt.CompareHashAndPassword(ledger.open_key_hash, presentedKey) │
└───────────────────────────────┬──────────────────────────────────────┘
                                │ Respuesta HTML / JSON del nodo
                                ▼
┌──────────────────────────────────────────────────────────────────────┐
│  PASO 5 — Retorno limpio + descuento de CAPEX stateless              │
│                                                                      │
│  · AURA retorna el HTML de informe al agente PulseScout              │
│  · Registra el consumo en ai_conversations + ai_messages (Token Eco) │
│  · El CAPEX se descuenta en el ledger de ACADEP (PostgreSQL central) │
│  · PulseScout renderiza el resultado — sin conocer la capa usada     │
└──────────────────────────────────────────────────────────────────────┘
```

### Invariantes de gobernanza

| Invariante | Descripción | Consecuencia de violación |
|---|---|---|
| **Opacidad del enrutamiento** | PulseScout nunca conoce qué capa (A/B/C) resolvió la solicitud | Acoplamiento que impide failover dinámico |
| **Tenant Lock único** | El `company_id` solo lo extrae AURA del session_token — nunca el agente | Exposición cruzada de datos entre tenants |
| **Auditoría centralizada** | Solo AURA escribe en `ai_conversations` / `ai_messages` | Consumo fantasma no auditable en el ledger |
| **Bypass soberano** | En colapso comercial, AURA redirige solo a ACADEP — sin confirmación externa | Continuidad garantizada sin intervención humana |
| **Credenciales inmutables** | `ACADEP_AURA_KEY` solo en `.env` — nunca en `ai_settings` ni en respuestas | Brecha de seguridad perimetral |

---

## 1. CONCEPTO: QUÉ ES EL NODO SOBERANO ACADEP

El **Nodo Soberano ACADEP** es un servidor central de IA desarrollado en **Go** que expone una API REST propia. Actúa como la **Capa A de mayor prioridad** en el pipeline de inteligencia artificial de Brokers Connector.

A diferencia de los proveedores comerciales (OpenAI, Groq, Mistral), ACADEP:

- **No requiere API key de terceros** — usa el protocolo propio `X-AURA-KEY`.
- **Opera en red local LAN** (192.168.x.x) con latencia de microsegundos.
- **Almacena el ledger de tokens** en PostgreSQL propio (`acadep_core_tokens_ledger`).
- **Valida las llaves con bcrypt** — el Daemon Go ejecuta `bcrypt.CompareHashAndPassword()`.

---

## 2. ARQUITECTURA DE 3 CAPAS EN CASCADA

```
Solicitud de IA (AiAnalyticsController@query)
          │
          ▼
┌─────────────────────────────────────────────────────────┐
│  CAPA A — ACADEP AURA (AcadepAuraService)               │
│  Prioridad máxima · Protocolo OPEN KEY                  │
│                                                         │
│  A1: LAN  (ACADEP_AURA_URL_LAN)  connect_timeout: 1.5s  │
│      ↓ Si ConnectException                              │
│  A2: WAN  (ACADEP_AURA_URL_WAN)  connect_timeout: 5s    │
│      ↓ Si ambas fallan                                  │
└───────────────────────┬─────────────────────────────────┘
                        │ error de red
                        ▼
┌─────────────────────────────────────────────────────────┐
│  CAPA B — AIService (Failover Comercial)                │
│  Proveedores: Groq → Mistral → Gemini → OpenAI          │
│  Escalera de failover dinámica desde ai_settings        │
│      ↓ Si todos fallan → RuntimeException               │
└───────────────────────┬─────────────────────────────────┘
                        │ RuntimeException
                        ▼
┌─────────────────────────────────────────────────────────┐
│  CAPA C — Mock Estructurado (buildMockReport)           │
│  Informe HTML con métricas reales del tenant            │
│  Confirma conectividad HTTP end-to-end al developer     │
│  Se elimina cuando hay llaves de API válidas en .env    │
└─────────────────────────────────────────────────────────┘
```

### Códigos de retorno del Nodo Central

| HTTP | Status interno | Acción del sistema                        |
|------|---------------|-------------------------------------------|
| 200 + success:true  | `ok`      | Respuesta entregada al cliente       |
| 200 + success:false | `blocked` | Congela el DOM (`halted: true`)      |
| 401                 | `error`   | X-AURA-KEY inválida — Capa B activa  |
| 402                 | `blocked` | CAPEX agotado — igual que `blocked`  |
| ConnectException    | `_lan_unreachable` | Failover silencioso → WAN   |
| ConnectException WAN| `error`   | Ambas capas caídas → Capa B activa   |

---

## 3. CONTRATO DE AUTENTICACIÓN (PROTOCOLO OPEN KEY)

### Header enviado en cada request

```
X-AURA-KEY: {valor de ACADEP_AURA_KEY en .env}
Content-Type: application/json
Accept: application/json
```

### Payload del Handshake / Ping

```json
{
  "agent_id":     "AURA_BKC_V1",
  "user_session": "handshake_test_{timestamp}",
  "prompt":       "HANDSHAKE_PING: Responde con un saludo breve en JSON."
}
```

### Validación en el servidor Go (Daemon ACADEP)

```go
err := bcrypt.CompareHashAndPassword(storedHash, []byte(presentedKey))
// storedHash: columna open_key_hash en acadep_core_tokens_ledger
// presentedKey: header X-AURA-KEY del request entrante
```

### SQL de sincronización del ledger (generado vía panel Super Admin)

```sql
UPDATE `acadep_core_tokens_ledger`
SET    `open_key_hash` = '$2y$12$[HASH_BCRYPT_GENERADO]'
WHERE  `project_name`  = 'BROKERS CONNECTOR';
```

El hash se genera en el panel Super Admin: **Orquestador IA → Generar Query de Sincronización**.

---

## 4. FUENTE DE VERDAD DE CREDENCIALES

> **LEY INMUTABLE:** Las credenciales de ACADEP se leen **exclusivamente** desde `.env` del servidor.  
> La tabla `ai_settings` **NUNCA** almacena ni gestiona configuración ACADEP.

### Variables de entorno requeridas

```ini
# config/services.php mapea estas variables:
ACADEP_AURA_URL_LAN=http://192.168.X.X:8080/api/v1/aura/chat
ACADEP_AURA_URL_WAN=http://[IPv6_GLOBAL]:8080/api/v1/aura/chat
ACADEP_AURA_KEY=1fc70c7c...  # llave completa — NUNCA exponerla en respuestas
```

### Mapa en config/services.php

```php
'acadep' => [
    'url_lan'  => env('ACADEP_AURA_URL_LAN', ''),
    'url_wan'  => env('ACADEP_AURA_URL_WAN', ''),
    'key'      => env('ACADEP_AURA_KEY', ''),
    'agent_id' => 'AURA_BKC_V1',
],
```

---

## 5. ENDPOINTS DEL PANEL SUPER ADMIN (ACADEP)

| Método | Ruta                                   | Controlador                       | Descripción                          |
|--------|----------------------------------------|-----------------------------------|--------------------------------------|
| POST   | `/api/v2/analytics/test-connection`    | `AiAnalyticsController@testAcadepConnection`   | Handshake físico LAN → Go      |
| GET    | `/api/v2/admin/acadep/status`          | `AiAnalyticsController@acadepStatus`           | Telemetría estática desde .env |
| POST   | `/api/v2/admin/acadep/generate-query`  | `AiAnalyticsController@generateAcadepSyncQuery`| Genera SQL de rotación bcrypt  |
| DELETE | `/api/v2/admin/ai-providers/{id}`      | `AiAnalyticsController@deleteAiProvider`       | Purga registro BD duplicado    |

---

## 6. CUATRO ERRORES HISTÓRICOS RESUELTOS EN ESTA SESIÓN

### Error 1 — Adaptador comercial bloqueando el despacho ACADEP

**Síntoma:** El modal "⚡ Diagnóstico de Conexión" mostraba "Proveedor sin adaptador activo" al hacer Test de un proveedor ACADEP desde la tabla de la escalera, incluso cuando el servidor respondía correctamente.

**Causa raíz:** La función `classifyApiError()` en `security.js` tenía una rama que detectaba la cadena `"adaptador"` en el mensaje de error y aplicaba la clasificación genérica sin distinguir el proveedor.

**Resolución:**
```javascript
// Antes — aplicaba a todos los proveedores:
if (m.includes('adaptador') || m.includes('no adapter')) { return { type: 'Sin adaptador' }; }

// Después — guardado explícito para acadep:
if ((m.includes('adaptador') || m.includes('no adapter')) && prov !== 'acadep') {
  return { type: 'Proveedor sin adaptador activo' };
}
```
`classifyApiError()` ahora acepta `providerName` como segundo parámetro. `openPingModal()` pasa `data.provider_name` automáticamente.

---

### Error 2 — Colapso del Bridge 404 por variable no cacheada

**Síntoma:** El handshake devolvía `success: false` porque `testAcadepConnection()` buscaba el registro ACADEP en `ai_settings` (BD). Al no encontrarlo, bloqueaba el flujo antes de intentar la conexión de red.

**Causa raíz:** El método hacía 3 consultas a BD:
1. `AiSetting::where('provider_name', 'acadep')` → falla si el registro fue eliminado
2. `$setting->decryptedKey()` → dependía del registro BD
3. `$extra = $setting->extra_config['endpoint']` → ignoraba el .env

**Resolución — Decoupling total:**
```php
// Eliminado por completo. Nuevo flujo sin BD:
$endpoint = rtrim(config('services.acadep.url_lan', env('ACADEP_AURA_URL_LAN', '')), '/');
$auraKey  = config('services.acadep.key', env('ACADEP_AURA_KEY', ''));
$agentId  = config('services.acadep.agent_id', 'AURA_BKC_V1');
```

---

### Error 3 — Spinner de carga infinito bloqueando la UX

**Síntoma:** Al hacer clic en "Iniciar Handshake", el spinner giraba indefinidamente. El botón quedaba bloqueado sin importar si el servidor respondía o no.

**Causa raíz:** El reset del spinner usaba `spinner.classList.add('hidden')`. La clase CSS `.acadep-spinner` tenía mayor especificidad que `.hidden`, ignorando el ocultamiento.

**Resolución — Inline style forzado:**
```javascript
// Antes — vulnerable a especificidad CSS:
spinner.classList.add('hidden');
btn.disabled = false;

// Después — función resetHandshakeBtn() con style.display directo:
function resetHandshakeBtn() {
  spinner.style.display = 'none';  // vence toda especificidad CSS sin excepción
  btn.disabled      = false;
  btn.style.opacity = '1';
}
// Invocada tanto en .then() como en .catch()
```

---

### Error 4 — Desalineación criptográfica 401 (X-AURA-KEY vs bcrypt ledger)

**Síntoma:** El Handshake alcanzaba físicamente el servidor Go de ACADEP pero devolvía `HTTP 401`. El transporte de red era correcto; el fallo era de autenticación.

**Causa raíz:** El Daemon Go de ACADEP ejecuta `bcrypt.CompareHashAndPassword(storedHash, presentedKey)`. La columna `open_key_hash` en `acadep_core_tokens_ledger` (PostgreSQL) no tenía el hash bcrypt correspondiente al valor de `ACADEP_AURA_KEY` del `.env` de Brokers Connector.

**Resolución — Sincronización del ledger:**
1. Se implementó el endpoint `POST /api/v2/admin/acadep/generate-query` en `AiAnalyticsController`.
2. El método `generateAcadepSyncQuery()` ejecuta `password_hash($key, PASSWORD_BCRYPT, ['cost' => 12])`.
3. Genera el SQL de actualización y lo pinta en el terminal de telemetría del panel Super Admin.
4. El administrador copia el SQL y lo ejecuta directamente en el PostgreSQL del servidor ACADEP.

```sql
-- Query generada y ejecutada en el servidor central ACADEP:
UPDATE `acadep_core_tokens_ledger`
SET    `open_key_hash` = '$2y$12$[HASH]'
WHERE  `project_name`  = 'BROKERS CONNECTOR';
```

**Resultado post-fix:** `Handshake SUCCESS ✓` — canal LAN validado al 100%.

---

## 7. SEPARACIÓN DE ENTORNOS: ACADEP vs IA COMERCIAL

| Aspecto                | ACADEP (Nodo Soberano)            | IA Comercial (AIService)          |
|------------------------|-----------------------------------|-----------------------------------|
| Configuración          | `.env` del servidor (INMUTABLE)   | Tabla `ai_settings` (editable)    |
| Formulario Super Admin | ❌ NO — solo lectura / telemetría  | ✅ Sí — CRUD completo              |
| Llave de autenticación | `X-AURA-KEY` (header HTTP)        | API Key por proveedor             |
| Validación de llave    | bcrypt en Go (PostgreSQL)         | Header Bearer / query param       |
| Prioridad en cascade   | Capa A (máxima)                   | Capa B (failover)                 |
| Latencia esperada      | < 5 ms (LAN) / < 200 ms (WAN)    | 800 ms – 3 s (red pública)        |

---

## 8. TERMINAL DE TELEMETRÍA (PANEL SUPER ADMIN)

El componente `#acadep-telemetry-terminal` en `security.html` es un `<pre>` monoespaciado con fondo `#0d1117` que muestra:

- **Verde** (`#7ee787`) → Handshake SUCCESS — JSON completo del servidor Go
- **Rojo** (`#f85149`) → Handshake FAILED — mensaje exacto de Guzzle
- **Amarillo dorado** (`#e3b341`) → Query SQL de sincronización bcrypt

El estado del último test se persiste en `localStorage('acadep_last_handshake')` y se restaura automáticamente al recargar la vista.

---

## 9. CHECKLIST DE DEPLOY AL SERVIDOR DE PRODUCCIÓN

- [ ] Copiar `.env` con `ACADEP_AURA_URL_LAN`, `ACADEP_AURA_URL_WAN` y `ACADEP_AURA_KEY`
- [ ] Ejecutar `php artisan config:cache` en el servidor destino
- [ ] Usar el botón "Generar Query de Sincronización" para obtener el hash bcrypt
- [ ] Ejecutar el SQL en el PostgreSQL del servidor ACADEP
- [ ] Verificar con el botón "Iniciar Handshake" → debe retornar `SUCCESS ✓`
- [ ] Confirmar que el badge `[CONEXIÓN POR RED LOCAL LAN]` muestra la IP correcta

---

*Documento generado y validado en la sesión del 2026-06-05 — Hito Fase 1 cerrado.*
