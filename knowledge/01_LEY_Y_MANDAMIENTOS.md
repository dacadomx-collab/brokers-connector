# 📜 LOS 10 MANDAMIENTOS DEL GÉNESIS (LEY SUPREMA)

## ⚖️ DECLARACIÓN DE AUTORIDAD
Este documento rige sobre cualquier sugerencia de la IA. La IA es una ejecutora DETERMINÍSTICA, no creativa. 

## ⚖️ LOS MANDAMIENTOS
1. **Mobile-First & Responsivo:** Todo componente nace para celular. Prohibido el uso de anchos fijos (px) en contenedores principales.
2. **Seguridad Nivel Militar:** Sanitización obligatoria de inputs. Uso de Prepared Statements. Blindaje contra Inyección SQL, XSS y CSRF.
3. **Modo Oscuro & Toggle Nativo:** Soporte de tema fluido (Light/Dark). Contraste mínimo 4.5:1 (Estándar WCAG 2.1).
4. **Protocolo Anti-Alucinación:** PROHIBIDO inventar variables. Si no existe en el `02_SYSTEM_CODEX_REGISTRY.md`, la IA debe DETENERSE.
5. **Contrato de API Estricto:** Prohibido alterar nombres de propiedades JSON definidos en `03_CONTRATOS_API_Y_LOGICA.md`.
6. **Ejecución Determinística:** No se permiten "mejoras" o "extensiones" no solicitadas. 
7. **Naming Registry:** `snake_case` para Backend/DB; `camelCase` para Frontend/React.
8. **Detección de Dead Code:** Auditoría obligatoria para eliminar funciones, imports y variables huérfanas antes de cada entrega.
9. **Inmutabilidad del Sistema:** La IA NO puede crear tablas o alterar esquemas de DB sin autorización humana explícita.
10. **Sinónimos Prohibidos:** Solo existe UN nombre válido por concepto. Cero tolerancia a traducciones libres.
11. **Arranque Blindado (Fundación del Proyecto):** NINGÚN proyecto puede iniciar su desarrollo visual o lógico sin antes haber establecido la "Fundación de Seguridad". Esto exige que los primeros 4 archivos en crearse sean: `.env` (credenciales locales/servidor), `.env.example` (plantilla pública), `.htaccess` (blindaje Apache Nivel Militar) y `api/conexion.php` (Conexión PDO centralizada y segura).
12. **Autonomía Analítica de la IA:** Al interactuar con los Agentes IA (Claude/Gemini), el Arquitecto o Humano solo proporcionará el "Qué" (Objetivo), el "Por qué" (Contexto) y las "Reglas Inmutables". El "Cómo" (ejecución, estructuración y lógica) se delega completamente a la capacidad analítica de la IA para maximizar velocidad y evitar micro-management.
---

## 🔐 REGLAS TÉCNICAS DEL CÓDIGO FUENTE (HALLAZGOS DE AUDITORÍA)

> Extraídas del análisis directo del código fuente. Fecha: 2026-04-10.
> Estas reglas son OBLIGATORIAS para cualquier IA o desarrollador que genere código nuevo.

---

### 🏗️ ARQUITECTURA DE AUTENTICACIÓN (DUAL)

El sistema usa **dos guards de autenticación simultáneos**:

| Guard | Middleware | Usado en | Mecanismo |
|---|---|---|---|
| `auth:api` | `auth:api` | Rutas `/api/...` | Laravel Passport — Bearer Token (OAuth2 Personal Access Token) |
| `auth` (web) | `auth` | Rutas web `/home/...` | Sesión PHP + cookies de Laravel |

**Regla:** Nunca mezclar guards. Las rutas API usan `auth:api`. Las rutas web usan `auth`. Si se crea un endpoint nuevo, definir explícitamente cuál guard aplica.

---

### 🔗 CADENA DE MIDDLEWARE (RUTAS PROTEGIDAS)

Las rutas del panel interno usan **triple middleware en orden**:

```
auth → company → companyPayment
```

| Middleware | Clase | Comportamiento |
|---|---|---|
| `auth` | `Authenticate.php` | Redirige a `/` si no autenticado |
| `company` | `Company.php` | Redirige a `/home` si `auth()->user()->company == null` |
| `companyPayment` | `CompanyPayment.php` | Redirige a `/suspended` si `company->is_active == false` |

**Regla:** Todo endpoint nuevo del panel interno DEBE incluir los tres middlewares. Omitir `company` o `companyPayment` crea una brecha de acceso.

---

### 🌐 CORS GLOBAL

`App\Http\Middleware\Cors` está registrado como **middleware global** (aplica a TODOS los requests, web y API). Esto significa que no es necesario añadirlo por ruta.

---

### 🔑 SISTEMA DE ROLES (SPATIE RBAC)

- **Paquete:** `spatie/laravel-permission`
- **Roles conocidos:** `Admin`, `Agent` (y posiblemente más — catálogo en tabla `roles`)
- **Uso:** `$user->hasRole("Admin")`, `$user->assignRole($role)`, `User::role(["Agent","Admin"])->get()`
- **Regla:** Los roles se asignan con `assignRole()`. No usar columna directa en `users` — el sistema de roles es polimórfico vía `model_has_roles`.
- **Restricción crítica:** El rol `Admin` nunca puede ser asignado desde `UserController::showCreate()` (solo muestra roles con `name != "Admin"`). Solo el sistema puede crear Admins.
- **Restricción de borrado:** `UserController::delete()` verifica `hasRole("Admin")` — los Admins no se pueden borrar por esta ruta.

---

### 📁 MANEJO DE ARCHIVOS (STORAGE)

- **Driver:** `public_path()` directamente — NO usa `Storage::disk()`. Los archivos se guardan en `public/` con `$file->move(public_path($path), $name)`.
- **Ruta de imágenes de propiedades:** `public/companies/{company_id}/YYYY/MM/DD/{uniqid}.ext`
- **Ruta de logos de compañías:** `public/companies/{user_id}/{filename}`
- **Ruta de avatares de usuarios:** `public/companies/{company_id}/avatars/{filename}`
- **Permisos:** `chmod($path, 0755)` — se aplica manualmente después de cada upload y creación de directorio.
- **Thumbnails:** Se generan automáticamente para imágenes tipo 1 usando `Intervention\Image`. Tamaño: 300px ancho, proporcional. Canvas de relleno `#d3d3d3` si `height > width`.
- **Regla:** No usar `Storage::link()` ni `Storage::disk('public')` — este proyecto escribe directamente en `public/`. Verificar que la carpeta `public/companies/` tiene permisos de escritura antes de subir archivos.

---

### ✅ VALIDACIONES ESTRICTAS (Form Requests)

#### `CreatePropertyRequest` — Reglas que la IA DEBE respetar:
| Campo | Regla | Límite |
|---|---|---|
| `title` | required | máx. 100 chars |
| `description` | required | máx. 1,000 chars |
| `price` | required, numeric | min: 0, máx: 9,999,999,999 |
| `currency` | required | — |
| `prop_type_id` | required | — |
| `prop_status_id` | required | — |
| `local_id` | required | — |
| `mun` | required | — |
| `state` | required | — |
| `lat` / `lng` | required | — |
| `key` | optional | máx. 15 chars, único por company |
| `bedrooms/baths/medium_baths/parking_lots` | optional | máx: 999 |
| `floor` | optional | máx: 99 |
| `commission` | optional | máx: 9,999,999,999 |

#### `ContactRequest`:
- `email` único por `company_id` (validado en controlador, no en FormRequest)
- Teléfonos: solo dígitos (`is_numeric()`). Si `type` vacío → `"Celular"` por defecto.

#### `StoreCompanyRequest` (Compañía):
- Logo/avatar: `image|mimes:jpeg,png,jpg,gif,svg|max:2048` (máx 2 MB)
- Dominio: regex `^[a-zA-Z0-9][a-zA-Z0-9-]+[a-zA-Z0-9]$` + único en BD

#### `ValidateUser` (Usuarios):
- Password: `required|confirmed|min:8` (si se proporciona)
- Avatar: `image|mimes:jpeg,png,jpg,gif,svg|max:2048`

---

### 🚨 VULNERABILIDADES Y DEUDA TÉCNICA DETECTADAS

> Documentadas para que la IA las conozca y NO las replique en código nuevo.

| # | Archivo | Problema | Severidad |
|---|---|---|---|
| 1 | `routes/web.php` | Ruta `/generar-codex` expone schema completo sin middleware | 🔴 CRÍTICO — eliminar |
| 2 | `PropertyController.php` | API key de SendGrid hardcodeada (`SG.LFNHt9yHSqOhintBn8ToTw...`) | 🔴 CRÍTICO — mover a `.env` |
| 3 | `InvoicesController.php` | Keys de OpenPay hardcodeadas en `openPay_paynet()` y `openPay_spei()` — no usa `env()` | 🟠 ALTO — mover a `.env` |
| 4 | `PropertyController.php` | Imágenes físicas NO se eliminan al hacer delete (código comentado) | 🟡 MEDIO — acumula archivos huérfanos |
| 5 | `UserController.php` | `$request_all;` sin inicializar si `filled('password')=false` — bug potencial | 🟡 MEDIO |
| 6 | `CompanyController.php` | `$company;` declarada sin inicializar en `plans()` — si `company==null`, falla silenciosamente | 🟡 MEDIO |
| 7 | `PropertyController.php` | Imágenes no se eliminan del disco en `deleteFromArray` tampoco | 🟡 MEDIO |
| 8 | `AuthController.php` | `register()` tiene `dd($request)` hardcodeado — el registro de usuarios está inutilizado | 🟠 ALTO |

**Regla:** La IA NO debe replicar ninguno de estos patrones. Siempre usar `env('CLAVE')` para secretos. Nunca usar `dd()` en producción.