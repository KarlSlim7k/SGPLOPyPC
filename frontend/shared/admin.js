(function () {
  const TOKEN_KEY = 'sgplopypc_token';
  const USER_KEY = 'sgplopypc_user';

  function getToken() {
    return localStorage.getItem(TOKEN_KEY);
  }

  function getUser() {
    try {
      return JSON.parse(localStorage.getItem(USER_KEY) || 'null');
    } catch (_) {
      return null;
    }
  }

  function setUser(user) {
    localStorage.setItem(USER_KEY, JSON.stringify(user || null));
  }

  function clearSession() {
    localStorage.removeItem(TOKEN_KEY);
    localStorage.removeItem(USER_KEY);
  }

  function logout() {
    clearSession();
    window.location.href = '/frontend/auth/login.html';
  }

  function protectRoute() {
    if (!getToken()) {
      window.location.href = '/frontend/auth/login.html';
      return false;
    }
    return true;
  }

  async function parseResponse(response) {
    const contentType = response.headers.get('content-type') || '';
    if (contentType.includes('application/json')) {
      return response.json();
    }
    return null;
  }

  async function authFetch(url, options) {
    const token = getToken();
    const headers = Object.assign({}, (options && options.headers) || {}, {
      Authorization: 'Bearer ' + token,
    });

    if (!(options && options.body instanceof FormData)) {
      headers['Content-Type'] = headers['Content-Type'] || 'application/json';
    }

    const response = await fetch('/api/v1' + url, Object.assign({}, options || {}, { headers }));
    const data = await parseResponse(response);

    if (response.status === 401) {
      clearSession();
      window.location.href = '/frontend/auth/login.html';
      throw new Error('Sesion expirada. Inicia sesion nuevamente.');
    }

    if (!response.ok) {
      const message = (data && data.message) || 'Error al procesar la solicitud.';
      const errors = (data && data.errors) || [];
      const error = new Error(message);
      error.errors = errors;
      throw error;
    }

    return data;
  }

  function formatCurrency(amount) {
    const numeric = Number(amount || 0);
    return new Intl.NumberFormat('es-MX', {
      style: 'currency',
      currency: 'MXN',
      maximumFractionDigits: 0,
    }).format(numeric);
  }

  function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    if (Number.isNaN(date.getTime())) return 'N/A';
    return new Intl.DateTimeFormat('es-MX', {
      day: '2-digit',
      month: 'short',
      year: 'numeric',
    }).format(date);
  }

  function formatDateTime(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    if (Number.isNaN(date.getTime())) return 'N/A';
    return new Intl.DateTimeFormat('es-MX', {
      day: '2-digit',
      month: 'short',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    }).format(date);
  }

  function escapeHtml(value) {
    return String(value ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  async function downloadApiFile(url, filename, options) {
    const token = getToken();
    const response = await fetch('/api/v1' + url, Object.assign({}, options || {}, {
      headers: Object.assign({}, (options && options.headers) || {}, {
        Authorization: 'Bearer ' + token,
      }),
    }));

    if (response.status === 401) {
      clearSession();
      window.location.href = '/frontend/auth/login.html';
      throw new Error('Sesion expirada. Inicia sesion nuevamente.');
    }

    if (!response.ok) {
      throw new Error('No se pudo descargar el archivo.');
    }

    const blob = await response.blob();
    const blobUrl = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = blobUrl;
    a.download = filename;
    a.click();
    URL.revokeObjectURL(blobUrl);
  }

  function attachLogoutHandlers() {
    document.querySelectorAll('[data-action="logout"]').forEach(function (btn) {
      btn.addEventListener('click', logout);
    });
  }

  function setText(selector, value) {
    const el = document.querySelector(selector);
    if (el) el.textContent = value;
  }

  // Provider status helpers -------------------------------------------------
  // Estatus posibles en backend: PENDIENTE | VALIDADO | RECHAZADO | SUSPENDIDO
  function providerStatusClass(estatus) {
    if (estatus === 'VALIDADO') return 'bg-emerald-50 text-emerald-700 border-emerald-200';
    if (estatus === 'PENDIENTE') return 'bg-amber-50 text-amber-700 border-amber-200';
    if (estatus === 'RECHAZADO') return 'bg-red-50 text-red-700 border-red-200';
    if (estatus === 'SUSPENDIDO') return 'bg-red-50 text-red-700 border-red-200';
    return 'bg-slate-100 text-slate-700 border-slate-200';
  }

  function providerStatusIcon(estatus) {
    if (estatus === 'VALIDADO') return 'ph-seal-check';
    if (estatus === 'PENDIENTE') return 'ph-clock';
    if (estatus === 'RECHAZADO') return 'ph-x-circle';
    if (estatus === 'SUSPENDIDO') return 'ph-prohibit';
    return 'ph-question';
  }

  function providerStatusLabel(estatus) {
    if (estatus === 'VALIDADO') return 'Validado';
    if (estatus === 'PENDIENTE') return 'Pendiente de validación';
    if (estatus === 'RECHAZADO') return 'Rechazado';
    if (estatus === 'SUSPENDIDO') return 'Suspendido';
    return estatus || 'Sin estatus';
  }

  function providerStatusDescription(estatus) {
    if (estatus === 'VALIDADO') {
      return 'Tu perfil está validado. Puedes inscribirte en convocatorias, enviar propuestas, cargar documentos y consultar contratos adjudicados.';
    }
    if (estatus === 'PENDIENTE') {
      return 'Tu perfil está en revisión por el administrador. Aún no puedes inscribirte ni enviar propuestas; completa tus datos fiscales y espera la validación.';
    }
    if (estatus === 'RECHAZADO') {
      return 'Tu perfil fue rechazado. Revisa tus datos en Mi perfil, corrige lo necesario y contacta a la dependencia para solicitar una nueva revisión.';
    }
    if (estatus === 'SUSPENDIDO') {
      return 'Tu perfil está suspendido. No puedes inscribirte ni enviar propuestas. Contacta soporte para resolver la suspensión.';
    }
    return 'No se detectó un estatus de proveedor. Completa tu perfil para continuar.';
  }

  function providerCanOperate(estatus) {
    return estatus === 'VALIDADO';
  }

  function extractProviderStatus(me) {
    if (!me) return null;
    if (me.proveedor && me.proveedor.estatus) return me.proveedor.estatus;
    if (me.estatus && me.rol === 'PROVEEDOR') return me.estatus;
    return null;
  }

  function renderProviderBadge(el, estatus) {
    if (!el) return;
    const label = providerStatusLabel(estatus);
    const cssClass = providerStatusClass(estatus);
    const icon = providerStatusIcon(estatus);
    el.className = 'inline-flex w-fit items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold border ' + cssClass;
    el.setAttribute('data-estatus', estatus || '');
    el.innerHTML = '<i class="ph ' + icon + '"></i><span>' + escapeHtml(label) + '</span>';
  }

  function renderProviderBanner(el, estatus) {
    if (!el) return;
    const canOperate = providerCanOperate(estatus);
    const cssClass = canOperate
      ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
      : (estatus === 'PENDIENTE'
        ? 'bg-amber-50 text-amber-800 border-amber-200'
        : 'bg-red-50 text-red-700 border-red-200');
    const icon = providerStatusIcon(estatus);
    el.classList.remove('hidden');
    el.setAttribute('data-estatus', estatus || '');
    el.className = 'border rounded-xl px-4 py-3 text-sm flex items-start gap-3 ' + cssClass;
    el.innerHTML =
      '<i class="ph ' + icon + ' text-xl mt-0.5"></i>'
      + '<div>'
      + '<p class="font-semibold">' + escapeHtml(providerStatusLabel(estatus)) + '</p>'
      + '<p class="mt-1">' + escapeHtml(providerStatusDescription(estatus)) + '</p>'
      + '</div>';
  }

  function applyProviderGuards(estatus, selectors) {
    const canOperate = providerCanOperate(estatus);
    (selectors || []).forEach(function (selector) {
      document.querySelectorAll(selector).forEach(function (el) {
        if (canOperate) {
          el.disabled = false;
          el.removeAttribute('data-provider-locked');
          if (el.dataset.providerOriginalTitle !== undefined) {
            el.title = el.dataset.providerOriginalTitle;
            delete el.dataset.providerOriginalTitle;
          }
        } else {
          if (el.dataset.providerOriginalTitle === undefined) {
            el.dataset.providerOriginalTitle = el.title || '';
          }
          el.disabled = true;
          el.setAttribute('data-provider-locked', estatus || '');
          el.title = 'Acción disponible sólo para proveedores validados. Estatus actual: ' + providerStatusLabel(estatus);
        }
      });
    });
    return canOperate;
  }

  function downloadCsv(filename, headers, rows) {
    const esc = function (v) {
      const s = String(v ?? '');
      if (s.includes('"') || s.includes(',') || s.includes('\n')) {
        return '"' + s.replace(/"/g, '""') + '"';
      }
      return s;
    };
    const content = [headers.map(esc).join(',')]
      .concat(rows.map(function (r) { return r.map(esc).join(','); }))
      .join('\n');
    const blob = new Blob(["\uFEFF" + content], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    a.click();
    URL.revokeObjectURL(url);
  }

  window.SGPLAdmin = {
    getToken,
    getUser,
    setUser,
    clearSession,
    logout,
    protectRoute,
    authFetch,
    formatCurrency,
    formatDate,
    formatDateTime,
    escapeHtml,
    downloadApiFile,
    attachLogoutHandlers,
    setText,
    downloadCsv,
    // provider status helpers
    providerStatusClass,
    providerStatusLabel,
    providerStatusDescription,
    providerStatusIcon,
    providerCanOperate,
    extractProviderStatus,
    renderProviderBadge,
    renderProviderBanner,
    applyProviderGuards,
  };
})();
