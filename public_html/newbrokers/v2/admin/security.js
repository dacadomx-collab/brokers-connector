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
  }).then(function (res) {
    if (res.status === 401) {
      throw new Error('Sesión expirada. Regresa al panel.');
    }

    // Intentar parsear el cuerpo siempre — incluye respuestas de error
    return res.text().then(function (text) {
      var data;
      try {
        data = text ? JSON.parse(text) : {};
      } catch (_) {
        // El servidor devolvió HTML (ej. error 500 de PHP) en lugar de JSON
        console.error('[apiFetch] Respuesta no-JSON del servidor:', res.status, text.slice(0, 300));
        throw new Error('El servidor devolvió un error inesperado (' + res.status + '). Revisa los logs.');
      }

      if (res.status === 422) {
        // Laravel Validation: {message, errors: {field: [msgs]}}
        var firstMsg = '';
        if (data.errors) {
          var firstField = Object.values(data.errors)[0];
          firstMsg = Array.isArray(firstField) ? firstField[0] : String(firstField);
        }
        throw new Error(firstMsg || data.message || 'Datos de formulario inválidos.');
      }

      if (!res.ok) {
        // 403, 404, 500, etc.
        throw new Error(data.error || data.message || 'Error del servidor (' + res.status + ').');
      }

      return data;
    });
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
  { btn: 'tab-tokens',    panel: 'panel-tokens'    },
  { btn: 'tab-prompts',   panel: 'panel-prompts'   },
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
$('tab-tokens').addEventListener('click', function () {
  activateTab('tab-tokens');
  loadTokenStats();
});
$('tab-prompts').addEventListener('click', function () {
  activateTab('tab-prompts');
  loadAiPrompts();
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

  // Nodo final: representa el caso extremo de fallo total (no es un error activo)
  const end = document.createElement('div');
  end.className = 'ai-ladder-step ai-ladder-end';
  end.innerHTML =
    '<span class="sa-badge" style="background:rgba(100,116,139,.15);color:#475569;border:1px solid #cbd5e1;font-size:.7rem;">FALLBACK</span>' +
    '<span class="ai-end-label" style="color:var(--v2-text-subtle);font-style:italic;">Si todos fallan → RuntimeException (log del sistema)</span>';
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
      '<td class="ai-last-test-cell" data-setting-id="' + s.id + '">' + renderLastTestCell(s) + '</td>' +
      '<td class="sa-actions-cell">' +
        '<button class="v2-btn v2-btn-sm js-ai-test" ' +
                'data-id="' + s.id + '" ' +
                'data-provider="' + escAttr(s.provider_name) + '" ' +
                'title="Probar conexión con la API Key guardada" ' +
                'style="background:rgba(8,145,178,.08);color:#0891b2;border:1px solid rgba(8,145,178,.25);">&#9889; Test</button>' +
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

  // Botón Test de Conexión (Ping)
  tbody.querySelectorAll('.js-ai-test').forEach((btn) => {
    btn.addEventListener('click', function () {
      execTestAi(Number(btn.dataset.id), btn.dataset.provider, btn);
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

$('ai-form').addEventListener('submit', function (e) {
  e.preventDefault();

  var id     = state.aiEditingId;
  var isEdit = id !== null;

  // ── BUG #3 FIX: Validación client-side antes de tocar el servidor ──────────
  var providerVal  = $('ai-provider').value.trim();
  var keyVal       = $('ai-key').value.trim();
  var priorityVal  = $('ai-priority').value.trim();
  var extraVal     = $('ai-extra').value.trim();

  if (!providerVal) {
    showToast('Selecciona un proveedor de IA.', 'error');
    $('ai-provider').focus();
    return;
  }
  if (!isEdit && !keyVal) {
    showToast('La API Key es obligatoria al crear un proveedor.', 'error');
    $('ai-key').focus();
    return;
  }
  if (!priorityVal || isNaN(Number(priorityVal)) || Number(priorityVal) < 1) {
    showToast('La prioridad debe ser un número mayor a 0.', 'error');
    $('ai-priority').focus();
    return;
  }

  // ── BUG #4 FIX: Validar JSON de extra_config antes de enviar ──────────────
  if (extraVal) {
    try {
      JSON.parse(extraVal);
    } catch (_) {
      showToast('El campo Config extra no es un JSON válido. Revisa la sintaxis.', 'error');
      $('ai-extra').focus();
      return;
    }
  }

  // ── BUG #2 FIX: Estado de carga en el botón ───────────────────────────────
  var submitBtn = $('ai-btn-submit');
  var prevLabel = submitBtn.textContent;
  submitBtn.disabled    = true;
  submitBtn.textContent = 'Guardando…';

  var url    = isEdit ? '/api/v2/admin/ai-settings/' + id : '/api/v2/admin/ai-settings';
  var method = isEdit ? 'PUT' : 'POST';

  var body = {
    provider_name:  providerVal,
    priority_order: Number(priorityVal),
    is_active:      Number($('ai-active').value),
  };

  if (keyVal)   body.api_key      = keyVal;
  if (extraVal) body.extra_config = extraVal;

  // restoreBtn(): función local que restaura el botón siempre.
  // Usamos restore explícito en .then() Y .catch() en lugar de .finally()
  // para garantizar compatibilidad con entornos que no soporten Promise.finally.
  function restoreBtn() {
    submitBtn.disabled    = false;
    submitBtn.textContent = prevLabel;
  }

  apiFetch(url, { method: method, body: JSON.stringify(body) })
    .then(function (d) {
      if (!d.success) throw new Error(d.error || 'Error al guardar.');
      showToast(d.message || (isEdit ? 'Proveedor actualizado.' : 'Proveedor registrado.'));
      restoreBtn();
      resetAiForm();
      loadAiSettings();
    })
    .catch(function (err) {
      showToast(err.message || 'Error desconocido. Revisa la consola.', 'error');
      console.error('[AI Settings] Error al guardar:', err);
      restoreBtn();
    });
});

// ══════════════════════════════════════════════════════════════════════════════
// TEST DE CONEXIÓN (PING) — Diagnóstico Forense
// ══════════════════════════════════════════════════════════════════════════════

// ── Clasificador de errores ──────────────────────────────────────────────────
function classifyApiError(errorMsg) {
  var m = (errorMsg || '').toLowerCase();

  if (m.includes('invalid api key') || m.includes('incorrect api key') ||
      m.includes('invalid_api_key') || m.includes('401') || m.includes('unauthorized') ||
      m.includes('authentication') || m.includes('api key') ||
      m.includes('api_key_invalid') || m.includes('permission_denied') ||
      m.includes('api key not valid') || m.includes('invalid x-goog')) {
    return {
      icon:  '🔑',
      type:  'API Key inválida o expirada',
      hint:  'Verifica que hayas copiado la llave completa y sin espacios. Groq: "gsk_…", OpenAI: "sk-…", Mistral: empieza con letras/números, Gemini: AIza… Reingresa la llave si sigue fallando.',
    };
  }
  if (m.includes('rate limit') || m.includes('429') || m.includes('too many requests') ||
      m.includes('quota') || m.includes('capacity') || m.includes('overloaded')) {
    return {
      icon:  '⏱',
      type:  'Límite de solicitudes excedido (429)',
      hint:  'Esta API Key alcanzó su cuota de solicitudes. Espera unos minutos y vuelve a probar. Si el error persiste, considera actualizar el plan en el proveedor.',
    };
  }
  if (m.includes('ssl') || m.includes('certificate') || m.includes('curl error 60') ||
      m.includes('verify') || m.includes('peer') || m.includes('handshake')) {
    return {
      icon:  '🔒',
      type:  'Error de certificado SSL',
      hint:  'En entorno local (XAMPP), el servidor puede no tener los certificados CA actualizados. Solución: descarga "cacert.pem" de https://curl.se/ca/cacert.pem, ponlo en tu PHP y configura curl.cainfo en php.ini.',
    };
  }
  if (m.includes('timeout') || m.includes('timed out') || m.includes('connection refused') ||
      m.includes('could not resolve') || m.includes('network') || m.includes('curl error 6') ||
      m.includes('curl error 7') || m.includes('no route')) {
    return {
      icon:  '🌐',
      type:  'Error de red / timeout',
      hint:  'El servidor no pudo conectarse a la API externa. Verifica que tengas acceso a internet, que no haya un firewall bloqueando el puerto 443, y que el dominio del proveedor sea accesible.',
    };
  }
  if (m.includes('decrypt') || m.includes('app_key') || m.includes('mac is invalid') ||
      m.includes('decryptstring') || m.includes('unserialize')) {
    return {
      icon:  '🔐',
      type:  'Error de desencriptado',
      hint:  'La APP_KEY del sistema puede haber cambiado desde que se guardó la llave. Solución: edita este proveedor y reingresa la API Key para que se cifre con la llave actual.',
    };
  }
  if (m.includes('not implemented') || m.includes('adaptador') || m.includes('no adapter')) {
    return {
      icon:  '⚙️',
      type:  'Proveedor sin adaptador activo',
      hint:  'Este tipo de proveedor (ej. Anthropic, Gemini) aún no tiene un adaptador implementado en el sistema. Está en el roadmap de la Fase 2 de V2.',
    };
  }
  if (m.includes('insufficient') || m.includes('credits') || m.includes('billing') ||
      m.includes('payment') || m.includes('saldo')) {
    return {
      icon:  '💳',
      type:  'Saldo insuficiente / Problema de facturación',
      hint:  'La cuenta del proveedor puede tener créditos agotados. Ingresa al dashboard del proveedor y recarga saldo o verifica el método de pago.',
    };
  }
  return {
    icon:  '⚠️',
    type:  'Error desconocido',
    hint:  'Revisa el log del sistema (storage/logs/laravel-YYYY-MM-DD.log) para obtener el stack trace completo.',
  };
}

// ── Formatea fecha ISO a string local legible ────────────────────────────────
function formatPingDate(isoString) {
  if (!isoString) return null;
  try {
    var d = new Date(isoString);
    return d.toLocaleString('es-MX', {
      day:    '2-digit',
      month:  'long',
      year:   'numeric',
      hour:   '2-digit',
      minute: '2-digit',
      hour12: false,
    });
  } catch (_) {
    return isoString;
  }
}

// ── Tiempo relativo para la columna ─────────────────────────────────────────
function relativeTime(isoString) {
  if (!isoString) return null;
  try {
    var diff = Math.floor((Date.now() - new Date(isoString)) / 1000);
    if (diff < 60)    return 'hace menos de 1 min';
    if (diff < 3600)  return 'hace ' + Math.floor(diff / 60) + ' min';
    if (diff < 86400) return 'hace ' + Math.floor(diff / 3600) + ' h';
    if (diff < 604800) return 'hace ' + Math.floor(diff / 86400) + ' días';
    return formatPingDate(isoString);
  } catch (_) {
    return null;
  }
}

// ── Render de la celda "Último Test" (columna de la tabla) ───────────────────
function renderLastTestCell(s) {
  if (!s.last_tested_at) {
    return '<span style="color:var(--v2-text-subtle);font-size:.8rem;">Nunca probado</span>';
  }

  var rel  = relativeTime(s.last_tested_at);
  var full = formatPingDate(s.last_tested_at);

  if (s.last_test_status === 'ok') {
    var lat = s.last_test_latency_ms ? ' · ' + s.last_test_latency_ms + ' ms' : '';
    return '<span title="' + escHtml(full) + '" style="display:flex;flex-direction:column;gap:.1rem;">' +
      '<span style="font-size:.8rem;font-weight:700;color:#047857;">✓ OK' + escHtml(lat) + '</span>' +
      '<span style="font-size:.7rem;color:var(--v2-text-subtle);">' + escHtml(rel) + '</span>' +
    '</span>';
  }

  return '<span title="' + escHtml(full) + '" style="display:flex;flex-direction:column;gap:.1rem;">' +
    '<span style="font-size:.8rem;font-weight:700;color:#dc2626;">✗ Error</span>' +
    '<span style="font-size:.7rem;color:var(--v2-text-subtle);">' + escHtml(rel) + '</span>' +
  '</span>';
}

// ── Modal de Diagnóstico Forense ─────────────────────────────────────────────

function openPingModal(providerLabel, isOk, data) {
  var modal = $('ai-ping-modal');

  // ── RESET COMPLETO antes de repintar ────────────────────────────────────────
  // Usamos style.display = 'none' EXPLÍCITO en lugar de classList.add('hidden').
  // Razón: la clase .hidden puede tener problemas de especificidad CSS en ciertos
  // entornos, dejando los contenedores visibles pero vacíos (estado residual lógico).
  // style.display anula cualquier regla CSS sin excepción.
  $('ai-ping-result-block').removeAttribute('style');
  $('ai-ping-result-icon').textContent  = '';
  $('ai-ping-result-label').textContent = '';
  $('ai-ping-result-label').removeAttribute('style');
  $('ai-ping-result-meta').textContent  = '';

  $('ai-ping-error-block').style.display = 'none';
  $('ai-ping-ok-block').style.display    = 'none';

  $('ai-ping-error-icon').textContent   = '';
  $('ai-ping-error-type').textContent   = '';
  $('ai-ping-error-detail').textContent = '';
  $('ai-ping-hint').textContent         = '';
  $('ai-ping-response').textContent     = '';

  // Configurar badge del proveedor
  var info = aiLabel(data.provider_name || '');
  $('ai-ping-modal-badge').innerHTML =
    '<span class="ai-prov-badge ' + info.css + '" style="font-size:.7rem;">' +
    escHtml(info.label) + '</span>';

  if (isOk) {
    // ── Resultado exitoso ────────────────────────────────────────────────────
    var lat  = data.latency_ms ? data.latency_ms + ' ms' : '—';
    var full = formatPingDate(data.last_tested_at);

    $('ai-ping-result-block').style.background = 'rgba(4,120,87,.05)';
    $('ai-ping-result-block').style.borderColor = 'rgba(4,120,87,.25)';
    $('ai-ping-result-icon').textContent = '✅';
    $('ai-ping-result-label').textContent = 'Conexión exitosa';
    $('ai-ping-result-label').style.color = '#047857';
    $('ai-ping-result-meta').textContent = 'Latencia: ' + lat + ' · ' + (full || '');

    $('ai-ping-error-block').style.display = 'none';
    $('ai-ping-ok-block').style.display    = '';       // restores CSS default (block)
    $('ai-ping-response').textContent = data.response || '(sin contenido)';

  } else {
    // ── Resultado con error ──────────────────────────────────────────────────
    var classification = classifyApiError(data.error || '');
    var full2 = formatPingDate(data.last_tested_at);

    $('ai-ping-result-block').style.background  = 'rgba(220,38,38,.04)';
    $('ai-ping-result-block').style.borderColor  = 'rgba(220,38,38,.18)';
    $('ai-ping-result-icon').textContent  = '❌';
    $('ai-ping-result-label').textContent = 'Falló la conexión';
    $('ai-ping-result-label').style.color = '#dc2626';
    $('ai-ping-result-meta').textContent  = full2 || '';

    $('ai-ping-ok-block').style.display    = 'none';
    $('ai-ping-error-block').style.display = '';      // restores CSS default (block)

    $('ai-ping-error-icon').textContent  = classification.icon;
    $('ai-ping-error-type').textContent  = classification.type;
    $('ai-ping-error-detail').textContent = data.error || 'Sin detalles adicionales.';
    $('ai-ping-hint').textContent        = classification.hint;
  }

  modal.classList.remove('hidden');
  $('ai-ping-modal-close-btn').focus();
}

function closePingModal() {
  $('ai-ping-modal').classList.add('hidden');
}

$('ai-ping-close').addEventListener('click', closePingModal);
$('ai-ping-modal-close-btn').addEventListener('click', closePingModal);
$('ai-ping-modal').addEventListener('click', function (e) {
  if (e.target === $('ai-ping-modal')) closePingModal();
});

// ── Test de Conexión (Ping) — versión con historial persistente ──────────────

function execTestAi(id, providerLabel, btn) {
  var prevHTML     = btn.innerHTML;
  var prevDisabled = btn.disabled;

  btn.disabled      = true;
  btn.innerHTML     = '&#9203; Probando…';
  btn.style.opacity = '0.65';

  apiFetch('/api/v2/admin/ai-settings/' + id + '/test', { method: 'POST' })
    .then(function (d) {
      if (!d.success) throw Object.assign(new Error(d.error || 'Error de conexión'), { apiData: d });
      return d;
    })
    .then(function (d) {
      // Actualizar celda "Último Test" inline (sin recargar la tabla)
      updateLastTestCell(id, d);

      // Restaurar botón
      btn.innerHTML         = '⚡ Test';
      btn.style.opacity     = '1';
      btn.disabled          = prevDisabled;
      btn.style.background  = '';
      btn.style.color       = '';
      btn.style.borderColor = '';

      // Abrir modal de diagnóstico
      openPingModal(providerLabel, true, Object.assign({ provider_name: btn.dataset.provider }, d));
    })
    .catch(function (err) {
      var apiData = err.apiData || {};

      // Actualizar celda "Último Test" inline si tenemos datos de fecha del backend
      if (apiData.last_tested_at) {
        updateLastTestCell(id, apiData);
      }

      // Restaurar botón
      btn.innerHTML         = '⚡ Test';
      btn.style.opacity     = '1';
      btn.disabled          = prevDisabled;
      btn.style.background  = '';
      btn.style.color       = '';
      btn.style.borderColor = '';

      // Abrir modal de diagnóstico con detalle del error
      openPingModal(providerLabel, false, Object.assign(
        { provider_name: btn.dataset.provider, error: err.message },
        apiData
      ));
    });
}

// ── Actualización inline de la celda "Último Test" ───────────────────────────
function updateLastTestCell(settingId, pingData) {
  var cell = document.querySelector('.ai-last-test-cell[data-setting-id="' + settingId + '"]');
  if (!cell) return;
  cell.innerHTML = renderLastTestCell({
    last_tested_at:       pingData.last_tested_at,
    last_test_status:     pingData.last_test_status,
    last_test_latency_ms: pingData.last_test_latency_ms || pingData.latency_ms || null,
  });
}

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
  // Empresas
  activate:              'Activar empresa',
  suspend:               'Suspender empresa',
  delete:                'Eliminar empresa',
  update:                'Editar empresa',
  subscription_override: 'Ajuste financiero manual',
  // Usuarios
  toggle_role:           'Cambio de Rol',
  reset_password:        'Reset Contraseña',
  // Orquestador IA
  CREATE_AI_SETTING:     'Crear proveedor IA',
  UPDATE_AI_SETTING:     'Editar proveedor IA',
  DELETE_AI_SETTING:     'Eliminar proveedor IA',
  TOGGLE_AI_SETTING:     'Toggle estado proveedor IA',
  TEST_AI_SETTING:       'Test de conexión proveedor IA',
  // Prompts Maestros
  UPDATE_AI_PROMPT:      'Editar Prompt Maestro IA',
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
// MÓDULO F — MONITOR DE CONSUMO IA (Tokens)
// Registro Maestro: agrega tokens_used de ai_messages por tenant.
// ══════════════════════════════════════════════════════════════════════════════

var tokState = { period: 'all', data: null };

// ── Formateo numérico ────────────────────────────────────────────────────────

function fmtTokens(n) {
  n = Number(n) || 0;
  if (n >= 1000000) return (n / 1000000).toFixed(2) + 'M';
  if (n >= 1000)    return (n / 1000).toFixed(1) + 'K';
  return n.toLocaleString('es-MX');
}

function fmtNumInt(n) {
  return (Number(n) || 0).toLocaleString('es-MX');
}

function fmtTokDate(isoStr) {
  if (!isoStr) return '—';
  try {
    return new Date(isoStr).toLocaleString('es-MX', {
      day: '2-digit', month: 'short', year: 'numeric',
      hour: '2-digit', minute: '2-digit', hour12: false,
    });
  } catch (_) { return isoStr; }
}

// ── Carga de datos ───────────────────────────────────────────────────────────

function loadTokenStats(period) {
  period = period || tokState.period;
  tokState.period = period;

  // Actualizar botones de período
  document.querySelectorAll('.tok-period-btn').forEach(function (btn) {
    btn.classList.toggle('tok-period-active', btn.dataset.period === period);
  });

  $('tok-loading').style.display  = '';
  $('tok-content').style.display  = 'none';

  apiFetch('/api/v2/admin/token-stats?period=' + period)
    .then(function (d) {
      if (!d.success) throw new Error(d.error || 'Error al cargar estadísticas.');
      tokState.data = d;
      renderTokenStats(d);
      $('tok-loading').style.display = 'none';
      $('tok-content').style.display = '';
    })
    .catch(function (e) {
      $('tok-loading').style.display = 'none';
      showToast(e.message, 'error');
    });
}

// ── Render principal ─────────────────────────────────────────────────────────

function renderTokenStats(d) {
  renderTokKPIs(d.global, d.period);
  renderTokBarChart(d.by_company);
  renderTokTable(d.by_company);
}

// ── KPI Cards ────────────────────────────────────────────────────────────────

function renderTokKPIs(global, period) {
  var periodLabels = { all: 'de todo el tiempo', '90d': 'últimos 90 días', '30d': 'últimos 30 días', '7d': 'últimos 7 días' };
  var periodSub    = periodLabels[period] || '';

  var cards = [
    {
      icon: '⚡', primary: true,
      value: fmtTokens(global.total_tokens),
      label: 'Tokens totales', sub: periodSub,
    },
    {
      icon: '💬', primary: false,
      value: fmtNumInt(global.total_messages),
      label: 'Mensajes IA', sub: 'procesados',
    },
    {
      icon: '🔁', primary: false,
      value: fmtNumInt(global.total_conversations),
      label: 'Conversaciones', sub: 'abiertas',
    },
    {
      icon: '📊', primary: false,
      value: fmtNumInt(Math.round(global.avg_tokens_per_message)),
      label: 'Tokens / mensaje', sub: 'promedio del sistema',
    },
    {
      icon: '🏢', primary: false,
      value: fmtNumInt(global.total_companies_active),
      label: 'Empresas activas', sub: 'con uso de IA',
    },
  ];

  $('tok-kpi-grid').innerHTML = cards.map(function (c) {
    return '<div class="tok-kpi' + (c.primary ? ' tok-kpi-primary' : '') + '">' +
      '<div class="tok-kpi-icon">' + c.icon + '</div>' +
      '<div class="tok-kpi-value">' + escHtml(c.value) + '</div>' +
      '<div class="tok-kpi-label">' + escHtml(c.label) + '</div>' +
      '<div class="tok-kpi-sub">' + escHtml(c.sub) + '</div>' +
    '</div>';
  }).join('');
}

// ── Gráfico de barras CSS ────────────────────────────────────────────────────

function renderTokBarChart(companies) {
  var list = $('tok-bar-list');
  var empty = $('tok-no-usage');

  var top = companies.slice(0, 12); // top 12 en el gráfico
  $('tok-chart-label').textContent = companies.length + ' empresa' + (companies.length !== 1 ? 's' : '');

  if (!top.length) {
    list.innerHTML  = '';
    list.style.display  = 'none';
    empty.classList.remove('hidden');
    return;
  }

  empty.classList.add('hidden');
  list.style.display = '';

  var maxTokens = top[0].total_tokens || 1;

  list.innerHTML = top.map(function (c, i) {
    var pct = Math.max(2, Math.round((c.total_tokens / maxTokens) * 100));

    // Degradado de color: índigo profundo (#4f46e5) para el #1, más claro hacia abajo
    var lightness = 50 + Math.round((i / Math.max(top.length - 1, 1)) * 22);
    var barColor  = 'hsl(244,' + (84 - i * 2) + '%,' + lightness + '%)';

    // Badge de posición
    var rankBadge = i === 0
      ? '<span class="tok-rank tok-rank-gold">🥇</span>'
      : i === 1
        ? '<span class="tok-rank tok-rank-silver">🥈</span>'
        : i === 2
          ? '<span class="tok-rank tok-rank-bronze">🥉</span>'
          : '<span class="tok-rank tok-rank-plain">' + (i + 1) + '</span>';

    return '<div class="tok-bar-row">' +
      '<div class="tok-bar-company">' + rankBadge + '<span title="' + escHtml(c.company_name) + '">' + escHtml(c.company_name) + '</span></div>' +
      '<div class="tok-bar-bg"><div class="tok-bar-fill" data-width="' + pct + '%" style="background:' + barColor + ';width:0%;"></div></div>' +
      '<div class="tok-bar-tokens">' + escHtml(fmtTokens(c.total_tokens)) + '<span class="tok-bar-pct"> (' + c.pct_of_total + '%)</span></div>' +
    '</div>';
  }).join('');

  // Animar barras tras renderizar
  requestAnimationFrame(function () {
    list.querySelectorAll('.tok-bar-fill').forEach(function (bar) {
      bar.style.width = bar.dataset.width;
    });
  });
}

// ── Tabla detallada ──────────────────────────────────────────────────────────

function renderTokTable(companies) {
  var tbody = $('tok-tbody');
  $('tok-table-count').textContent = companies.length;

  if (!companies.length) {
    tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:1.5rem;color:var(--v2-text-subtle);">Sin datos en el período seleccionado.</td></tr>';
    return;
  }

  tbody.innerHTML = companies.map(function (c, i) {
    // Barra micro-progress en la celda de % Sistema
    var pctBar = '<div class="tok-micro-bar"><div class="tok-micro-fill" style="width:' + c.pct_of_total + '%"></div></div>';

    return '<tr>' +
      '<td><span class="tok-rank tok-rank-plain" style="font-size:.7rem;">' + (i + 1) + '</span></td>' +
      '<td><strong>' + escHtml(c.company_name) + '</strong><br>' +
        '<span style="font-size:.7rem;color:var(--v2-text-subtle);">ID #' + c.company_id + '</span></td>' +
      '<td><span class="tok-token-pill">' + escHtml(fmtTokens(c.total_tokens)) + '</span>' +
        '<span style="font-size:.7rem;color:var(--v2-text-subtle);display:block;">' + fmtNumInt(c.total_tokens) + '</span></td>' +
      '<td>' + fmtNumInt(c.total_messages) + '</td>' +
      '<td>' + fmtNumInt(c.total_conversations) + '</td>' +
      '<td>' + fmtNumInt(Math.round(c.avg_tokens_per_msg)) + '</td>' +
      '<td>' + pctBar + '<span style="font-size:.75rem;font-weight:600;">' + c.pct_of_total + '%</span></td>' +
      '<td style="font-size:.8rem;color:var(--v2-text-muted);">' + escHtml(fmtTokDate(c.last_activity)) + '</td>' +
    '</tr>';
  }).join('');
}

// ── Exportar CSV ─────────────────────────────────────────────────────────────

function exportTokCSV() {
  if (!tokState.data || !tokState.data.by_company.length) {
    showToast('Sin datos para exportar.', 'error');
    return;
  }

  var periodLabel = { all: 'todos', '90d': '90dias', '30d': '30dias', '7d': '7dias' }[tokState.period] || 'todos';
  var rows = [['#', 'ID Empresa', 'Empresa', 'Tokens', 'Mensajes', 'Conversaciones', 'Avg Tokens/Msg', '% Sistema', 'Última Actividad']];

  tokState.data.by_company.forEach(function (c, i) {
    rows.push([
      i + 1,
      c.company_id,
      c.company_name,
      c.total_tokens,
      c.total_messages,
      c.total_conversations,
      c.avg_tokens_per_msg,
      c.pct_of_total + '%',
      c.last_activity || '',
    ]);
  });

  // Encabezado con resumen global
  var g = tokState.data.global;
  rows.unshift(['Sistema', '', 'TOTAL GLOBAL', g.total_tokens, g.total_messages, g.total_conversations, g.avg_tokens_per_message, '100%', '']);
  rows.unshift(['Período:', tokState.period, '', '', '', '', '', '', '']);
  rows.unshift(['Brokers Connector — Monitor de Tokens IA', '', '', '', '', '', '', '', '']);

  var csv = rows.map(function (r) {
    return r.map(function (cell) {
      return '"' + String(cell).replace(/"/g, '""') + '"';
    }).join(',');
  }).join('\r\n');

  var blob = new Blob(['﻿' + csv], { type: 'text/csv;charset=utf-8;' }); // BOM para Excel
  var url  = URL.createObjectURL(blob);
  var a    = document.createElement('a');
  a.href   = url;
  a.download = 'tokens-ia-' + periodLabel + '-' + new Date().toISOString().slice(0, 10) + '.csv';
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  URL.revokeObjectURL(url);
  showToast('CSV generado correctamente.');
}

// ── Event listeners del módulo Tokens ────────────────────────────────────────

$('tok-refresh').addEventListener('click', function () { loadTokenStats(tokState.period); });
$('tok-export-csv').addEventListener('click', exportTokCSV);

document.querySelectorAll('.tok-period-btn').forEach(function (btn) {
  btn.addEventListener('click', function () { loadTokenStats(btn.dataset.period); });
});

// ══════════════════════════════════════════════════════════════════════════════
// MÓDULO G — SYNAPTIC CORE™ (Visual Prompt Architect)
// Motor Cognitivo AVM — gestión modular de System Prompts en tiempo real.
// ══════════════════════════════════════════════════════════════════════════════

var scState = { editingSlug: null, editingPrompt: null };

// ── Carga y render de tarjetas ───────────────────────────────────────────────

function loadAiPrompts() {
  $('prompts-loading').classList.remove('hidden');
  $('prompts-list').classList.add('hidden');

  apiFetch('/api/v2/admin/ai-prompts')
    .then(function (d) {
      $('prompts-loading').classList.add('hidden');
      if (!d.success || !d.data || !d.data.length) {
        $('prompts-list').innerHTML =
          '<p style="color:var(--v2-text-subtle);padding:1rem;">Sin módulos cognitivos registrados. ' +
          'Ejecuta el INSERT del Synaptic Core en phpMyAdmin para crear el prompt base.</p>';
        $('prompts-list').classList.remove('hidden');
        $('prompts-count').textContent = '0';
        return;
      }
      $('prompts-count').textContent = d.data.length + ' módulo' + (d.data.length !== 1 ? 's' : '');
      renderPromptsCards(d.data);
      $('prompts-list').classList.remove('hidden');
    })
    .catch(function (e) {
      $('prompts-loading').classList.add('hidden');
      showToast(e.message, 'error');
    });
}

function renderPromptsCards(prompts) {
  var list = $('prompts-list');
  list.innerHTML = '';

  prompts.forEach(function (p) {
    var updatedAt = p.updated_at
      ? new Date(p.updated_at).toLocaleString('es-MX', { hour12: false })
      : '—';

    // Mostrar el módulo más representativo del prompt
    var previewSrc  = p.system_role || p.prompt_text || '';
    var preview     = previewSrc.slice(0, 220).replace(/\n/g, ' ');
    var totalChars  = (p.system_role  || '').length
                    + (p.prompt_text  || '').length
                    + (p.immutable_rules || '').length;
    var isModular   = !!(p.system_role);
    var modeLabel   = isModular ? '🧩 Modular' : '📄 Legacy';
    var modeClass   = isModular ? 'sc-mode-modular' : 'sc-mode-legacy';

    var card = document.createElement('div');
    card.className = 'prm-card sa-card';
    card.innerHTML =
      '<div class="prm-card-head">' +
        '<div class="prm-card-meta">' +
          '<code class="prm-slug-badge">' + escHtml(p.slug) + '</code>' +
          '<span class="prm-name">' + escHtml(p.name) + '</span>' +
          '<span class="' + modeClass + '">' + modeLabel + '</span>' +
          '<span class="sc-version-chip">v' + (p.version || 1) + '</span>' +
        '</div>' +
        '<div class="prm-card-actions">' +
          '<span class="prm-updated">Actualizado: ' + escHtml(updatedAt) + '</span>' +
          '<button class="v2-btn v2-btn-sm v2-btn-primary sc-open-btn">' +
            '⚡ Synaptic Core™' +
          '</button>' +
        '</div>' +
      '</div>' +
      '<div class="prm-preview">' + escHtml(preview) + (previewSrc.length > 220 ? '…' : '') + '</div>' +
      '<div class="prm-char-count">' + totalChars.toLocaleString('es-MX') + ' caracteres totales</div>';

    card.querySelector('.sc-open-btn').addEventListener('click', function () {
      openSynapticCore(p);
    });

    list.appendChild(card);
  });
}

// ── Synaptic Core™ Modal ─────────────────────────────────────────────────────

function openSynapticCore(promptData) {
  scState.editingSlug   = promptData.slug;
  scState.editingPrompt = promptData;

  // Header
  $('sc-header-slug').textContent   = promptData.slug;
  $('sc-header-name').textContent   = promptData.name || promptData.slug;
  $('sc-version-badge').textContent = 'v' + (promptData.version || 1);

  // Identidad
  $('sc-name').value            = promptData.name || '';
  $('sc-system-role').value     = promptData.system_role || '';
  $('sc-preferred-model').value = promptData.preferred_model || '';

  // Contexto
  $('sc-business-context').value = promptData.business_context || '';
  $('sc-tone-profile').value     = promptData.tone_profile
    ? JSON.stringify(promptData.tone_profile, null, 2) : '';

  // Reglas
  $('sc-immutable-rules').value = promptData.immutable_rules || '';
  $('sc-prompt-text').value     = promptData.prompt_text || '';

  // Esquemas
  $('sc-output-schema').value     = promptData.output_schema
    ? JSON.stringify(promptData.output_schema, null, 2) : '';
  $('sc-variables-schema').value  = promptData.variables_schema
    ? JSON.stringify(promptData.variables_schema, null, 2) : '';

  // Playground
  renderPlaygroundVars(promptData.variables_schema);
  $('sc-playground-response').textContent = 'Ejecuta un test para ver la respuesta aquí…';
  $('sc-playground-response').className   = 'sc-response-pre sc-response-idle';
  $('sc-playground-meta').style.display   = 'none';
  $('sc-compiled-prompt').textContent     = '';
  $('sc-compiled-prompt').className       = 'sc-response-pre sc-compiled-hidden';
  $('sc-playground-provider').textContent = '';

  // Reset error + activate first tab
  $('sc-save-error').classList.add('hidden');
  activateScTab('identity');

  $('synaptic-core-modal').classList.remove('hidden');
  $('sc-system-role').focus();
}

function closeSynapticCore() {
  $('synaptic-core-modal').classList.add('hidden');
  scState.editingSlug   = null;
  scState.editingPrompt = null;
}

// ── Tab navigation ───────────────────────────────────────────────────────────

function activateScTab(tabId) {
  document.querySelectorAll('.sc-tab').forEach(function (t) {
    t.classList.toggle('sc-tab-active', t.dataset.scTab === tabId);
  });
  document.querySelectorAll('.sc-panel').forEach(function (p) {
    p.classList.toggle('hidden', p.id !== 'sc-panel-' + tabId);
  });
}

document.querySelectorAll('.sc-tab').forEach(function (btn) {
  btn.addEventListener('click', function () { activateScTab(btn.dataset.scTab); });
});

// ── Save ─────────────────────────────────────────────────────────────────────

function saveSynapticCore() {
  var slug    = scState.editingSlug;
  var saveBtn = $('sc-save-btn');
  var errEl   = $('sc-save-error');
  errEl.classList.add('hidden');

  // Validar y parsear campos JSON
  var toneProfile = null, outputSchema = null, variablesSchema = null;

  try {
    var toneRaw = $('sc-tone-profile').value.trim();
    if (toneRaw) toneProfile = JSON.parse(toneRaw);
  } catch (_) {
    errEl.textContent = 'Perfil de Tono: JSON inválido. Verifica la sintaxis.';
    errEl.classList.remove('hidden');
    activateScTab('context');
    return;
  }
  try {
    var outputRaw = $('sc-output-schema').value.trim();
    if (outputRaw) outputSchema = JSON.parse(outputRaw);
  } catch (_) {
    errEl.textContent = 'Output Schema: JSON inválido. Verifica la sintaxis.';
    errEl.classList.remove('hidden');
    activateScTab('schemas');
    return;
  }
  try {
    var varsRaw = $('sc-variables-schema').value.trim();
    if (varsRaw) variablesSchema = JSON.parse(varsRaw);
  } catch (_) {
    errEl.textContent = 'Variables Schema: JSON inválido. Verifica la sintaxis.';
    errEl.classList.remove('hidden');
    activateScTab('schemas');
    return;
  }

  var systemRole  = $('sc-system-role').value.trim();
  var promptText  = $('sc-prompt-text').value.trim();
  if (!systemRole && !promptText) {
    errEl.textContent = 'El Rol del Sistema o el Prompt Legacy deben tener contenido.';
    errEl.classList.remove('hidden');
    activateScTab('identity');
    return;
  }

  var payload = {
    name:             $('sc-name').value.trim(),
    system_role:      systemRole,
    business_context: $('sc-business-context').value.trim(),
    immutable_rules:  $('sc-immutable-rules').value.trim(),
    preferred_model:  $('sc-preferred-model').value.trim(),
    prompt_text:      promptText,
    tone_profile:     toneProfile,
    output_schema:    outputSchema,
    variables_schema: variablesSchema,
  };

  saveBtn.disabled    = true;
  saveBtn.textContent = '⏳ Guardando…';

  apiFetch('/api/v2/admin/ai-prompts/' + encodeURIComponent(slug), {
    method: 'PUT',
    body:   JSON.stringify(payload),
  })
    .then(function (d) {
      if (!d.success) throw new Error(d.error || 'Error al guardar.');
      $('sc-version-badge').textContent = 'v' + (d.new_version || '?');
      showToast('⚡ Synaptic Core™ "' + slug + '" guardado como ' + $('sc-version-badge').textContent + '. Motor Cognitivo actualizado.');
      loadAiPrompts();
    })
    .catch(function (e) {
      errEl.textContent = e.message;
      errEl.classList.remove('hidden');
    })
    .finally(function () {
      saveBtn.disabled    = false;
      saveBtn.textContent = '💾 Guardar versión';
    });
}

// ── Playground ───────────────────────────────────────────────────────────────

function renderPlaygroundVars(schema) {
  var container = $('sc-playground-vars');
  container.innerHTML = '';

  var vars = Array.isArray(schema) ? schema : [];
  if (!vars.length && schema && typeof schema === 'object') {
    vars = Object.keys(schema).map(function (k) { return { key: k, label: k, type: 'string' }; });
  }

  vars.forEach(function (v) {
    container.appendChild(buildVarRow(v.key, v.label || v.key, v.type || 'string', false));
  });
}

function buildVarRow(key, label, type, isManual) {
  var row = document.createElement('div');
  row.className = 'sc-var-row';
  if (isManual) row.className += ' sc-var-manual';

  if (isManual) {
    row.innerHTML =
      '<input type="text" class="v2-input sc-manual-key" placeholder="{{nombre}}" style="flex:1;min-width:0;">' +
      '<span style="padding:0 .25rem;color:var(--v2-text-subtle);">=</span>' +
      '<input type="text" class="v2-input sc-manual-val" placeholder="valor" style="flex:2;min-width:0;">' +
      '<button class="v2-btn v2-btn-sm v2-btn-ghost sc-rm-var" title="Quitar">✕</button>';
    row.querySelector('.sc-rm-var').addEventListener('click', function () { row.remove(); });
  } else {
    row.innerHTML =
      '<label class="sc-var-label">' + escHtml(label) +
      ' <code class="sc-var-key-code">{{' + escHtml(key) + '}}</code></label>' +
      '<input type="' + (type === 'number' ? 'number' : 'text') + '"' +
             ' class="v2-input sc-var-input" data-var-key="' + escAttr(key) + '"' +
             ' placeholder="valor de prueba">';
  }
  return row;
}

function addPlaygroundVar() {
  $('sc-playground-vars').appendChild(buildVarRow('', '', 'string', true));
  $('sc-playground-vars').lastElementChild.querySelector('.sc-manual-key').focus();
}

function runPlayground() {
  var runBtn     = $('sc-playground-run');
  var responseEl = $('sc-playground-response');
  var metaEl     = $('sc-playground-meta');
  var compEl     = $('sc-compiled-prompt');

  runBtn.disabled      = true;
  runBtn.textContent   = '⏳ Ejecutando…';
  responseEl.textContent = 'Consultando al modelo…';
  responseEl.className   = 'sc-response-pre sc-response-loading';
  metaEl.style.display   = 'none';

  // Recopilar variables
  var variables = {};
  $('sc-playground-vars').querySelectorAll('[data-var-key]').forEach(function (inp) {
    variables[inp.dataset.varKey] = inp.value;
  });
  $('sc-playground-vars').querySelectorAll('.sc-var-manual').forEach(function (row) {
    var key = (row.querySelector('.sc-manual-key').value || '').replace(/^\{\{|\}\}$/g, '').trim();
    if (key) variables[key] = row.querySelector('.sc-manual-val').value;
  });

  // Parsear JSON fields del estado actual del formulario (no guardado)
  var tp = null, os = null, vs = null;
  try { var t = $('sc-tone-profile').value.trim();    if (t) tp = JSON.parse(t); } catch (_) {}
  try { var o = $('sc-output-schema').value.trim();   if (o) os = JSON.parse(o); } catch (_) {}
  try { var v = $('sc-variables-schema').value.trim(); if (v) vs = JSON.parse(v); } catch (_) {}

  var payload = {
    system_role:      $('sc-system-role').value.trim(),
    business_context: $('sc-business-context').value.trim(),
    immutable_rules:  $('sc-immutable-rules').value.trim(),
    preferred_model:  $('sc-preferred-model').value.trim(),
    prompt_text:      $('sc-prompt-text').value.trim(),
    tone_profile:     tp,
    output_schema:    os,
    variables_schema: vs,
    test_message:     $('sc-playground-msg').value.trim() || 'Prueba del sistema.',
    variables:        variables,
  };

  apiFetch('/api/v2/admin/ai-prompts/' + encodeURIComponent(scState.editingSlug) + '/test', {
    method: 'POST',
    body:   JSON.stringify(payload),
  })
    .then(function (d) {
      if (!d.success) throw new Error(d.error);

      // Mostrar prompt compilado
      compEl.textContent = d.compiled_prompt || '';

      // Formatear respuesta JSON
      var raw = d.raw_response || '';
      try { raw = JSON.stringify(JSON.parse(raw), null, 2); } catch (_) {}
      responseEl.textContent = raw;
      responseEl.className   = 'sc-response-pre sc-response-ok';

      // Meta
      var lat = d.latency_ms ? d.latency_ms + ' ms' : '';
      metaEl.textContent   = lat || 'Test completado.';
      metaEl.style.display = '';

      $('sc-playground-provider').textContent = lat ? '⚡ ' + lat : '';
    })
    .catch(function (e) {
      responseEl.textContent = '❌ ' + e.message;
      responseEl.className   = 'sc-response-pre sc-response-error';
    })
    .finally(function () {
      runBtn.disabled    = false;
      runBtn.textContent = '▶ Ejecutar Test en AURA';
    });
}

// ── Listeners del Synaptic Core™ ─────────────────────────────────────────────

$('prompts-refresh').addEventListener('click', loadAiPrompts);
$('sc-close-btn').addEventListener('click', closeSynapticCore);
$('synaptic-core-modal').addEventListener('click', function (e) {
  if (e.target === $('synaptic-core-modal')) closeSynapticCore();
});
$('sc-save-btn').addEventListener('click', saveSynapticCore);
$('sc-playground-add-var').addEventListener('click', addPlaygroundVar);
$('sc-playground-run').addEventListener('click', runPlayground);
$('sc-toggle-compiled').addEventListener('click', function () {
  var el  = $('sc-compiled-prompt');
  var btn = $('sc-toggle-compiled');
  var isHidden = el.classList.contains('sc-compiled-hidden');
  el.classList.toggle('sc-compiled-hidden', !isHidden);
  btn.textContent = isHidden ? 'Ocultar' : 'Mostrar';
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
