/**
 * security.js — Panel Super Admin V2
 * Módulos: Administradores + Orquestador IA
 * camelCase para JS · snake_case en payloads API
 */

'use strict';

// ── Estado global ────────────────────────────────────────────────────────────
const state = {
  sessionToken:    null,
  apiBase:         '',
  pendingReset:    null,   // { userId, email }
  aiEditingId:     null,   // id del proveedor en edición
};

// ── Selector de DOM ──────────────────────────────────────────────────────────
const $ = (id) => document.getElementById(id);

const screens = {
  loading: $('screen-loading'),
  error:   $('screen-error'),
  main:    $('screen-main'),
};

// ── Utilidades ───────────────────────────────────────────────────────────────

function showScreen(name) {
  Object.values(screens).forEach((s) => s.classList.add('hidden'));
  screens[name].classList.remove('hidden');
}

function showError(msg) {
  $('error-message').textContent = msg;
  showScreen('error');
}

function apiFetch(path, options = {}) {
  return fetch(state.apiBase + path, {
    ...options,
    headers: {
      'Content-Type':  'application/json',
      'Accept':        'application/json',
      'Authorization': 'Bearer ' + state.sessionToken,
      ...(options.headers || {}),
    },
  }).then((res) => {
    if (res.status === 401) throw new Error('Sesión expirada. Regresa al panel.');
    return res.json();
  });
}

function showToast(msg, type = 'success') {
  const t = $('sa-toast');
  t.textContent = msg;
  t.className = 'sa-toast sa-toast-' + type;
  t.classList.remove('hidden');
  clearTimeout(t._t);
  t._t = setTimeout(() => t.classList.add('hidden'), 4500);
}

function escHtml(str) {
  return String(str)
    .replace(/&/g, '&amp;').replace(/</g, '&lt;')
    .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function escAttr(str) {
  return String(str).replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}

function getInitials(name) {
  if (!name) return '??';
  const p = name.trim().split(' ');
  return ((p[0] || '')[0] + ((p[1] || '')[0] || '')).toUpperCase();
}

// ── Tabs ─────────────────────────────────────────────────────────────────────

const TABS = [
  { btn: 'tab-users',     panel: 'panel-users'     },
  { btn: 'tab-ai',        panel: 'panel-ai'        },
  { btn: 'tab-payments',  panel: 'panel-payments'  },
  { btn: 'tab-companies', panel: 'panel-companies' },
  { btn: 'tab-audit',     panel: 'panel-audit'     },
];

function activateTab(targetBtnId) {
  TABS.forEach(({ btn, panel }) => {
    const isTarget = btn === targetBtnId;
    $(btn).classList.toggle('sa-tab-active', isTarget);
    $(btn).setAttribute('aria-selected', String(isTarget));
    $(panel).classList.toggle('hidden', !isTarget);
  });
}

$('tab-users').addEventListener('click', () => activateTab('tab-users'));
$('tab-ai').addEventListener('click', () => {
  activateTab('tab-ai');
  loadAiSettings();
});
$('tab-payments').addEventListener('click', () => {
  activateTab('tab-payments');
  loadGateways();
});
$('tab-companies').addEventListener('click', () => {
  activateTab('tab-companies');
  loadCompanies();
});
$('tab-audit').addEventListener('click', () => {
  activateTab('tab-audit');
  loadAuditLogs();
});

// ══════════════════════════════════════════════════════════════════════════════
// MÓDULO A — ADMINISTRADORES
// ══════════════════════════════════════════════════════════════════════════════

// ── Modal reset password ─────────────────────────────────────────────────────

function openModal(userId, email) {
  state.pendingReset = { userId, email };
  $('modal-body').textContent =
    'Se generará una contraseña temporal para ' + email + '. Esta acción es irreversible.';
  $('sa-modal').classList.remove('hidden');
  $('modal-confirm').focus();
}

function closeModal() {
  state.pendingReset = null;
  $('sa-modal').classList.add('hidden');
}

$('modal-cancel').addEventListener('click', closeModal);
$('sa-modal').addEventListener('click', (e) => { if (e.target === $('sa-modal')) closeModal(); });
$('modal-confirm').addEventListener('click', () => {
  if (!state.pendingReset) return;
  const { userId, email } = state.pendingReset;
  closeModal();
  execResetPassword(userId, email);
});

// ── Resultado password temporal ──────────────────────────────────────────────

function showPasswordResult(pwd, email) {
  $('pwd-value').textContent = pwd;
  $('password-result').querySelector('.sa-pwd-user').textContent = 'Usuario: ' + email;
  $('password-result').classList.remove('hidden');
  $('password-result').scrollIntoView({ behavior: 'smooth' });
}

$('pwd-close').addEventListener('click', () => $('password-result').classList.add('hidden'));
$('pwd-copy').addEventListener('click', () => {
  navigator.clipboard.writeText($('pwd-value').textContent)
    .then(() => showToast('Contraseña copiada.'))
    .catch(() => showToast('Cópiala manualmente.', 'error'));
});

// ── Render tabla de admins ───────────────────────────────────────────────────

function renderAdmins(admins, meta) {
  const tbody = $('admins-tbody');
  tbody.innerHTML = '';

  if (!admins || !admins.length) {
    $('table-loading').classList.add('hidden');
    $('table-empty').classList.remove('hidden');
    return;
  }

  if (meta) {
    $('admin-count').textContent = meta.total;
    var pageInfo = $('admin-page-info');
    if (pageInfo) {
      pageInfo.textContent = 'Mostrando ' + admins.length + ' de ' + meta.total +
        ' (pág. ' + meta.current_page + '/' + meta.last_page + ')';
    }
  } else {
    $('admin-count').textContent = admins.length;
  }

  admins.forEach((u) => {
    const isSuper      = u.is_super;
    const roleBadge    = isSuper ? 'sa-role-super' : 'sa-role-admin';
    const roleLabel    = isSuper ? 'Super Admin' : 'Admin';
    const toggleLabel  = isSuper ? 'Degradar a Admin' : 'Promover';
    const toggleClass  = isSuper ? 'v2-btn-warning' : 'v2-btn-promote';

    const companyName = u.company_name || '—';

    const tr = document.createElement('tr');
    tr.dataset.userId = u.id;
    tr.innerHTML =
      '<td>' +
        '<div class="sa-user-cell">' +
          '<div class="sa-avatar">' + getInitials(u.full_name) + '</div>' +
          '<div>' +
            '<p class="sa-user-name">' + escHtml(u.full_name) + '</p>' +
            '<p class="sa-user-id">ID #' + u.id + '</p>' +
          '</div>' +
        '</div>' +
      '</td>' +
      '<td>' +
        '<span style="display:inline-flex;align-items:center;gap:.4rem;font-size:.8rem;">' +
          '<i class="fas fa-building" style="color:var(--v2-text-muted);font-size:.7rem;"></i>' +
          escHtml(companyName) +
        '</span>' +
      '</td>' +
      '<td><span class="sa-email">' + escHtml(u.email) + '</span></td>' +
      '<td><span class="sa-role-badge ' + roleBadge + '">' + roleLabel + '</span></td>' +
      '<td><span class="sa-status-dot ' + (u.active ? 'sa-status-active' : 'sa-status-inactive') + '">' +
        (u.active ? 'Activo' : 'Inactivo') + '</span></td>' +
      '<td class="sa-actions-cell">' +
        '<button class="v2-btn v2-btn-sm ' + toggleClass + ' js-toggle" data-id="' + u.id + '">' +
          toggleLabel + '</button>' +
        '<button class="v2-btn v2-btn-sm v2-btn-danger js-reset" data-id="' + u.id +
          '" data-email="' + escAttr(u.email) + '">Reset pwd</button>' +
      '</td>';

    tbody.appendChild(tr);
  });

  tbody.querySelectorAll('.js-toggle').forEach((b) =>
    b.addEventListener('click', () => execToggleRole(Number(b.dataset.id))));

  tbody.querySelectorAll('.js-reset').forEach((b) =>
    b.addEventListener('click', () => openModal(Number(b.dataset.id), b.dataset.email)));

  $('table-loading').classList.add('hidden');
  $('table-wrapper').classList.remove('hidden');
}

function loadAdmins(page, search) {
  $('table-loading').classList.remove('hidden');
  $('table-wrapper').classList.add('hidden');
  $('table-empty').classList.add('hidden');

  var qs = '?per_page=50';
  if (page  && page > 1) qs += '&page=' + page;
  if (search && search.trim()) qs += '&search=' + encodeURIComponent(search.trim());

  apiFetch('/api/v2/admin/users' + qs)
    .then(function (d) {
      if (!d.success) throw new Error(d.error);
      renderAdmins(d.data, d.meta);
      renderAdminPager(d.meta, search);
    })
    .catch((e) => showToast(e.message, 'error'));
}

function renderAdminPager(meta, search) {
  var pager = $('admin-pager');
  if (!pager || !meta || meta.last_page <= 1) {
    if (pager) pager.innerHTML = '';
    return;
  }
  var html = '';
  if (meta.current_page > 1) {
    html += '<button class="v2-btn v2-btn-sm" onclick="loadAdmins(' + (meta.current_page - 1) +
      ', \'' + escAttr(search || '') + '\')">← Anterior</button> ';
  }
  html += '<span style="font-size:.85rem;color:var(--v2-text-muted)">Pág. ' +
    meta.current_page + ' / ' + meta.last_page + '</span>';
  if (meta.current_page < meta.last_page) {
    html += ' <button class="v2-btn v2-btn-sm" onclick="loadAdmins(' + (meta.current_page + 1) +
      ', \'' + escAttr(search || '') + '\')">Siguiente →</button>';
  }
  pager.innerHTML = html;
}

function execToggleRole(userId) {
  apiFetch('/api/v2/admin/users/' + userId + '/toggle-role', { method: 'POST' })
    .then((d) => { if (!d.success) throw new Error(d.error); showToast(d.message); loadAdmins(); })
    .catch((e) => showToast(e.message, 'error'));
}

function execResetPassword(userId, email) {
  apiFetch('/api/v2/admin/users/' + userId + '/reset-password', { method: 'POST' })
    .then((d) => {
      if (!d.success) throw new Error(d.error);
      showPasswordResult(d.temporary_password, email);
      showToast('Contraseña generada para ' + email);
    })
    .catch((e) => showToast(e.message, 'error'));
}

// ══════════════════════════════════════════════════════════════════════════════
// MÓDULO B — ORQUESTADOR IA
// ══════════════════════════════════════════════════════════════════════════════

// Mapas de etiqueta y badge por proveedor
const AI_LABELS = {
  openai:    { label: 'OpenAI',     css: 'ai-badge-openai' },
  groq:      { label: 'Groq',       css: 'ai-badge-groq' },
  anthropic: { label: 'Anthropic',  css: 'ai-badge-anthropic' },
  gemini:    { label: 'Gemini',     css: 'ai-badge-gemini' },
  mistral:   { label: 'Mistral',    css: 'ai-badge-mistral' },
};

function aiLabel(name) {
  return AI_LABELS[name] || { label: name.toUpperCase(), css: 'ai-badge-default' };
}

// ── Render escalera de failover ──────────────────────────────────────────────

function renderLadder(settings) {
  const active = settings.filter((s) => s.is_active).sort((a, b) => a.priority_order - b.priority_order);
  const ladder = $('ai-ladder');
  ladder.innerHTML = '';

  $('ai-active-count').textContent = active.length + ' activos';

  if (!active.length) {
    $('ai-loading').classList.add('hidden');
    $('ai-empty').classList.remove('hidden');
    ladder.classList.add('hidden');
    return;
  }

  active.forEach((s, i) => {
    const { label, css } = aiLabel(s.provider_name);
    const isLast = i === active.length - 1;

    const step = document.createElement('div');
    step.className = 'ai-ladder-step';
    step.innerHTML =
      '<span class="ai-priority-pill">P' + s.priority_order + '</span>' +
      '<span class="ai-prov-badge ' + css + '">' + escHtml(label) + '</span>' +
      (!isLast ? '<span class="ai-arrow">↓</span>' : '');
    ladder.appendChild(step);
  });

  // Nodo final: error
  const end = document.createElement('div');
  end.className = 'ai-ladder-step ai-ladder-end';
  end.innerHTML =
    '<span class="sa-badge-danger">ERROR</span>' +
    '<span class="ai-end-label">RuntimeException → log interno</span>';
  ladder.appendChild(end);

  $('ai-loading').classList.add('hidden');
  $('ai-empty').classList.add('hidden');
  ladder.classList.remove('hidden');
}

// ── Render tabla de proveedores ──────────────────────────────────────────────

function renderAiTable(settings) {
  const tbody = $('ai-tbody');
  tbody.innerHTML = '';

  $('ai-total-count').textContent = settings.length;

  if (!settings.length) {
    $('ai-table-loading').classList.add('hidden');
    $('ai-table-empty').classList.remove('hidden');
    return;
  }

  settings.forEach((s) => {
    const { label, css } = aiLabel(s.provider_name);
    const tr = document.createElement('tr');
    tr.dataset.settingId = s.id;

    tr.innerHTML =
      '<td><span class="ai-prov-badge ' + css + '">' + escHtml(label) + '</span></td>' +
      '<td><code class="ai-key-masked">' + escHtml(s.api_key_masked) + '</code></td>' +
      '<td><span class="ai-priority-pill">P' + s.priority_order + '</span></td>' +
      '<td>' +
        '<label class="sa-toggle" title="' + (s.is_active ? 'Activo' : 'Inactivo') + '">' +
          '<input type="checkbox" class="js-ai-toggle" data-id="' + s.id + '"' +
            (s.is_active ? ' checked' : '') + '>' +
          '<span class="sa-toggle-slider"></span>' +
        '</label>' +
      '</td>' +
      '<td><span class="ai-tenant">' + (s.company_id ? '#' + s.company_id : 'Global') + '</span></td>' +
      '<td class="sa-actions-cell">' +
        '<button class="v2-btn v2-btn-sm v2-btn-ghost js-ai-edit" ' +
                'data-id="' + s.id + '" ' +
                'data-provider="' + escAttr(s.provider_name) + '" ' +
                'data-priority="' + s.priority_order + '" ' +
                'data-active="' + (s.is_active ? '1' : '0') + '" ' +
                'data-extra="' + escAttr(JSON.stringify(s.extra_config)) + '">' +
          'Editar' +
        '</button>' +
        '<button class="v2-btn v2-btn-sm v2-btn-danger js-ai-delete" data-id="' + s.id + '" ' +
                'data-provider="' + escAttr(label) + '">' +
          'Eliminar' +
        '</button>' +
      '</td>';

    tbody.appendChild(tr);
  });

  // Toggles inline
  tbody.querySelectorAll('.js-ai-toggle').forEach((chk) => {
    chk.addEventListener('change', () => execToggleAi(Number(chk.dataset.id)));
  });

  // Botón editar
  tbody.querySelectorAll('.js-ai-edit').forEach((btn) => {
    btn.addEventListener('click', () => {
      populateAiForm({
        id:             Number(btn.dataset.id),
        provider_name:  btn.dataset.provider,
        priority_order: btn.dataset.priority,
        is_active:      btn.dataset.active,
        extra_config:   btn.dataset.extra,
      });
    });
  });

  // Botón eliminar
  tbody.querySelectorAll('.js-ai-delete').forEach((btn) => {
    btn.addEventListener('click', () => {
      if (!confirm('¿Eliminar el proveedor ' + btn.dataset.provider + '?')) return;
      execDestroyAi(Number(btn.dataset.id));
    });
  });

  $('ai-table-loading').classList.add('hidden');
  $('ai-table-empty').classList.add('hidden');
  $('ai-table-wrapper').classList.remove('hidden');
}

// ── Cargar ambas vistas de AI ─────────────────────────────────────────────────

function loadAiSettings() {
  $('ai-loading').classList.remove('hidden');
  $('ai-ladder').classList.add('hidden');
  $('ai-table-loading').classList.remove('hidden');
  $('ai-table-wrapper').classList.add('hidden');
  $('ai-table-empty').classList.add('hidden');

  apiFetch('/api/v2/admin/ai-settings')
    .then((d) => {
      if (!d.success) throw new Error(d.error);
      renderLadder(d.data);
      renderAiTable(d.data);
    })
    .catch((e) => showToast(e.message, 'error'));
}

// ── Formulario AI ─────────────────────────────────────────────────────────────

function resetAiForm() {
  state.aiEditingId = null;
  $('ai-form').reset();
  $('ai-setting-id').value = '';
  $('ai-form-title').textContent = 'Agregar Proveedor';
  $('ai-btn-submit').textContent = 'Guardar';
  $('ai-btn-cancel').classList.add('hidden');
}

function populateAiForm({ id, provider_name, priority_order, is_active, extra_config }) {
  state.aiEditingId = id;
  $('ai-setting-id').value    = id;
  $('ai-provider').value      = provider_name;
  $('ai-priority').value      = priority_order;
  $('ai-active').value        = is_active;
  $('ai-key').value           = '';   // nunca pre-cargar la key

  let extraStr = '';
  try {
    const parsed = JSON.parse(extra_config);
    if (parsed && typeof parsed === 'object') {
      extraStr = JSON.stringify(parsed, null, 2);
    }
  } catch (_) {}
  $('ai-extra').value = extraStr;

  $('ai-form-title').textContent = 'Editar Proveedor #' + id;
  $('ai-btn-submit').textContent = 'Actualizar';
  $('ai-btn-cancel').classList.remove('hidden');
  $('ai-form').scrollIntoView({ behavior: 'smooth' });
}

$('ai-btn-cancel').addEventListener('click', resetAiForm);

$('ai-form').addEventListener('submit', (e) => {
  e.preventDefault();

  const id       = state.aiEditingId;
  const isEdit   = id !== null;
  const url      = isEdit
    ? '/api/v2/admin/ai-settings/' + id
    : '/api/v2/admin/ai-settings';
  const method   = isEdit ? 'PUT' : 'POST';

  const body = {
    provider_name:  $('ai-provider').value,
    priority_order: Number($('ai-priority').value),
    is_active:      Number($('ai-active').value),
  };

  const key = $('ai-key').value.trim();
  if (key) body.api_key = key;

  const extra = $('ai-extra').value.trim();
  if (extra) body.extra_config = extra;

  apiFetch(url, { method, body: JSON.stringify(body) })
    .then((d) => {
      if (!d.success) throw new Error(d.error || 'Error al guardar.');
      showToast(d.message || 'Guardado.');
      resetAiForm();
      loadAiSettings();
    })
    .catch((e) => showToast(e.message, 'error'));
});

// ── Acciones API: toggle, delete ─────────────────────────────────────────────

function execToggleAi(id) {
  apiFetch('/api/v2/admin/ai-settings/' + id + '/toggle', { method: 'PATCH' })
    .then((d) => { if (!d.success) throw new Error(d.error); loadAiSettings(); })
    .catch((e) => showToast(e.message, 'error'));
}

function execDestroyAi(id) {
  apiFetch('/api/v2/admin/ai-settings/' + id, { method: 'DELETE' })
    .then((d) => { if (!d.success) throw new Error(d.error); showToast('Proveedor eliminado.'); loadAiSettings(); })
    .catch((e) => showToast(e.message, 'error'));
}

// ══════════════════════════════════════════════════════════════════════════════
// MÓDULO C — PASARELAS DE PAGO
// ══════════════════════════════════════════════════════════════════════════════

const PGW_META = {
  stripe:  { label: 'Stripe',   color: '#635bff', icon: '💳' },
  openpay: { label: 'OpenPay',  color: '#1aa72c', icon: '🔐' },
  paypal:  { label: 'PayPal',   color: '#003087', icon: '🅿️'  },
  nuvei:   { label: 'Nuvei',    color: '#e5282a', icon: '🌐' },
};

var pgwState = { editingId: null, editingProvider: '' };

function pgwMeta(name) {
  return PGW_META[name.toLowerCase()] || { label: name, color: '#6b7280', icon: '💰' };
}

// ── Cargar y renderizar grid ─────────────────────────────────────────────────

function loadGateways() {
  $('pgw-loading').classList.remove('hidden');
  $('pgw-grid').classList.add('hidden');
  $('pgw-empty').classList.add('hidden');

  apiFetch('/api/v2/admin/payment-gateways')
    .then(function (d) {
      if (!d.success) throw new Error(d.error);
      renderGatewayGrid(d.data);
    })
    .catch(function (e) { showToast(e.message, 'error'); });
}

function renderGatewayGrid(gateways) {
  var grid = $('pgw-grid');
  grid.innerHTML = '';

  // Proveedores fijos — siempre muestra las 4 tarjetas aunque no haya registro en BD
  var providers = ['stripe', 'openpay', 'paypal', 'nuvei'];
  var byName    = {};
  gateways.forEach(function (g) { byName[g.provider_name.toLowerCase()] = g; });

  var active = gateways.filter(function (g) { return g.is_active; }).length;
  $('pgw-active-count').textContent = active + ' activo' + (active !== 1 ? 's' : '');

  providers.forEach(function (key) {
    var meta = pgwMeta(key);
    var g    = byName[key] || null;

    var isActive  = g ? g.is_active  : false;
    var isSandbox = g ? g.is_sandbox : true;
    var hasCreds  = g ? g.has_credentials : false;
    var gid       = g ? g.id : null;

    var card = document.createElement('div');
    card.className = 'pgw-card';
    card.dataset.provider = key;
    if (gid) card.dataset.id = gid;

    card.innerHTML =
      '<div class="pgw-card-head" style="border-color:' + meta.color + '">' +
        '<span class="pgw-icon">' + meta.icon + '</span>' +
        '<span class="pgw-name" style="color:' + meta.color + '">' + escHtml(meta.label) + '</span>' +
      '</div>' +
      '<div class="pgw-toggles">' +
        '<div class="pgw-toggle-row">' +
          '<span class="pgw-toggle-label">Activo</span>' +
          '<label class="sa-toggle">' +
            '<input type="checkbox" class="js-pgw-active"' + (isActive ? ' checked' : '') + (gid ? ' data-id="'+gid+'"' : '') + (gid ? '' : ' disabled') + '>' +
            '<span class="sa-toggle-slider"></span>' +
          '</label>' +
        '</div>' +
        '<div class="pgw-toggle-row">' +
          '<span class="pgw-toggle-label">' + (isSandbox ? '🔵 Sandbox' : '🟢 Producción') + '</span>' +
          '<label class="sa-toggle">' +
            '<input type="checkbox" class="js-pgw-sandbox"' + (!isSandbox ? ' checked' : '') + (gid ? ' data-id="'+gid+'"' : '') + (gid ? '' : ' disabled') + '>' +
            '<span class="sa-toggle-slider sa-toggle-prod"></span>' +
          '</label>' +
        '</div>' +
      '</div>' +
      '<div class="pgw-card-footer">' +
        (hasCreds ? '<span class="pgw-creds-ok">🔑 Llaves configuradas</span>' : '<span class="pgw-creds-missing">⚠️ Sin llaves</span>') +
        '<button class="v2-btn v2-btn-sm v2-btn-ghost js-pgw-config" data-key="' + key + '"' + (gid ? ' data-id="'+gid+'"' : '') + '>' +
          'Configurar' +
        '</button>' +
      '</div>';

    grid.appendChild(card);
  });

  // Listeners activo/sandbox/config
  grid.querySelectorAll('.js-pgw-active').forEach(function (cb) {
    cb.addEventListener('change', function () {
      if (!cb.dataset.id) return;
      apiFetch('/api/v2/admin/payment-gateways/' + cb.dataset.id + '/toggle', { method: 'PATCH' })
        .then(function () { loadGateways(); })
        .catch(function (e) { showToast(e.message, 'error'); cb.checked = !cb.checked; });
    });
  });

  grid.querySelectorAll('.js-pgw-sandbox').forEach(function (cb) {
    cb.addEventListener('change', function () {
      if (!cb.dataset.id) return;
      apiFetch('/api/v2/admin/payment-gateways/' + cb.dataset.id + '/toggle-sandbox', { method: 'PATCH' })
        .then(function () { loadGateways(); })
        .catch(function (e) { showToast(e.message, 'error'); cb.checked = !cb.checked; });
    });
  });

  grid.querySelectorAll('.js-pgw-config').forEach(function (btn) {
    btn.addEventListener('click', function () { openPgwModal(btn.dataset.key, btn.dataset.id || null, byName[btn.dataset.key] || null); });
  });

  $('pgw-loading').classList.add('hidden');
  if (!gateways.length) {
    $('pgw-empty').classList.remove('hidden');
  } else {
    grid.classList.remove('hidden');
  }
  grid.classList.remove('hidden');
}

// ── Modal configurar llaves ──────────────────────────────────────────────────

function openPgwModal(providerKey, gid, gatewayData) {
  pgwState.editingId       = gid;
  pgwState.editingProvider = providerKey;

  var meta = pgwMeta(providerKey);
  $('pgw-modal-title').textContent    = 'Configurar ' + meta.label;
  $('pgw-modal-provider').textContent = 'Las llaves se guardan encriptadas y nunca se exponen en texto claro.';

  // Limpiar inputs
  $('pgw-public-key').value    = '';
  $('pgw-secret-key').value    = '';
  $('pgw-webhook-secret').value = '';

  // Mostrar credenciales enmascaradas si existen
  var previewEl = $('pgw-creds-current');
  var maskedEl  = $('pgw-creds-masked');
  maskedEl.innerHTML = '';
  if (gatewayData && gatewayData.has_credentials && gatewayData.credentials && Object.keys(gatewayData.credentials).length) {
    previewEl.classList.remove('hidden');
    Object.entries(gatewayData.credentials).forEach(function (entry) {
      var row = document.createElement('div');
      row.className = 'pgw-masked-row';
      row.innerHTML = '<span class="pgw-masked-key">' + escHtml(entry[0]) + '</span>' +
                      '<code class="ai-key-masked">' + escHtml(entry[1]) + '</code>';
      maskedEl.appendChild(row);
    });
  } else {
    previewEl.classList.add('hidden');
  }

  $('pgw-modal').classList.remove('hidden');
  $('pgw-public-key').focus();
}

function closePgwModal() {
  $('pgw-modal').classList.add('hidden');
  pgwState.editingId = null;
  pgwState.editingProvider = '';
}

$('pgw-modal-close').addEventListener('click', closePgwModal);
$('pgw-modal-cancel').addEventListener('click', closePgwModal);
$('pgw-modal').addEventListener('click', function (e) { if (e.target === $('pgw-modal')) closePgwModal(); });

$('pgw-modal-save').addEventListener('click', function () {
  var pubKey     = $('pgw-public-key').value.trim();
  var secKey     = $('pgw-secret-key').value.trim();
  var webhookKey = $('pgw-webhook-secret').value.trim();

  if (!pubKey && !secKey) {
    showToast('Ingresa al menos la Public Key y la Secret Key.', 'error');
    return;
  }

  var creds = {};
  if (pubKey)     creds.public_key      = pubKey;
  if (secKey)     creds.secret_key      = secKey;
  if (webhookKey) creds.webhook_secret  = webhookKey;

  var provider = pgwState.editingProvider;
  var gid      = pgwState.editingId;

  var promise = gid
    ? apiFetch('/api/v2/admin/payment-gateways/' + gid, { method: 'PUT',
        body: JSON.stringify({ credentials: creds }) })
    : apiFetch('/api/v2/admin/payment-gateways', { method: 'POST',
        body: JSON.stringify({ provider_name: provider, credentials: creds }) });

  promise
    .then(function (d) {
      if (!d.success) throw new Error(d.error || 'Error al guardar.');
      showToast('Llaves de ' + pgwMeta(provider).label + ' guardadas.');
      closePgwModal();
      loadGateways();
    })
    .catch(function (e) { showToast(e.message, 'error'); });
});

// ══════════════════════════════════════════════════════════════════════════════
// MÓDULO D — GESTIÓN DE EMPRESAS
// ══════════════════════════════════════════════════════════════════════════════

var coState = {
  page:          1,
  search:        '',
  pendingAction: null,
  sortField:     'id',
  sortDir:       'asc',
};

// ── Inicializar listeners de sorting en thead ────────────────────────────────
(function initSortHeaders() {
  var thead = $('co-thead');
  if (!thead) return;
  var sortableThs = thead.querySelectorAll('.co-sortable');
  sortableThs.forEach(function (th) {
    th.addEventListener('click', function () {
      var field = th.getAttribute('data-field');
      if (coState.sortField === field) {
        coState.sortDir = coState.sortDir === 'asc' ? 'desc' : 'asc';
      } else {
        coState.sortField = field;
        coState.sortDir   = 'asc';
      }
      updateSortIcons();
      loadCompanies(1);
    });
  });
}());

function updateSortIcons() {
  var thead = $('co-thead');
  if (!thead) return;
  thead.querySelectorAll('.co-sortable').forEach(function (th) {
    var icon  = th.querySelector('.co-sort-icon');
    var field = th.getAttribute('data-field');
    if (field === coState.sortField) {
      th.setAttribute('aria-sort', coState.sortDir === 'asc' ? 'ascending' : 'descending');
      icon.textContent = coState.sortDir === 'asc' ? ' ▲' : ' ▼';
    } else {
      th.removeAttribute('aria-sort');
      icon.textContent = '';
    }
  });
}

function loadCompanies(page) {
  page = page || 1;
  coState.page = page;

  $('co-loading').classList.remove('hidden');
  $('co-wrapper').classList.add('hidden');
  $('co-empty').classList.add('hidden');

  var qs = '?per_page=20&page=' + page;
  if (coState.search)   qs += '&search='   + encodeURIComponent(coState.search);
  if (coState.sortField) qs += '&sort_by='  + encodeURIComponent(coState.sortField);
  if (coState.sortDir)   qs += '&sort_dir=' + encodeURIComponent(coState.sortDir);

  apiFetch('/api/v2/admin/companies' + qs)
    .then(function (d) {
      $('co-loading').classList.add('hidden');
      if (!d.success || !d.data.length) {
        $('co-empty').classList.remove('hidden');
        $('co-count').textContent = '0';
        return;
      }
      $('co-count').textContent = d.meta.total;
      renderCompanies(d.data);
      renderCoPager(d.meta);
      updateSortIcons();
      $('co-wrapper').classList.remove('hidden');
    })
    .catch(function (e) {
      $('co-loading').classList.add('hidden');
      showToast(e.message, 'error');
    });
}

var STATUS_CLASS = {
  'Activa':     'sa-badge-green',
  'Vencida':    'sa-badge-yellow',
  'Suspendida': 'sa-badge-red',
};

// ── Dropdown de acciones: estado del contexto activo ──────────────────────────
var coDropCtx = { id: null, name: null, active: null, data: null };

function renderCompanies(rows) {
  var tbody = $('co-tbody');
  tbody.innerHTML = '';
  rows.forEach(function (c) {
    var cls = STATUS_CLASS[c.status_label] || 'sa-badge-gray';
    var tr = document.createElement('tr');
    tr.dataset.companyId = c.id;
    // Guardamos el objeto completo en dataset para el modal de edición
    tr.dataset.companyJson = JSON.stringify(c);
    tr.innerHTML =
      '<td>' + escHtml(c.id) + '</td>' +
      '<td><strong>' + escHtml(c.name) + '</strong></td>' +
      '<td>' + escHtml(c.email || '—') + '</td>' +
      '<td>' + escHtml(c.package || '—') + '</td>' +
      '<td><span class="sa-badge ' + cls + '">' + escHtml(c.status_label) + '</span></td>' +
      '<td>' + escHtml(c.last_payment || '—') + '</td>' +
      '<td>' + escHtml(c.due_date || '—') + '</td>' +
      '<td class="sa-col-actions">' +
        '<button class="v2-btn v2-btn-ghost co-dd-trigger" ' +
                'style="font-size:.75rem;padding:.3rem .8rem;display:inline-flex;align-items:center;gap:.35rem;" ' +
                'data-id="' + c.id + '" data-name="' + escAttr(c.name) + '" data-active="' + (c.active ? '1' : '0') + '" ' +
                'title="Opciones de esta empresa">' +
          'Acciones <i class="fas fa-chevron-down" style="font-size:.6rem;"></i>' +
        '</button>' +
      '</td>';
    tbody.appendChild(tr);
  });

  // Registrar click handlers en los botones de trigger
  tbody.querySelectorAll('.co-dd-trigger').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      var tr     = btn.closest('tr');
      var data   = JSON.parse(tr.dataset.companyJson);
      coOpenDropdown(btn, data);
    });
  });
}

// ── Apertura del dropdown posicionado sobre el botón ─────────────────────────
// Se reconstruye el botón toggle en CADA apertura según el estado de la fila.
// Esto elimina cualquier posibilidad de mostrar la opción incorrecta por
// estado residual del DOM o renderizados anteriores.
function coOpenDropdown(triggerBtn, data) {
  // Normalización defensiva: la API puede devolver true/false, 1/0 o "1"/"0".
  // Boolean(Number("0")) === false  →  "0" string ya no es truthy por error.
  var isActive = Boolean(Number(data.active));

  coDropCtx = { id: data.id, name: data.name, active: isActive, data: data };

  var toggleBtn = $('co-dd-toggle');

  // Renderizado condicional estricto — solo una opción visible por apertura.
  if (isActive) {
    // Empresa ACTIVA → única opción: Suspender
    toggleBtn.className = 'co-dd-item co-dd-warn';
    toggleBtn.innerHTML =
      '<i class="fas fa-pause co-dd-icon"></i> Suspender';
  } else {
    // Empresa SUSPENDIDA / INACTIVA → única opción: Activar
    toggleBtn.className = 'co-dd-item co-dd-ok';
    toggleBtn.innerHTML =
      '<i class="fas fa-play co-dd-icon"></i> Activar';
  }

  var dd   = $('co-dropdown');
  var rect = triggerBtn.getBoundingClientRect();
  dd.style.display = ''; // limpia el inline display:none aplicado por coCloseDropdown
  dd.style.top  = (rect.bottom + window.scrollY + 4) + 'px';
  dd.style.left = Math.min(rect.left + window.scrollX, window.innerWidth - 180) + 'px';
  dd.classList.remove('hidden');
}

function coCloseDropdown() {
  var dd = $('co-dropdown');
  dd.classList.add('hidden');
  dd.style.display = 'none'; // fuerza ocultamiento; previene conflictos de especificidad CSS
}

// Cerrar al hacer clic fuera del dropdown
document.addEventListener('click', function (e) {
  var dd = $('co-dropdown');
  if (!dd.classList.contains('hidden') && !dd.contains(e.target)) {
    coCloseDropdown();
  }
});

// ── Acciones del dropdown ─────────────────────────────────────────────────────
// stopPropagation en cada acción: evita que el evento alcance el document-handler
// (que al ver e.target dentro de co-dropdown lo dejaría abierto en algunos navegadores).
$('co-dd-edit').addEventListener('click', function (e) {
  e.stopPropagation();
  coCloseDropdown();
  coOpenEditModal(coDropCtx.data);
});

$('co-dd-toggle').addEventListener('click', function (e) {
  e.stopPropagation();
  coCloseDropdown();
  coToggle(coDropCtx.id, coDropCtx.name, coDropCtx.active);
});

$('co-dd-delete').addEventListener('click', function (e) {
  e.stopPropagation();
  coCloseDropdown();
  coDelete(coDropCtx.id, coDropCtx.name);
});

// ── Modal de confirmación (toggle / eliminar) ─────────────────────────────────
function coToggle(id, name, currentActive) {
  $('co-modal-icon').textContent = currentActive ? '⏸️' : '▶️';
  $('co-modal-title').textContent = (currentActive ? 'Suspender' : 'Activar') + ' empresa';
  $('co-modal-body').textContent  = '¿Confirmas ' + (currentActive ? 'suspender' : 'activar') + ' la empresa "' + name + '"?';
  $('co-modal-confirm').className = 'v2-btn ' + (currentActive ? 'v2-btn-warning' : 'v2-btn-promote');
  coState.pendingAction = function () {
    apiFetch('/api/v2/admin/companies/' + id + '/toggle-status', { method: 'PATCH' })
      .then(function (d) {
        if (!d.success) throw new Error(d.error || 'Error.');
        showToast(d.message);
        loadCompanies(coState.page);
      })
      .catch(function (e) { showToast(e.message, 'error'); });
  };
  $('co-modal').classList.remove('hidden');
}

function coDelete(id, name) {
  $('co-modal-icon').textContent  = '🗑️';
  $('co-modal-title').textContent = 'Eliminar empresa';
  $('co-modal-body').textContent  = 'Esta acción suspenderá y marcará como eliminada la empresa "' + name + '". No se puede deshacer.';
  $('co-modal-confirm').className = 'v2-btn v2-btn-danger';
  coState.pendingAction = function () {
    apiFetch('/api/v2/admin/companies/' + id, { method: 'DELETE' })
      .then(function (d) {
        if (!d.success) throw new Error(d.error || 'Error.');
        showToast(d.message);
        loadCompanies(coState.page);
      })
      .catch(function (e) { showToast(e.message, 'error'); });
  };
  $('co-modal').classList.remove('hidden');
}

$('co-modal-cancel').addEventListener('click', function () {
  $('co-modal').classList.add('hidden');
  coState.pendingAction = null;
});
$('co-modal-confirm').addEventListener('click', function () {
  $('co-modal').classList.add('hidden');
  if (coState.pendingAction) { coState.pendingAction(); coState.pendingAction = null; }
});

// ── Modal de edición ──────────────────────────────────────────────────────────
function coOpenEditModal(c) {
  // Campos inmutables
  $('co-edit-id').value   = c.id;
  $('co-edit-name').value = c.name || '';

  // Campos editables
  $('co-edit-email').value   = c.email   || '';
  $('co-edit-phone').value   = c.phone   || '';
  $('co-edit-rfc').value     = c.rfc     || '';
  $('co-edit-address').value = c.address || '';
  $('co-edit-colony').value  = c.colony  || '';
  $('co-edit-zipcode').value = c.zipcode || '';

  // Bloque informativo de suscripción (solo lectura)
  $('co-edit-plan').textContent         = c.package      || 'Sin plan';
  $('co-edit-last-payment').textContent = c.last_payment || 'Sin registro';
  $('co-edit-due-date').textContent     = c.due_date     || 'Sin registro';

  // Badge de estatus con color
  var statusBadgeClass = { 'Activa': 'sa-badge-green', 'Vencida': 'sa-badge-yellow', 'Suspendida': 'sa-badge-red' };
  var cls = statusBadgeClass[c.status_label] || 'sa-badge-gray';
  $('co-edit-status').innerHTML = '<span class="sa-badge ' + cls + '">' + escHtml(c.status_label || '—') + '</span>';

  $('co-edit-subtitle').textContent = 'ID #' + c.id + ' · ' + c.name;
  $('co-edit-error').classList.add('hidden');
  $('co-edit-modal').classList.remove('hidden');
  $('co-edit-email').focus();
}

function coCloseEditModal() {
  $('co-edit-modal').classList.add('hidden');
}

$('co-edit-close').addEventListener('click', coCloseEditModal);
$('co-edit-cancel').addEventListener('click', coCloseEditModal);
$('co-edit-modal').addEventListener('click', function (e) {
  if (e.target === $('co-edit-modal')) coCloseEditModal();
});

// ── Botón "Ajuste Manual de Suscripción" dentro del modal de edición ──────────
$('co-btn-override').addEventListener('click', function () {
  var c = coDropCtx.data;
  // Pre-poblar campos con valores actuales de la empresa
  $('co-ov-plan').value     = c.package   || '';
  $('co-ov-payday').value   = c.last_payment || '';
  $('co-ov-due-date').value = c.due_date   || '';
  $('co-ov-reason').value   = '';
  $('co-override-error').classList.add('hidden');
  $('co-override-subtitle').textContent = 'ID #' + c.id + ' · ' + c.name;
  $('co-override-modal').classList.remove('hidden');
  $('co-ov-due-date').focus();
});

// ── Sub-modal Override Financiero ────────────────────────────────────────────
function coCloseOverrideModal() {
  $('co-override-modal').classList.add('hidden');
}

$('co-override-close').addEventListener('click', coCloseOverrideModal);
$('co-override-cancel').addEventListener('click', coCloseOverrideModal);
$('co-override-modal').addEventListener('click', function (e) {
  if (e.target === $('co-override-modal')) coCloseOverrideModal();
});

$('co-override-save').addEventListener('click', function () {
  var companyId = coDropCtx.id;
  var dueDate   = $('co-ov-due-date').value.trim();
  var reason    = $('co-ov-reason').value.trim();
  var errEl     = $('co-override-error');
  var saveBtn   = $('co-override-save');

  errEl.classList.add('hidden');

  if (!dueDate) {
    errEl.textContent = 'La nueva fecha de vencimiento es obligatoria.';
    errEl.classList.remove('hidden');
    return;
  }
  if (!reason) {
    errEl.textContent = 'Debes ingresar el motivo del ajuste para el Audit Trail.';
    errEl.classList.remove('hidden');
    return;
  }

  saveBtn.disabled = true;
  saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right:.4rem;font-size:.8rem;"></i>Aplicando…';

  var payload = {
    due_date: dueDate,
    reason:   reason,
  };
  var plan   = $('co-ov-plan').value.trim();
  var payday = $('co-ov-payday').value.trim();
  if (plan)   payload.plan   = parseInt(plan, 10);
  if (payday) payload.payday = payday;

  apiFetch('/api/v2/admin/companies/' + companyId + '/subscription-override', {
    method: 'PATCH',
    body: JSON.stringify(payload),
  })
    .then(function (d) {
      if (!d.success) throw new Error(d.error || 'Error en el servidor.');
      showToast('Ajuste aplicado. Audit Trail actualizado.');
      coCloseOverrideModal();
      coCloseEditModal();
      loadCompanies(coState.page); // recarga la tabla con los nuevos datos de suscripción
    })
    .catch(function (e) {
      errEl.textContent = e.message;
      errEl.classList.remove('hidden');
    })
    .finally(function () {
      saveBtn.disabled = false;
      saveBtn.innerHTML = '<i class="fas fa-check" style="margin-right:.4rem;font-size:.8rem;"></i>Aplicar Ajuste';
    });
});

$('co-edit-save').addEventListener('click', function () {
  var id      = $('co-edit-id').value;
  var errEl   = $('co-edit-error');
  var saveBtn = $('co-edit-save');

  errEl.classList.add('hidden');
  saveBtn.disabled = true;
  saveBtn.textContent = 'Guardando…';

  apiFetch('/api/v2/admin/companies/' + id, {
    method: 'PUT',
    body: JSON.stringify({
      email:   $('co-edit-email').value.trim()   || null,
      phone:   $('co-edit-phone').value.trim()   || null,
      rfc:     $('co-edit-rfc').value.trim()     || null,
      address: $('co-edit-address').value.trim() || null,
      colony:  $('co-edit-colony').value.trim()  || null,
      zipcode: $('co-edit-zipcode').value.trim() || null,
    }),
  })
    .then(function (d) {
      if (!d.success) throw new Error(d.error || 'Error al guardar.');
      showToast(d.message);
      coCloseEditModal();
      loadCompanies(coState.page);
    })
    .catch(function (e) {
      errEl.textContent = e.message;
      errEl.classList.remove('hidden');
    })
    .finally(function () {
      saveBtn.disabled = false;
      saveBtn.innerHTML = '<i class="fas fa-save" style="margin-right:.4rem;font-size:.8rem;"></i>Guardar cambios';
    });
});

// ── Búsqueda ──────────────────────────────────────────────────────────────────
$('co-search-btn').addEventListener('click', function () {
  coState.search = $('co-search').value.trim();
  loadCompanies(1);
});
$('co-clear-btn').addEventListener('click', function () {
  $('co-search').value = '';
  coState.search = '';
  loadCompanies(1);
});
$('co-search').addEventListener('keydown', function (e) {
  if (e.key === 'Enter') { coState.search = e.target.value.trim(); loadCompanies(1); }
});

function renderCoPager(meta) {
  var pager = $('co-pager');
  pager.innerHTML = '';
  if (meta.last_page <= 1) return;
  for (var p = 1; p <= meta.last_page; p++) {
    var btn = document.createElement('button');
    btn.textContent = p;
    btn.className = 'v2-btn ' + (p === meta.current_page ? 'v2-btn-primary' : 'v2-btn-ghost');
    btn.style.padding = '.3rem .7rem';
    (function (pg) { btn.addEventListener('click', function () { loadCompanies(pg); }); }(p));
    pager.appendChild(btn);
  }
}

// ══════════════════════════════════════════════════════════════════════════════
// MÓDULO E — AUDIT TRAIL + EXPORTACIÓN PDF
// ══════════════════════════════════════════════════════════════════════════════

var auditState = { page: 1, rows: [] };

var ACTION_LABEL = {
  activate:       'Activar',
  suspend:        'Suspender',
  delete:         'Eliminar',
  toggle_role:    'Cambio de Rol',
  reset_password: 'Reset Contraseña',
};

function loadAuditLogs(page) {
  page = page || 1;
  auditState.page = page;

  $('audit-loading').classList.remove('hidden');
  $('audit-wrapper').classList.add('hidden');
  $('audit-empty').classList.add('hidden');

  apiFetch('/api/v2/admin/audit-logs?per_page=50&page=' + page)
    .then(function (d) {
      $('audit-loading').classList.add('hidden');
      if (!d.success || !d.data.length) {
        $('audit-empty').classList.remove('hidden');
        $('audit-count').textContent = '0';
        return;
      }
      auditState.rows = d.data;
      $('audit-count').textContent = d.meta.total + ' registros';
      renderAuditLogs(d.data);
      renderAuditPager(d.meta);
      $('audit-wrapper').classList.remove('hidden');
    })
    .catch(function (e) {
      $('audit-loading').classList.add('hidden');
      showToast(e.message, 'error');
    });
}

function renderAuditLogs(rows) {
  var tbody = $('audit-tbody');
  tbody.innerHTML = '';
  rows.forEach(function (log, i) {
    var tr = document.createElement('tr');
    var fecha = log.created_at
      ? new Date(log.created_at).toLocaleString('es-MX', { hour12: false })
      : '—';
    tr.innerHTML =
      '<td>' + escHtml(log.id) + '</td>' +
      '<td style="white-space:nowrap;">' + escHtml(fecha) + '</td>' +
      '<td>' + escHtml(log.actor_email || '—') + '</td>' +
      '<td><strong>' + escHtml(ACTION_LABEL[log.action] || log.action) + '</strong></td>' +
      '<td>' + escHtml(log.target_name || log.target_id || '—') + '</td>' +
      '<td><span class="sa-badge sa-badge-gray">' + escHtml(log.from_status || '—') + '</span></td>' +
      '<td><span class="sa-badge sa-badge-green">' + escHtml(log.to_status || '—') + '</span></td>';
    tbody.appendChild(tr);
  });
}

function renderAuditPager(meta) {
  var pager = $('audit-pager');
  pager.innerHTML = '';
  if (meta.last_page <= 1) return;
  for (var p = 1; p <= meta.last_page; p++) {
    var btn = document.createElement('button');
    btn.textContent = p;
    btn.className = 'v2-btn ' + (p === meta.current_page ? 'v2-btn-primary' : 'v2-btn-ghost');
    btn.style.padding = '.3rem .7rem';
    (function (pg) { btn.addEventListener('click', function () { loadAuditLogs(pg); }); }(p));
    pager.appendChild(btn);
  }
}

$('audit-refresh').addEventListener('click', function () { loadAuditLogs(auditState.page); });

// Exportación a PDF con jsPDF
$('audit-export-pdf').addEventListener('click', function () {
  if (!auditState.rows.length) {
    showToast('Sin datos para exportar.', 'error');
    return;
  }

  var jsPDF = window.jspdf && window.jspdf.jsPDF;
  if (!jsPDF) {
    showToast('Librería jsPDF no disponible.', 'error');
    return;
  }

  var doc = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });
  var fechaGen = new Date().toLocaleString('es-MX', { hour12: false });

  doc.setFontSize(14);
  doc.text('Brokers Connector — Audit Trail', 14, 15);
  doc.setFontSize(9);
  doc.text('Generado: ' + fechaGen, 14, 21);
  doc.text('Página: 1', 267, 21, { align: 'right' });

  var cols = ['#', 'Fecha / Hora', 'Super Admin', 'Acción', 'Empresa', 'De', 'A'];
  var rows = auditState.rows.map(function (log) {
    var fecha = log.created_at
      ? new Date(log.created_at).toLocaleString('es-MX', { hour12: false })
      : '—';
    return [
      String(log.id),
      fecha,
      log.actor_email || '—',
      ACTION_LABEL[log.action] || log.action,
      log.target_name || String(log.target_id) || '—',
      log.from_status || '—',
      log.to_status   || '—',
    ];
  });

  // autoTable: incluido en jsPDF v2 UMD
  doc.autoTable({
    startY: 26,
    head: [cols],
    body: rows,
    styles: { fontSize: 8, cellPadding: 2 },
    headStyles: { fillColor: [48, 119, 183], textColor: 255, fontStyle: 'bold' },
    alternateRowStyles: { fillColor: [245, 248, 252] },
    margin: { left: 14, right: 14 },
  });

  doc.save('audit-trail-' + new Date().toISOString().slice(0, 10) + '.pdf');
  showToast('PDF generado correctamente.');
});

// ══════════════════════════════════════════════════════════════════════════════
// BOOT — Idéntico al flujo Bridge de checkout.js
// ══════════════════════════════════════════════════════════════════════════════

(function boot() {
  // Guardia de doble ejecución
  if (window.__securityBooted) return;
  window.__securityBooted = true;

  // URLSearchParams decodifica automáticamente — no aplicar decodeURIComponent adicional
  var params      = new URLSearchParams(window.location.search);
  var accessToken = params.get('access_token') || '';
  var apiRaw      = params.get('api')          || '';
  var apiBase     = apiRaw.replace(/\/+$/, '');

  state.apiBase      = apiBase;
  state.sessionToken = accessToken;   // Passport Bearer token listo para apiFetch()

  if (!accessToken) {
    showError('Token de acceso no proporcionado. Regresa al panel e intenta de nuevo.');
    return;
  }

  // Limpiar la URL inmediatamente — el access_token no debe quedar en el historial
  if (window.history && window.history.replaceState) {
    window.history.replaceState({}, '', window.location.pathname);
  }

  // Validar sesión directamente contra la API protegida por Passport (auth:api + role:super_admin)
  // Si el token es inválido o el rol falla, la API retorna 401/403 y apiFetch() lanza error.
  apiFetch('/api/v2/admin/users?per_page=50')
    .then(function (data) {
      if (!data.success) {
        showError('No tienes permisos de Super Administrador.');
        return;
      }

      showScreen('main');

      if (data.actor) {
        $('header-actor').textContent = data.actor.name;
      }

      renderAdmins(data.data, data.meta);
      renderAdminPager(data.meta, '');
    })
    .catch(function (err) {
      showError(err.message || 'Error al verificar la sesión. Regresa al panel.');
    });
}());
