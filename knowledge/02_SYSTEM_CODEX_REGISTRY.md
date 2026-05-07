# 🧬 SYSTEM CODEX & REGISTRY — BROKERS CONNECTOR (DICCIONARIO DE ORO)

> **Fuente de verdad extraída de:** `database/migrations/` + `BackUp/brokersconnect_bd.sql`
> **Framework:** Laravel (PHP) — Arquitectura multitenant por `company_id`

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
| `id` | BIGINT UNSIGNED PK | Auto-increment |
| `provider_name` | VARCHAR(255) | Identificador del adaptador: `openai`, `groq` |
| `api_key` | TEXT | Clave API **cifrada con `encrypt()`** — nunca texto plano |
| `extra_config` | JSON NULL | Config adicional del proveedor: `{"model":"gpt-4o"}` |
| `priority_order` | TINYINT UNSIGNED DEFAULT 1 | 1 = mayor prioridad en la escalera de failover |
| `is_active` | BOOLEAN DEFAULT 1 | 0 = excluido del orquestador |
| `company_id` | BIGINT UNSIGNED NULL | FK → `companies.id` CASCADE DELETE. NULL = configuración global |
| `created_at` / `updated_at` | TIMESTAMP NULL | Timestamps Laravel |

> **REGLA DE ORO:** `api_key` se guarda con `encrypt()` en `store`/`update`. Se recupera con `decryptedKey()` del Modelo — nunca en vistas. La vista recibe solo `api_key_masked` (ej. `••••••••4o3a`).

---

## 🧠 REGISTRO SEMÁNTICO (VOCABULARIO CONTROLADO)

### ✅ Términos Permitidos
`company_id`, `agent_id`, `contact_id`, `property_id`, `prop_type_id`, `prop_status_id`, `prop_use_id`, `full_name`, `last_name`, `built_area`, `total_area`, `parking_lots`, `feature_properties`, `contact_properties`, `file_properties`, `property_stocks`

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
companies ──< ai_settings          (company_id, CASCADE DELETE) ← NULL = global
ai_settings → AIService.php        (Patrón Strategy + Failover Dinámico)
ai_settings → OpenAIProvider       (priority_order 1, adaptador Tier 1)
ai_settings → GroqProvider         (priority_order 2, adaptador Tier 2)
```

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
| `GroqProvider.php` | `brokers_new/app/Services/Providers/GroqProvider.php` | Provider Adapter (Tier 2) | ✅ Producción | Adaptador Groq (LPU). Endpoint OpenAI-compatible. Modelo configurable (default: `llama3-8b-8192`). Timeout 15s (vs 30s de OpenAI). |
| `AISettingsController.php` | `brokers_new/app/Http/Controllers/AISettingsController.php` | Controller (Admin-only) | ✅ Producción | CRUD de `ai_settings`. Doble candado: triple middleware + `hasRole('Admin')`. `api_key` → `encrypt()` en store/update. Vista recibe solo `api_key_masked`. |
| `ai/settings.blade.php` | `brokers_new/resources/views/ai/settings.blade.php` | Blade View (Admin) | ✅ Producción | Panel de configuración del Orquestador. ARF-Grid. Tabla con keys enmascaradas, toggle inline de activo/inactivo, formulario store/update, escalera visual de failover. |
| `SuperAdminController.php` | `brokers_new/app/Http/Controllers/Api/SuperAdminController.php` | API Controller (V2) | ✅ Producción | Panel Super Admin. Auth: Bearer session_token V2 + `hasRole('super_admin')`. Endpoints: `listAdmins`, `toggleRole`, `resetPassword`. Sin Passport — usa patrón Bridge V2. |
| `BridgeController.php` (adminBridge) | `brokers_new/app/Http/Controllers/BridgeController.php` | Web Controller (Bridge) | ✅ Producción | Método `adminBridge()` añadido. Genera bridge token y redirige a `v2/admin/security.html`. Acceso: `GET /home/v2/admin-bridge` middleware `[auth, role:super_admin]`. |
| `v2/admin/security.html` | `public_html/newbrokers/v2/admin/security.html` | SPA HTML (V2) | ✅ Producción | Panel de gestión de credenciales de Super Admin. 3 pantallas: loading / error / main. Usa el flujo bridge V2 idéntico a checkout. |
| `v2/admin/security.js` | `public_html/newbrokers/v2/admin/security.js` | SPA JS (V2 Vanilla) | ✅ Producción | Cerebro del panel. Boot → bridge/validate → listAdmins. Funciones: `toggleRole()`, `openModal()` + `execResetPassword()`. camelCase estricto. `escHtml()` protege contra XSS. |
| `v2/admin/security.css` | `public_html/newbrokers/v2/admin/security.css` | CSS (V2 ARF-Grid) | ✅ Producción | Estilos del panel Super Admin. Usa variables `--v2-*` de `shared/v2.css`. Mobile-First. Sin `!important`, sin anchos fijos. Responsive: oculta columna Estado en móvil. |
