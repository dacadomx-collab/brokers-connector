# 🤝 CONTRATOS DE API Y LÓGICA DE NEGOCIO — BROKERS CONNECTOR

> **Fuente de verdad extraída de:** `routes/api.php`, `routes/web.php`, controladores en `app/Http/Controllers/`
> **Última auditoría:** 2026-04-10

---

## 📡 PROTOCOLO DE INTEGRACIÓN

- **Intercambio:** JSON UTF-8 para rutas `/api/`. Sesiones Laravel (cookies) para rutas `/web/`.
- **CORS:** Habilitado globalmente — `App\Http\Middleware\Cors` se aplica en **todos los requests** (middleware global).
- **Rate Limiting API:** `throttle:60,1` — máximo **60 requests por minuto** por IP en el grupo `api`.
- **Formato de token API:** `Bearer {access_token}` en header `Authorization`.
- **Formato de respuesta de error (API):** `{ "error": "string" }` con HTTP 400/401/404.
- **Formato de respuesta de éxito (API):** Varía por endpoint — algunos usan `PropertyResource::collection()`, otros JSON directo.

---

## 🔐 SISTEMA DE AUTENTICACIÓN

El proyecto usa **dos sistemas de autenticación simultáneos**:

### 1. Laravel Passport (OAuth2 — para rutas `/api/`)
- **Middleware:** `auth:api`
- **Endpoint de login:** `POST /api/auth/login`
- **Token type:** Personal Access Token (`Bearer`)
- El token se crea con `$user->createToken('Personal Access Token')`
- El token expira en **1 semana** si `remember_me=true`; sin `remember_me`, expira según config de Passport

### 2. Laravel Session/Auth (para rutas `/web/` — panel interno)
- **Middleware:** `auth` — basado en sesión PHP + cookies
- **Login:** ruta estándar de Laravel Auth (`Auth::routes()`)
- Confirmación de email requerida (token UUID en columna `users.token`)

---

## 🛠️ ENDPOINTS API — CONTRATOS DEFINITIVOS

---

### `POST /api/auth/login`
**Autenticación. Devuelve Bearer Token.**

- **Middleware:** Ninguno (público)
- **Payload (JSON):**
```json
{
  "email": "string|required",
  "password": "string|required",
  "remember_me": "boolean|optional"
}
```
- **Response 200:**
```json
{
  "access_token": "string",
  "token_type": "Bearer",
  "expires_at": "YYYY-MM-DD HH:MM:SS"
}
```
- **Response 401:**
```json
{ "message": "Unauthorized" }
```
- **Regla:** `remember_me=true` → token vive 1 semana. Sin él → expira según config Passport.

---

### `GET /api/auth/logout`
**Revoca el token activo.**

- **Middleware:** `auth:api` (requiere Bearer Token)
- **Response 200:**
```json
{ "message": "Successfully logged out" }
```

---

### `GET /api/getproperties` — `POST /api/getproperties`
**Listado de propiedades de una agencia (para sitios web externos).**

- **Middleware:** Ninguno (público con `api_key`)
- **Query params (GET) o Payload (POST):**
```json
{
  "api_key":    "string|required — debe existir en companies.api_key",
  "limit":      "numeric|optional",
  "pricemin":   "numeric|optional",
  "pricemax":   "numeric|optional",
  "free_search":"string|optional",
  "parking_lots":"numeric|optional",
  "baths":      "numeric|optional",
  "bedrooms":   "numeric|optional",
  "type":       "numeric|optional — FK property_types.id",
  "status":     "numeric|optional — FK property_statuses.id",
  "paginate":   "numeric|optional",
  "order":      "string|optional",
  "featured":   "boolean|optional",
  "features":   "array|optional",
  "city":       "numeric|optional",
  "description":"boolean|optional",
  "ubication":  "boolean|optional",
  "comission":  "boolean|optional",
  "sizes":      "boolean|optional",
  "images":     "boolean|optional"
}
```
- **Response 200:** Colección `PropertyResource` (JSON Array)
- **Response 400:** `{ "error": "Consulta no valida, por favor revise la documentación" }`
- **Regla de Piedra:** Solo devuelve propiedades `published=true` de la compañía identificada por `api_key`. El `api_key` actúa como aislamiento de tenant.

---

### `GET /api/property`
**Obtiene una propiedad específica por ID (para sitios externos).**

- **Middleware:** Ninguno (público con `api_key`)
- **Query Params:**
```
api_key (required), id (required|numeric), description, ubication, comission, sizes, images, features
```
- **Response 200:** `PropertyResource` (objeto JSON)
- **Response 404:** `{ "error": "404 Resource not found" }`
- **Regla de Piedra:** Valida que `property.company.api_key == api_key`. Si no coincide → 404. Nunca se expone una propiedad de otra compañía.

---

### `GET /api/getpropertiesgeneral`
**Bolsa inmobiliaria pública — propiedades marcadas `bbc_general=true`.**

- **Middleware:** Ninguno (completamente público)
- **Query Params:** `location`, `min`, `max`, `baths`, `beds`, `status`, `types`
- **Response 200:** Colección `PropertyResource`
- **Regla de Piedra:** Solo incluye propiedades donde `bbc_general=true AND published=true`.

---

### `GET /api/agents`
**Agentes de una compañía (para sitios externos).**

- **Middleware:** Ninguno (público)
- **Query Param:** `api_key` (presumiblemente requerido — ver `AgentApi.php`)

---

### `GET /api/property/search-params`
**Catálogos de tipos y estados para buscadores de portales.**

- **Middleware:** Ninguno (público)
- **Response 200:**
```json
{
  "statuses": [ { "id": 1, "name": "Venta" } ],
  "types":    [ { "id": 1, "name": "Casa" } ]
}
```
- **Regla:** Solo devuelve entradas con `luly=true` (filtro de portal interno).

---

### `GET /api/account/data`
**Datos de configuración de una compañía (logo, nombre, etc.).**

- **Middleware:** Ninguno (aparentemente público)

---

### `GET /api/getfeatures`
**Catálogo completo de características/amenidades, ordenado por nombre.**

- **Middleware:** Ninguno (público)
- **Response 200:** Colección `FeatureResource`

---

### `POST /api/invoice/paynet/pay`
**Webhook de pago Paynet/OpenPay.**

- **Middleware:** Ninguno (llamado por OpenPay externamente)
- **Payload:** Estructura enviada por OpenPay (webhook)

---

---

### `POST /api/ai/chat`
**Enviar mensaje a la IA. Crea o continúa un hilo de conversación por tenant.**

- **Middleware:** `auth:api` (requiere `Bearer {access_token}`)
- **Controlador:** `AiChatController@sendMessage`
- **Payload (JSON):**
```json
{
  "message":         "string|required — texto del mensaje del usuario",
  "conversation_id": "integer|nullable — omitir para iniciar un nuevo hilo"
}
```
- **Response 200 — Mensaje recibido (placeholder pre-OpenAI):**
```json
{
  "conversation_id": 12,
  "message_id":      47,
  "status":          "received",
  "ai_response":     null
}
```
- **Response 403 — Sin empresa asociada:**
```json
{ "error": "No company associated with this user." }
```
- **Response 403 — Conversación de otro tenant:**
```json
{ "error": "Conversation not found or access denied." }
```
- **Response 422 — Validación fallida (Laravel estándar):**
```json
{ "message": "The given data was invalid.", "errors": { "message": ["..."] } }
```
- **Reglas de Piedra:**
  1. `company_id` se toma **siempre** de `auth()->user()->company_id`. Nunca del payload.
  2. Si `conversation_id` viene en el request, se verifica que `ai_conversations.company_id == company_id` del usuario autenticado antes de continuar. Acceso cruzado → 403.
  3. Si no viene `conversation_id`, se crea una nueva `AiConversation` con `title` = primeros 80 caracteres del mensaje.
  4. El mensaje del usuario se persiste en `ai_messages` con `role = 'user'` antes de llamar a la IA.
  5. `ai_response` es `null` hasta la Fase 7.5 (integración OpenAI). El endpoint ya está listo para recibir carga real.

---

## 🌐 ENDPOINTS WEB (Panel Interno — requieren sesión)

> Todos los siguientes requieren el triple middleware: `auth` + `company` + `companyPayment`

---

### `GET /home/subscription`
**Vista de suscripción recurrente. Muestra selector de planes y formulario de tarjeta.**

- **Middleware:** `web, auth, company, companyPayment`
- **Controlador:** `InvoicesController@subscription`
- **Datos pasados a la vista:** `$company` (tenant activo), `$services` (planes disponibles — excluye ID 4 "usuario extra")
- **Respuesta:** Blade `companies/subscription.blade.php`

---

### `POST /home/subscription`
**Procesamiento de suscripción recurrente con tokenización OpenPay.**

- **Middleware:** `web, auth, company, companyPayment`
- **Controlador:** `InvoicesController@processSubscription`
- **Payload (form POST con CSRF):**
```json
{
  "token_id":                "string|required — token generado por OpenPay.js (nunca datos de tarjeta)",
  "selected_plan_id":        "integer|required — ID de plan de la tabla services (1, 2, 3)",
  "deviceIdHiddenFieldName": "string|required — device session ID generado por openpay-data.js"
}
```
- **Mapeo interno → OpenPay (debe coincidir con planes creados en el dashboard):**

| `selected_plan_id` (DB) | `plan_id` en OpenPay |
|---|---|
| 1 | `plan_basico_mensual` |
| 2 | `plan_profesional_mensual` |
| 3 | `plan_enterprise_mensual` |

> ⚠️ **EL HUMANO DEBE CREAR ESTOS PLANES EN EL DASHBOARD DE OPENPAY** antes de activar esta ruta en producción. Los IDs del array `$planMap` en el controlador deben coincidir exactamente con los creados en el panel.

- **Response éxito:** `redirect → /home/invoices` con flash `success`
- **Responses de error (todos retornan `back()` con flash `error`):**

| Excepción OpenPay | Mensaje mostrado al usuario |
|---|---|
| `OpenpayApiTransactionError` (3001) | "La tarjeta fue declinada. Comunícate con tu banco." |
| `OpenpayApiTransactionError` (3002) | "La tarjeta ha expirado." |
| `OpenpayApiTransactionError` (3003) | "Fondos insuficientes." |
| `OpenpayApiTransactionError` (3005) | "Rechazada por antifraudes." |
| `OpenpayApiRequestError` | "Datos de tarjeta incorrectos." |
| `OpenpayApiAuthError` | "Error de configuración. Contacta soporte." |
| `OpenpayApiConnectionError` | "No se pudo conectar. Intenta en minutos." |
| `OpenpayApiError` | Mensaje crudo del SDK |
| `\Exception` | "Error inesperado. Intenta de nuevo." |

- **Persistencia en caso de éxito:**
  - `companies.openpay_subscription_id` ← `$subscription->id`
  - `companies.package` ← `$request->selected_plan_id`
- **Regla de Piedra:** Si OpenPay rechaza la tarjeta, **ningún dato del usuario se modifica** — la transacción falla limpiamente en el catch y el usuario recibe un mensaje accionable en español.

---

### `POST /home/ai/chat`
**Chat IA del panel de agentes. Crea o continúa un hilo de conversación por tenant.**

- **Middleware:** `web, auth, company, companyPayment` (sesión + CSRF)
- **Controlador:** `AiChatController@sendMessage`
- **Autenticación frontend:** `X-CSRF-TOKEN` desde `<meta name="csrf-token">` — **no requiere Bearer Token**
- **Payload (JSON):**
```json
{
  "message":         "string|required — texto del mensaje del usuario",
  "conversation_id": "integer|nullable — omitir para iniciar un nuevo hilo"
}
```
- **Persistencia:** Cada llamada guarda 1 `AiMessage` con `role='user'` y, si OpenAI responde, otro con `role='assistant'` incluyendo `tokens_used`. Si no hay `conversation_id`, crea una nueva `AiConversation` con `title` = primeros 80 chars del mensaje.
- **Contexto enviado a OpenAI:** Últimos 5 mensajes del hilo (`CONTEXT_WINDOW=5`) en orden cronológico + System Prompt de asistente inmobiliario.
- **Response 200:**
```json
{
  "conversation_id": 12,
  "message_id":      49,
  "status":          "ok",
  "ai_response":     "Texto generado por la IA...",
  "tokens_used":     187
}
```
- **Response 403:** `company_id` nulo o `conversation_id` pertenece a otro tenant.
- **Response 500:** Error de red/API de OpenAI (`RequestException` o `\Exception`).
- **Reglas de Piedra:**
  1. `company_id` siempre del servidor (`auth()->user()->company_id`), nunca del payload.
  2. Si viene `conversation_id`, se valida `WHERE company_id = $company_id` antes de continuar (anti cross-tenant).
  3. El mensaje del usuario se persiste ANTES de llamar a OpenAI para garantizar trazabilidad.

---

### `POST /home/ai/generate-copy`
**Generador de copywriting inmobiliario one-shot. No persiste en DB.**

- **Middleware:** `web, auth, company, companyPayment` (sesión + CSRF)
- **Controlador:** `AiChatController@generateCopy`
- **Autenticación frontend:** `X-CSRF-TOKEN` desde `<meta name="csrf-token">`
- **Origen:** Botón `#btn-ai-copy` en `properties/create.blade.php` y `properties/edit.blade.php` (vía `aiCopywriter.js`)
- **Payload (JSON):** todos los campos son `nullable`
```json
{
  "title":       "string — título de la propiedad",
  "prop_type":   "string — texto del tipo (ej. 'Casa', 'Departamento')",
  "prop_status": "string — texto de la operación (ej. 'Venta', 'Renta')",
  "bedrooms":    "integer — número de recámaras",
  "baths":       "integer — número de baños",
  "price":       "numeric — precio numérico (del input hidden)",
  "currency":    "string — texto de la moneda (ej. 'MXN', 'USD')"
}
```
- **Response 200 — Éxito:**
```json
{ "copy": "✨ Descripción generada por la IA con viñetas y call-to-action..." }
```
- **Response 200 — API Key no configurada o cualquier fallo:** *(siempre 200, nunca 500)*
```json
{ "copy": "⚠️ La Inteligencia Artificial está lista, pero falta configurar la API Key de OpenAI en el servidor para generar el texto." }
```
- **Reglas de Piedra:**
  1. **Sin persistencia en DB** — es una llamada one-shot, no guarda en `ai_messages`.
  2. **Early exit** si `env('OPENAI_API_KEY')` está vacío — retorna 200 con mensaje amigable sin hacer llamada HTTP.
  3. **Catch universal** (`\Exception`) — cualquier fallo de red, timeout o rechazo de OpenAI retorna 200 con mensaje amigable. La UI nunca recibe un error 500.
  4. System Prompt construido dinámicamente con los campos disponibles — funciona aunque lleguen campos vacíos (`array_filter` elimina nulls).
  5. `prop_type` y `prop_status` llegan como **texto legible** (no como ID numérico) — el JS envía el `text` de la `<option>` seleccionada, no su `value`.

---

### `POST /properties/store`
**Crear nueva propiedad.**

- **Form Request:** `CreatePropertyRequest` — validación estricta
- **Campos requeridos:** `title`, `prop_type_id`, `prop_status_id`, `description`, `price`, `currency`, `local_id`, `mun`, `state`, `lat`, `lng`
- **Campos opcionales clave:** `key` (máx. 15 chars, único por compañía), `bedrooms`, `baths`, `features[]`, `agent_id`
- **Regla de Piedra:** `key` debe ser único por `company_id` (excluye soft-deleted). Si `commission <= 0` → `type_commission` se fuerza a `0`.
- **Post-éxito:** Redirige a `properties/add-images/{id}`. Envía notificación por SendGrid si la compañía está en `config('app.notification_companies')`.

---

### `POST /properties/update`
**Actualizar propiedad existente.**

- **Form Request:** `CreatePropertyRequest` (mismas reglas)
- **Campos adicionales:** `property_id` (requerido para identificar el registro)
- **Regla de Piedra:** La `key` única se valida excluyendo el registro actual: `id != property_id`.
- **Sincronización de features:** `FeatureProperty::syncFeatures($request->features, $property->id)` — reemplaza todas las características.

---

### `POST /properties/delete`
**Soft-delete de una propiedad.**

- **Payload:** `{ "id": number }`
- **Regla de Piedra:** Usa soft-delete (`$property->delete()`). El registro permanece en BD con `deleted_at` set. Las imágenes físicas NO se eliminan del disco (código comentado — ver líneas 447-452).
- **Respuesta AJAX:** `"La propiedad {title} ha sido eliminada"` (texto plano, HTTP 200)

---

### `POST /properties/agent`
**Asignar agente a una propiedad.**

- **Payload:** `{ "agent_id": number, "property_id": number }`
- **Response JSON:**
```json
{ "agent_name": "string", "agent_img": "string (url)", "ant": number }
```

---

### `POST /properties/state`
**Toggle del estado `published` de una propiedad.**

- **Payload:** `{ "property_id": number, "published": 0|1 }`
- **Regla:** Invierte el valor — si llega `1`, guarda `0`; si llega `0`, guarda `1`.
- **Response JSON:** `{ "ant": number, "published": 0|1 }`

---

### `POST /properties/changeStatus`
**Toggle del campo `featured` (propiedad destacada).**

- **Payload:** `{ "id_property": number }`
- **Response JSON:** `{ "featured": boolean, "msg": "string" }`

---

### `POST /files/upload/store`
**Subir imagen, video o PDF a una propiedad.**

- **Payload:** `multipart/form-data` — campos: `file` (archivo), `property_id`, `type` (1=imagen, 3=youtube, 4=video, 5=pdf)
- **Reglas de Piedra:**
  - **Imágenes** (type=1): máx. **20 imágenes**, máx. **8 MB** por archivo
  - **Videos** (type=4): máx. **1 video**, máx. **50 MB**. Formatos: `mp4`, `avi`, `3gp`
  - **PDFs** (type=5): máx. **15 archivos**, máx. **2 MB** por archivo
  - **YouTube** (type=3): máx. **5 enlaces**, debe pasar regex de URL de YouTube válida
  - Primera imagen subida → se convierte automáticamente en `featured_image` de la propiedad
  - Genera **thumbnail** a 300px de ancho (Intervention Image) para imágenes tipo 1
  - Ruta de guardado: `public/companies/{companyId}/YYYY/MM/DD/{uniqid}.ext`
- **Response 200:** `{ "type_msg": 2, "msg": "ruta/archivo", "id": number }`
- **Response 401:** `{ "type_msg": 1, "msg": "mensaje de error" }`

---

### `POST /files/upload/delete`
**Eliminar un archivo de una propiedad.**

- **Payload:** `{ "id": number }`
- **Regla:** Elimina físicamente `src` y `thumbnail` del disco vía `File::delete()`. Si el archivo era `featured_image`, limpia el campo en `properties`.

---

### `POST /files/upload/set_featured`
**Cambiar imagen destacada de una propiedad.**

- **Payload:** `{ "id": number (file_property.id) }`
- **Response:** `{ "type_msg": 2, "msg": "string", "featured_id": number (anterior featured) }`

---

### `POST /home/contact/create`
**Crear nuevo contacto/prospecto.**

- **Payload (form):** `name`, `email`, `surname`, `job`, `status`, `origin`, `type`, `address`, `otros`, `phone[]` (array de `{phone, type}`)
- **Regla de Piedra:** Email único por `company_id`. Si ya existe → error "El email ya esta en uso".
- **Teléfonos:** Solo dígitos. Si `type` vacío → se asigna `"Celular"` por defecto.

---

### `POST /home/contact/agent`
**Asignar agente a un contacto.**

- **Payload:** `{ "contact_id": number, "agent_id": number }`
- **Response JSON:** `{ "agent_name": "string", "ant": number (anterior agent_id) }`

---

### `POST /home/contact/form` *(público — sin auth)*
**Formulario de contacto desde el sitio web de la agencia.**

- **Payload:** `property_id`, `name`, `lastname`, `email`, `content`
- **Regla de Piedra:** Si el email ya existe en la compañía → no crea un nuevo contacto, solo agrega una nota. La nota se guarda en `contact_notes` con `type=2` (actividad) y `content` en JSON: `{ "property_id": N, "message": "string" }`.

---

### `POST /store/company`
**Registro de nueva agencia (primer setup).**

- **Payload:** datos de `StoreCompanyRequest` + `dominio`
- **Regla de Piedra:** El dominio se normaliza: sin espacios, minúsculas, se concatena `.brokersconnector.com`. Formato: solo `[a-zA-Z0-9][a-zA-Z0-9-]+[a-zA-Z0-9]`.
- **Post-registro:** Crea automáticamente una `Invoice` de prueba con `cost_package=0`, `due_date = hoy + 7 días`, status `'1'`.

---

### `POST /home/invoices/{invoice}/payment`
**Procesar pago con tarjeta vía OpenPay.**

- **Payload:** `token_id` (tokenización de tarjeta), `deviceIdHiddenFieldName`
- **Regla de Piedra:** Crea una nueva `Invoice` con `status=2` (pendiente de pago) ANTES de intentar el cargo. Si OpenPay falla → `$invoice->delete()`. Si éxito → redirige a URL de 3D Secure de OpenPay.
- **Configuración:** `OPENPAY_ID`, `OPENPAY_KEY_SECRET`, `OPENPAY_PRODUCTION` desde `.env`.

---

### `GET /register/confirm/{token}`
**Confirmación de email por token.**

- **Middleware:** Público
- **Regla:** Busca `users.token = {token}`. Si no existe → error. Si existe → activa la cuenta.

---

### `GET /propiedades-com/feed` | `/lgi/feed` | `/doomos/feed` | `/lamudi/feed` | `/casafy/feed`
**Feeds XML para portales inmobiliarios.**

- **Middleware:** Público
- **Regla de Piedra:** Solo incluyen propiedades con el flag del portal correspondiente activado (`bbc_general`, `propiedades`, `lamudi`, etc.).

---

## 🧠 LÓGICA DE NEGOCIO — REGLAS DE PIEDRA

1. **Aislamiento de Tenant (Mandamiento #1 del Sistema):** Toda consulta a `properties`, `contacts`, `invoices` DEBE filtrar por `company_id = auth()->user()->company_id`. Nunca se exponen datos cross-tenant.

2. **API Key como Guardia de Tenant (API Pública):** En endpoints públicos, el `api_key` es el único identificador del tenant. Validar con `exists:companies,api_key`. Verificar que `property.company.api_key == api_key` para acceso a propiedad individual.

3. **Inmutabilidad de `created_by`:** Al crear una propiedad, `created_by` siempre se fuerza a `auth()->user()->id`. No puede ser enviado por el cliente.

4. **`company_id` siempre del servidor:** En store/update de propiedades, `company_id = auth()->user()->company->id`. Nunca del payload.

5. **Soft Delete para Propiedades:** `properties.delete()` usa soft-delete. Las imágenes físicas NO se eliminan automáticamente (código comentado intencionalmente). Limpiar imágenes requiere proceso manual o tarea programada.

6. **Primera imagen = `featured_image` automático:** Al subir la primera imagen de una propiedad (`file_properties` vacía para esa propiedad), se establece automáticamente como `featured_image` en `properties`.

7. **Unicidad de `key` por compañía:** El campo `key` (identificador interno) es único por `company_id` y excluye soft-deleted (`deleted_at IS NULL`).

8. **Email único por `company_id` en Contactos:** No por sistema global — dos agencias distintas pueden tener el mismo email de prospecto. La unicidad es solo dentro del mismo tenant.

9. **Plan package=1 → máximo 1 usuario:** `UserController::create()` bloquea la creación si `company.package == 1`.

10. **No eliminar Admin:** `UserController::delete()` verifica `if(!$user->hasRole("Admin"))` antes de borrar. Un usuario Admin nunca puede ser eliminado por este endpoint.

11. **Factura creada antes del cobro:** `InvoicesController::openPay_payment()` guarda el registro `Invoice` con `status=2` ANTES de llamar a OpenPay. Si el cargo falla → `$invoice->delete()`. Garantiza trazabilidad.

12. **CommissionType forzado a 0:** Si `commission <= 0` o vacío → `type_commission` se fuerza a `0` automáticamente en store y update.

13. **Toggle de Published:** `POST /properties/state` invierte el valor actual. No acepta el valor deseado directamente — calcula el opuesto. Enviar `published=1` resulta en `published=0` y viceversa.

14. **Nota pública de contacto (JSON en content):** `contact_notes.content` cuando viene del formulario público se almacena como JSON string: `{"property_id": N, "message": "texto"}`. Al leer, se debe hacer `json_decode()`.

15. **Ruta temporal `/generar-codex` — ELIMINAR EN PRODUCCIÓN:** `web.php` contiene una ruta sin middleware que expone el schema completo de BD. Viola el Mandamiento #8. **Debe eliminarse antes de cualquier deploy a producción.**

---

## 🎭 CATÁLOGOS NUMÉRICOS (SIN TABLA — Hardcoded)

> Estos valores NO tienen tabla de catálogo en BD. Están hardcoded en el código.

### `contacts.origin`
| Valor | Significado |
|---|---|
| 0 | Desconocido |
| 5 | Formulario Web (desde portal de propiedad) |

### `contacts.status`
| Valor | Significado |
|---|---|
| 0 | Sin status |
| 1 | Activo / Nuevo |

### `contacts.type`
| Valor | Significado |
|---|---|
| 0 | Sin tipo |
| 1 | Comprador/Interesado |

### `contact_notes.type`
| Valor | Significado |
|---|---|
| 2 | Actividad / Nota automática (formulario web) |

### `file_properties.type`
| Valor | Significado |
|---|---|
| 1 | Imagen (`jpg`, `png`, `gif`, `svg`) |
| 3 | Video YouTube (URL embed) |
| 4 | Video archivo (`mp4`, `avi`, `3gp`) |
| 5 | PDF |

### `invoices.status`
| Valor | Significado |
|---|---|
| `'1'` | Pagada / Trial activo |
| `'2'` | Pendiente de pago |
| `pending` | Pendiente (string alternativo) |
| `paid` | Pagada (string alternativo) |
| `overdue` | Vencida (string alternativo) |

---

---

## 🌉 SISTEMA V2 — CONTRATOS DEL MÓDULO DE SUSCRIPCIONES

> **Implementado en:** Misiones #15–#24  
> **Última actualización:** 2026-04-29 (Misión #27)  
> **Arquitectura:** Strangler Fig Pattern — SPA ligera + API Legacy como backend

---

### CONFIGURACIÓN DE ENTORNO (`.env`)

| Variable | Producción | XAMPP Local | Descripción |
|---|---|---|---|
| `V2_FRONTEND_BASE` | `""` (vacío) | `http://localhost/brokersconnect_dev/public_html/newbrokers` | Prefijo URL de la SPA V2. Vacío = same-origin |
| `V2_API_BASE` | `""` (vacío) | `http://localhost/brokersconnect_dev/public_html` | Prefijo URL de la API Legacy. Vacío = same-origin |
| `OPENPAY_PLAN_BASICO` | ID del plan en OpenPay | igual | `plan_basico_mensual` por defecto |
| `OPENPAY_PLAN_PROFESIONAL` | ID del plan en OpenPay | igual | `plan_profesional_mensual` por defecto |
| `OPENPAY_PLAN_ENTERPRISE` | ID del plan en OpenPay | igual | `plan_enterprise_mensual` por defecto |

> **Entry point real de Laravel en XAMPP local:** `public_html/index.php` (NO `brokers_new/public/`).
> El `.htaccess` de `public_html/` tiene `RewriteBase /brokersconnect_dev/public_html/`.

---

### `GET /api/v2/bridge/validate?token={TOKEN}`

**Intercambia el bridge token de 60 s por un session_token de 30 min. Destruye el bridge token al primer uso (anti-replay).**

- **Middleware:** Ninguno (el token ES la autenticación)
- **Controlador:** `Api\V2BridgeController@bridgeValidate`
- **Query param:** `token` (string, 64 chars, generado por `BridgeController::generateBridgeToken()`)

**Response 200 — Token válido:**
```json
{
  "success": true,
  "session_token": "string — 64 chars, TTL 30 min en Cache",
  "company": {
    "id":      "integer",
    "name":    "string",
    "email":   "string",
    "package": "integer — FK services.id del plan actual"
  },
  "plans": [
    {
      "id":             "integer",
      "service":        "string — nombre del plan",
      "price":          "numeric",
      "users_included": "integer",
      "user_price":     "numeric",
      "days_trial":     "integer"
    }
  ],
  "openpay": {
    "id":         "string — OPENPAY_ID del .env",
    "public_key": "string — OPENPAY_KEY_PUBLIC del .env",
    "sandbox":    "boolean — !OPENPAY_PRODUCTION"
  }
}
```

**Response 400 — Token ausente:**
```json
{ "success": false, "error": "Token requerido." }
```

**Response 401 — Token inválido o expirado (>60 s o ya usado):**
```json
{ "success": false, "error": "Enlace inválido o expirado. Regresa al panel e intenta de nuevo." }
```

**Response 404 — Compañía no encontrada:**
```json
{ "success": false, "error": "Cuenta no encontrada." }
```

**⚠ REGLA DE ORO:** El bridge token se quema con `Cache::forget()` ANTES de retornar cualquier dato. Si el Cache falla después del forget, el token está igualmente destruido. No existe segunda oportunidad de uso.

---

### `POST /api/v2/subscriptions`

**Crea una suscripción recurrente en OpenPay. Requiere session_token como Bearer. Destruye el session_token tras el éxito (anti-doble-cobro).**

- **Middleware:** Ninguno de Laravel — la autenticación es vía `Authorization: Bearer {session_token}` validado contra Cache
- **Controlador:** `Api\V2BridgeController@subscribe`
- **Headers requeridos:**
  - `Authorization: Bearer {session_token}`
  - `Content-Type: application/json`

**Payload (JSON):**
```json
{
  "plan_id":   "integer|required — FK services.id (1,2,3)",
  "token_id":  "string|required — token de tarjeta generado por OpenPay.js en el cliente",
  "device_id": "string|required — device_session_id generado por openpay-data.js"
}
```

**Response 200 — Suscripción activada:**
```json
{
  "success":         true,
  "message":         "¡Suscripción activada! Bienvenido a Brokers Connector.",
  "subscription_id": "string — ID de suscripción en OpenPay (persiste en companies.openpay_subscription_id)"
}
```

**Response 401 — Sin token / sesión expirada:**
```json
{ "success": false, "error": "No autenticado." }
{ "success": false, "error": "Sesión expirada. Regresa al panel e intenta de nuevo." }
```

**Response 422 — Datos incompletos o error de tarjeta:**
```json
{ "success": false, "error": "Datos de pago incompletos." }
{ "success": false, "error": "Tu tarjeta fue rechazada. Verifica los datos o usa otra tarjeta." }
```

**Response 503 — Sin conexión con OpenPay:**
```json
{ "success": false, "error": "Sin conexión con el procesador de pagos. Intente de nuevo." }
```

**Mapa plan_id → OpenPay Plan ID:**

| `plan_id` | Variable `.env` | Valor por defecto |
|---|---|---|
| 1 | `OPENPAY_PLAN_BASICO` | `plan_basico_mensual` |
| 2 | `OPENPAY_PLAN_PROFESIONAL` | `plan_profesional_mensual` |
| 3 | `OPENPAY_PLAN_ENTERPRISE` | `plan_enterprise_mensual` |

**⚠ REGLA PCI DSS:** Los datos de tarjeta NUNCA tocan este endpoint. El `token_id` es un token opaco de un solo uso generado por `OpenPay.token.extractFormAndCreate()` en el cliente. El número de tarjeta nunca sale del browser.

---

### `GET /home/v2/subscription-bridge`
**Genera el bridge token y redirige al módulo V2 de Suscripciones.**

- **Middleware:** `auth` (sesión Legacy — sin `companyPayment` para permitir renovar cuenta vencida)
- **Controlador:** `BridgeController@subscriptionBridge`
- **Comportamiento:** Lee `V2_FRONTEND_BASE` y `V2_API_BASE` del `.env`. Redirige a:
  ```
  {V2_FRONTEND_BASE}/v2/subscriptions/index.html?token={TOKEN}[&api={V2_API_BASE}]
  ```
  El parámetro `&api=` solo se adjunta si `V2_API_BASE` no está vacío.

---

### `GET /home/v2/logout`
**Destruye la sesión Laravel desde la SPA V2 (GET para evitar CSRF desde HTML estático).**

- **Middleware:** `auth`
- **Comportamiento:** `Auth::logout()` + `redirect('/')`
- **Seguridad:** Solo usuarios autenticados pueden invocarla. El GET es intencional — la cookie de sesión acompaña el request automáticamente al ser mismo origen.

---

### CICLO DE VIDA COMPLETO DE TOKENS

```
[Usuario en Legacy panel]
      ↓ clic en "Suscripción y Facturación"
GET /home/v2/subscription-bridge  (auth middleware)
      ↓
BridgeController genera bridge_token (64 chars, TTL 60s)
Cache::put('v2_bridge_{token}', {user_id, company_id}, 60)
      ↓ redirect
/v2/subscriptions/index.html?token={BRIDGE_TOKEN}&api={API_BASE}
      ↓
[SPA JS] lee ?token= y ?api= de la URL
      ↓
GET {API_BASE}/api/v2/bridge/validate?token={BRIDGE_TOKEN}
      ↓ servidor: Cache::get → datos → Cache::forget (QUEMAR BRIDGE TOKEN)
      ↓ servidor: genera session_token (64 chars, TTL 1800s)
      ↓
[SPA JS] state.sessionToken = session_token (en memoria, NO localStorage)
      ↓ usuario selecciona plan + ingresa tarjeta
[OpenPay.js] tokeniza tarjeta → devuelve card_token (nunca pasa por nuestro servidor)
      ↓
POST {API_BASE}/api/v2/subscriptions
  Authorization: Bearer {session_token}
  Body: { plan_id, token_id: card_token, device_id }
      ↓ servidor: valida session, crea suscripción en OpenPay, guarda IDs
      ↓ servidor: Cache::forget session_token (QUEMAR — anti-doble-cobro)
      ↓
[SPA JS] pantalla de éxito → countdown 5s → redirect /home
```

---

### ESTADO DEL MÓDULO LEGACY DE SUSCRIPCIONES (Paralelo — No Eliminado)

Las rutas `/home/subscription` (GET/POST) y la vista `companies/subscription.blade.php` siguen activas como **fallback administrativo** pero NO son el flujo primario. El menú lateral apunta exclusivamente a `v2.subscription.bridge`. No constituyen dead code — son red de seguridad.

| Ruta | Estado | Rol |
|---|---|---|
| `GET /home/v2/subscription-bridge` | ✅ Primaria | Punto de entrada oficial |
| `GET /home/subscription` | ⚠ Fallback | Accesible por URL directa, no vinculada en menú |
| `POST /home/subscription` | ⚠ Fallback | Procesa formulario Legacy si se usa el fallback |
