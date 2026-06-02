(function () {
  'use strict';

  var TOKEN_KEY = 'sgplopypc_token';

  function getToken() {
    return localStorage.getItem(TOKEN_KEY);
  }

  function showBadgeCount(count) {
    var badge = document.querySelector('[data-notif-badge]');
    if (!badge) return;
    var span = badge.querySelector('.notif-badge-count');
    if (!span) return;
    var n = parseInt(count, 10) || 0;
    if (n > 0) {
      span.textContent = String(n);
      span.classList.remove('hidden');
    } else {
      span.textContent = '0';
      span.classList.add('hidden');
    }
  }

  function showToast(notif) {
    var container = document.getElementById('notif-toast-container');
    if (!container) return;

    var toast = document.createElement('div');
    toast.className = 'flex items-start gap-3 bg-white border border-slate-200 shadow-lg rounded-xl px-4 py-3 max-w-sm animate-slide-in cursor-pointer';

    var iconClass = 'ph-bell';
    if (notif.tipo === 'ADJUDICACION') iconClass = 'ph-trophy';
    else if (notif.tipo === 'RESULTADO_EVALUACION') iconClass = 'ph-clipboard-text';
    else if (notif.tipo === 'ACLARACION') iconClass = 'ph-chat-circle';
    else if (notif.tipo === 'CAMBIO_ESTADO') iconClass = 'ph-arrows-clockwise';
    else if (notif.tipo === 'CONVOCATORIA_PUBLICADA') iconClass = 'ph-megaphone';

    var titulo = notif.titulo || 'Nueva notificación';
    var tipo = notif.tipo || 'GENERAL';

    toast.innerHTML =
      '<i class="ph ' + iconClass + ' text-xl text-primary-600 mt-0.5 shrink-0"></i>'
      + '<div class="min-w-0 flex-1">'
      + '<p class="text-sm font-semibold text-slate-900 truncate">' + titulo + '</p>'
      + '<p class="text-xs text-slate-500 mt-0.5">' + tipo + '</p>'
      + '</div>'
      + '<button class="shrink-0 text-slate-400 hover:text-slate-600" aria-label="Cerrar"><i class="ph ph-x"></i></button>';

    toast.addEventListener('click', function (e) {
      if (e.target.closest('button[aria-label="Cerrar"]')) {
        toast.remove();
        return;
      }
      toast.remove();
      var href = null;
      if (notif.id_licitacion) {
        href = '/frontend/proveedor/licitacion.html?id=' + notif.id_licitacion;
      }
      if (href) {
        window.location.href = href;
      } else {
        window.location.href = '/frontend/proveedor/notificaciones.html';
      }
    });

    container.appendChild(toast);

    setTimeout(function () {
      if (toast.parentNode) {
        toast.style.opacity = '0';
        toast.style.transition = 'opacity 0.3s';
        setTimeout(function () { toast.remove(); }, 300);
      }
    }, 5000);
  }

  function injectStyles() {
    if (document.getElementById('notif-badge-toast-styles')) return;
    var style = document.createElement('style');
    style.id = 'notif-badge-toast-styles';
    style.textContent = '@keyframes notif-slide-in{from{transform:translateX(100%);opacity:0}to{transform:translateX(0);opacity:1}}.animate-slide-in{animation:notif-slide-in .3s ease-out}';
    document.head.appendChild(style);
  }

  function init() {
    var token = getToken();
    if (!token) return;
    injectStyles();

    var badge = document.querySelector('[data-notif-badge]');
    if (badge && !badge.dataset.notifBound) {
      badge.dataset.notifBound = '1';
      badge.addEventListener('click', function (e) {
        e.preventDefault();
        window.location.href = '/frontend/proveedor/notificaciones.html';
      });
    }

    if (!document.getElementById('notif-toast-container')) {
      var container = document.createElement('div');
      container.id = 'notif-toast-container';
      container.className = 'fixed bottom-4 right-4 z-50 flex flex-col gap-2';
      document.body.appendChild(container);
    }

    if (typeof window.NotifStream !== 'undefined') {
      window.NotifStream.start({
        token: token,
        onBadge: showBadgeCount,
        onNotif: showToast,
      });
    }
  }

  window.SGPLNotifBadge = { init: init, showBadgeCount: showBadgeCount };
})();
