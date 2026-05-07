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
  };
})();
