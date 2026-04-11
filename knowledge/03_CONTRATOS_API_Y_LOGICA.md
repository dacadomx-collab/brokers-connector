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

## 🌐 ENDPOINTS WEB (Panel Interno — requieren sesión)

> Todos los siguientes requieren el triple middleware: `auth` + `company` + `companyPayment`

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
