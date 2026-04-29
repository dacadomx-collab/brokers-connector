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
| Suscripciones / Checkout | 🟡 En progreso | 🔴 Alta | Primer módulo V2. Puente creado. |
| Facturación / Historial | 🔲 Pendiente | 🟠 Media | Después de Suscripciones |
| Dashboard de Analytics | 🔲 Pendiente | 🟡 Baja | Requiere datos históricos |
| Gestión de Propiedades | 🔲 Pendiente | 🟡 Baja | Alto acoplamiento en Legacy |
| Autenticación | 🔲 Pendiente | 🔲 Última | Reemplaza Passport |

---

## 🔐 PROTOCOLO DE AUTENTICACIÓN DEL PUENTE

```
Usuario autenticado en Legacy
        ↓
GET /home/v2/{modulo}-bridge  (web, auth middleware)
        ↓
BridgeController::generateBridgeToken()
  → Str::random(64) = $token
  → Cache::put('v2_bridge_' . $token, [
        'user_id'    => auth()->id(),
        'company_id' => auth()->user()->company_id,
        'created_at' => now()->timestamp,
    ], 60)  ← 60 segundos TTL
        ↓
redirect('/newbrokers/v2/{modulo}/index.html?token=' . $token)
        ↓
Módulo V2 (JS)
  → GET /api/v2/bridge/validate?token={TOKEN}  ← endpoint a crear en V2
  → Lee los datos, invalida el token del Cache
  → Almacena sesión local (localStorage / sessionStorage)
```

---

## 📋 REGISTRO DE COMPONENTES V2

| Componente | Ruta física | Estado | Puente Legacy |
|---|---|---|---|
| `subscriptions/index.html` | `newbrokers/v2/subscriptions/index.html` | ✅ Completo (Misión #16) | `GET /home/v2/subscription-bridge` ✅ |
| `subscriptions/checkout.js` | `newbrokers/v2/subscriptions/checkout.js` | ✅ Completo (Misión #16) | — |
| `shared/v2.css` | `newbrokers/v2/shared/v2.css` | ✅ Completo (Misión #16) | — |
| `api/v2/bridge/validate` | `brokers_new/routes/api.php` + `Api/V2BridgeController@validate` | ✅ Completo (Misión #16) | — |
| `api/v2/subscriptions` | `brokers_new/routes/api.php` + `Api/V2BridgeController@subscribe` | ✅ Completo (Misión #16) | — |
