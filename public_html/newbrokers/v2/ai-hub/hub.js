/**
 * hub.js — AI Hub: Dashboard Central del Broker Brain IA (V2 SPA)
 *
 * Flujo:
 *  1. Lee ?token= (bridge token) y ?api= (API base) de la URL
 *  2. GET /api/v2/bridge/validate → quema el bridge token, obtiene session_token + company
 *  3. Renderiza el hub con nombre de empresa y configura los botones de módulos
 *  4. Clic en módulo activo → navega a su bridge route Legacy (usa cookie de sesión)
 *
 * El hub es un Launcher puro: no hace llamadas API adicionales tras la validación.
 * Auth: Bearer session_token en memoria (no en localStorage).
 */

'use strict';

// ── Estado global ─────────────────────────────────────────────────
const state = {
  sessionToken: null,
  company:      null,
  apiBase:      '',
};

// ── Rutas de módulos (se rellenan tras conocer apiBase) ───────────
const MODULE_ROUTES = {
  cma:   '/home/v2/broker-brain-bridge',
  radar: '/home/v2/radar-bridge',
};

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

// ── Hamburger ─────────────────────────────────────────────────────

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

// ── Navegación de módulos ─────────────────────────────────────────

function initModuleButtons() {
  document.querySelectorAll('.hub-btn-action').forEach(btn => {
    const module = btn.dataset.module;
    if (!module || !MODULE_ROUTES[module]) return;

    btn.addEventListener('click', () => {
      // Navega al bridge Legacy — la cookie de sesión PHP acompaña el request
      window.location.href = state.apiBase + MODULE_ROUTES[module];
    });
  });
}

// ── Boot principal ────────────────────────────────────────────────

async function boot() {
  document.getElementById('footer-year').textContent = new Date().getFullYear();
  showScreen('loading');

  const params      = new URLSearchParams(window.location.search);
  const bridgeToken = params.get('token');
  state.apiBase     = params.get('api') ? decodeURIComponent(params.get('api')) : '';

  // Navbar estándar V2 — 3 botones fijos
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
  setHref('error-back-btn',   homeUrl);
  setHref('footer-privacy',   privacyUrl);
  setHref('hub-back-panel',   homeUrl);

  initHamburger();

  // Fallback de logo
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

    // Mostrar nombre de empresa en el hero
    const nameEl = document.getElementById('company-name');
    if (nameEl) nameEl.textContent = data.company ? data.company.name : 'Tu agencia';

    showScreen('main');
    initModuleButtons();

  } catch (_) {
    showError('Error de conexión al verificar tu sesión. Recarga la página e intenta de nuevo.');
  }
}

document.addEventListener('DOMContentLoaded', boot);
