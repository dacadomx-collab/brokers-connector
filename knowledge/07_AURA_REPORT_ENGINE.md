# 📊 07. AURA DOCUMENT INTELLIGENCE & EXECUTIVE BRIEFING (ADI-CORE)

> **Estatus:** Documento de Arquitectura, Contrato de API y Roadmap de Implementación
> **Módulo:** Motor de Generación de Informes y Reportes con Inteligencia Artificial (Fase 1 - Evolución V2)
> **Lead Architect:** Gemini | **Agente Ejecutor:** Claude

---

## 🎯 1. CONCEPTO MAESTRO Y VISIÓN DE IMPACTO
El módulo **AURA Briefing Engine** no es un "chatbot de conversación". Es un motor de Business Intelligence relacional y documental diseñado para actuar como un Analista Ejecutivo Elite. Su misión es procesar intenciones del broker (vía texto libre, notas de voz o archivos adjuntos), cruzar el comando con el stock real del Tenant en la base de datos (filtrado por `company_id`) y escupir un reporte analítico de nivel corporativo listo para pantalla, descarga en PDF nativo o impresión física.

### ⚖️ Aplicación Estricta de la Regla de ORO (O.R.O.):
* **O - Origen:** Cero lógica pesada en el cliente. El análisis, ruteo de intención y compresión de contexto ocurren en el controlador raíz backend (`/brokers_new/app/Http/Controllers/Api/V2/AiReportController.php`). El aislamiento multitenant está blindado a nivel de base de datos; la IA jamás conocerá registros ajenos al `company_id` del usuario autenticado.
* **R - Recursos:** Los estilos del reporte están centralizados en `public_html/newbrokers/css/v2.css`. Prohibido el uso de estilos inline (`style="..."`) o la bandera `!important`. La estructura HTML devuelta por la IA utiliza clases semánticas normalizadas compatibles tanto con la SPA responsiva (ARF-Grid) como con las reglas de impresión `@media print` del navegador y el compilador de PDFs.
* **O - Orden:** Estructura modular limpia. Registro en la base de datos usando `snake_case` para tablas relacionales de historial, y `camelCase` para el manejo de estados dentro del JavaScript de la SPA.

---

## 🏗️ 2. EL PIPELINE DE DATOS EN 5 CAPAS (TOKEN ECONOMY)
Para mitigar el riesgo de saturación de la ventana de contexto de la API (evitando inyectar las 31 tablas o miles de inmuebles a la IA), el sistema operará bajo una arquitectura de flujo desacoplada: