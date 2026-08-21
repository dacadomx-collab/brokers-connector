/**
 * create.js — Expediente Jurídico: Nueva Propiedad (Motor MAIA, V2 SPA)
 *
 * Flujo de arranque idéntico al resto de módulos V2 (Mandamiento #14):
 *  1. Lee ?token= (bridge token) y ?api= (API base) de la URL
 *  2. GET /api/v2/bridge/validate → quema el bridge token, obtiene session_token
 *  3. Renderiza el formulario del Expediente Jurídico
 *
 * Esta misión NO implementa el envío real (POST) — ver handleSubmit().
 * Auth: Bearer session_token en memoria (no en localStorage).
 */

'use strict';

// ── Estado global ─────────────────────────────────────────────────
const state = {
  sessionToken: null,
  company:      null,
  apiBase:      '',
};

// Documentos obligatorios — el botón de envío depende de que estos 4
// tengan un archivo seleccionado en el DOM.
const REQUIRED_DOC_KEYS = [
  'titulo_propiedad',
  'libertad_gravamen',
  'valuacion_catastral',
  'contrato_comercializacion',
];

// data-doc-key → Set de keys que ya tienen archivo seleccionado
const filledDocs = new Set();

// ── Utilidades de pantalla ────────────────────────────────────────

function showScreen(id) {
  ['loading', 'error', 'main'].forEach(name => {
    const el = document.getElementById('screen-' + name);
    if (!el) return;
    if (name === id) {
      el.classList.remove('hidden');
      if (name === 'main') el.style.display = 'flex';
    } else {
      el.classList.add('hidden');
      if (name === 'main') el.style.display = 'none';
    }
  });
}

function showError(msg) {
  const el = document.getElementById('error-message');
  if (el) el.textContent = msg;
  showScreen('error');
}

// ── Hamburger (idéntico al estándar V2) ───────────────────────────

function initHamburger() {
  const btn  = document.getElementById('hamburger-btn');
  const menu = document.getElementById('mobile-menu');
  if (!btn || !menu) return;

  btn.addEventListener('click', e => {
    e.stopPropagation();
    const isOpen = menu.classList.toggle('open');
    btn.classList.toggle('open', isOpen);
    btn.setAttribute('aria-expanded', String(isOpen));
    menu.setAttribute('aria-hidden', String(!isOpen));
  });

  document.addEventListener('click', () => {
    if (menu.classList.contains('open')) {
      menu.classList.remove('open');
      btn.classList.remove('open');
      btn.setAttribute('aria-expanded', 'false');
      menu.setAttribute('aria-hidden', 'true');
    }
  });
}

// ── Validación reactiva del Expediente Jurídico ───────────────────

function updateSubmitState() {
  const btn  = document.getElementById('btn-submit-maia');
  const hint = document.getElementById('maia-submit-hint');
  if (!btn) return;

  const missing = REQUIRED_DOC_KEYS.filter(key => !filledDocs.has(key));
  const allRequiredFilled = missing.length === 0;

  btn.disabled = !allRequiredFilled;

  if (hint) {
    hint.innerHTML = allRequiredFilled
      ? '<i class="fa fa-check-circle"></i> Todos los documentos obligatorios están listos.'
      : '<i class="fa fa-info-circle"></i> Faltan ' + missing.length + ' documento(s) obligatorio(s) para continuar.';
    hint.classList.toggle('maia-submit-hint--ready', allRequiredFilled);
  }
}

function handleFileSelected(input) {
  const key  = input.dataset.docKey;
  const card = input.closest('.maia-doc-card');
  const filenameEl = input.closest('.maia-dropzone').querySelector('.maia-dropzone-filename');
  const file = input.files && input.files[0] ? input.files[0] : null;

  if (file) {
    filledDocs.add(key);
    if (card) card.classList.add('maia-doc-card--filled');
    if (filenameEl) filenameEl.textContent = file.name;
  } else {
    filledDocs.delete(key);
    if (card) card.classList.remove('maia-doc-card--filled');
    if (filenameEl) filenameEl.textContent = '';
  }

  updateSubmitState();
}

function initDropzones() {
  document.querySelectorAll('.maia-doc-input').forEach(input => {
    input.addEventListener('change', () => handleFileSelected(input));
  });

  // Soporte de arrastrar-y-soltar sobre cada dropzone
  document.querySelectorAll('.maia-dropzone').forEach(zone => {
    const input = zone.querySelector('.maia-doc-input');
    if (!input) return;

    ['dragenter', 'dragover'].forEach(evt => {
      zone.addEventListener(evt, e => {
        e.preventDefault();
        zone.classList.add('maia-dropzone--dragover');
      });
    });

    ['dragleave', 'dragend'].forEach(evt => {
      zone.addEventListener(evt, () => zone.classList.remove('maia-dropzone--dragover'));
    });

    zone.addEventListener('drop', e => {
      e.preventDefault();
      zone.classList.remove('maia-dropzone--dragover');

      if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length) {
        input.files = e.dataTransfer.files;
        input.dispatchEvent(new Event('change'));
      }
    });
  });
}

// ── Envío del formulario (preparado, sin fetch aún) ───────────────

function handleSubmit(event) {
  event.preventDefault();

  const form = document.getElementById('form-maia-expediente');
  const formData = new FormData(form);

  // Próxima misión: reemplazar este log por el fetch POST real hacia
  // el endpoint del Motor MAIA (procesamiento async vía Laravel Queues).
  console.log('[MAIA] FormData del Expediente Jurídico:');
  for (const [fieldName, value] of formData.entries()) {
    console.log(' -', fieldName, ':', value instanceof File ? `${value.name} (${value.size} bytes)` : value);
  }
}

function initForm() {
  const form = document.getElementById('form-maia-expediente');
  if (form) form.addEventListener('submit', handleSubmit);

  initDropzones();
  updateSubmitState();
}

// ── Boot principal ────────────────────────────────────────────────

async function boot() {
  document.getElementById('footer-year').textContent = new Date().getFullYear();
  showScreen('loading');

  const params      = new URLSearchParams(window.location.search);
  const bridgeToken = params.get('token');
  state.apiBase     = params.get('api') ? decodeURIComponent(params.get('api')) : '';

  // Navbar estándar V2 — URLs dinámicas, nunca hardcodeadas (Mandamiento #14)
  const homeUrl    = state.apiBase + '/home';
  const logoutUrl  = state.apiBase + '/home/v2/logout';
  const aiHubUrl   = state.apiBase + '/home/v2/ai-hub-bridge';
  const logoUrl    = state.apiBase + '/img/logo/logo-recortado.png';
  const privacyUrl = state.apiBase + '/privacy-politics';

  const setHref = (id, href) => { const el = document.getElementById(id); if (el) el.href = href; };
  const setSrc  = (id, src)  => { const el = document.getElementById(id); if (el) el.src  = src;  };

  setSrc('header-logo-img',   logoUrl);
  setHref('header-logo-link', homeUrl);
  setHref('nav-ai-hub',       aiHubUrl);
  setHref('nav-ai-hub-m',     aiHubUrl);
  setHref('nav-panel',        homeUrl);
  setHref('nav-panel-m',      homeUrl);
  setHref('nav-logout',       logoutUrl);
  setHref('nav-logout-m',     logoutUrl);
  setHref('footer-privacy',   privacyUrl);

  // El botón de retorno del error se configura ANTES de cualquier
  // return del boot — debe funcionar incluso con token inválido/expirado.
  setHref('error-back-btn', homeUrl);

  initHamburger();

  const logoImg      = document.getElementById('header-logo-img');
  const logoFallback = document.getElementById('header-logo-fallback');
  if (logoImg && logoFallback) {
    logoImg.addEventListener('error', () => {
      logoImg.style.display      = 'none';
      logoFallback.style.display = 'flex';
    });
  }

  if (!bridgeToken) {
    showError('Enlace inválido. No se encontró el parámetro de autenticación. Regresa al panel.');
    return;
  }

  try {
    const res  = await fetch(
      state.apiBase + '/api/v2/bridge/validate?token=' + encodeURIComponent(bridgeToken),
      { headers: { 'Accept': 'application/json' } }
    );
    const data = await res.json();

    if (!data.success) {
      showError(data.error || 'Token inválido o expirado. Regresa al panel e intenta de nuevo.');
      return;
    }

    state.sessionToken = data.session_token;
    state.company      = data.company;

    showScreen('main');
    initForm();

  } catch (_) {
    showError('Error de conexión al verificar tu sesión. Recarga la página e intenta de nuevo.');
  }
}

document.addEventListener('DOMContentLoaded', boot);
