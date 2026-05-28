/**
 * notif-stream.js — cliente de notificaciones en tiempo real.
 *
 * Estrategia:
 *   1. Intenta conectar via EventSource (SSE) al endpoint /notificaciones/stream
 *   2. Si SSE no está disponible o falla, cae a polling simple cada 30s
 *      via GET /notificaciones/count
 *
 * Uso:
 *   <script src="/frontend/shared/notif-stream.js"></script>
 *   <script>
 *     NotifStream.start({
 *       token: localStorage.getItem('sgplopypc_token'),
 *       onBadge: function(count) { /* actualizar badge *\/ },
 *       onNotif: function(notif) { /* mostrar notificación *\/ },
 *     });
 *   </script>
 */
(function (global) {
  'use strict';

  var BASE_URL = '/api/v1';
  var POLL_INTERVAL_MS = 30000;  // fallback polling cada 30s
  var SSE_RECONNECT_MS = 3000;   // reconexión SSE tras heartbeat/cierre

  var _token = '';
  var _onBadge = null;
  var _onNotif = null;
  var _since = null;
  var _es = null;
  var _pollTimer = null;
  var _sseTimer = null;
  var _useFallback = false;

  function start(opts) {
    _token = (opts && opts.token) || '';
    _onBadge = (opts && typeof opts.onBadge === 'function') ? opts.onBadge : null;
    _onNotif = (opts && typeof opts.onNotif === 'function') ? opts.onNotif : null;
    _since = (opts && opts.since) ? opts.since : Math.floor(Date.now() / 1000 - 60);

    if (typeof EventSource !== 'undefined' && _token) {
      connectSSE();
    } else {
      startFallback();
    }
  }

  function stop() {
    if (_es) {
      _es.close();
      _es = null;
    }
    if (_pollTimer) {
      clearInterval(_pollTimer);
      _pollTimer = null;
    }
    if (_sseTimer) {
      clearTimeout(_sseTimer);
      _sseTimer = null;
    }
  }

  function connectSSE() {
    if (_es) {
      _es.close();
      _es = null;
    }
    var url = BASE_URL + '/notificaciones/stream?token=' + encodeURIComponent(_token)
      + '&since=' + encodeURIComponent(String(_since));

    try {
      _es = new EventSource(url);
    } catch (e) {
      startFallback();
      return;
    }

    _es.addEventListener('notificacion', function (e) {
      try {
        var data = JSON.parse(e.data);
        if (_onNotif) _onNotif(data);
      } catch (_) {}
    });

    _es.addEventListener('badge', function (e) {
      try {
        var data = JSON.parse(e.data);
        if (_onBadge) _onBadge(data.count);
      } catch (_) {}
    });

    _es.addEventListener('sync', function (e) {
      try {
        var data = JSON.parse(e.data);
        if (data.since) _since = data.since;
      } catch (_) {}
    });

    _es.addEventListener('heartbeat', function () {
      // El servidor cerró la conexión sin datos; reconectar
      _es.close();
      _es = null;
      _sseTimer = setTimeout(connectSSE, SSE_RECONNECT_MS);
    });

    _es.onerror = function () {
      if (_es) {
        _es.close();
        _es = null;
      }
      // Tras 3 errores consecutivos, caer a fallback
      _useFallback = (_useFallback || false);
      if (!_useFallback) {
        _useFallback = true;
        startFallback();
      }
    };

    _es.onopen = function () {
      _useFallback = false;
    };
  }

  function startFallback() {
    if (_pollTimer) return; // ya corriendo
    pollCount();
    _pollTimer = setInterval(pollCount, POLL_INTERVAL_MS);
  }

  function pollCount() {
    if (!_token) return;
    fetch(BASE_URL + '/notificaciones/count', {
      headers: { Authorization: 'Bearer ' + _token },
    })
      .then(function (r) { return r.ok ? r.json() : null; })
      .then(function (body) {
        if (body && body.data && _onBadge) {
          _onBadge(body.data.count);
        }
      })
      .catch(function () {});
  }

  global.NotifStream = { start: start, stop: stop };
})(window);
