/**
 * radar.js — Radar de Plusvalía y Crecimiento Urbano (V2 SPA)
 *
 * Flujo:
 *  1. Lee ?token= (bridge token) y ?api= (API base) de la URL
 *  2. GET /api/v2/bridge/validate → quema el bridge token, obtiene session_token
 *  3. GET /api/v2/radar/heatmap  → carga zonas y renderiza en Leaflet + sidebar
 *  4. Click en zona → GET /api/v2/radar/zone/{zipcode} → panel detallado
 *
 * Auth: Bearer session_token en memoria (no en localStorage).
 * Arquitectura: Strangler Fig Pattern — SPA V2 aislada sin Blade.
 */

'use strict';

// ── Estado global ─────────────────────────────────────────────────
const state = {
  sessionToken:    null,
  company:         null,
  apiBase:         '',
  zones:           [],
  selectedZipcode: null,
  leafletMap:      null,
  leafletMarkers:  [],
  filterStatus:    '',
  filterType:      '',
  loading:         false,
};

// Guard de ejecución única: previene double-burn si boot() se invoca más de una vez
// (extensiones de navegador, devtools, history navigation, etc.)
let _bootExecuted = false;

// ── Utilidades generales ──────────────────────────────────────────

function escHtml(str) {
  const div = document.createElement('div');
  div.textContent = String(str ?? '');
  return div.innerHTML;
}

function formatMXN(amount) {
  if (!amount || isNaN(amount)) return '—';
  return new Intl.NumberFormat('es-MX', {
    style: 'currency', currency: 'MXN', maximumFractionDigits: 0,
  }).format(amount);
}

function formatNum(n) {
  if (n == null || isNaN(n)) return '—';
  return new Intl.NumberFormat('es-MX').format(Math.round(n));
}

// ── Manejo de pantallas ───────────────────────────────────────────

function showScreen(id) {
  const screens = ['loading', 'error', 'main'];
  screens.forEach(name => {
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

// ── Manual Radar — toggle ocultar/mostrar ─────────────────────────

function initManualToggle(toggleId, bodyId, textId) {
  const btn  = document.getElementById(toggleId);
  const body = document.getElementById(bodyId);
  const txt  = document.getElementById(textId);
  if (!btn || !body) return;

  btn.addEventListener('click', () => {
    const collapsed = body.classList.toggle('collapsed');
    btn.classList.toggle('open', collapsed);
    btn.setAttribute('aria-expanded', String(!collapsed));
    if (txt) txt.textContent = collapsed ? 'Ver guía' : 'Ocultar guía';
  });
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

// ── Color del calor ───────────────────────────────────────────────

function heatColor(score) {
  // 0-33: azul (frío), 34-66: ámbar (tibio), 67-100: rojo (caliente)
  if (score >= 67) return '#ef4444';
  if (score >= 34) return '#f59e0b';
  return '#3b82f6';
}

function heatLabel(signal) {
  const labels = {
    ninguna:    'Sin señal',
    incipiente: 'Incipiente',
    moderada:   'Moderada',
    avanzada:   'Avanzada',
  };
  return labels[signal] || signal;
}

function heatSignalClass(signal) {
  const map = {
    ninguna:    'signal-none',
    incipiente: 'signal-low',
    moderada:   'signal-mid',
    avanzada:   'signal-high',
  };
  return map[signal] || 'signal-none';
}

// ── Mapa Leaflet ──────────────────────────────────────────────────

function initMap() {
  if (state.leafletMap) return;

  // Guard: Leaflet puede no estar disponible si la CDN no cargó (sin internet en local)
  if (typeof L === 'undefined') {
    const container = document.getElementById('radar-map');
    if (container) {
      container.className = 'radar-map-offline';
      container.innerHTML = '<i class="fa fa-wifi" aria-hidden="true"></i>'
        + '<p>Mapa no disponible (Leaflet CDN no cargó).</p>'
        + '<p class="radar-map-offline-hint">Requiere conexión a internet para tiles de OpenStreetMap.</p>';
    }
    return;
  }

  const placeholder = document.getElementById('map-placeholder');
  if (placeholder) placeholder.remove();

  // Centrar en México por defecto
  state.leafletMap = L.map('radar-map', {
    center: [23.6345, -102.5528],
    zoom:   5,
    zoomControl: true,
  });

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; <a href="https://openstreetmap.org/copyright">OpenStreetMap</a>',
    maxZoom: 19,
  }).addTo(state.leafletMap);
}

function renderMapZones(zones) {
  // Limpiar marcadores anteriores
  state.leafletMarkers.forEach(m => m.remove());
  state.leafletMarkers = [];

  const boundsPoints = [];

  zones.forEach(zone => {
    if (!zone.center_lat || !zone.center_lng) return;

    const lat   = parseFloat(zone.center_lat);
    const lng   = parseFloat(zone.center_lng);
    const score = zone.heat_score || 0;
    const color = heatColor(score);

    if (isNaN(lat) || isNaN(lng)) return;

    boundsPoints.push([lat, lng]);

    const radius = Math.max(14, Math.min(40, 14 + zone.active_listings * 2));

    const circle = L.circleMarker([lat, lng], {
      radius:      radius,
      fillColor:   color,
      color:       '#fff',
      weight:      2,
      opacity:     1,
      fillOpacity: 0.75,
    }).addTo(state.leafletMap);

    // Popup al hover
    circle.bindTooltip(
      `<strong>CP ${escHtml(zone.zipcode)}</strong><br>` +
      `${escHtml(zone.active_listings)} prop. | ` +
      (zone.avg_price_per_m2 ? `$${formatNum(zone.avg_price_per_m2)}/m²` : 'sin precio/m²'),
      { sticky: true, className: 'radar-tooltip' }
    );

    circle.on('click', () => selectZone(zone.zipcode));

    state.leafletMarkers.push(circle);
  });

  // Ajustar bounds al conjunto de coordenadas
  if (boundsPoints.length > 0) {
    const bounds = L.latLngBounds(boundsPoints);
    state.leafletMap.fitBounds(bounds, { padding: [40, 40], maxZoom: 13 });
  }
}

function highlightMapZone(zipcode) {
  state.leafletMarkers.forEach((m, i) => {
    const zone = state.zones.find(z => z.zipcode === state.zones[i]?.zipcode);
    if (!zone) return;
    if (zone.zipcode === zipcode) {
      m.setStyle({ weight: 3, color: '#4f46e5', fillOpacity: 1 });
      m.bringToFront();
    } else {
      m.setStyle({ weight: 2, color: '#fff', fillOpacity: 0.65 });
    }
  });
}

// ── KPIs ──────────────────────────────────────────────────────────

function renderKpis(summary) {
  const set = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };
  set('kpi-zones',    summary.total_zones    ?? '—');
  set('kpi-listings', summary.total_listings ?? '—');
  set('kpi-hottest',  summary.hottest_zipcode ? 'CP ' + summary.hottest_zipcode : '—');
  set('kpi-avg-m2',   summary.avg_price_per_m2 ? formatMXN(summary.avg_price_per_m2) + '/m²' : '—');
}

// ── Lista de zonas ────────────────────────────────────────────────

function renderZoneList(zones) {
  const skeleton = document.getElementById('zone-skeleton');
  if (skeleton) skeleton.remove();

  const list = document.getElementById('zone-list');
  if (!list) return;

  if (zones.length === 0) {
    list.innerHTML = `
      <div class="zone-empty">
        <i class="fa fa-map-o"></i>
        <p>No hay zonas con propiedades activas.</p>
        <p class="zone-empty-hint">Agrega propiedades con código postal para ver el análisis.</p>
      </div>`;
    return;
  }

  list.innerHTML = zones.map(zone => {
    const score   = zone.heat_score ?? 0;
    const color   = heatColor(score);
    const signal  = heatLabel(zone.gentrification_signal || 'ninguna');
    const sigCls  = heatSignalClass(zone.gentrification_signal || 'ninguna');
    const drivers = (zone.growth_drivers || []).map(d =>
      `<span class="driver-chip">${escHtml(d.replace(/_/g, ' '))}</span>`
    ).join('');

    return `
      <div class="zone-card" data-zip="${escHtml(zone.zipcode)}"
           role="button" tabindex="0" aria-label="Ver detalle zona ${escHtml(zone.zipcode)}">
        <div class="zone-card-top">
          <div class="zone-card-left">
            <div class="zone-heat-dot" style="background:${escHtml(color)};"
                 title="Heat score: ${escHtml(score)}">
              <span class="zone-heat-score">${escHtml(score)}</span>
            </div>
            <div>
              <div class="zone-cp">CP ${escHtml(zone.zipcode)}</div>
              <div class="zone-listings">${escHtml(zone.active_listings)} propiedad${zone.active_listings !== 1 ? 'es' : ''}</div>
            </div>
          </div>
          <div class="zone-card-right">
            <div class="zone-pm2">
              ${zone.avg_price_per_m2 ? escHtml(formatMXN(zone.avg_price_per_m2)) + '<small>/m²</small>' : '<span class="muted">—</span>'}
            </div>
            <span class="zone-signal ${sigCls}">${escHtml(signal)}</span>
          </div>
        </div>
        ${drivers ? `<div class="zone-drivers">${drivers}</div>` : ''}
        <div class="zone-confidence">
          <div class="zone-conf-bar">
            <div class="zone-conf-fill" style="width:${Math.min(100, (zone.confidence_score || 0))}%"></div>
          </div>
          <span class="zone-conf-label">Confianza: ${escHtml(zone.confidence_score || 0)}%</span>
        </div>
      </div>`;
  }).join('');

  // Eventos en cada tarjeta
  list.querySelectorAll('.zone-card').forEach(card => {
    const zip = parseInt(card.dataset.zip, 10);
    card.addEventListener('click', () => selectZone(zip));
    card.addEventListener('keydown', e => {
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); selectZone(zip); }
    });
  });
}

function markActiveCard(zipcode) {
  document.querySelectorAll('.zone-card').forEach(c => {
    c.classList.toggle('active', parseInt(c.dataset.zip, 10) === zipcode);
  });
}

// ── Selección y detalle de zona ───────────────────────────────────

async function selectZone(zipcode) {
  state.selectedZipcode = zipcode;
  markActiveCard(zipcode);
  highlightMapZone(zipcode);
  showDetailPanel(zipcode);

  const content = document.getElementById('zone-detail-content');
  if (content) {
    content.innerHTML = `
      <div class="v2-screen" style="min-height:auto; padding:40px 0;">
        <div class="v2-spinner"></div>
        <p class="v2-loading-text">Cargando detalle&hellip;</p>
      </div>`;
  }

  try {
    const res  = await fetch(state.apiBase + '/api/v2/radar/zone/' + zipcode, {
      headers: {
        'Accept':        'application/json',
        'Authorization': 'Bearer ' + state.sessionToken,
      },
    });
    const data = await res.json();

    if (!res.ok || data.success === false) {
      if (content) content.innerHTML = `<p class="detail-error">${escHtml(data.error || 'Error al cargar el detalle.')}</p>`;
      return;
    }

    renderZoneDetail(data);

  } catch (_) {
    if (content) content.innerHTML = `<p class="detail-error">Error de conexión. Intenta de nuevo.</p>`;
  }
}

function showDetailPanel(zipcode) {
  document.getElementById('panel-list').classList.add('hidden');
  document.getElementById('panel-detail').classList.remove('hidden');
  const zipEl = document.getElementById('detail-zip');
  if (zipEl) zipEl.textContent = zipcode;
}

function showListPanel() {
  document.getElementById('panel-detail').classList.add('hidden');
  document.getElementById('panel-list').classList.remove('hidden');
  state.selectedZipcode = null;
  markActiveCard(null);
  // Quitar highlight en mapa
  state.leafletMarkers.forEach(m => m.setStyle({ weight: 2, color: '#fff', fillOpacity: 0.75 }));
}

function renderZoneDetail(data) {
  const content = document.getElementById('zone-detail-content');
  if (!content) return;

  const byType = (data.breakdown_by_type || []).map(t => `
    <tr>
      <td>${escHtml(t.type)}</td>
      <td>${escHtml(t.count)}</td>
      <td>${t.avg_price ? escHtml(formatMXN(t.avg_price)) : '—'}</td>
      <td>${t.avg_m2 ? escHtml(formatMXN(t.avg_m2)) + '/m²' : '—'}</td>
    </tr>`).join('');

  const trendRows = (data.trend_series || []).map(t => `
    <tr>
      <td>${escHtml(t.period_start ? t.period_start.substring(0, 7) : '—')}</td>
      <td>${t.avg_price_per_m2 ? escHtml(formatMXN(t.avg_price_per_m2)) : '—'}</td>
      <td class="${t.trend_pct > 0 ? 'trend-up' : t.trend_pct < 0 ? 'trend-down' : ''}">
        ${t.trend_pct != null ? (t.trend_pct > 0 ? '+' : '') + parseFloat(t.trend_pct).toFixed(1) + '%' : '—'}
      </td>
      <td>${escHtml(t.sample_count)}</td>
    </tr>`).join('');

  const hasTrends  = data.trend_series && data.trend_series.length > 0;
  const signalCls  = heatSignalClass(data.gentrification_signal || 'ninguna');
  const signalLbl  = heatLabel(data.gentrification_signal || 'ninguna');
  const drivers    = (data.growth_drivers || []).map(d =>
    `<span class="driver-chip">${escHtml(d.replace(/_/g, ' '))}</span>`
  ).join('');

  content.innerHTML = `

    <!-- Métricas principales -->
    <div class="detail-kpi-grid">
      <div class="detail-kpi">
        <span class="detail-kpi-val">${escHtml(data.active_listings)}</span>
        <span class="detail-kpi-lbl">Propiedades activas</span>
      </div>
      <div class="detail-kpi">
        <span class="detail-kpi-val">${data.avg_price_per_m2 ? escHtml(formatMXN(data.avg_price_per_m2)) : '—'}</span>
        <span class="detail-kpi-lbl">Precio / m² promedio</span>
      </div>
      <div class="detail-kpi">
        <span class="detail-kpi-val">${data.avg_price ? escHtml(formatMXN(data.avg_price)) : '—'}</span>
        <span class="detail-kpi-lbl">Precio promedio</span>
      </div>
      <div class="detail-kpi">
        <span class="detail-kpi-val ${signalCls}">${escHtml(signalLbl)}</span>
        <span class="detail-kpi-lbl">Señal de gentrificación</span>
      </div>
    </div>

    <!-- Rango de precios -->
    <div class="detail-range">
      <span class="detail-range-label">Rango</span>
      <span class="detail-range-value">${data.min_price ? escHtml(formatMXN(data.min_price)) : '—'}</span>
      <div class="detail-range-bar"></div>
      <span class="detail-range-value">${data.max_price ? escHtml(formatMXN(data.max_price)) : '—'}</span>
    </div>

    <!-- Señales de crecimiento -->
    ${drivers ? `
    <div class="detail-section">
      <h3 class="detail-section-title"><i class="fa fa-signal"></i> Señales de mercado</h3>
      <div class="detail-drivers">${drivers}</div>
    </div>` : ''}

    <!-- Insight AURA (si disponible) -->
    ${data.aura_insight ? `
    <div class="detail-section detail-aura">
      <h3 class="detail-section-title"><i class="fa fa-lightbulb-o"></i> Análisis AURA</h3>
      <p class="detail-aura-text">${escHtml(data.aura_insight)}</p>
    </div>` : ''}

    <!-- Desglose por tipo -->
    ${byType ? `
    <div class="detail-section">
      <h3 class="detail-section-title"><i class="fa fa-home"></i> Desglose por tipo</h3>
      <div class="detail-table-wrap">
        <table class="detail-table">
          <thead><tr><th>Tipo</th><th>N</th><th>Precio prom.</th><th>Por m²</th></tr></thead>
          <tbody>${byType}</tbody>
        </table>
      </div>
    </div>` : ''}

    <!-- Tendencias históricas -->
    <div class="detail-section">
      <h3 class="detail-section-title"><i class="fa fa-line-chart"></i> Tendencias históricas</h3>
      ${hasTrends ? `
      <div class="detail-table-wrap">
        <table class="detail-table">
          <thead><tr><th>Período</th><th>$/m²</th><th>Cambio</th><th>Muestra</th></tr></thead>
          <tbody>${trendRows}</tbody>
        </table>
      </div>` : `
      <div class="detail-no-trends">
        <i class="fa fa-clock-o"></i>
        <p>Sin datos históricos aún. Los snapshots semanales comenzarán a acumularse
           en cuanto el Scheduler esté activo.</p>
      </div>`}
    </div>

    <!-- Confianza del análisis -->
    <div class="detail-confidence">
      <div class="zone-conf-bar">
        <div class="zone-conf-fill" style="width:${Math.min(100, data.confidence_score || 0)}%"></div>
      </div>
      <p class="zone-conf-label">
        Confianza del análisis: <strong>${escHtml(data.confidence_score || 0)}%</strong>
        ${data.active_listings < 3 ? ' — muestra pequeña; añade más propiedades para mayor precisión.' : '.'}
      </p>
    </div>`;
}

// ── Carga del heatmap ─────────────────────────────────────────────

async function loadHeatmap(fresh = false) {
  if (state.loading) return;
  state.loading = true;

  const btn = document.getElementById('btn-refresh');
  if (btn) btn.disabled = true;

  let url = state.apiBase + '/api/v2/radar/heatmap?min_listings=1';
  if (state.filterStatus) url += '&prop_status_id=' + encodeURIComponent(state.filterStatus);
  if (state.filterType)   url += '&prop_type_id='   + encodeURIComponent(state.filterType);
  if (fresh)              url += '&fresh=true';

  try {
    const res  = await fetch(url, {
      headers: {
        'Accept':        'application/json',
        'Authorization': 'Bearer ' + state.sessionToken,
      },
    });
    const data = await res.json();

    if (!res.ok || data.success === false) {
      if (data.error) {
        const list = document.getElementById('zone-list');
        const skeleton = document.getElementById('zone-skeleton');
        if (skeleton) skeleton.remove();
        if (list) list.innerHTML = `<div class="zone-empty"><i class="fa fa-map-o"></i><p>${escHtml(data.error)}</p></div>`;
      }
      return;
    }

    state.zones = data.zones || [];
    renderKpis(data.summary || {});
    renderZoneList(state.zones);
    renderMapZones(state.zones);

  } catch (_) {
    const skeleton = document.getElementById('zone-skeleton');
    if (skeleton) skeleton.remove();
    const list = document.getElementById('zone-list');
    if (list) list.innerHTML = `<div class="zone-empty"><i class="fa fa-map-o"></i><p>Error de conexión. Recarga la página.</p></div>`;
  } finally {
    state.loading = false;
    if (btn) btn.disabled = false;
  }
}

// ── Carga de catálogos para filtros ──────────────────────────────

async function loadCatalogs() {
  try {
    const res  = await fetch(state.apiBase + '/api/property/search-params', {
      headers: { 'Accept': 'application/json' },
    });
    const data = await res.json();

    const fillSelect = (id, items, key) => {
      const sel = document.getElementById(id);
      if (!sel || !items) return;
      items.forEach(item => {
        const opt = document.createElement('option');
        opt.value       = item.id;
        opt.textContent = item[key] || item.name;
        sel.appendChild(opt);
      });
    };

    fillSelect('filter-status', data.statuses, 'name');
    fillSelect('filter-type',   data.types,    'name');

  } catch (_) {
    // Los catálogos son opcionales — el radar funciona sin filtros
  }
}

// ── Boot principal ────────────────────────────────────────────────

async function boot() {
  // ── Anti double-burn guard ─────────────────────────────────────────
  if (_bootExecuted) {
    console.warn('[Radar] boot() invocado más de una vez — segunda ejecución ignorada.');
    return;
  }
  _bootExecuted = true;

  document.getElementById('footer-year').textContent = new Date().getFullYear();
  showScreen('loading');

  const params      = new URLSearchParams(window.location.search);
  const bridgeToken = params.get('token');
  state.apiBase     = params.get('api') ? decodeURIComponent(params.get('api')) : '';

  // Navbar estándar V2 — 3 botones fijos
  const homeUrl      = state.apiBase + '/home';
  const logoutUrl    = state.apiBase + '/home/v2/logout';
  const aiHubUrl     = state.apiBase + '/home/v2/ai-hub-bridge';
  const logoUrl      = state.apiBase + '/img/logo/logo-recortado.png';
  const privacyUrl   = state.apiBase + '/privacy-politics';

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
  setHref('footer-privacy',    privacyUrl);

  initHamburger();
  initManualToggle('radar-manual-toggle', 'radar-manual-body', 'radar-manual-text');

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

  // ── BLOQUE 1: Validación del bridge token ──────────────────────────
  // El try-catch es EXCLUSIVO de esta operación de red.
  // Errores de UI (mapa, catálogos) NO deben caer aquí.
  const validateUrl = state.apiBase + '/api/v2/bridge/validate?token=' + encodeURIComponent(bridgeToken);
  console.log('[Radar DIAGNÓSTICO] Iniciando validación de sesión.');
  console.log('[Radar DIAGNÓSTICO] URL validate:', validateUrl);
  console.log('[Radar DIAGNÓSTICO] apiBase:', state.apiBase);
  console.log('[Radar DIAGNÓSTICO] bridgeToken (primeros 12):', bridgeToken.substring(0, 12) + '...');

  try {
    const res = await fetch(validateUrl, { headers: { 'Accept': 'application/json' } });

    console.log('[Radar DIAGNÓSTICO] HTTP status recibido:', res.status, res.statusText);

    // Leer como texto primero para diagnóstico completo
    const text = await res.text();
    console.log('[Radar DIAGNÓSTICO] Respuesta cruda del servidor:', text.substring(0, 500));

    let data;
    try {
      data = JSON.parse(text);
    } catch (parseErr) {
      console.error('[Radar DIAGNÓSTICO] JSON.parse falló — respuesta no es JSON. parseErr:', parseErr.message);
      showError('Respuesta inesperada del servidor (HTTP ' + res.status + '). Regresa al panel e intenta de nuevo.');
      return;
    }

    console.log('[Radar DIAGNÓSTICO] data.success:', data.success, '| data.error:', data.error || '(ninguno)');

    if (!data.success) {
      console.error('[Radar DIAGNÓSTICO] Validación fallida. Error del servidor:', data.error);
      showError(data.error || 'Token inválido o expirado. Regresa al panel e intenta de nuevo.');
      return;
    }

    // ── Sesión válida: asentar en memoria y limpiar URL ─────────────
    state.sessionToken = data.session_token;
    state.company      = data.company;

    // Limpiar el bridge token de la URL para evitar reuso en back/refresh
    try {
      const cleanUrl = new URL(window.location.href);
      cleanUrl.searchParams.delete('token');
      window.history.replaceState({}, '', cleanUrl.toString());
      console.log('[Radar DIAGNÓSTICO] Token limpiado de la URL. Sesión asentada.');
    } catch (_) { /* replaceState no crítico */ }

  } catch (netErr) {
    console.error('[Radar DIAGNÓSTICO] Error de red en fetch validate:', netErr.message, netErr);
    showError('Error de red al verificar tu sesión. Verifica tu conexión e intenta de nuevo.');
    return;
  }

  // ── BLOQUE 2: Inicialización de la UI — FUERA del try de sesión ────
  // Un fallo aquí (ej: Leaflet CDN no cargó) NO debe mostrar "error de sesión".
  const companyEl = document.getElementById('company-name');
  if (companyEl && state.company) companyEl.textContent = state.company.name;

  showScreen('main');

  initMap();       // guard interno maneja el caso L === undefined
  loadCatalogs();
  loadHeatmap();

  // ── BLOQUE 3: Event listeners ───────────────────────────────────────
  ['filter-status', 'filter-type'].forEach(id => {
    const el = document.getElementById(id);
    if (!el) return;
    el.addEventListener('change', () => {
      state.filterStatus = document.getElementById('filter-status').value;
      state.filterType   = document.getElementById('filter-type').value;
      if (state.selectedZipcode) showListPanel();
      loadHeatmap(false);
    });
  });

  document.getElementById('btn-refresh').addEventListener('click', () => {
    if (state.selectedZipcode) showListPanel();
    loadHeatmap(true);
  });

  document.getElementById('btn-back').addEventListener('click', showListPanel);
}

document.addEventListener('DOMContentLoaded', boot);
