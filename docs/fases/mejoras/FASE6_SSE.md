# Fase 6 — Notificaciones en tiempo real (SSE)

**Estado:** ✅ Completada — 2026-05-28
**Commit:** `056e49e0d69d1e9499f175f825e903bcae43c665`
**Deployment Railway:** `d04b93c7-392d-4fcd-9096-bb9ea6460585`
**URL producción:** https://sgplopypc.up.railway.app

## 1. Objetivo

Habilitar notificaciones en tiempo real para los usuarios del sistema mediante Server-Sent Events (SSE), con fallback automático a polling simple cuando SSE no está disponible. Incluye badge visual en el dashboard admin y toasts de notificación.

## 2. Cambios entregados

### 2.1 Backend

| Archivo | Cambio |
|---|---|
| `app/repositories/NotificacionRepository.php` | + `findNoLeidasCount(idUsuario)` — COUNT WHERE leida=0. + `findRecientes(idUsuario, since, limit)` — SELECT desde timestamp dado. |
| `app/controllers/NotificacionStreamController.php` | NEW. `stream()` SSE con long-polling interno. `count()` para fallback. |

#### Estrategia SSE (long-polling interno)

```
Cliente → GET /notificaciones/stream?token=...&since=...
Servidor:
  while (tiempo < 25s):
    notifs = findRecientes(usuario, since)
    if notifs:
      emit event:notificacion (por cada una)
      emit event:badge (count actualizado)
      emit event:sync (nuevo since)
      exit
    sleep(2s)
  emit event:heartbeat
  exit
```

El cliente reconecta automáticamente tras heartbeat o cierre. Actualiza `since` con el timestamp del último evento recibido para no recibir duplicados.

#### Eventos SSE emitidos

| Evento | Payload | Descripción |
|---|---|---|
| `notificacion` | `{id_notificacion, tipo, titulo, mensaje, leida, fecha_envio, id_licitacion}` | Nueva notificación para el usuario. |
| `badge` | `{count}` | Conteo actualizado de no leídas. |
| `sync` | `{since}` | Nuevo timestamp para la próxima conexión. |
| `heartbeat` | `{ts}` | Sin datos nuevos; el cliente reconecta. |

#### Cabeceras SSE

```
Content-Type: text/event-stream; charset=utf-8
Cache-Control: no-cache, no-store
X-Accel-Buffering: no
Connection: keep-alive
Access-Control-Allow-Origin: *
```

### 2.2 Endpoints nuevos

| Método | Ruta | Auth | Descripción |
|---|---|---|---|
| `GET` | `/api/v1/notificaciones/stream` | Bearer o `?token=` | SSE long-polling. `set_time_limit(30)`. |
| `GET` | `/api/v1/notificaciones/count` | Bearer | Conteo de no leídas (fallback polling). |

### 2.3 Frontend

**`frontend/shared/notif-stream.js`** — `NotifStream.start(opts)` / `NotifStream.stop()`:

1. Si `EventSource` disponible y hay token → conecta SSE.
2. Si SSE falla → fallback a `setInterval(pollCount, 30000)`.
3. Maneja eventos `notificacion`, `badge`, `sync`, `heartbeat`.
4. Reconecta en 3s tras heartbeat.

**`frontend/admin/dashboard.html`** ampliado:

- Badge `#notif-badge` (span rojo, oculto si count=0, `99+` si >99).
- Botón `#notif-badge-btn` con ícono `ph-bell`.
- Toast de 5s al recibir evento `notificacion`.
- `NotifStream.start()` inicializado con token del localStorage.

### 2.4 Tests E2E

`e2e/tests/notif-realtime.spec.ts` — **7 casos**:

1. ✅ `GET /notificaciones/count` responde 200 con `count` numérico
2. ✅ `GET /notificaciones/count` sin auth devuelve 401
3. ✅ Crear notificación y verificar que count aumenta
4. ✅ `GET /notificaciones/stream` con token query param devuelve SSE headers (tolerante a timeout)
5. ✅ `GET /notificaciones/stream` sin auth devuelve 401
6. ✅ `notif-stream.js` disponible como estático con contenido correcto
7. ✅ Badge visible en dashboard admin con `NotifStream` inicializado

## 3. Verificación en producción

```bash
TOKEN=$(curl -s -X POST https://sgplopypc.up.railway.app/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@sgplopypc.gob.mx","password":"admin123"}' | jq -r .data.token)

# Count de no leídas
curl -s "https://sgplopypc.up.railway.app/api/v1/notificaciones/count" \
  -H "Authorization: Bearer $TOKEN" | jq .data.count

# Crear notificación de prueba
curl -s -X POST "https://sgplopypc.up.railway.app/api/v1/notificaciones" \
  -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"id_usuario_destino":2,"tipo_notificacion":"GENERAL","titulo":"Test","mensaje":"Prueba SSE"}'

# Verificar que count aumentó
curl -s "https://sgplopypc.up.railway.app/api/v1/notificaciones/count" \
  -H "Authorization: Bearer $TOKEN" | jq .data.count
```

## 4. Resultados E2E

```
Fase 6 (notif-realtime):            7 passed
Smoke regresivo:
  auth-mfa:                          4 passed
  admin-auditoria:                   5 passed
  admin-auth-and-navigation:         2 passed
  datos-abiertos:                   10 passed
                                  ─────────
TOTAL:                            28 passed / 0 failed
```

Sin regresiones de fases anteriores.

## 5. Decisiones técnicas

### Long-polling en lugar de streaming puro
Apache/PHP con `mod_php` no soporta bien el streaming continuo (el output buffer puede acumular datos). La estrategia de long-polling (esperar hasta 25s, emitir si hay datos, cerrar) es más robusta y compatible con cualquier configuración de servidor. El cliente reconecta automáticamente, logrando el mismo efecto de "tiempo real" con latencia máxima de 2s.

### Token como query param para EventSource
La API `EventSource` del navegador no permite enviar headers personalizados. Se acepta el token como `?token=...` en la URL. El servidor lo inyecta en `$_SERVER['HTTP_AUTHORIZATION']` antes de llamar a `AuthMiddleware::handle()`. Esto es un patrón estándar para SSE autenticado.

### Fallback a polling simple
Si `EventSource` no está disponible (navegadores muy antiguos) o si SSE falla repetidamente, `notif-stream.js` cae automáticamente a `setInterval(pollCount, 30000)`. El usuario sigue recibiendo actualizaciones del badge, sólo con mayor latencia.

### `set_time_limit(30)` en la ruta SSE
PHP por defecto tiene un límite de ejecución de 30s. Se establece explícitamente para que el long-polling de 25s no sea interrumpido por el límite por defecto de Railway (que puede ser menor).

## 6. Próxima fase

Avanzar a **Fase 7 — Firma electrónica avanzada (e.firma/FIEL)** según `docs/fases/mejoras/FASES_MEJORAS.md`.

---

## Anexo — Plantilla de cierre

```text
Commit:        056e49e0d69d1e9499f175f825e903bcae43c665
Deployment:    d04b93c7-392d-4fcd-9096-bb9ea6460585
URL:           https://sgplopypc.up.railway.app
Healthcheck:   /healthz=200  /api/v1/health app=ok db=ok
E2E fase 6:    7 passed / 0 failed
E2E regresión: 21 passed / 0 failed (Fases 1-5)
Total:         28 passed / 0 failed
Endpoints:     GET /notificaciones/stream (SSE, token query param)
               GET /notificaciones/count (fallback polling)
Eventos SSE:   notificacion, badge, sync, heartbeat
Estrategia:    Long-polling 25s, poll BD cada 2s, reconexión 3s
Fallback:      polling simple cada 30s si SSE no disponible
```
