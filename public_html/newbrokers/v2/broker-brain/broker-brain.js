/* ═══════════════════════════════════════════════════════════════════
   Broker Brain IA — broker-brain.js
   Motor de Tasación y CMA Dinámico — SPA V2 Vanilla JS

   Seguridad:
     - session_token en memoria (jamás en localStorage)
     - escHtml() en toda interpolación de datos externos
     - Sin eval(), sin innerHTML con datos crudos de API

   Flujo:
     boot() → bridgeValidate (intercambia bridge token por session_token)
            → loadCatalogs() → screen-main
            → runCMA() → renderResults()
   ═══════════════════════════════════════════════════════════════════ */

const state = {
  sessionToken: null,    // MEMORIA ÚNICAMENTE — jamás localStorage
  apiBase:      '',
};

// ── Utilidades ───────────────────────────────────────────────────────

function escHtml(str) {
  return String(str ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

function fmtCurrency(n) {
  return '$' + Number(n).toLocaleString('es-MX', { minimumFractionDigits: 0, maximumFractionDigits: 0 }) + ' MXN';
}

function show(id)  { document.getElementById(id).style.display = ''; }
function hide(id)  { document.getElementById(id).style.display = 'none'; }
function qs(sel)   { return document.querySelector(sel); }
function qid(id)   { return document.getElementById(id); }

function setScreen(name) {
  ['loading', 'error', 'main'].forEach(s => {
    const el = document.getElementById('screen-' + s);
    if (el) el.style.display = s === name ? '' : 'none';
  });
}

// ── Boot: intercambio bridge token → session_token ────────────────────

async function boot() {
  setScreen('loading');

  const params  = new URLSearchParams(window.location.search);
  const token   = params.get('token');
  const apiBase = params.get('api') ? decodeURIComponent(params.get('api')) : '';

  if (!token) {
    showError('No se encontró el token de acceso. Regresa al panel.');
    return;
  }

  state.apiBase = apiBase;

  const url = `${apiBase}/api/v2/bridge/validate?token=${encodeURIComponent(token)}`;

  try {
    const res  = await fetch(url, { headers: { 'Accept': 'application/json' } });
    const data = await res.json();

    if (!res.ok || !data.success) {
      showError(data.error || 'Sesión inválida. Regresa al panel e intenta de nuevo.');
      return;
    }

    // session_token en memoria — única ubicación autorizada
    state.sessionToken = data.session_token;

    // Limpiar token de la URL (evitar reuso en historial del navegador)
    const clean = new URL(window.location.href);
    clean.searchParams.delete('token');
    window.history.replaceState({}, '', clean.toString());

    await Promise.all([loadCatalogs(), loadMyProperties()]);
    setScreen('main');

  } catch (err) {
    showError('Error de conexión. Verifica tu red e intenta de nuevo.');
  }
}

function showError(msg) {
  qid('error-msg').textContent = msg;
  setScreen('error');
}

// ── Cargar catálogos (tipos y estados de propiedad) ───────────────────

async function loadCatalogs() {
  try {
    const [typesRes, statusesRes] = await Promise.all([
      fetch(`${state.apiBase}/api/property/types`, { headers: { 'Accept': 'application/json' } }),
      fetch(`${state.apiBase}/api/property/statuses`, { headers: { 'Accept': 'application/json' } }),
    ]);

    if (typesRes.ok) {
      const types = await typesRes.json();
      populateSelect('f-prop-type', types);
    }

    if (statusesRes.ok) {
      const statuses = await statusesRes.json();
      populateSelect('f-prop-status', statuses);
    }
  } catch (_) {
    // Catálogos no críticos; el usuario puede ingresar ID manual si fuera necesario
  }
}

function populateSelect(selectId, items) {
  const sel = qid(selectId);
  // Los endpoints /api/property/types y /api/property/statuses usan Resources
  // que serializan el id como "key" (no "id"). Soporte de ambos para robustez.
  (Array.isArray(items) ? items : (items.data || [])).forEach(item => {
    const val = item.key ?? item.id;   // Resources devuelven "key"; Eloquent directo devuelve "id"
    const opt = document.createElement('option');
    opt.value       = val;
    opt.textContent = item.name || val;
    sel.appendChild(opt);
  });
}

// ── CMA: enviar solicitud ─────────────────────────────────────────────

async function runCMA() {
  const btn     = qid('btn-cma');
  const spinner = qid('btn-cma-spinner');
  const btnText = qid('btn-cma-text');
  const errBox  = qid('form-error');

  // Leer inputs
  const zipcode       = qid('f-zipcode').value.trim();
  const propTypeId    = parseInt(qid('f-prop-type').value, 10);
  const propStatusId  = parseInt(qid('f-prop-status').value, 10);
  const totalArea     = parseFloat(qid('f-area').value);
  const priceRef      = parseFloat(qid('f-price-ref').value) || 0;

  // Validación client-side
  errBox.style.display = 'none';
  errBox.textContent   = '';

  if (!zipcode || zipcode.length < 4) {
    return showFormError('El código postal debe tener al menos 4 caracteres.');
  }
  if (!propTypeId) {
    return showFormError('Selecciona el tipo de propiedad.');
  }
  if (!propStatusId) {
    return showFormError('Selecciona el tipo de operación (venta/renta).');
  }
  if (!totalArea || totalArea <= 0) {
    return showFormError('La superficie total debe ser mayor a cero.');
  }

  // Deshabilitar botón durante request
  btn.disabled      = true;
  spinner.style.display = '';
  btnText.textContent   = 'Calculando…';

  try {
    const res = await fetch(`${state.apiBase}/api/v2/broker-brain/cma`, {
      method:  'POST',
      headers: {
        'Content-Type':  'application/json',
        'Accept':        'application/json',
        'Authorization': `Bearer ${state.sessionToken}`,
      },
      body: JSON.stringify({
        zipcode,
        prop_type_id:   propTypeId,
        prop_status_id: propStatusId,
        total_area:     totalArea,
        price_ref:      priceRef,
      }),
    });

    const data = await res.json();

    if (!res.ok || !data.success) {
      showFormError(data.error || 'Error al calcular. Intenta de nuevo.');
      return;
    }

    renderResults(data);

  } catch (_) {
    showFormError('Error de conexión. Verifica tu red e intenta de nuevo.');
  } finally {
    btn.disabled          = false;
    spinner.style.display = 'none';
    btnText.textContent   = 'Recalcular';
  }
}

function showFormError(msg) {
  const errBox = qid('form-error');
  errBox.textContent   = msg;
  errBox.style.display = '';
}

// ── Render de resultados ──────────────────────────────────────────────

function renderResults(data) {
  // KPIs
  qid('kpi-value').textContent = fmtCurrency(data.estimated_market_value);
  qid('kpi-range').textContent = `Rango: ${fmtCurrency(data.price_range.min)} – ${fmtCurrency(data.price_range.max)}`;
  qid('kpi-psqm').textContent  = fmtCurrency(data.price_per_sqm);
  qid('kpi-comps').textContent = data.meta?.comparables_found ?? data.comparables.length;
  qid('kpi-dom').textContent   = data.dom_projection.days + ' días';
  qid('kpi-dom-label').textContent = data.dom_projection.label;

  // DOM Projection bars
  renderDomBars(data.dom_projection);

  // Comparables table
  renderComparables(data.comparables);

  // AURA — Análisis IA estructurado
  renderAura(data.narrative);

  // Mostrar panel de resultados
  hide('results-placeholder');
  show('results-cma');

  // Scroll suave al panel de resultados en móvil
  if (window.innerWidth < 1024) {
    qid('results-cma').scrollIntoView({ behavior: 'smooth', block: 'start' });
  }
}

function renderDomBars(dom) {
  const wrap     = qid('dom-scenarios');
  const maxDays  = Math.max(...dom.data_points.map(d => d.days)) || 1;
  wrap.innerHTML = '';

  dom.data_points.forEach(pt => {
    const pct = Math.round((pt.days / maxDays) * 100);

    const row = document.createElement('div');
    row.className = 'bb-dom-row';
    row.innerHTML = `
      <span class="bb-dom-row-label">${escHtml(pt.label)}</span>
      <div class="bb-dom-bar-bg">
        <div class="bb-dom-bar-fill" style="width:0%;" data-width="${pct}%"></div>
      </div>
      <span class="bb-dom-bar-days">${escHtml(pt.days)} días</span>
    `;
    wrap.appendChild(row);
  });

  // Animar barras tras inserción en DOM
  requestAnimationFrame(() => {
    wrap.querySelectorAll('.bb-dom-bar-fill').forEach(bar => {
      bar.style.width = bar.dataset.width;
    });
  });
}

function renderComparables(comps) {
  const tbody = qid('comp-tbody');
  tbody.innerHTML = '';

  if (!comps || comps.length === 0) {
    const row = document.createElement('tr');
    row.innerHTML = `<td colspan="9" style="text-align:center;color:var(--v2-text-subtle);padding:1.5rem;">Sin comparables en esta zona.</td>`;
    tbody.appendChild(row);
    return;
  }

  comps.forEach(c => {
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td style="max-width:12rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
          title="${escHtml(c.title)}">${escHtml(c.title || '—')}</td>
      <td>${fmtCurrency(c.price)}</td>
      <td>${escHtml(c.total_area)} m²</td>
      <td>${fmtCurrency(c.price_per_sqm)}</td>
      <td>${escHtml(c.bedrooms ?? '—')}</td>
      <td>${escHtml(c.baths ?? '—')}</td>
      <td>${escHtml(c.parking_lots ?? '—')}</td>
      <td>${escHtml(c.zipcode ?? '—')}</td>
      <td>
        <a class="bb-link" href="${escHtml(c.link)}" target="_blank" rel="noopener">
          Ver
        </a>
      </td>
    `;
    tbody.appendChild(tr);
  });
}

// ── AURA — Render del análisis IA estructurado ───────────────────────

function renderAura(narrative) {
  // narrative puede ser null, string vacío (sin IA) u objeto AURA
  if (!narrative || typeof narrative !== 'object') {
    hide('aura-wrap');
    return;
  }

  const score = Number(narrative.confidence_score ?? 0);

  // Medidor de confianza
  qid('aura-score-value').textContent = score + '%';
  requestAnimationFrame(() => {
    qid('aura-score-bar').style.width = score + '%';

    // Color del medidor según rango
    const bar = qid('aura-score-bar');
    if (score >= 90)      bar.style.background = 'linear-gradient(90deg,#06b6d4,#22d3ee)';
    else if (score >= 75) bar.style.background = 'linear-gradient(90deg,#0891b2,#06b6d4)';
    else if (score >= 50) bar.style.background = 'linear-gradient(90deg,#b45309,#d97706)';
    else                  bar.style.background = 'linear-gradient(90deg,#dc2626,#ef4444)';
  });

  // Resumen de mercado
  const summaryEl = qid('aura-market-summary');
  summaryEl.textContent = narrative.market_summary
    ? '"' + narrative.market_summary + '"'
    : '';

  // Tarjetas
  qid('aura-pricing-verdict').textContent  = narrative.pricing_verdict  || '—';
  qid('aura-buyer-psychology').textContent = narrative.buyer_psychology  || '—';
  qid('aura-seller-strategy').textContent  = narrative.seller_strategy   || '—';
  qid('aura-closing-argument').textContent = narrative.closing_argument  || '—';

  show('aura-wrap');
}

// ── Inventario del tenant (auto-fill) ────────────────────────────────

// Almacena las propiedades indexadas por id para lookup O(1)
const inventoryMap = {};

async function loadMyProperties() {
  try {
    const res = await fetch(`${state.apiBase}/api/v2/broker-brain/my-properties`, {
      headers: {
        'Accept':        'application/json',
        'Authorization': `Bearer ${state.sessionToken}`,
      },
    });

    if (!res.ok) return;

    const data = await res.json();
    if (!data.success || !data.data || data.data.length === 0) return;

    const sel = qid('f-inventory');

    data.data.forEach(prop => {
      inventoryMap[prop.id] = prop;

      const opt = document.createElement('option');
      opt.value = prop.id;
      const label = prop.key
        ? `[${escHtml(prop.key)}] ${escHtml(prop.title ?? '').substring(0, 50)}`
        : escHtml((prop.title ?? '').substring(0, 60));
      opt.textContent = label;
      sel.appendChild(opt);
    });

    // Mostrar el bloque solo si hay propiedades
    qid('inventory-group').style.display = '';

  } catch (_) {
    // Inventario no crítico — el formulario sigue funcionando sin él
  }
}

function onInventorySelect() {
  const propId = parseInt(qid('f-inventory').value, 10);
  if (!propId || !inventoryMap[propId]) return;

  const p = inventoryMap[propId];

  // Auto-fill de campos del formulario
  if (p.zipcode)       qid('f-zipcode').value  = p.zipcode;
  if (p.total_area)    qid('f-area').value      = p.total_area;
  if (p.price)         qid('f-price-ref').value = p.price;

  // Selects: solo sobrescribir si el catálogo ya tiene la opción cargada
  if (p.prop_type_id) {
    const typeOpt = qid('f-prop-type').querySelector(`option[value="${p.prop_type_id}"]`);
    if (typeOpt) qid('f-prop-type').value = p.prop_type_id;
  }
  if (p.prop_status_id) {
    const statusOpt = qid('f-prop-status').querySelector(`option[value="${p.prop_status_id}"]`);
    if (statusOpt) qid('f-prop-status').value = p.prop_status_id;
  }

  // Limpiar error previo si el auto-fill satisface los campos
  qid('form-error').style.display = 'none';
}

// ── Regresar al Panel Legacy ──────────────────────────────────────────

function goBack() {
  window.location.href = state.apiBase + '/home';
}

// ── Logout ───────────────────────────────────────────────────────────

function logout() {
  state.sessionToken = null;
  window.location.href = `${state.apiBase}/home/v2/logout`;
}

// ── Iniciar ───────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', boot);
