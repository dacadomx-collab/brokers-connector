/**
 * checkout.js — Módulo V2 de Suscripciones
 *
 * Flujo:
 *  1. Lee ?token= de la URL (bridge token, 60s TTL)
 *  2. GET /api/v2/bridge/validate → quema el token, recibe session_token + datos
 *  3. Renderiza los planes de la DB, inicia OpenPay
 *  4. Usuario selecciona plan + ingresa tarjeta
 *  5. OpenPay tokeniza la tarjeta en el cliente (PCI DSS — número nunca toca nuestro servidor)
 *  6. POST /api/v2/subscriptions con Authorization: Bearer {session_token}
 *  7. Éxito → pantalla de confirmación + countdown → redirect /home
 */

'use strict';

const state = {
  sessionToken:   null,
  company:        null,
  plans:          [],
  openpay:        null,
  selectedPlanId: null,
  apiBase:        '',   // prefijo para todos los fetch — '' = same-origin (producción)
};

// ── Utilidades de UI ──────────────────────────────────────────

function showScreen(id) {
  ['loading', 'error', 'checkout', 'success'].forEach(name => {
    const el = document.getElementById('screen-' + name);
    if (!el) return;
    if (name === id) {
      el.classList.remove('hidden');
      // El screen-checkout usa display:flex pero arranca con display:none inline
      if (name === 'checkout') {
        el.style.display = 'flex';
      }
    } else {
      el.classList.add('hidden');
      if (name === 'checkout') el.style.display = 'none';
    }
  });
}

function showError(message) {
  const el = document.getElementById('error-message');
  if (el) el.textContent = message;
  showScreen('error');
}

function setInlineError(message) {
  const el = document.getElementById('inline-error');
  if (!el) return;
  el.textContent = message;
  el.classList.toggle('visible', Boolean(message));
}

function setPayBtnState(loading) {
  const btn = document.getElementById('btn-pay');
  if (!btn) return;
  btn.disabled = loading;
  btn.innerHTML = loading
    ? '<i class="fa fa-spinner fa-spin"></i> Procesando...'
    : '<i class="fa fa-lock"></i> Suscribirse de forma segura';
}

// ── Renderizado de planes ─────────────────────────────────────

const PLAN_FEATURES_EXTRA = {
  default: [
    { icon: 'check', cls: '',       text: 'Propiedades ilimitadas' },
    { icon: 'check', cls: '',       text: 'Soporte Lun–Dom 8:00–20:00' },
    { icon: 'check', cls: '',       text: 'Bolsa inmobiliaria compartida' },
  ]
};

function renderPlans() {
  const grid = document.getElementById('plan-grid');
  if (!grid || !state.plans.length) return;

  // Nombre del plan actual
  const currentPlan = state.plans.find(p => p.id === state.company.package);
  const nameEl = document.getElementById('current-plan-name');
  if (nameEl) nameEl.textContent = currentPlan ? currentPlan.service : 'Sin plan activo';

  grid.innerHTML = state.plans.map(plan => {
    const isCurrent  = plan.id === state.company.package;
    const priceFormatted = new Intl.NumberFormat('es-MX', {
      style: 'currency', currency: 'MXN', maximumFractionDigits: 0
    }).format(plan.price);

    const extraUserLine = plan.user_price > 0
      ? `<li><span class="feat-icon accent"><i class="fa fa-plus"></i></span>
           ${new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN', maximumFractionDigits: 0 }).format(plan.user_price)} por usuario extra</li>`
      : '';

    const trialLine = plan.days_trial > 0
      ? `<li><span class="feat-icon" style="background:rgba(245,158,11,.1);color:#f59e0b;"><i class="fa fa-gift"></i></span>
           ${plan.days_trial} días de prueba gratis</li>`
      : '';

    const sharedFeatures = PLAN_FEATURES_EXTRA.default.map(f =>
      `<li><span class="feat-icon"><i class="fa fa-${f.icon}"></i></span>${f.text}</li>`
    ).join('');

    return `
      <div class="v2-plan-card ${isCurrent ? 'current-plan' : ''}"
           data-plan-id="${plan.id}"
           data-plan-name="${plan.service}"
           data-plan-price="${priceFormatted}">
        <div class="v2-plan-header">
          <span class="v2-plan-name">${plan.service}</span>
          <div class="v2-plan-price">
            <span class="amount">${priceFormatted.replace('MX$', '$')}</span>
            <span class="currency"> MXN</span>
            <span class="period">por mes</span>
          </div>
        </div>
        <ul class="v2-plan-features">
          <li>
            <span class="feat-icon"><i class="fa fa-users"></i></span>
            ${plan.users_included} usuario${plan.users_included !== 1 ? 's' : ''} incluido${plan.users_included !== 1 ? 's' : ''}
          </li>
          ${extraUserLine}
          ${trialLine}
          ${sharedFeatures}
        </ul>
      </div>`;
  }).join('');

  // Click en cualquier plan card
  grid.querySelectorAll('.v2-plan-card').forEach(card => {
    card.addEventListener('click', () => selectPlan(
      parseInt(card.dataset.planId, 10),
      card.dataset.planName,
      card.dataset.planPrice
    ));
  });

  // Pre-seleccionar plan actual si existe, si no → ninguno seleccionado
  if (currentPlan) {
    selectPlan(currentPlan.id, currentPlan.service,
      new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN', maximumFractionDigits: 0 })
        .format(currentPlan.price).replace('MX$', '$') + ' MXN');
  }
}

function selectPlan(planId, planName, planPrice) {
  state.selectedPlanId = planId;

  // Actualizar clases
  document.querySelectorAll('.v2-plan-card').forEach(card => {
    card.classList.toggle('selected', parseInt(card.dataset.planId, 10) === planId);
  });

  // Actualizar indicador en el formulario
  const labelEl = document.getElementById('selected-plan-label');
  const priceEl = document.getElementById('selected-plan-price');
  if (labelEl) labelEl.textContent = planName;
  if (priceEl) priceEl.textContent = planPrice + ' / mes';

  // Ocultar aviso de "selecciona un plan"
  const hint = document.getElementById('no-plan-hint');
  if (hint) hint.classList.remove('visible');

  setInlineError('');
}

// ── Hamburguesa (menú móvil) ──────────────────────────────────

function initHamburger() {
  const btn  = document.getElementById('v2-hamburger');
  const menu = document.getElementById('v2-mobile-menu');
  if (!btn || !menu) return;

  btn.addEventListener('click', (e) => {
    e.stopPropagation();
    const isOpen = menu.classList.toggle('open');
    btn.classList.toggle('open', isOpen);
    btn.setAttribute('aria-expanded', String(isOpen));
    menu.setAttribute('aria-hidden', String(!isOpen));
  });

  // Cerrar al hacer clic fuera
  document.addEventListener('click', () => {
    if (menu.classList.contains('open')) {
      menu.classList.remove('open');
      btn.classList.remove('open');
      btn.setAttribute('aria-expanded', 'false');
      menu.setAttribute('aria-hidden', 'true');
    }
  });
}

// ── Logo OpenPay con fallback de texto ────────────────────────

function initOpenPayLogo() {
  const img      = document.getElementById('openpay-logo');
  const fallback = document.getElementById('openpay-text-fallback');
  if (!img) return;
  img.addEventListener('error', () => {
    img.style.display = 'none';
    if (fallback) fallback.style.display = 'inline-block';
  });
}

// ── Inicialización de OpenPay ─────────────────────────────────

function initOpenPay() {
  if (typeof OpenPay === 'undefined') return;
  OpenPay.setId(state.openpay.id);
  OpenPay.setApiKey(state.openpay.public_key);
  OpenPay.setSandboxMode(state.openpay.sandbox);
  OpenPay.deviceData.setup('checkout-form', 'device-id-field');
}

// ── Submit del formulario ─────────────────────────────────────

function handlePayClick() {
  if (!state.selectedPlanId) {
    const hint = document.getElementById('no-plan-hint');
    if (hint) hint.classList.add('visible');
    return;
  }

  setInlineError('');
  setPayBtnState(true);

  // OpenPay exige el número de tarjeta sin espacios
  const cardInput = document.querySelector('[data-openpay-card="card_number"]');
  if (cardInput) cardInput.value = cardInput.value.replace(/\s+/g, '');

  OpenPay.token.extractFormAndCreate(
    'checkout-form',
    onOpenPaySuccess,
    onOpenPayError
  );
}

async function onOpenPaySuccess(response) {
  const tokenId  = response.data.id;
  const deviceId = document.getElementById('device-id-field').value;

  try {
    const res = await fetch(state.apiBase + '/api/v2/subscriptions', {
      method: 'POST',
      headers: {
        'Content-Type':  'application/json',
        'Authorization': 'Bearer ' + state.sessionToken,
        'Accept':        'application/json',
      },
      body: JSON.stringify({
        plan_id:   state.selectedPlanId,
        token_id:  tokenId,
        device_id: deviceId,
      }),
    });

    const data = await res.json();

    if (data.success) {
      startSuccessFlow();
    } else {
      setInlineError(data.error || 'Error al procesar el pago.');
      setPayBtnState(false);
    }

  } catch (_) {
    setInlineError('Error de conexión. Verifica tu internet e intenta de nuevo.');
    setPayBtnState(false);
  }
}

function onOpenPayError(response) {
  const errorMap = {
    1001: 'Todos los campos de la tarjeta son requeridos.',
    2004: 'El número de tarjeta no es válido.',
    2005: 'La fecha de vencimiento ha pasado.',
    2006: 'El código de seguridad es requerido.',
  };
  const code  = response.data && response.data.error_code;
  const error = errorMap[code]
    || (response.data && response.data.description)
    || 'Error al validar la tarjeta.';

  setInlineError(error);
  setPayBtnState(false);
}

// ── Pantalla de éxito + redirección ──────────────────────────

function startSuccessFlow() {
  const nameEl = document.getElementById('success-company-name');
  if (nameEl && state.company) nameEl.textContent = state.company.name;

  showScreen('success');

  let seconds = 5;
  const countEl = document.querySelector('#success-countdown strong');

  const interval = setInterval(() => {
    seconds -= 1;
    if (countEl) countEl.textContent = seconds;
    if (seconds <= 0) {
      clearInterval(interval);
      window.location.href = state.apiBase + '/home';
    }
  }, 1000);
}

// ── Boot principal ────────────────────────────────────────────

async function boot() {
  // Año en footer
  const yearEl = document.getElementById('footer-year');
  if (yearEl) yearEl.textContent = new Date().getFullYear();

  showScreen('loading');

  const params = new URLSearchParams(window.location.search);
  const bridgeToken = params.get('token');

  // api param: URL base de la API de Laravel. Inyectada por BridgeController cuando
  // frontend y API están en orígenes distintos (ej. XAMPP local). Vacía en producción.
  state.apiBase = params.get('api') ? decodeURIComponent(params.get('api')) : '';

  // ── Inyectar URLs dinámicas ahora que apiBase está resuelto ──
  const homeUrl    = state.apiBase + '/home';
  const logoutUrl  = state.apiBase + '/home/v2/logout';
  const logoUrl    = state.apiBase + '/img/logo/logo-recortado.png';
  const privacyUrl = state.apiBase + '/privacy-politics';

  const setHref = (id, href) => { const el = document.getElementById(id); if (el) el.href = href; };
  const setSrc  = (id, src)  => { const el = document.getElementById(id); if (el) el.src  = src;  };

  setSrc('header-logo-img', logoUrl);
  setHref('header-logo-link', homeUrl);
  setHref('nav-dashboard',   homeUrl);
  setHref('nav-dashboard-m', homeUrl);
  setHref('nav-logout',      logoutUrl);
  setHref('nav-logout-m',    logoutUrl);
  setHref('error-back-btn',  homeUrl);
  setHref('footer-privacy',  privacyUrl);

  // Activar logo fallback y hamburguesa inmediatamente
  initOpenPayLogo();
  initHamburger();

  // Activar logo fallback de texto si la imagen no carga
  const logoImg      = document.getElementById('header-logo-img');
  const logoFallback = document.getElementById('header-logo-fallback');
  if (logoImg && logoFallback) {
    logoImg.addEventListener('error', () => {
      logoImg.style.display    = 'none';
      logoFallback.style.display = 'flex';
    });
  }

  if (!bridgeToken) {
    showError('Enlace inválido. No se encontró el parámetro de autenticación. Regresa al panel.');
    return;
  }

  try {
    const res = await fetch(state.apiBase + '/api/v2/bridge/validate?token=' + encodeURIComponent(bridgeToken), {
      headers: { 'Accept': 'application/json' },
    });

    const data = await res.json();

    if (!data.success) {
      showError(data.error || 'Token inválido o expirado. Regresa al panel e intenta de nuevo.');
      return;
    }

    // Guardar contexto en estado local (no en localStorage — es sensible)
    state.sessionToken = data.session_token;
    state.company      = data.company;
    state.plans        = data.plans;
    state.openpay      = data.openpay;

    renderPlans();
    initOpenPay();
    showScreen('checkout');

    document.getElementById('btn-pay').addEventListener('click', handlePayClick);

  } catch (_) {
    showError('Error de conexión al verificar tu sesión. Recarga la página e intenta de nuevo.');
  }
}

document.addEventListener('DOMContentLoaded', boot);
