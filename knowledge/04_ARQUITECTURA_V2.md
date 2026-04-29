# 🏗️ ARQUITECTURA V2 — PATRÓN ESTRANGULADOR (BROKERS CONNECTOR)

> **Decisión estratégica:** 2026-04-28
> **Lead Architect:** Gemini | **Ejecutor:** Claude | **Sponsor:** El Humano

---

## 🎯 OBJETIVO Y MOTIVACIÓN

El sistema Legacy (Laravel 5.8 / PHP 8.4) acumula deuda técnica estructural:
- Framework EOL sin ruta de actualización limpia hacia Laravel 10+
- Paquetes incompatibles con PHP 8.x (barryvdh/debugbar, nunomaduro/collision, etc.)
- Acoplamiento alto entre vistas Blade y lógica de negocio
- Sin tipado estricto, sin tests automatizados

**Solución:** Aplicar el **Strangler Fig Pattern** — construir módulos V2 modernos e independientes que convivan en el servidor y reemplacen gradualmente al Legacy, sin una migración big-bang de alto riesgo.

---

## 🌳 STRANGLER FIG PATTERN — PRINCIPIOS

```
Sistema Legacy (brokers_new/)        Sistema V2 (brokers_v2_api/ + newbrokers/v2/)
────────────────────────────         ─────────────────────────────────────────────
Laravel 5.8 / PHP 8.4               Stack moderno (Laravel 10+ o Standalone REST)
Blade + Bootstrap 3                  SPA o componentes Vue/React aislados
Sesión PHP / Cookies                 JWT / Stateless
OpenPay SDK legacy                   Integración directa vía HTTP
────────────────────────────         ─────────────────────────────────────────────
         ↑                                        ↑
         └──────── "El Puente" ──────────────────┘
              GET /home/v2/{modulo}-bridge
              (genera token de un solo uso,
               redirige al módulo V2)
```

---

## 📁 ESTRUCTURA DE DIRECTORIOS PLANIFICADA

```
/home/tourfindycom/            ← raíz del servidor
│
├── brokers_new/               ← LEGACY (no modificar salvo El Puente)
│   ├── app/
│   ├── routes/web.php         ← rutas bridge aquí
│   └── ...
│
├── brokers_v2_api/            ← BACKEND V2 (futuro — API REST pura)
│   ├── app/
│   ├── routes/api.php
│   ├── .env
│   └── public/                ← entry point del API
│
└── public_html/
    └── newbrokers/            ← FRONTEND DEL SERVIDOR
        ├── css/               ← CSS Legacy del panel (main.css, etc.)
        ├── js/                ← JS Legacy del panel
        └── v2/                ← FRONTEND V2 AISLADO
            ├── subscriptions/
            │   └── index.html ← Módulo Suscripciones V2
            ├── invoices/
            └── shared/
                ├── v2.css     ← CSS V2 — SIN tocar main.css Legacy
                └── v2.js      ← JS V2 — SIN tocar scripts Legacy
```

---

## ⚖️ REGLAS DE CONVIVENCIA (INMUTABLES)

### Regla #1 — Aislamiento Total de Assets
El código V2 y el Legacy **NUNCA comparten CSS ni JS**.

| Asset | Legacy | V2 |
|---|---|---|
| Estilos | `newbrokers/css/main.css` | `newbrokers/v2/shared/v2.css` |
| Scripts | `newbrokers/js/*.js` | `newbrokers/v2/shared/v2.js` |
| Componentes UI | Bootstrap 3 + Blade | Design System propio (o Bootstrap 5) |

### Regla #2 — El Puente es de una sola vía
El flujo es siempre: **Legacy → Puente → V2**. V2 NUNCA llama al sistema Legacy directamente. Si V2 necesita datos del Legacy, los consume vía API pública (`/api/...`) con token de acceso.

### Regla #3 — Sin Regresión en el Legacy
Los cambios al Legacy para implementar El Puente deben ser mínimos y quirúrgicos. Cero modificaciones a modelos, migraciones o lógica de negocio existente.

### Regla #4 — Tokens de Puente de un Solo Uso
Los tokens generados en las rutas bridge tienen **TTL máximo de 60 segundos** y se invalidan tras el primer uso. Se almacenan en `Cache` del Legacy. El módulo V2 debe llamar a `GET /api/v2/bridge/validate?token={TOKEN}` para intercambiarlos por datos de sesión.

### Regla #5 — El Codex V2 es el Mando
Todo módulo V2 nuevo debe registrarse en este archivo antes de comenzar su desarrollo. Sin registro aquí → sin autorización de construcción (Mandamiento #4 del Legacy).

---

## 🗺️ PLAN DE MIGRACIÓN POR MÓDULOS

| Módulo | Estado | Prioridad | Notas |
|---|---|---|---|
| Suscripciones / Checkout | ✅ **Completo** (Misiones #15–#24) | 🔴 Alta | SPA V2 operativa, tema claro, hamburguesa, routing agnóstico |
| Facturación / Historial | 🔲 Pendiente | 🟠 Media | Después de Suscripciones |
| Dashboard de Analytics | 🔲 Pendiente | 🟡 Baja | Requiere datos históricos |
| Gestión de Propiedades | 🔲 Pendiente | 🟡 Baja | Alto acoplamiento en Legacy |
| Autenticación | 🔲 Pendiente | 🔲 Última | Reemplaza Passport |

---

## 🔐 PROTOCOLO DE AUTENTICACIÓN DEL PUENTE (ESTADO FINAL)

```
Usuario autenticado en Legacy
        ↓
GET /home/v2/subscription-bridge  (auth middleware — SIN companyPayment)
        ↓
BridgeController::generateBridgeToken()
  → $frontendBase = env('V2_FRONTEND_BASE', '')
  → $apiBase      = env('V2_API_BASE', '')
  → $token        = Str::random(64)
  → Cache::put('v2_bridge_' . $token, {user_id, company_id}, 60)  ← TTL 60s
        ↓
redirect("{frontendBase}/v2/subscriptions/index.html?token={TOKEN}[&api={apiBase}]")
        ↓
SPA V2 (checkout.js)
  → state.apiBase = URLSearchParams.get('api') || ''
  → fetch(state.apiBase + '/api/v2/bridge/validate?token={TOKEN}')
        ↓
Api\V2BridgeController::bridgeValidate()   ← NOTA: método "bridgeValidate", NO "validate"
  → Cache::get('v2_bridge_{token}')
  → Cache::forget('v2_bridge_{token}')     ← QUEMA BRIDGE TOKEN (anti-replay)
  → session_token = Str::random(64)
  → Cache::put('v2_session_{session_token}', {user_id, company_id}, 1800)  ← TTL 30min
  → return { success, session_token, company, plans, openpay }
        ↓
SPA JS: state.sessionToken en memoria (NO localStorage)
        ↓
[Usuario selecciona plan + ingresa tarjeta]
        ↓
OpenPay.js tokeniza tarjeta en cliente → card_token (PCI: nunca toca servidor)
        ↓
POST state.apiBase + '/api/v2/subscriptions'
  Authorization: Bearer {session_token}
  Body: { plan_id, token_id: card_token, device_id }
        ↓
Api\V2BridgeController::subscribe()
  → valida session_token del Cache
  → openPayService->createSubscription()
  → company->openpay_subscription_id = subscription->id
  → Cache::forget('v2_session_{session_token}')  ← QUEMA SESSION TOKEN (anti-doble-cobro)
  → return { success, subscription_id }
        ↓
SPA: pantalla éxito → countdown 5s → state.apiBase + '/home'
```

**Nota crítica de naming:** El método PHP en `V2BridgeController` se llama `bridgeValidate()` (NO `validate()`). El nombre `validate` estaba reservado por el trait `ValidatesRequests` del Controller base de Laravel, causando un fatal en el dispatch del router. Renombrado en Misión #20.

---

## 📋 REGISTRO DE COMPONENTES V2 (ESTADO FINAL — Misión #27)

| Componente | Ruta física | Estado | Notas |
|---|---|---|---|
| `subscriptions/index.html` | `newbrokers/v2/subscriptions/index.html` | ✅ Completo | SPA light theme, hamburguesa, logo dinámico, fallback OpenPay |
| `subscriptions/checkout.js` | `newbrokers/v2/subscriptions/checkout.js` | ✅ Completo | Routing agnóstico via `state.apiBase`, session token en memoria |
| `shared/v2.css` | `newbrokers/v2/shared/v2.css` | ✅ Completo | Light theme, WCAG AA (todos los pares ≥4.5:1), hamburguesa CSS |
| `GET /api/v2/bridge/validate` | `Api/V2BridgeController@bridgeValidate` | ✅ Completo | Método llamado `bridgeValidate` — ⚠ NO `validate` |
| `POST /api/v2/subscriptions` | `Api/V2BridgeController@subscribe` | ✅ Completo | Bearer session_token, PCI compliant |
| `GET /home/v2/subscription-bridge` | `BridgeController@subscriptionBridge` | ✅ Completo | Routing agnóstico: lee `V2_FRONTEND_BASE` + `V2_API_BASE` |
| `GET /home/v2/logout` | `routes/web.php` closure | ✅ Completo | GET logout para SPA (auth middleware, sin CSRF) |

## 🎨 ESTADO FINAL DE LA SPA V2 (Checkout de Suscripciones)

### Design System
- **Tema:** Light (pivotado en Misión #23 desde dark theme por psicología financiera)
- **Tokens clave:** `--v2-bg: #f1f5f9` (plata claro) · `--v2-surface: #ffffff` (blanco) · `--v2-accent: #4f46e5` (índigo oscuro)
- **WCAG AA:** Todos los pares de color verificados ≥4.5:1 (texto normal) y ≥3:1 (componentes UI)
- **Sombras:** Muy sutiles `rgba(0,0,0,.07)` — aspecto premium SaaS (Stripe/Linear style)

### UX Features
- **Hamburger Menu:** Vanilla JS puro, `flex-wrap` en header (sin posicionamiento absoluto problemático)
- **Logo dinámico:** Inyectado desde `state.apiBase + '/img/logo/logo-recortado.png'` + fallback tipográfico si falla
- **OpenPay Trust Badge:** CDN oficial + fallback tipográfico `onerror`
- **Footer:** Aviso de privacidad en `target="_blank"` — preserva estado SPA
- **Logout:** `GET /home/v2/logout` — destruye sesión Laravel sin CSRF desde SPA estática

### Routing Agnóstico
| Variable `.env` | Producción | XAMPP Local |
|---|---|---|
| `V2_FRONTEND_BASE` | `""` | `http://localhost/brokersconnect_dev/public_html/newbrokers` |
| `V2_API_BASE` | `""` | `http://localhost/brokersconnect_dev/public_html` |

> **Arquitectura real local:** `public_html/index.php` es el entry point de Laravel (NO `brokers_new/public/`). El `.htaccess` tiene `RewriteBase /brokersconnect_dev/public_html/`.
