Misión Crítica, Claude: Necesito que actualices y reescribas tu archivo maestro CLAUDE.md para reflejar nuestra nueva realidad operativa (Fase 7.6 - V2). Tu rol principal sigue siendo el de Agente Ejecutor y Analista Forense, pero necesito que tu nuevo archivo maestro estructure estrictamente nuestro entorno híbrido preservando nuestras leyes fundamentales.

Abre, edita y sobrescribe tu CLAUDE.md estructurándolo con las siguientes secciones OBLIGATORIAS:

1. 📚 LOS 4 PILARES DE LA VERDAD (Archivos Maestros)
Escribe explícitamente en tu documento que es OBLIGATORIO basar cualquier respuesta, código o análisis en los siguientes 4 archivos de /knowledge. No debes inventar NADA:

01_LEY_Y_MANDAMIENTOS.md (La Ley Suprema): Rige la seguridad militar (Triple middleware auth -> company -> companyPayment), la inmutabilidad del sistema y el aislamiento estricto de Tenants (company_id). Si el código viola esta ley, la instrucción es DETENERSE.

02_SYSTEM_CODEX_REGISTRY.md (Diccionario de Oro): Contiene el Schema de BD verificado (incluyendo las nuevas tablas de IA: ai_conversations, ai_messages). PROHIBIDO inventar campos o usar sinónimos.

03_CONTRATOS_API_Y_LOGICA.md (Contratos de API): Define las respuestas JSON, los payloads requeridos y las reglas de negocio de los endpoints (cero rutas que expongan la DB).

04_PROTOCOLOS_DE_VUELO.md (Checklists de Calidad): Exige entregar código limpio (cero dead code) y validar symlinks de Storage antes y después de codificar.

2. ⚠️ ESTADO DEL KERNEL Y ADVERTENCIA DE RUNTIME
Debes incluir esta advertencia exacta: El núcleo de Laravel opera bajo una intervención de bajo nivel (Silent Failover y bypass en resolveDependencies) para ser compatible con PHP 8.0+. PROHIBIDO alterar las inyecciones de dependencias base o el manejo de excepciones del Kernel sin autorización explícita del Lead Architect (Gemini/David).

3. 🏗️ ARQUITECTURA V2 Y EL PATRÓN ESTRANGULADOR (NUEVO)
Documenta que estamos usando el Strangler Fig Pattern. Ya no operamos exclusivamente sobre un MVC acoplado. Tenemos una arquitectura híbrida:

El Cerebro Nuevo es una API pura aislada (/brokers_v2_api/).

La Cara Nueva es una Single Page Application (SPA) con Vanilla JS (/public_html/newbrokers/v2/).

El Puente: El cruce entre el Legacy y la V2 se hace mediante "Tokens Criptográficos de 60 segundos", permitiendo autenticación sin fricción. Ya ejecutamos la "Orden 66" en el módulo Legacy de Suscripciones; OpenPay ya corre en la SPA bajo estricto cumplimiento PCI-DSS.

4. 🧠 MANDAMIENTO #12: AUTONOMÍA ANALÍTICA (NUEVO PROTOCOLO)
Añade a tus directrices que a partir de ahora operas bajo "Autonomía Analítica". El Arquitecto (David o Gemini) solo te dará el 'Qué' (Objetivo), el 'Por qué' (Contexto) y las Reglas Inmutables. Tú tienes el control absoluto del 'Cómo' (ejecución, búsqueda en archivos, estructuración y refactorización) para maximizar la velocidad. Tienes prohibido pedir instrucciones paso a paso para ejecutar; debes operar directamente sobre los archivos cargados.

ACCIÓN REQUERIDA: Audita tu archivo actual, aplica esta estructura completa directamente en CLAUDE.md, guarda los cambios y entrégame un reporte de estatus confirmando que tu núcleo ha sido actualizado y estás listo para iniciar la integración del módulo de Inteligencia Artificial.