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
  { btn: 'tab-users', panel: 'panel-users' },
  { btn: 'tab-ai',    panel: 'panel-ai'    },
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
