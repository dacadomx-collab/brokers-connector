# 🧬 SYSTEM CODEX & REGISTRY — BROKERS CONNECTOR (DICCIONARIO DE ORO)

> **Fuente de verdad extraída de:** `knowledge/tourfindycom_newbrokers_db.sql` (dump de producción, 2026-06-03)
> **Framework:** Laravel 5.8 (PHP 8.4) — Arquitectura multitenant por `company_id`
> **⚠️ PROHIBIDO** usar el dump antiguo `BackUp/brokersconnect_bd.sql` — fue reemplazado por este dump de producción.

---

## 📊 MAPEO DE VARIABLES CLAVE (DB → FRONTEND)

| Concepto | DB / Backend (snake_case) | Tipo de Dato | Notas |
| :--- | :--- | :--- | :--- |
| ID compañía | `company_id` | BIGINT UNSIGNED | Llave de tenant en casi todas las tablas |
| Nombre de agente | `full_name` + `last_name` | VARCHAR(255) | Dos campos separados en `users` |
| Estado de usuario | `active` | BOOLEAN (1/0) | En `users` |
| Rol de usuario | vía `roles` + `model_has_roles` | Spatie RBAC | No es un campo directo en `users` |
| Estado de propiedad | `prop_status_id` → `property_statuses.name` | BIGINT FK | Valores: catálogo editable |
| Tipo de propiedad | `prop_type_id` → `property_types.name` | BIGINT FK | Valores: catálogo editable |
| Uso de propiedad | `prop_use_id` → `property_uses.name` | BIGINT FK | Nullable |
| Agente asignado | `agent_id` → `users.id` | BIGINT FK | En `contacts` y `properties` |
| Imagen destacada | `featured_image` → `file_properties.id` | BIGINT FK | Self-reference dentro de `file_properties` |
| Portales activos | `property_stocks` (24_7, aspi, ampi) | TINYINT flags | Tabla auxiliar por propiedad |
| Soft delete | `deleted_at` | TIMESTAMP NULL | En: `users`, `contacts`, `properties`, `invoices` |
| Hilo de chat IA | `conversation_id` → `ai_conversations.id` | BIGINT FK | En `ai_messages`; aislado por `company_id` |
| Rol de mensaje IA | `role` | ENUM('user','assistant','system') | En `ai_messages`; define el emisor |
| Tokens consumidos | `tokens_used` | INT DEFAULT 0 | En `ai_messages`; para auditoría de costos |
| Cliente OpenPay | `openpay_customer_id` | VARCHAR(64) NULL | En `companies`; vincula el tenant con su perfil en OpenPay |
| Suscripción OpenPay | `openpay_subscription_id` | VARCHAR(64) NULL | En `companies`; referencia la suscripción recurrente activa |

> **NOTA DE FUNDACIÓN:** Todas las conexiones a BD deben realizarse a través de la clase centralizada, leyendo variables del archivo `.env`.

---

## 🗄️ ESTRUCTURA DE TABLAS (SCHEMA COMPLETO)

---

### 🏢 Tabla: `companies`
> Cada inmobiliaria/agencia que contrata el CRM. Raíz del modelo multitenant.

| Columna | Tipo | Notas |
| :--- | :--- | :--- |
| `id` | BIGINT UNSIGNED PK | Auto-increment |
| `api_key` | VARCHAR(80) UNIQUE NULL | Clave de integración externa |
| `name` | VARCHAR(255) | Nombre de la agencia |
| `phone` | VARCHAR(255) | Teléfono corporativo |
| `address` | VARCHAR(255) | Dirección física |
| `rfc` | VARCHAR(255) | RFC fiscal (México) |
| `colony` | VARCHAR(255) | Colonia |
| `zipcode` | VARCHAR(255) | Código postal |
| `email` | VARCHAR(255) | Correo corporativo |
| `package` | INT NULL | Plan contratado |
| `cutoff_date` | DATETIME NULL | Fecha de corte de facturación |
| `dominio` | VARCHAR(255) NULL | Dominio del sitio web |
| `logo` | VARCHAR(255) NULL | Ruta del logotipo |
| `banner` | VARCHAR(255) NULL | Ruta del banner |
| `cover` | VARCHAR(255) NULL | Imagen de portada |
| `team` | VARCHAR(255) NULL | Foto de equipo |
| `about` | TEXT NULL | Descripción de la empresa |
| `website_config` | TEXT NULL | Configuración JSON del sitio web |
| `owner` | BIGINT UNSIGNED | ID del usuario propietario (FK implícita a `users`) |
| `active` | INT UNSIGNED | 1 = activa, 0 = inactiva |
| `openpay_customer_id` | VARCHAR(64) NULL | ID del cliente en OpenPay. Permite trazabilidad, tarjetas guardadas y cargos recurrentes. Se asigna al primer cobro. |
| `openpay_subscription_id` | VARCHAR(64) NULL | ID de la suscripción activa en OpenPay. Permite gestión del ciclo de facturación recurrente y cancelación. |
| `created_at` / `updated_at` | TIMESTAMP NULL | Timestamps Laravel |

---

### 👤 Tabla: `users`
> Agentes y administradores del CRM. Pertenecen a una `company`.

| Columna | Tipo | Notas |
| :--- | :--- | :--- |
| `id` | BIGINT UNSIGNED PK | Auto-increment |
| `full_name` | VARCHAR(255) NULL | Nombre(s) |
| `last_name` | VARCHAR(255) NULL | Apellido(s) |
| `email` | VARCHAR(255) | Email (login) |
| `signature_email` | LONGTEXT NULL | Firma HTML para correos |
| `email_verified_at` | TIMESTAMP NULL | Verificación email |
| `password` | VARCHAR(255) NULL | Hash bcrypt |
| `phone` | VARCHAR(255) NULL | Teléfono del agente |
| `avatar` | VARCHAR(255) NULL | Ruta de foto de perfil |
| `title` | VARCHAR(255) NULL | Cargo / título profesional |
| `active` | BOOLEAN DEFAULT 1 | Estado de cuenta |
| `verified` | BOOLEAN DEFAULT 0 | Verificación interna |
| `token` | VARCHAR(255) NULL | Token de activación/reset |
| `company_id` | BIGINT UNSIGNED NULL | FK → `companies.id` |
| `deleted_at` | TIMESTAMP NULL | **Soft Delete** |
| `remember_token` | VARCHAR(100) NULL | Cookie de sesión |
| `created_at` / `updated_at` | TIMESTAMP NULL | Timestamps Laravel |

---

### 📇 Tabla: `contacts`
> Prospectos / clientes de cada agencia. CRM central.

| Columna | Tipo | Notas |
| :--- | :--- | :--- |
| `id` | BIGINT UNSIGNED PK | Auto-increment |
| `name` | VARCHAR(255) | Nombre del contacto |
| `surname` | VARCHAR(255) NULL | Apellido |
| `job` | VARCHAR(255) NULL | Ocupación / empresa |
| `email` | VARCHAR(255) | Email del prospecto |
| `address` | VARCHAR(255) NULL | Dirección |
| `origin` | INT DEFAULT 0 | Origen del lead (catálogo numérico) |
| `status` | INT DEFAULT 0 | Estado del prospecto (catálogo numérico) |
| `type` | INT DEFAULT 0 | Tipo de contacto (catálogo numérico) |
| `otros` | VARCHAR(255) NULL | Campo libre adicional |
| `company_id` | BIGINT UNSIGNED | FK → `companies.id` |
| `agent_id` | BIGINT UNSIGNED NULL | FK → `users.id` (agente asignado) |
| `deleted_at` | TIMESTAMP NULL | **Soft Delete** |
| `created_at` / `updated_at` | TIMESTAMP NULL | Timestamps Laravel |

---

### 📞 Tabla: `contact_phones`
> Teléfonos de un contacto (uno a muchos).

| Columna | Tipo | Notas |
| :--- | :--- | :--- |
| `id` | BIGINT UNSIGNED PK | Auto-increment |
| `phone` | VARCHAR(255) | Número telefónico |
| `type` | VARCHAR(255) | Tipo: `celular`, `oficina`, `casa`, etc. |
| `contact_id` | BIGINT UNSIGNED | FK → `contacts.id` |
| `created_at` / `updated_at` | TIMESTAMP NULL | Timestamps Laravel |

---

### 📝 Tabla: `contact_notes`
> Notas/actividades registradas sobre un contacto (historial CRM).

| Columna | Tipo | Notas |
| :--- | :--- | :--- |
| `id` | BIGINT UNSIGNED PK | Auto-increment |
| `contact_id` | BIGINT UNSIGNED | FK → `contacts.id` |
| `user_id` | BIGINT UNSIGNED NULL | FK → `users.id` (agente que registró) |
| `type` | INT UNSIGNED | Tipo de nota (catálogo numérico) |
| `content` | MEDIUMTEXT | Contenido de la nota |
| `created_at` / `updated_at` | TIMESTAMP NULL | Timestamps Laravel |

---

### 🔗 Tabla: `contact_properties`
> Pivot — vincula un contacto con propiedades de su interés.

| Columna | Tipo | Notas |
| :--- | :--- | :--- |
| `id` | BIGINT UNSIGNED PK | Auto-increment |
| `contact_id` | BIGINT UNSIGNED | FK → `contacts.id` |
| `property_id` | BIGINT UNSIGNED | FK → `properties.id` |
| `content` | MEDIUMTEXT NULL | Comentario sobre el interés |
| `created_at` / `updated_at` | TIMESTAMP NULL | Timestamps Laravel |

---

### 🏠 Tabla: `properties`
> Propiedades inmobiliarias registradas por cada agencia. Tabla central del CRM.

| Columna | Tipo | Notas |
| :--- | :--- | :--- |
| `id` | BIGINT UNSIGNED PK | Auto-increment |
| `title` | MEDIUMTEXT | Título (español) |
| `title_en` | MEDIUMTEXT NULL | Título (inglés) |
| `description` | LONGTEXT NULL | Descripción (español) |
| `description_en` | LONGTEXT NULL | Descripción (inglés) |
| `bedrooms` | INT NULL DEFAULT 0 | Recámaras |
| `baths` | INT NULL DEFAULT 0 | Baños completos |
| `medium_baths` | INT NULL DEFAULT 0 | Medios baños |
| `floor` | INT NULL | Número de pisos totales |
| `floor_located` | INT NULL | Piso donde se ubica la unidad |
| `parking_lots` | INT NULL DEFAULT 0 | Lugares de estacionamiento |
| `total_area` | DOUBLE DEFAULT 0 | Superficie total (m²) |
| `built_area` | DOUBLE DEFAULT 0 | Superficie construida (m²) |
| `length` | DOUBLE DEFAULT 0 | Fondo del terreno |
| `front` | DOUBLE DEFAULT 0 | Frente del terreno |
| `price` | DOUBLE | Precio de la propiedad |
| `currency` | INT UNSIGNED | Moneda: catálogo numérico (MXN/USD) |
| `local_id` | MEDIUMTEXT NULL | ID interno de la agencia |
| `lng` / `lat` | VARCHAR(255) NULL | Coordenadas GPS |
| `address` | VARCHAR(255) NULL | Dirección |
| `exterior` | VARCHAR(100) DEFAULT 's/n' | Número exterior |
| `interior` | VARCHAR(100) DEFAULT 's/n' | Número interior |
| `zipcode` | INT UNSIGNED NULL | Código postal |
| `commission` | VARCHAR(255) NULL | Valor de comisión |
| `type_commission` | INT UNSIGNED NULL | Tipo: % o monto fijo |
| `antiquity` | INT NULL | Antigüedad en años |
| `key` | LONGTEXT NULL | Clave/código interno |
| `featured_image` | BIGINT UNSIGNED NULL | FK → `file_properties.id` |
| `video` | VARCHAR(255) NULL | URL de video |
| `published` | BOOLEAN DEFAULT 1 | Publicada en portales |
| `featured` | BOOLEAN DEFAULT 0 | Destacada |
| `bbc_general` | BOOLEAN DEFAULT 0 | Compartida en bolsa BBC General |
| `bbc_aspi` | BOOLEAN DEFAULT 0 | Compartida en ASPI |
| `bbc_ampi` | BOOLEAN DEFAULT 0 | Compartida en AMPI |
| `company_id` | BIGINT UNSIGNED | FK → `companies.id` |
| `prop_status_id` | BIGINT UNSIGNED | FK → `property_statuses.id` |
| `prop_type_id` | BIGINT UNSIGNED | FK → `property_types.id` |
| `prop_use_id` | BIGINT UNSIGNED NULL | FK → `property_uses.id` |
| `agent_id` | BIGINT UNSIGNED NULL | FK → `users.id` (responsable) |
| `created_by` | BIGINT UNSIGNED | FK → `users.id` (creó el registro) |
| `deleted_at` | TIMESTAMP NULL | **Soft Delete** |
| `created_at` / `updated_at` | TIMESTAMP NULL | Timestamps Laravel |

---

### 🖼️ Tabla: `file_properties`
> Fotos y archivos multimedia de una propiedad.

| Columna | Tipo | Notas |
| :--- | :--- | :--- |
| `id` | BIGINT UNSIGNED PK | Auto-increment |
| `property_id` | BIGINT UNSIGNED | FK → `properties.id` (CASCADE DELETE) |
| `src` | VARCHAR(255) | Ruta o URL del archivo |
| `thumbnail` | VARCHAR(255) NULL | Ruta de la miniatura |
| `type` | INT UNSIGNED | Tipo: foto, video, plano, etc. |
| `index_order` | BIGINT NULL | Orden de visualización |
| `created_at` / `updated_at` | TIMESTAMP NULL | Timestamps Laravel |

---

### ✨ Tabla: `features`
> Catálogo de características/amenidades. Árbol con auto-referencia.

| Columna | Tipo | Notas |
| :--- | :--- | :--- |
| `id` | BIGINT UNSIGNED PK | Auto-increment |
| `parent_id` | BIGINT UNSIGNED NULL | FK → `features.id` (agrupa características) |
| `name` | VARCHAR(255) | Ej: "Alberca", "Gym", "Seguridad 24h" |
| `created_at` / `updated_at` | TIMESTAMP NULL | Timestamps Laravel |

---

### 🔗 Tabla: `feature_properties`
> Pivot — características asignadas a una propiedad. PK compuesta.

| Columna | Tipo | Notas |
| :--- | :--- | :--- |
| `feature_id` | BIGINT UNSIGNED | FK → `features.id` (CASCADE DELETE) |
| `property_id` | BIGINT UNSIGNED | FK → `properties.id` |
| — | PK compuesta | `(feature_id, property_id)` |

---

### 📋 Catálogos de Propiedad

#### Tabla: `property_types`
> Tipos de inmueble: Casa, Departamento, Local, Terreno, etc.

| Columna | Tipo | Notas |
| :--- | :--- | :--- |
| `id` | BIGINT UNSIGNED PK | |
| `name` | VARCHAR(255) | Nombre del tipo |
| `luly` | BOOLEAN NULL | Flag portal interno |
| `propiedades` | VARCHAR(100) NULL | Equivalencia portal Propiedades.com |
| `gran_inmobiliaria` | VARCHAR(100) NULL | Equivalencia Gran Inmobiliaria |
| `lamudi` | VARCHAR(100) NULL | Equivalencia Lamudi |
| `created_at` / `updated_at` | TIMESTAMP NULL | |

#### Tabla: `property_statuses`
> Estados de operación: Venta, Renta, Venta/Renta, etc.

| Columna | Tipo | Notas |
| :--- | :--- | :--- |
| `id` | BIGINT UNSIGNED PK | |
| `name` | VARCHAR(255) | Nombre del estado |
| `luly` | BOOLEAN NULL | Flag portal interno |
| `propiedades` | VARCHAR(100) NULL | Equivalencia portal Propiedades.com |
| `gran_inmobiliaria` | VARCHAR(100) NULL | Equivalencia Gran Inmobiliaria |
| `lamudi` | VARCHAR(100) NULL | Equivalencia Lamudi |
| `created_at` / `updated_at` | TIMESTAMP NULL | |

#### Tabla: `property_uses`
> Uso del inmueble: Habitacional, Comercial, Industrial, etc.

| Columna | Tipo | Notas |
| :--- | :--- | :--- |
| `id` | BIGINT UNSIGNED PK | |
| `name` | VARCHAR(255) | Nombre del uso |
| `luly` | BOOLEAN NULL | Flag portal interno |
| `created_at` / `updated_at` | TIMESTAMP NULL | |

---

### 📡 Tabla: `property_stocks`
> Flags de publicación en portales externos por propiedad.

| Columna | Tipo | Notas |
| :--- | :--- | :--- |
| `id` | BIGINT UNSIGNED PK | |
| `property_id` | BIGINT UNSIGNED | FK → `properties.id` |
| `24_7` | TINYINT DEFAULT 0 | Portal 24-7 Inmuebles |
| `aspi` | TINYINT DEFAULT 0 | Portal ASPI |
| `ampi` | TINYINT DEFAULT 0 | Portal AMPI |
| `created_at` / `updated_at` | TIMESTAMP NULL | |

---

### 💳 Tabla: `invoices`
> Facturas/suscripciones de cada compañía al SaaS.

| Columna | Tipo | Notas |
| :--- | :--- | :--- |
| `id` | BIGINT UNSIGNED PK | |
| `name` | VARCHAR(255) | Nombre del plan |
| `cost_package` | DOUBLE | Costo del paquete |
| `cost_user` | DOUBLE | Costo por usuario adicional |
| `users` | INT UNSIGNED NULL DEFAULT 0 | Usuarios contratados |
| `status` | VARCHAR(255) | Estado: `pending`, `paid`, `overdue`, etc. |
| `charge_id` | VARCHAR(255) NULL | ID de cobro externo (Conekta/Stripe) |
| `payday` | DATETIME NULL | Fecha de pago efectivo |
| `due_date` | DATETIME | Fecha límite de pago |
| `company_id` | BIGINT UNSIGNED | FK → `companies.id` |
| `deleted_at` | TIMESTAMP NULL | **Soft Delete** |
| `created_at` / `updated_at` | TIMESTAMP NULL | |

---

### 🛒 Tabla: `services`
> Planes/paquetes disponibles en el SaaS.

| Columna | Tipo | Notas |
| :--- | :--- | :--- |
| `id` | BIGINT UNSIGNED PK | |
| `service` | VARCHAR(255) | Nombre del plan |
| `price` | DOUBLE NULL | Precio del paquete |
| `user_price` | DOUBLE NULL | Precio por usuario adicional |
| `days_trial` | INT NULL | Días de prueba gratuita |
| `users_included` | INT NULL | Usuarios incluidos en el plan base |
| `created_at` / `updated_at` | TIMESTAMP NULL | |

---

### 🔗 Tabla: `invoices_services`
> Pivot — servicios incluidos en cada factura.

| Columna | Tipo | Notas |
| :--- | :--- | :--- |
| `id` | BIGINT UNSIGNED PK | |
| `invoice_id` | BIGINT UNSIGNED | FK → `invoices.id` |
| `service_id` | BIGINT UNSIGNED | FK → `services.id` |
| `price` | DOUBLE | Precio al momento de contratación (snapshot) |
| `created_at` / `updated_at` | TIMESTAMP NULL | |

---

### 🗺️ Geografía

#### Tabla: `states`
> Catálogo de estados/provincias.

| Columna | Tipo | Notas |
| :--- | :--- | :--- |
| `id` | BIGINT UNSIGNED PK | |
| `name` | VARCHAR(255) | Nombre del estado |
| *(sin timestamps)* | — | Solo id + name |

#### Tabla: `cities`
> Ciudades, vinculadas a un estado.

| Columna | Tipo | Notas |
| :--- | :--- | :--- |
| `id` | BIGINT UNSIGNED PK | |
| `name` | VARCHAR(255) | Nombre de la ciudad |
| `state_id` | BIGINT UNSIGNED | FK → `states.id` |
| *(sin timestamps)* | — | Solo id + name + FK |

#### Tabla: `districts`
> Colonias / municipios, vinculados a una ciudad.

| Columna | Tipo | Notas |
| :--- | :--- | :--- |
| `id` | BIGINT UNSIGNED PK | |
| `name` | VARCHAR(255) | Nombre de la colonia/municipio |
| `city_id` | BIGINT | FK implícita → `cities.id` |
| `created_at` / `updated_at` | TIMESTAMP NULL | |

---

### 🔐 Sistema de Permisos (Spatie Laravel Permission)

#### Tabla: `permissions`
| Columna | Tipo |
| :--- | :--- |
| `id` | INT PK |
| `name` | VARCHAR(255) — nombre del permiso |
| `guard_name` | VARCHAR(255) — `web` o `api` |
| `created_at` / `updated_at` | TIMESTAMP |

#### Tabla: `roles`
| Columna | Tipo |
| :--- | :--- |
| `id` | INT PK |
| `name` | VARCHAR(255) — nombre del rol |
| `display_name` | VARCHAR(255) NULL — nombre legible |
| `guard_name` | VARCHAR(255) |
| `created_at` / `updated_at` | TIMESTAMP |

**Catálogo de roles activos (guard_name: web):**
| ID | name | display_name | Nivel de acceso |
| :--- | :--- | :--- | :--- |
| 1 | `Admin` | Propietario | Panel CRM completo por tenant |
| 2 | `Agent` | Agente | Acceso operativo limitado |
| 3 | `super_admin` | Super Administrador | Panel V2 Admin — gestión de roles y credenciales |

> **Regla:** Solo un `super_admin` puede promover/degradar Admins. Nunca se elimina el usuario de la BD. El puente de acceso es `GET /home/v2/admin-bridge` (middleware `role:super_admin`).

#### Tabla: `model_has_permissions`
> Permisos directos asignados a un modelo (polimórfico).

| Columna | Tipo |
| :--- | :--- |
| `permission_id` | INT FK → `permissions.id` |
| `model_type` | VARCHAR(255) — ej. `App\User` |
| `model_id` | BIGINT UNSIGNED |
| PK compuesta | `(permission_id, model_id, model_type)` |

#### Tabla: `model_has_roles`
> Roles asignados a un modelo (polimórfico).

| Columna | Tipo |
| :--- | :--- |
| `role_id` | INT FK → `roles.id` |
| `model_type` | VARCHAR(255) |
| `model_id` | BIGINT UNSIGNED |
| PK compuesta | `(role_id, model_id, model_type)` |

#### Tabla: `role_has_permissions`
> Permisos asignados a un rol.

| Columna | Tipo |
| :--- | :--- |
| `permission_id` | INT FK → `permissions.id` |
| `role_id` | INT FK → `roles.id` |
| PK compuesta | `(permission_id, role_id)` |

---

### 🔑 Autenticación OAuth (Laravel Passport)

| Tabla | Propósito |
| :--- | :--- |
| `oauth_access_tokens` | Tokens de acceso emitidos |
| `oauth_auth_codes` | Códigos de autorización OAuth |
| `oauth_clients` | Aplicaciones cliente registradas |
| `oauth_personal_access_clients` | Clientes de acceso personal |
| `oauth_refresh_tokens` | Tokens de refresco |
| `password_resets` | Tokens para reset de contraseña |

---

---

### 🤖 Tabla: `ai_conversations`
> Hilos de chat IA por tenant. Raíz del módulo de Inteligencia Artificial.

| Columna | Tipo | Notas |
| :--- | :--- | :--- |
| `id` | BIGINT UNSIGNED PK | Auto-increment |
| `company_id` | BIGINT UNSIGNED — INDEX | FK → `companies.id` **[TENANT LOCK]** |
| `user_id` | BIGINT UNSIGNED NULL | FK → `users.id` (agente que inició el hilo) |
| `title` | VARCHAR(255) | Título del hilo de conversación |
| `status` | BOOLEAN DEFAULT 1 | 1 = activa, 0 = archivada |
| `created_at` / `updated_at` | TIMESTAMP NULL | Timestamps Laravel |

---

### 💬 Tabla: `ai_messages`
> Mensajes individuales de un hilo IA. Inmutables post-creación.

| Columna | Tipo | Notas |
| :--- | :--- | :--- |
| `id` | BIGINT UNSIGNED PK | Auto-increment |
| `conversation_id` | BIGINT UNSIGNED | FK → `ai_conversations.id` CASCADE DELETE |
| `role` | ENUM('user','assistant','system') | Emisor del mensaje |
| `content` | LONGTEXT | Cuerpo del mensaje |
| `tokens_used` | INT DEFAULT 0 | Tokens consumidos (auditoría de costos) |
| `created_at` / `updated_at` | TIMESTAMP NULL | Timestamps Laravel |

---

### ⚙️ Tabla: `ai_settings`
> Configuración del Orquestador IA con Failover Dinámico. Patrón Strategy.

| Columna | Tipo | Notas |
| :--- | :--- | :--- |
| `id` | INT UNSIGNED PK | Auto-increment |
| `provider_name` | VARCHAR(50) | Identificador del adaptador: `openai`, `groq`, `mistral`, `gemini` |
| `api_key` | TEXT NOT NULL | Clave API **cifrada con `Crypt::encryptString()`** — nunca texto plano |
| `extra_config` | JSON NULL | Config adicional: `{"model":"gpt-4o-mini", "endpoint":"..."}` |
| `priority_order` | INT(11) DEFAULT 1 | 1 = mayor prioridad en la escalera de failover |
| `is_active` | BOOLEAN DEFAULT 1 | 0 = excluido del orquestador |
| `company_id` | INT UNSIGNED NULL | FK → `companies.id`. NULL = configuración global del sistema |
| `last_tested_at` | TIMESTAMP NULL | Fecha/hora del último ping de conectividad |
| `last_test_status` | VARCHAR(10) NULL | Resultado del último ping: `ok` o `error` |
| `last_test_latency_ms` | INT UNSIGNED NULL | Latencia en ms del último ping exitoso |
| `last_test_error` | TEXT NULL | Mensaje de error sanitizado del último ping fallido |
| `created_at` / `updated_at` | TIMESTAMP NULL | Timestamps Laravel |

> **REGLA DE ORO:** `api_key` se guarda con `Crypt::encryptString()` en `store`/`update`. Se recupera con `decryptedKey()` del Modelo (`Crypt::decryptString()`) — nunca en vistas. La vista recibe solo `api_key_masked`.
>
> **ADAPTADORES registrados:** `openai` → `OpenAIProvider`, `groq` → `GroqProvider`, `mistral` → `MistralProvider`, `gemini` → `GeminiProvider`.

---

### 🔍 Tabla: `audit_logs`
> Registro de auditoría inmutable de acciones del Super Admin.
>
> **REGLA INQUEBRANTABLE:** Todo endpoint de escritura del `SuperAdminController` DEBE insertar un registro aquí. Sin log = operación incompleta.
>
> **SCHEMA DE PRODUCCIÓN REAL** (verificado en dump 2026-06-03):

| Columna | Tipo | Notas |
| :--- | :--- | :--- |
| `id` | BIGINT UNSIGNED PK | Auto-increment |
| `company_id` | BIGINT UNSIGNED NOT NULL | FK → `companies.id` — empresa afectada |
| `super_admin_id` | BIGINT UNSIGNED NOT NULL | FK → `users.id` — Super Admin que ejecutó la acción |
| `action` | VARCHAR(255) | Descripción de la acción ejecutada |
| `created_at` / `updated_at` | TIMESTAMP NULL | Timestamps Laravel |

> **⚠️ ADVERTENCIA DE DISCREPANCIA:** El Codex anterior listaba columnas adicionales (`actor_id`, `actor_email`, `target_type`, etc.) que NO existen en producción. Esas columnas son del modelo de aplicación del SuperAdminController, no de la tabla real. Usar solo las 5 columnas arriba listadas.

---

---

## 🤖 MÓDULO IA — TABLAS NUEVAS (SCHEMA DE PRODUCCIÓN 2026-06-03)

> Estas 6 tablas **no existían en el Codex anterior**. Verificadas en el dump de producción.

---

### 📋 Tabla: `ai_prompts`
> Synaptic Core™ — Prompts maestros del Motor Cognitivo AVM. Editables vía panel Super Admin.

| Columna | Tipo | Notas |
| :--- | :--- | :--- |
| `id` | INT UNSIGNED PK | Auto-increment |
| `slug` | VARCHAR(80) UNIQUE | Identificador único. Se usa `slug` (no `key` — palabra reservada MySQL) |
| `name` | VARCHAR(120) | Nombre descriptivo del prompt |
| `prompt_text` | TEXT NULL | Prompt monolítico legacy. El Compiler Engine lo usa si `system_role` está vacío |
| `system_role` | TEXT NULL | Módulo 1: Identidad y rol del agente IA |
| `business_context` | TEXT NULL | Módulo 2: Contexto situacional del negocio |
| `immutable_rules` | TEXT NULL | Módulo 3: Reglas que el modelo no puede violar |
| `tone_profile` | JSON NULL | Módulo 4: `{"language","formality","perspective","style"[]}` |
| `output_schema` | JSON NULL | Módulo 5: Estructura JSON obligatoria de la respuesta |
| `variables_schema` | JSON NULL | Módulo 6: `[{"key","label","type","required"}]` — variables inyectables |
| `preferred_model` | VARCHAR(80) NULL | Modelo preferido: ej. `gpt-4o-mini`. NULL = predeterminado del sistema |
| `version` | SMALLINT UNSIGNED DEFAULT 1 | Contador de versiones. Auto-incrementa en cada guardado |
| `is_active` | BOOLEAN DEFAULT 1 | 0 = prompt desactivado |
| `created_at` / `updated_at` | TIMESTAMP NULL | Timestamps Laravel |

**Slug maestro del CMA:** `cma_urban_intelligence` — inyectado por `AiPrompt::compileBySlug()` en `BrokerBrainController::synthesizeFromAI()`.

---

### 🔖 Tabla: `ai_prompt_versions`
> Historial de versiones de cada prompt maestro (auditoría de cambios).

| Columna | Tipo | Notas |
| :--- | :--- | :--- |
| `id` | INT UNSIGNED PK | Auto-increment |
| `prompt_id` | INT UNSIGNED | FK → `ai_prompts.id` |
| `version` | SMALLINT UNSIGNED DEFAULT 1 | Número de versión del snapshot |
| `system_role` | TEXT NULL | Snapshot del módulo 1 |
| `business_context` | TEXT NULL | Snapshot del módulo 2 |
| `immutable_rules` | TEXT NULL | Snapshot del módulo 3 |
| `tone_profile` | JSON NULL | Snapshot del módulo 4 |
| `output_schema` | JSON NULL | Snapshot del módulo 5 |
| `variables_schema` | JSON NULL | Snapshot del módulo 6 |
| `preferred_model` | VARCHAR(80) NULL | Modelo al momento del snapshot |
| `prompt_text` | TEXT NULL | Prompt monolítico al momento del snapshot |
| `changed_by_email` | VARCHAR(191) NULL | Email del Super Admin que realizó el cambio |
| `change_note` | VARCHAR(255) NULL | Nota del cambio (opcional) |
| `created_at` | TIMESTAMP NULL | Fecha del snapshot |

---

### 🗺️ Tabla: `ai_zone_heatmaps`
> **Caché del Radar de Plusvalía.** Resultados pre-computados por zona/CP con TTL de 24 h.

| Columna | Tipo | Notas |
| :--- | :--- | :--- |
| `id` | BIGINT UNSIGNED PK | Auto-increment |
| `company_id` | BIGINT UNSIGNED NOT NULL | FK → `companies.id` **[TENANT LOCK]** |
| `zipcode` | INT UNSIGNED NOT NULL | Código postal de la zona |
| `center_lat` | DECIMAL(10,7) NULL | Latitud central calculada (AVG de `properties.lat`) |
| `center_lng` | DECIMAL(10,7) NULL | Longitud central calculada (AVG de `properties.lng`) |
| `heat_score` | TINYINT UNSIGNED DEFAULT 0 | Score 0-100 de plusvalía relativa (percentil dentro del tenant) |
| `appreciation_index` | DECIMAL(5,2) NULL | Índice de apreciación (Fase 2-B, aún `null`) |
| `avg_price_per_m2` | DOUBLE NULL | Precio promedio por m² en la zona |
| `active_listings` | INT UNSIGNED DEFAULT 0 | Propiedades activas en la zona al momento del cómputo |
| `gentrification_signal` | ENUM('ninguna','incipiente','moderada','avanzada') | Señal inferida por `heat_score` |
| `growth_drivers` | JSON NULL | Array de drivers: `["demanda_alta","mercado_activo",...]` |
| `aura_insight` | TEXT NULL | Insight narrativo de AURA (Fase 2-B) |
| `aura_prompt_slug` | VARCHAR(80) NULL | Slug del prompt usado para generar `aura_insight` |
| `computed_at` | TIMESTAMP NULL | Timestamp del cómputo |
| `valid_until` | TIMESTAMP NULL | Expiración del caché (TTL 24 h) |
| `created_at` / `updated_at` | TIMESTAMP NULL | Timestamps Laravel |

**Cálculo de `heat_score`:** percentil normalizado `((pm2 - min) / (max - min)) * 100` dentro del portafolio del tenant. NO es un valor absoluto de mercado.

---

### 📈 Tabla: `ai_market_trends`
> Tendencias agregadas de precios por zona/periodo. Alimenta el Radar Fase 2-B (predictivo).

| Columna | Tipo | Notas |
| :--- | :--- | :--- |
| `id` | BIGINT UNSIGNED PK | Auto-increment |
| `company_id` | BIGINT UNSIGNED NOT NULL | FK → `companies.id` **[TENANT LOCK]** |
| `zipcode` | INT UNSIGNED NOT NULL | Código postal |
| `period_start` | DATE NOT NULL | Inicio del periodo analizado |
| `period_end` | DATE NOT NULL | Fin del periodo analizado |
| `prop_status_id` | BIGINT UNSIGNED NULL | FK → `property_statuses.id` — NULL = todos los estados |
| `prop_type_id` | BIGINT UNSIGNED NULL | FK → `property_types.id` — NULL = todos los tipos |
| `sample_count` | INT UNSIGNED DEFAULT 0 | Propiedades incluidas en el cálculo |
| `avg_price` | DOUBLE NULL | Precio promedio del periodo |
| `median_price` | DOUBLE NULL | Mediana de precios del periodo |
| `avg_price_per_m2` | DOUBLE NULL | Precio/m² promedio del periodo |
| `min_price` | DOUBLE NULL | Precio mínimo del periodo |
| `max_price` | DOUBLE NULL | Precio máximo del periodo |
| `trend_pct` | DOUBLE NULL | Variación % vs periodo anterior |
| `confidence_score` | TINYINT UNSIGNED DEFAULT 0 | Confianza 0-100 según `sample_count` |
| `generated_by` | ENUM('scheduler','manual','aura') DEFAULT 'scheduler' | Origen del registro |
| `created_at` / `updated_at` | TIMESTAMP NULL | Timestamps Laravel |

---

### 📸 Tabla: `ai_property_price_snapshots`
> Historial de precios por propiedad para cálculo de tendencias. Base de datos temporal del Radar.

| Columna | Tipo | Notas |
| :--- | :--- | :--- |
| `id` | BIGINT UNSIGNED PK | Auto-increment |
| `company_id` | BIGINT UNSIGNED NOT NULL | FK → `companies.id` **[TENANT LOCK]** |
| `property_id` | BIGINT UNSIGNED NOT NULL | FK → `properties.id` |
| `snapshot_date` | DATE NOT NULL | Fecha del snapshot de precio |
| `price` | DOUBLE NOT NULL | Precio al momento del snapshot |
| `built_area` | DOUBLE DEFAULT 0 | Superficie construida al momento del snapshot |
| `total_area` | DOUBLE DEFAULT 0 | Superficie total al momento del snapshot |
| `price_per_m2` | DOUBLE GENERATED | `price / built_area` (columna calculada STORED). NULL si `built_area = 0` |
| `prop_status_id` | BIGINT UNSIGNED NOT NULL | FK → `property_statuses.id` |
| `prop_type_id` | BIGINT UNSIGNED NOT NULL | FK → `property_types.id` |
| `zipcode` | INT UNSIGNED NULL | CP al momento del snapshot |
| `created_at` / `updated_at` | TIMESTAMP NULL | Timestamps Laravel |

> **NOTA:** `price_per_m2` es una columna `GENERATED ALWAYS AS (if(built_area > 0, price / built_area, NULL)) STORED`. No se puede insertar/actualizar directamente.

---

### 🌍 Tabla: `ai_external_zone_data`
> Datos externos de mercado por zona (futuro feed de APIs de datos socioeconómicos).

| Columna | Tipo | Notas |
| :--- | :--- | :--- |
| `id` | BIGINT UNSIGNED PK | Auto-increment |
| `zipcode` | INT UNSIGNED NOT NULL | Código postal |
| `data_source` | VARCHAR(80) NOT NULL | Fuente: `inegi`, `denue`, `banxico`, etc. |
| `data_type` | VARCHAR(80) NOT NULL | Tipo de dato: `poblacion`, `ingreso_promedio`, `empleos`, etc. |
| `period` | YEAR(4) NULL | Año del dato |
| `value_numeric` | DOUBLE NULL | Valor numérico del indicador |
| `value_json` | LONGTEXT (JSON) NULL | Datos estructurados adicionales |
| `fetched_at` | TIMESTAMP NULL | Fecha de descarga |
| `created_at` / `updated_at` | TIMESTAMP NULL | Timestamps Laravel |

---

### 💳 Tabla: `payment_gateway_settings`
> Configuración de pasarelas de pago por ambiente (sandbox/producción).

| Columna | Tipo | Notas |
| :--- | :--- | :--- |
| `id` | BIGINT UNSIGNED PK | Auto-increment |
| `provider_name` | VARCHAR(50) NOT NULL | Identificador: `openpay`, `stripe`, etc. |
| `is_active` | BOOLEAN DEFAULT 0 | 1 = pasarela activa |
| `is_sandbox` | BOOLEAN DEFAULT 1 | 1 = modo sandbox/pruebas |
| `credentials` | TEXT NULL | JSON cifrado — NUNCA texto plano |
| `company_id` | BIGINT UNSIGNED NULL | FK → `companies.id`. NULL = configuración global |
| `created_at` / `updated_at` | TIMESTAMP NULL | Timestamps Laravel |

---

## 🧠 REGISTRO SEMÁNTICO (VOCABULARIO CONTROLADO)

### ✅ Términos Permitidos
**Core CRM:** `company_id`, `agent_id`, `contact_id`, `property_id`, `prop_type_id`, `prop_status_id`, `prop_use_id`, `full_name`, `last_name`, `built_area`, `total_area`, `parking_lots`, `feature_properties`, `contact_properties`, `file_properties`, `property_stocks`

**Módulo IA:** `session_token`, `bridge_token`, `heat_score`, `appreciation_index`, `avg_price_per_m2`, `confidence_score`, `gentrification_signal`, `growth_drivers`, `aura_insight`, `trend_pct`, `sample_count`, `slug`, `system_role`, `business_context`, `immutable_rules`, `tone_profile`, `output_schema`, `variables_schema`, `preferred_model`, `price_per_m2`, `center_lat`, `center_lng`

### ❌ Términos Prohibidos / Evitar
`inmueble_id`, `agente`, `prospecto`, `inmobiliaria_id`, `tipo_propiedad`, `estado_propiedad`, `superficie`, `foto_id`, `caracteristicas`

---

## 🗺️ MAPA DE RELACIONES CLAVE

```
companies ──< users          (company_id)
companies ──< contacts       (company_id)
companies ──< properties     (company_id)
companies ──< invoices       (company_id)

users     ──< contacts       (agent_id)
users     ──< properties     (agent_id, created_by)
users     ──< contact_notes  (user_id)

contacts  ──< contact_phones       (contact_id)
contacts  ──< contact_notes        (contact_id)
contacts  ──< contact_properties   (contact_id)

properties ──< file_properties     (property_id)
properties ──< feature_properties  (property_id)
properties ──< contact_properties  (property_id)
properties ──< property_stocks     (property_id)
properties >── property_types      (prop_type_id)
properties >── property_statuses   (prop_status_id)
properties >── property_uses       (prop_use_id)

features  ──< features             (parent_id — árbol)
features  ──< feature_properties   (feature_id)

invoices  ──< invoices_services    (invoice_id)
services  ──< invoices_services    (service_id)

states    ──< cities               (state_id)
cities    ──< districts            (city_id)

── IA / CHAT ──
companies ──< ai_conversations     (company_id)   ← AISLAMIENTO DE TENANT obligatorio
users     ──< ai_conversations     (user_id)      ← nullable; sesión del agente
ai_conversations ──< ai_messages   (conversation_id, CASCADE DELETE)

── IA / ORQUESTADOR ──
companies ──< ai_settings          (company_id) ← NULL = global
ai_settings → AIService.php        (Patrón Strategy + Failover Dinámico)
                                    Adaptadores: openai, groq, mistral, gemini

── IA / PROMPTS (Synaptic Core™) ──
ai_prompts ──< ai_prompt_versions  (prompt_id) ← historial de cambios
ai_prompts → BrokerBrainController (slug: cma_urban_intelligence)

── IA / RADAR (Fase 2) ──
companies ──< ai_zone_heatmaps     (company_id) ← TTL 24h, caché del heatmap
companies ──< ai_market_trends     (company_id) ← tendencias por periodo/zipcode
companies ──< ai_property_price_snapshots (company_id) ← historial de precios
              ai_external_zone_data (zipcode) ← datos externos, sin tenant lock

── PAGOS ──
companies ──< payment_gateway_settings (company_id) ← NULL = global
```

---

---

## 🔬 ESTADO OPERATIVO DE PRODUCCIÓN (verificado en dump 2026-06-03)

### Datos Sembrados Confirmados

| Tabla | Registros | Notas |
| :--- | :--- | :--- |
| `properties` | ~300+ | La Paz, BCS. Zipcodes 23050-23200. Campos `lat`/`lng` como `VARCHAR`. Mezcla de `built_area` NULL / 0 / poblado |
| `ai_prompts` | 1 | Slug: `cma_urban_intelligence` — estructura modular completa (6 módulos + output_schema + variables_schema) |
| `ai_settings` | 2 | Groq (priority 1, DECOMMISSIONED), Mistral (priority 2, OK) |
| `companies` | 1 (producción) | La Paz BCS |
| `ai_zone_heatmaps` | 0 | Se computa automáticamente en el primer request al Radar |
| `ai_market_trends` | 0 | Fase 2-B — aún sin datos históricos |
| `ai_property_price_snapshots` | 0 | Fase 2-B — sin snapshots aún |

### ⚠️ Alerta Operativa: Groq Model Decommissioned

```
provider: groq | model: llama3-8b-8192 | estado: DECOMMISSIONED
error: "The model llama3-8b-8192 has been decommissioned and is no longer supported."
```

**Impacto:** El failover dinámico de `AIService` intenta Groq primero (priority 1), falla con 400, y escala a Mistral (priority 2). El sistema funciona pero con **+300-400ms de latencia adicional** por el intento fallido.

**Acción requerida:** Actualizar `extra_config` del provider Groq a un modelo vigente (`llama-3.1-8b-instant` o `llama3-70b-8192`) vía panel Super Admin → Orquestador IA.

---

## 📋 MÓDULO ADI-CORE: AURA Document Intelligence & Executive Briefing

> **Referencia:** `knowledge/07_AURA_REPORT_ENGINE.md`
> **Estado:** En diseño — Fase 1 por implementar
> **Controller destino:** `App\Http\Controllers\Api\V2\AiReportController.php`

### Contrato de Arquitectura (extraído del Blueprint)

| Principio | Definición |
| :--- | :--- |
| **O (Origen)** | Lógica en backend exclusivamente. Aislamiento por `company_id`. La IA no accede a datos de otros tenants |
| **R (Recursos)** | Estilos en `v2.css`. HTML semántico compatible con ARF-Grid Y `@media print`. Sin `!important`, sin `style=""` |
| **O (Orden)** | `snake_case` en tablas DB. `camelCase` en estado JS de la SPA. Estructura modular limpia |

### Pipeline de Datos — 5 Capas (Token Economy)
> Diseñado para evitar saturar la ventana de contexto de la API al procesar portafolios grandes.

| Capa | Función | Estado |
| :--- | :--- | :--- |
| 1 | Recepción de intención (texto libre, notas de voz, archivos) | Por implementar |
| 2 | Ruteo de intención + clasificación de tipo de reporte | Por implementar |
| 3 | Extracción SQL contextualizada (filtrada por `company_id`) | Por implementar |
| 4 | Compresión semántica del contexto antes de la IA | Por implementar |
| 5 | Generación HTML del reporte + persistencia | Por implementar |

### Nuevas Tablas Requeridas (pendientes de migración)

> ⚠️ **Mandamiento de Inmutabilidad:** NO crear estas tablas sin autorización explícita del Lead Architect. Están registradas aquí para anti-alucinación.

| Tabla | Propósito |
| :--- | :--- |
| `ai_reports` | Historial de reportes generados por tenant |
| `ai_report_sections` | Secciones modulares de cada reporte (BI multibloque) |

---

## 🧩 REGISTRO DE COMPONENTES FRONTEND

> **Ruta canónica de assets:** `public_html/newbrokers/` (alineada con servidor de pruebas)

| Componente | Ruta física | Tipo | Estado | Descripción |
| :--- | :--- | :--- | :--- | :--- |
| `ai-chat.blade.php` | `brokers_new/resources/views/components/ai-chat.blade.php` | Blade Component | ✅ Producción | Widget flotante de chat IA. Inyectado en `layouts/app.blade.php` vía `@include`. Incluye botón flotante, ventana con header/messages/footer, y el `<script>` de `aiChat.js`. |
| `aiChat.js` | `public_html/newbrokers/js/aiChat.js` | JavaScript (vanilla) | ✅ Producción | Cerebro del widget de chat. Toggle UI, `fetch` POST a `/home/ai/chat` con `X-CSRF-TOKEN`, renderizado de burbujas, indicador "escribiendo...", persistencia del `conversation_id` en el hilo. |
| `aiCopywriter.js` | `public_html/newbrokers/js/aiCopywriter.js` | JavaScript (vanilla) | ✅ Producción | Generador de copywriting inmobiliario. Recolecta campos del formulario de propiedad (title, prop_type, prop_status, bedrooms, baths, price, currency), llama a `POST /home/ai/generate-copy` e inyecta la descripción generada en `#description`. |
| `main.css — sección 11` | `public_html/newbrokers/css/main.css` | CSS (ARF-Grid) | ✅ Producción | Estilos del Widget de Chat IA. Clases: `.ai-chat-btn`, `.ai-chat-window`, `.ai-chat-open`, `.ai-chat-header`, `.ai-chat-messages`, `.ai-message-user`, `.ai-message-assistant`, `.ai-message-typing`, `.ai-chat-footer`, `.ai-chat-input`, `.ai-chat-send`. Mobile-First: 100% en móvil, `22rem` en desktop (`min-width: 48rem`). |
| `AiConversation.php` | `brokers_new/app/AiConversation.php` | Eloquent Model | ✅ Producción | Modelo de hilo de chat. Relaciones: `belongsTo(Company)`, `belongsTo(User)`, `hasMany(AiMessage, 'conversation_id')`. Fillable: `company_id`, `user_id`, `title`, `status`. |
| `AiMessage.php` | `brokers_new/app/AiMessage.php` | Eloquent Model | ✅ Producción | Modelo de mensaje IA. Relación: `belongsTo(AiConversation, 'conversation_id')`. Fillable: `conversation_id`, `role`, `content`, `tokens_used`. |
| `AiChatController.php` | `brokers_new/app/Http/Controllers/AiChatController.php` | Controller | ✅ Producción | Controlador IA. Métodos: `sendMessage()` (chat + persistencia) y `generateCopy()` (copywriting one-shot). Usa Guzzle 6 para llamadas a OpenAI. |
| `AiSetting.php` | `brokers_new/app/AiSetting.php` | Eloquent Model | ✅ Producción | Modelo del Orquestador. `api_key` en `$hidden`. Método `decryptedKey()` — único punto de desencriptación. |
| `AIProviderInterface.php` | `brokers_new/app/Services/Contracts/AIProviderInterface.php` | Interface (Strategy) | ✅ Producción | Contrato para todos los adaptadores. Método: `request(array $payload, array $config): array`. |
| `AIService.php` | `brokers_new/app/Services/AIService.php` | Service (Orchestrator) | ✅ Producción | Orquestador maestro con failover dinámico. Itera proveedores por `priority_order`, hace `Log::warning` en cada fallo y lanza `RuntimeException` si todos fallan. |
| `OpenAIProvider.php` | `brokers_new/app/Services/Providers/OpenAIProvider.php` | Provider Adapter (Tier 1) | ✅ Producción | Adaptador OpenAI. Modelo configurable vía `extra_config.model` (default: `gpt-4o`). Retorna JSON estandarizado con `latency_ms`. |
| `GroqProvider.php` | `brokers_new/app/Services/Providers/GroqProvider.php` | Provider Adapter (Tier 2) | ⚠️ Producción | Adaptador Groq (LPU). Endpoint OpenAI-compatible. **MODEL DECOMMISSIONED:** `llama3-8b-8192` ya no existe. Actualizar a `llama-3.1-8b-instant` vía panel Super Admin. Timeout 15s. |
| `AISettingsController.php` | `brokers_new/app/Http/Controllers/AISettingsController.php` | Controller (Admin-only) | ✅ Producción | CRUD de `ai_settings`. Doble candado: triple middleware + `hasRole('Admin')`. `api_key` → `encrypt()` en store/update. Vista recibe solo `api_key_masked`. |
| `ai/settings.blade.php` | `brokers_new/resources/views/ai/settings.blade.php` | Blade View (Admin) | ✅ Producción | Panel de configuración del Orquestador. ARF-Grid. Tabla con keys enmascaradas, toggle inline de activo/inactivo, formulario store/update, escalera visual de failover. |
| `SuperAdminController.php` | `brokers_new/app/Http/Controllers/Api/SuperAdminController.php` | API Controller (V2) | ✅ Producción | Panel Super Admin. Auth: Bearer session_token V2 + `hasRole('super_admin')`. Endpoints: `listAdmins`, `toggleRole`, `resetPassword`. Sin Passport — usa patrón Bridge V2. |
| `BridgeController.php` (adminBridge) | `brokers_new/app/Http/Controllers/BridgeController.php` | Web Controller (Bridge) | ✅ Producción | Método `adminBridge()` añadido. Genera bridge token y redirige a `v2/admin/security.html`. Acceso: `GET /home/v2/admin-bridge` middleware `[auth, role:super_admin]`. |
| `v2/admin/security.html` | `public_html/newbrokers/v2/admin/security.html` | SPA HTML (V2) | ✅ Producción | Panel de gestión de credenciales de Super Admin. 3 pantallas: loading / error / main. Usa el flujo bridge V2 idéntico a checkout. |
| `v2/admin/security.js` | `public_html/newbrokers/v2/admin/security.js` | SPA JS (V2 Vanilla) | ✅ Producción | Cerebro del panel. Boot → bridge/validate → listAdmins. Funciones: `toggleRole()`, `openModal()` + `execResetPassword()`. camelCase estricto. `escHtml()` protege contra XSS. |
| `v2/admin/security.css` | `public_html/newbrokers/v2/admin/security.css` | CSS (V2 ARF-Grid) | ✅ Producción | Estilos del panel Super Admin. Usa variables `--v2-*` de `shared/v2.css`. Mobile-First. Sin `!important`, sin anchos fijos. Responsive: oculta columna Estado en móvil. |
| `V2RadarController.php` | `brokers_new/app/Http/Controllers/Api/V2RadarController.php` | API Controller (V2 — Fase 2) | ✅ Producción | Radar de Plusvalía. Endpoints: `GET /api/v2/radar/heatmap`, `GET /api/v2/radar/zone/{zipcode}`. Calcula `heat_score` como percentil relativo por tenant. Caché TTL 24h en `ai_zone_heatmaps`. Fallback `built_area → total_area` para price/m². |
| `v2/radar/index.html` | `public_html/newbrokers/v2/radar/index.html` | SPA HTML (V2) | ✅ Producción | Radar de Plusvalía SPA. 3 pantallas: loading / error / main. Leaflet CDN con guard `typeof L !== 'undefined'`. Manual interactivo 3 pasos. Boot guard `_bootExecuted`. |
| `v2/radar/radar.js` | `public_html/newbrokers/v2/radar/radar.js` | SPA JS (V2 Vanilla) | ✅ Producción | Flujo bridge V2. Separación de try-catch: Bloque 1 (sesión), Bloque 2 (UI). `replaceState` post-validación. Diagnóstico console.log en validación. `initMap()` con guard Leaflet. |
| `BridgeController.php` (todos) | `brokers_new/app/Http/Controllers/BridgeController.php` | Web Controller (Bridge) | ✅ Producción | Métodos: `subscriptionBridge`, `brokerBrainBridge`, `aiHubBridge`, `radarBridge`, `adminBridge`. Helpers: `resolveFrontendBase()` (url('/') + '/newbrokers'), `resolveApiBase()` (str_replace localhost fix). |
| `BrokerBrainController.php` | `brokers_new/app/Http/Controllers/Api/BrokerBrainController.php` | API Controller (V2 — Fase 1) | ✅ Producción | Motor CMA con Cascada 3 Capas. `synthesizeFromAI()` inyecta prompt `cma_urban_intelligence`. `interpolateSyntheticFallback()` — 17 bandas regionales México. Mandamiento Anti-422 activo. |
