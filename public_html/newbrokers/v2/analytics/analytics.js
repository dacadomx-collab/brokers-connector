/**
 * Pulse Metrics IA — analytics.js
 * Bootstrap V2 + Smart Input + Drag & Drop + Voice I/O + TTS + Print
 *
 * Arquitectura: Strangler Fig Pattern
 * Auth: Bridge token (60s) → session_token (30min) vía /api/v2/analytics/validate
 * Tenant Lock: company_id viaja en el session_token del servidor; el frontend solo lo lee.
 *
 * Input multimodal:
 *   1. Texto libre (textarea)
 *   2. Voz → transcripción en tiempo real (Web Speech API - SpeechRecognition)
 *   3. Archivo / imagen → Base64 o texto plano → campo contexto
 *   4. Drag & Drop → misma lógica que selector de archivos
 *
 * Output:
 *   - Informe HTML renderizado en pantalla
 *   - Text-to-Speech nativo (SpeechSynthesis API)
 *   - Exportación PDF vía window.print()
 */

'use strict';

/* ── Estado global ──────────────────────────────────────────── */
const state = {
  sessionToken: null,
  companyName:  null,
  apiBase:      null,
  recognition:  null,
  isRecording:  false,
  isSpeaking:   false,
  halted:       false, // true cuando la pantalla de error es permanente
};

/* ── Bootstrap ──────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
  const params      = new URLSearchParams(window.location.search);
  const bridgeToken = params.get('token');
  const rawApi      = params.get('api');

  // Resolver apiBase — fallback vacío si no viene el parámetro (acceso directo malicioso)
  state.apiBase = rawApi ? decodeURIComponent(rawApi).replace(/\/$/, '') : '';

  // ── Configurar TODAS las URLs de navegación ANTES del primer early-return ──
  // Garantía: el botón "Volver al panel" de la pantalla de error siempre funciona,
  // independientemente de si el token es válido o no.
  // Patrón: broker-brain.js L57-66 (módulo de referencia).
  const panelUrl  = `${state.apiBase}/home`;
  const hubUrl    = `${state.apiBase}/home/v2/ai-hub-bridge`;
  const logoutUrl = `${state.apiBase}/home/v2/logout`;

  setHref('error-back-btn',   panelUrl);   // pantalla de error — funciona siempre
  setHref('nav-hub',          hubUrl);
  setHref('nav-panel',        panelUrl);
  setHref('nav-logout',       logoutUrl);
  setHref('nav-hub-m',        hubUrl);
  setHref('nav-panel-m',      panelUrl);
  setHref('nav-logout-m',     logoutUrl);
  setHref('header-logo-link', panelUrl);

  // ── Inyección asíncrona del logo del sistema desde apiBase ───────────────
  // Se ejecuta inmediatamente tras resolver apiBase, independientemente del token.
  // El logo estándar V2 vive en {apiBase}/img/logo/logo-recortado.png.
  // onerror → fallback tipográfico; nunca se mostrará el icono de imagen rota.
  injectNavLogo(state.apiBase);

  if (!bridgeToken) {
    showError('Enlace incompleto: no se encontró el token de acceso. Regresa al panel e intenta de nuevo.');
    return;
  }

  validateBridgeToken(bridgeToken);
});

/* ── Inyección del logo del sistema ─────────────────────────── */
function injectNavLogo(apiBase) {
  const logoImg  = el('v2-nav-logo');
  const fallback = el('header-logo-fallback');
  if (!logoImg) return;

  const logoSrc = (apiBase || '') + '/img/logo/logo-recortado.png';

  logoImg.onerror = () => {
    // Imagen no disponible: mantener el fallback tipográfico visible
    logoImg.style.display = 'none';
    if (fallback) fallback.style.display = 'flex';
  };

  logoImg.onload = () => {
    logoImg.style.display = 'block';
    if (fallback) fallback.style.display = 'none';
  };

  logoImg.src = logoSrc;
}

/* ── Validar bridge token ────────────────────────────────────── */
async function validateBridgeToken(token) {
  // Guard: si ya estamos en pantalla de error, no continuar
  if (state.halted) return;

  try {
    const res  = await fetch(`${state.apiBase}/api/v2/analytics/validate?token=${encodeURIComponent(token)}`);
    const data = await res.json();

    if (state.halted) return; // segunda verificación por si la respuesta llegó tarde

    if (!data.success) {
      showError(data.error || 'Enlace inválido o expirado. Regresa al panel e intenta de nuevo.');
      return;
    }

    state.sessionToken = data.session_token;
    state.companyName  = data.company ? data.company.name : 'Mi Empresa';

    bootUI(data);

  } catch (_e) {
    if (!state.halted) {
      showError('No se pudo conectar con el servidor. Verifica tu conexión e intenta de nuevo.');
    }
  }
}

/* ── Inicializar la UI ───────────────────────────────────────── */
function bootUI(data) {
  // Guard absoluto: si el módulo está en estado de error permanente, no tocar el DOM.
  if (state.halted) return;

  // Las URLs de nav ya fueron asignadas en DOMContentLoaded — no repetir.
  el('screen-loading').classList.add('hidden');
  el('screen-main').classList.remove('hidden');

  el('company-badge').textContent = state.companyName;

  // Logo ya fue inyectado por injectNavLogo() en DOMContentLoaded.
  // Si la empresa tiene un logo propio (diferente al del sistema), sobreescribirlo.
  if (data.company && data.company.logo) {
    const logoImg = el('v2-nav-logo');
    if (logoImg) {
      logoImg.onerror = () => {
        logoImg.style.display = 'none';
        const fb = el('header-logo-fallback');
        if (fb) fb.style.display = 'flex';
      };
      logoImg.onload = () => { logoImg.style.display = 'block'; };
      logoImg.src = data.company.logo;
    }
  }

  wireEvents();
}

/* ── Métricas ────────────────────────────────────────────────── */
function updateMetrics(metrics) {
  if (!metrics) return;
  el('met-total').textContent       = metrics.total_properties        ?? '—';
  el('met-published').textContent   = metrics.published_properties    ?? '—';
  el('met-unpublished').textContent = metrics.unpublished_properties  ?? '—';
  el('met-contacts').textContent    = metrics.total_contacts          ?? '—';
  el('aura-engine-monitor-box').style.display = 'grid';

  // ── Banner ACADEP: visible al cargar métricas, URL resuelta dinámicamente ──
  var acadepBanner = el('acadep-hero-banner');
  var acadepBtn    = el('acadep-infographic-btn');
  if (acadepBanner) acadepBanner.style.display = '';
  if (acadepBtn)    acadepBtn.href = (state.apiBase || '')
    + '/newbrokers/reportes/reporte_infraestructura_aura_v2.html';
}

/* ── Wiring de eventos ──────────────────────────────────────── */
function wireEvents() {
  // Contador de caracteres
  const queryInput = el('query-input');
  queryInput.addEventListener('input', () => {
    el('char-count').textContent = queryInput.value.length;
  });

  // Sugerencias rápidas
  document.querySelectorAll('.bp-sug-chip').forEach(chip => {
    chip.addEventListener('click', () => {
      queryInput.value = chip.dataset.query;
      el('char-count').textContent = queryInput.value.length;
      queryInput.focus();
    });
  });

  // Adjuntar documento (clic)
  el('attach-btn').addEventListener('click', () => el('file-input').click());
  el('file-input').addEventListener('change', e => handleFileAttach(e.target.files[0]));

  // Cámara / foto (PulseScout IA — misma lógica de procesamiento)
  el('camera-btn').addEventListener('click', () => el('camera-input').click());
  el('camera-input').addEventListener('change', e => handleFileAttach(e.target.files[0]));

  // Quitar contexto
  el('remove-context-btn').addEventListener('click', () => {
    el('context-input').value = '';
    el('context-block').style.display = 'none';
  });

  // Voz
  el('voice-btn').addEventListener('click', toggleVoice);

  // Generar informe
  el('generate-btn').addEventListener('click', generateReport);

  // Acciones del reporte
  el('copy-btn').addEventListener('click', copyReport);
  el('print-btn').addEventListener('click', printReport);
  el('listen-btn').addEventListener('click', toggleTTS);
  el('new-query-btn').addEventListener('click', resetToInput);

  // Hamburger — patrón estándar V2: toggle clase "open" en botón y en <nav>
  const hamburgerBtn  = el('hamburger-btn');
  const mobileMenu    = el('mobile-menu');
  hamburgerBtn.addEventListener('click', () => {
    const isOpen = mobileMenu.classList.toggle('open');
    hamburgerBtn.classList.toggle('open', isOpen);
    hamburgerBtn.setAttribute('aria-expanded', String(isOpen));
    mobileMenu.setAttribute('aria-hidden', String(!isOpen));
  });

  // ── PulseScout modal — botones de confirmación ───────────────
  wirePulseScoutModal();

  // ── Enter en textarea — activa filtro de pre-vuelo ───────────
  wireQueryEnterKey();

  // ── Drag & Drop ──────────────────────────────────────────────
  wireDragDrop();
}

/* ══════════════════════════════════════════════════════════════
   PULSESCOUT IA — MODAL DE CONFIRMACIÓN DE INTENCIÓN
   ══════════════════════════════════════════════════════════════ */

function wirePulseScoutModal() {
  // Confirmar → liberar el flujo asíncrono hacia AURA con bypassPreflight=true
  el('ps-confirm-btn').addEventListener('click', () => {
    const query = el('ps-extracted-query') ? el('ps-extracted-query').textContent.trim() : '';
    hidePulseScoutModal();
    if (query) {
      el('query-input').value = query;
      el('char-count').textContent = query.length;
      generateReport(true);   // bypassPreflight=true: salta el interceptor
    }
  });

  // Cancelar → cerrar modal sin acción
  el('ps-cancel-btn').addEventListener('click', () => {
    hidePulseScoutModal();
    showToast('Solicitud cancelada.', 'success');
  });

  // Click fuera del card → cerrar
  el('pulsescout-preview-modal').addEventListener('click', e => {
    if (e.target === el('pulsescout-preview-modal')) hidePulseScoutModal();
  });
}

/* ── Enter en la textarea → dispara el filtro de pre-vuelo ─── */
function wireQueryEnterKey() {
  const queryInput = el('query-input');
  if (!queryInput) return;
  queryInput.addEventListener('keydown', e => {
    // Ctrl+Enter o Cmd+Enter → generar directo (bypass)
    if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) {
      e.preventDefault();
      generateReport(true);
      return;
    }
    // Enter solo (sin Shift) → activa el filtro de pre-vuelo
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      generateReport(false);
    }
  });
}

/**
 * Abre el modal de confirmación de PulseScout con los datos de la intención detectada.
 * Llamar desde el handler de respuesta de POST /api/v2/pulsescout/intake (Fase 2).
 *
 * @param {object} intent  - Objeto `intent` de la respuesta del servidor
 * @param {string} query   - La `extracted_query` devuelta por el servidor
 */
function showPulseScoutModal(intent, query) {
  if (state.halted) return;

  if (intent.detected_type) {
    const typeEl = el('ps-intent-type');
    if (typeEl) typeEl.textContent = intent.detected_type.replace(/_/g, ' ').toUpperCase();
  }
  if (intent.report_title) {
    const titleEl = el('ps-report-title');
    if (titleEl) titleEl.textContent = intent.report_title;
  }
  if (intent.report_description) {
    const descEl = el('ps-report-description');
    if (descEl) descEl.textContent = intent.report_description;
  }
  if (intent.confirmation_prompt) {
    const subtitleEl = document.getElementById('ps-modal-subtitle') || null;
    if (subtitleEl) subtitleEl.textContent = intent.confirmation_prompt;
  }

  const queryEl = el('ps-extracted-query');
  if (queryEl) queryEl.textContent = query || '';

  el('pulsescout-preview-modal').classList.remove('hidden');
  el('ps-confirm-btn').focus();
}

function hidePulseScoutModal() {
  el('pulsescout-preview-modal').classList.add('hidden');
}

/* ══════════════════════════════════════════════════════════════
   DRAG & DROP
   ══════════════════════════════════════════════════════════════ */
function wireDragDrop() {
  const dropZone = el('drop-zone');

  // Prevenir comportamiento por defecto del navegador en toda la página
  ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(evt => {
    document.addEventListener(evt, e => e.preventDefault(), false);
  });

  dropZone.addEventListener('dragenter', () => dropZone.classList.add('bp-drop-active'));
  dropZone.addEventListener('dragover',  () => dropZone.classList.add('bp-drop-active'));
  dropZone.addEventListener('dragleave', () => dropZone.classList.remove('bp-drop-active'));

  dropZone.addEventListener('drop', e => {
    dropZone.classList.remove('bp-drop-active');
    const file = e.dataTransfer.files[0];
    if (file) handleFileAttach(file);
  });
}

/* ── Procesamiento de archivos (clic + drag & drop) ─────────── */
function handleFileAttach(file) {
  if (!file) return;

  const queryInput = el('query-input');

  if (file.type.startsWith('image/')) {
    // Imagen → Base64 → campo contexto para que la IA de visión la analice
    const reader = new FileReader();
    reader.onload = e => {
      const base64 = e.target.result; // data:image/xxx;base64,…
      el('context-input').value =
        `[Imagen adjunta: ${file.name}]\nBase64: ${base64.substring(0, 200)}…\n`
        + `Describe el contenido de esta imagen en tu consulta para que el motor de IA pueda analizarla.`;
      el('context-block').style.display = 'block';
      showToast(`Imagen adjunta: ${file.name}`, 'success');
    };
    reader.readAsDataURL(file);
    return;
  }

  if (file.type === 'text/plain') {
    const reader = new FileReader();
    reader.onload = e => {
      el('context-input').value = e.target.result.slice(0, 2000);
      el('context-block').style.display = 'block';
      showToast('Documento de texto cargado como contexto.', 'success');
    };
    reader.readAsText(file);
    return;
  }

  // PDF / Word / otros — el navegador no puede leerlos nativamente sin librería
  el('context-input').value =
    `[Documento adjunto: ${file.name} (${(file.size / 1024).toFixed(1)} KB)]\n`
    + 'Pega aquí el texto extraído del documento para que Pulse Metrics IA pueda analizarlo.';
  el('context-block').style.display = 'block';
  showToast('Adjunto registrado. Pega el texto del documento en el campo de contexto.', 'success');

  // Limpiar input file para permitir re-seleccionar el mismo archivo
  const fileInput = el('file-input');
  if (fileInput) fileInput.value = '';
}

/* ══════════════════════════════════════════════════════════════
   VOZ — ENTRADA (Speech Recognition)
   ══════════════════════════════════════════════════════════════ */
function toggleVoice() {
  const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

  if (!SpeechRecognition) {
    showToast('Tu navegador no soporta captura de voz. Usa Chrome o Edge.', 'error');
    return;
  }

  if (state.isRecording) {
    state.recognition && state.recognition.stop();
    return;
  }

  const rec = new SpeechRecognition();
  rec.lang            = 'es-MX';
  rec.continuous      = false;
  rec.interimResults  = false;

  rec.onstart = () => {
    state.isRecording = true;
    el('voice-btn').classList.add('bp-active');
    el('voice-btn').querySelector('.bp-tool-label').textContent = 'Escuchando…';
  };

  rec.onresult = evt => {
    const transcript = evt.results[0][0].transcript;
    const queryInput = el('query-input');
    queryInput.value += (queryInput.value ? ' ' : '') + transcript;
    el('char-count').textContent = queryInput.value.length;
  };

  rec.onend = () => {
    state.isRecording = false;
    el('voice-btn').classList.remove('bp-active');
    el('voice-btn').querySelector('.bp-tool-label').textContent = 'Voz';
  };

  rec.onerror = () => {
    state.isRecording = false;
    el('voice-btn').classList.remove('bp-active');
    el('voice-btn').querySelector('.bp-tool-label').textContent = 'Voz';
    showToast('No se pudo capturar el audio. Intenta de nuevo.', 'error');
  };

  state.recognition = rec;
  rec.start();
}

/* ══════════════════════════════════════════════════════════════
   VOZ — SALIDA (Text-to-Speech)
   ══════════════════════════════════════════════════════════════ */
function toggleTTS() {
  if (!window.speechSynthesis) {
    showToast('Tu navegador no soporta lectura de voz.', 'error');
    return;
  }

  if (state.isSpeaking) {
    window.speechSynthesis.cancel();
    state.isSpeaking = false;
    updateTTSBtn(false);
    return;
  }

  const text = el('report-body').innerText.trim();
  if (!text) return;

  const utterance = new SpeechSynthesisUtterance(text);
  utterance.lang  = 'es-MX';
  utterance.rate  = 0.95;
  utterance.pitch = 1.0;

  // Seleccionar voz en español si está disponible
  const voices = window.speechSynthesis.getVoices();
  const esVoice = voices.find(v => v.lang.startsWith('es'));
  if (esVoice) utterance.voice = esVoice;

  utterance.onstart = () => {
    state.isSpeaking = true;
    updateTTSBtn(true);
  };

  utterance.onend = utterance.onerror = () => {
    state.isSpeaking = false;
    updateTTSBtn(false);
  };

  window.speechSynthesis.speak(utterance);
}

function updateTTSBtn(speaking) {
  const btn = el('listen-btn');
  if (!btn) return;
  if (speaking) {
    btn.innerHTML = '<i class="fa fa-stop"></i> Detener';
    btn.classList.add('bp-active-tts');
  } else {
    btn.innerHTML = '<i class="fa fa-volume-up"></i> Escuchar';
    btn.classList.remove('bp-active-tts');
  }
}

/* ══════════════════════════════════════════════════════════════
   FILTRO DE PRE-VUELO — PulseScout IA
   Intercepta consultas cortas antes de despachar a AURA.
   Muestra el bloque de confirmación con contexto del tenant.
   Umbral: < 80 chars sin contexto adicional.
   ══════════════════════════════════════════════════════════════ */

const PREFLIGHT_THRESHOLD = 80; // caracteres

/**
 * Construye el objeto de intención para el modal PulseScout
 * usando los datos reales del tenant ya cargados en el DOM.
 */
function buildPreflightIntent(query) {
  const total    = el('met-total')       ? el('met-total').textContent.trim()    : '—';
  const contacts = el('met-contacts')    ? el('met-contacts').textContent.trim() : '—';
  const pub      = el('met-published')   ? el('met-published').textContent.trim(): '—';

  return {
    detected_type:       'consulta_analitica',
    report_title:        query.length > 60 ? query.slice(0, 57) + '…' : query,
    report_description:
      `🤖 Agente Inteligente: Detecté una consulta analítica. ` +
      `Voy a procesar tus ${total} propiedades (${pub} publicadas) ` +
      `y ${contacts} prospectos para estructurar el análisis. ` +
      `¿Deseas confirmar la ejecución?`,
    confirmation_prompt: '¿Es esto lo que necesitas?',
  };
}

/* ══════════════════════════════════════════════════════════════
   GENERAR INFORME
   ══════════════════════════════════════════════════════════════ */
async function generateReport(bypassPreflight) {
  if (state.halted) return;

  const query   = el('query-input').value.trim();
  const context = el('context-input').value.trim();

  if (!query) {
    showToast('Escribe una consulta antes de generar el informe.', 'error');
    el('query-input').focus();
    return;
  }

  // ── FILTRO DE PRE-VUELO ──────────────────────────────────────
  // Interceptar consultas cortas sin contexto para mostrar el bloque
  // de confirmación con datos reales del tenant antes de despachar a AURA.
  if (!bypassPreflight && query.length < PREFLIGHT_THRESHOLD && !context) {
    const intent = buildPreflightIntent(query);
    showPulseScoutModal(intent, query);
    return;
  }

  // Detener TTS si está activo
  if (state.isSpeaking) {
    window.speechSynthesis && window.speechSynthesis.cancel();
    state.isSpeaking = false;
    updateTTSBtn(false);
  }

  showOverlay(true);
  el('report-section').classList.add('hidden');

  try {
    const res = await fetch(`${state.apiBase}/api/v2/analytics/query`, {
      method: 'POST',
      headers: {
        'Content-Type':      'application/json',
        'Authorization':     `Bearer ${state.sessionToken}`,
        'X-Requested-With':  'XMLHttpRequest',
      },
      body: JSON.stringify({ query, context }),
    });

    const data = await res.json();

    if (!data.success) {
      showOverlay(false);
      console.error('[ERROR CRÍTICO DETECTADO]:', data.error);
      showQueryError(data.error || 'El servidor devolvió un error. Recarga la página para continuar.');
      return;
    }

    // Pasar meta (network_layer, transaction_id) para el badge ACADEP
    const meta = {
      network_layer:  data.network_layer  || null,
      transaction_id: data.transaction_id || null,
    };
    renderReport(data.report, data.metrics, meta);

  } catch (err) {
    showOverlay(false);
    console.error('[ERROR CRÍTICO DETECTADO]:', err);
    showQueryError('Sin conexión con el servidor. Recarga la página para continuar.');
  }
}

/* ── Error de consulta — banner permanente hasta recarga manual ─ */
function showQueryError(msg) {
  // Congelar el módulo: ninguna acción de submit puede ejecutarse tras este punto.
  state.halted = true;

  // Detener procesos de audio activos
  if (state.isSpeaking && window.speechSynthesis) {
    window.speechSynthesis.cancel();
    state.isSpeaking = false;
    updateTTSBtn(false);
  }
  if (state.isRecording && state.recognition) {
    state.recognition.abort();
    state.isRecording = false;
  }

  // Deshabilitar el botón de generar para reforzar visualmente el estado de error
  const generateBtn = el('generate-btn');
  if (generateBtn) {
    generateBtn.disabled = true;
    generateBtn.textContent = 'Módulo bloqueado';
  }

  // Inyectar el mensaje exacto del servidor en el banner permanente
  const banner = el('query-error-banner');
  const msgEl  = el('query-error-message');
  if (msgEl) msgEl.textContent = msg;
  if (banner) banner.classList.remove('hidden');

  // Scroll al banner para que el usuario lo vea sin buscar
  if (banner) banner.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

/* ── Renderizar el reporte ──────────────────────────────────── */
function renderReport(htmlContent, metrics, meta) {
  showOverlay(false);
  updateMetrics(metrics);

  const now = new Date();
  const ts  = now.toLocaleDateString('es-MX', { day: '2-digit', month: 'long', year: 'numeric' })
            + ' — ' + now.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' });

  el('report-timestamp').textContent  = ts;
  el('print-date').textContent        = ts;
  el('print-footer-date').textContent = ts;
  el('print-company').textContent     = `Informe de Cartera — ${state.companyName}`;

  el('report-body').innerHTML = htmlContent;

  // ── Badge de nodo ACADEP ──────────────────────────────────────
  const badge = el('acadep-node-badge');
  if (badge && meta && meta.network_layer && meta.network_layer !== 'none') {
    const layer = meta.network_layer;        // 'lan' | 'wan'
    const txId  = meta.transaction_id || ''; // TX del nodo central

    badge.className   = `bp-acadep-badge bp-acadep-${layer}`;
    badge.textContent = `ACADEP · ${layer.toUpperCase()}`;
    if (txId) badge.title = `Nodo ACADEP (${layer.toUpperCase()}) · TX: ${txId}`;
    badge.classList.remove('hidden');
  } else if (badge) {
    badge.classList.add('hidden');
  }

  el('report-section').classList.remove('hidden');
  el('report-section').scrollIntoView({ behavior: 'smooth', block: 'start' });

  // Reiniciar botón TTS
  updateTTSBtn(false);
}

/* ── Copiar al portapapeles ─────────────────────────────────── */
function copyReport() {
  const text = el('report-body').innerText;
  if (!navigator.clipboard) {
    showToast('Portapapeles no disponible en este navegador.', 'error');
    return;
  }
  navigator.clipboard.writeText(text).then(() => {
    showToast('Informe copiado al portapapeles.', 'success');
  }).catch(() => {
    showToast('No se pudo copiar el texto.', 'error');
  });
}

/* ── Imprimir / Exportar PDF ────────────────────────────────── */
function printReport() {
  // Detener TTS antes de imprimir para evitar conflictos
  if (state.isSpeaking) {
    window.speechSynthesis && window.speechSynthesis.cancel();
    state.isSpeaking = false;
    updateTTSBtn(false);
  }
  window.print();
}

/* ── Resetear para nueva consulta ───────────────────────────── */
function resetToInput() {
  if (state.isSpeaking) {
    window.speechSynthesis && window.speechSynthesis.cancel();
    state.isSpeaking = false;
    updateTTSBtn(false);
  }
  el('report-section').classList.add('hidden');
  el('query-input').value   = '';
  el('context-input').value = '';
  el('context-block').style.display = 'none';
  el('char-count').textContent = '0';
  el('query-input').focus();
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

/* ── Pantalla de error — estado permanente hasta acción del usuario ── */
function showError(msg) {
  // Activar el flag de halt: cualquier callback asíncrono pendiente
  // (fetch, timeout) verificará este flag y se abortará antes de modificar el DOM.
  state.halted = true;

  // Detener síntesis de voz si estaba activa
  if (state.isSpeaking && window.speechSynthesis) {
    window.speechSynthesis.cancel();
    state.isSpeaking = false;
  }

  // Detener reconocimiento de voz si estaba activo
  if (state.isRecording && state.recognition) {
    state.recognition.abort();
    state.isRecording = false;
  }

  // Renderizar error con el mensaje exacto del servidor — pantalla estática y permanente
  el('screen-loading').classList.add('hidden');
  el('screen-main').classList.add('hidden');
  el('screen-error').classList.remove('hidden');
  el('error-message').textContent = msg;

  // Re-afirmar la URL del botón de retorno con el valor ya resuelto en DOMContentLoaded
  const backUrl = state.apiBase ? `${state.apiBase}/home` : '/home';
  setHref('error-back-btn', backUrl);
}

/* ── Overlay de generación ──────────────────────────────────── */
function showOverlay(show) {
  const overlay = el('generating-overlay');
  if (show) {
    overlay.classList.remove('hidden');
  } else {
    overlay.classList.add('hidden');
  }
}

/* ── Toast ──────────────────────────────────────────────────── */
let toastTimeout = null;
function showToast(msg, type) {
  const toast = el('toast');
  toast.textContent = msg;
  toast.className   = 'bp-toast';
  if (type === 'success') toast.classList.add('bp-toast--success');
  if (type === 'error')   toast.classList.add('bp-toast--error');
  toast.classList.remove('hidden');

  clearTimeout(toastTimeout);
  toastTimeout = setTimeout(() => toast.classList.add('hidden'), 3500);
}

/* ── Helpers ─────────────────────────────────────────────────── */
function el(id)        { return document.getElementById(id); }
function setHref(id, url) { const n = document.getElementById(id); if (n) n.href = url; }
