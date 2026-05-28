# Fase 1 — Auditoría y bitácora de acciones

**Estado:** ✅ Completada — 2026-05-27
**Commit:** `d568d58240c464e37453ce07929ad1bca7acf567`
**Deployment Railway:** `a4f7a00b-0efe-48ff-85b6-b3bcedaaccb0`
**URL producción:** https://sgplopypc.up.railway.app

## 1. Objetivo

Habilitar un registro inmutable y consultable de todas las acciones críticas del sistema (autenticación, cambios de estado, adjudicaciones, firmas, exportaciones), como base para transparencia gubernamental, auditorías y análisis forense.

## 2. Cambios entregados

### 2.1 Base de datos

`database/migrations/012_auditoria_extendida.sql` (idempotente):

| Cambio | Detalle |
|---|---|
| Enum `accion` ampliado | + `FIRMAR`, `LOGIN_OK`, `LOGIN_FALLIDO`, `LOGOUT`, `PASSWORD_CHANGE`, `PASSWORD_RESET`, `EXPORT`, `CONSULTA` |
| Columna nueva | `user_agent VARCHAR(500) NULL` |
| Columna nueva | `request_id VARCHAR(40) NULL` |
| `id_usuario` | Ahora `NULL` permitido (login fallido) |
| `id_registro_afectado` | `DEFAULT 0` (eventos sin registro asociado) |
| Índice nuevo | `idx_historial_usuario_fecha (id_usuario, fecha_accion)` |
| Índice nuevo | `idx_historial_request_id (request_id)` |
| Índice nuevo | `idx_historial_fecha (fecha_accion)` |

### 2.2 Backend

| Archivo | Cambio |
|---|---|
| `app/middlewares/RequestIdMiddleware.php` | NEW. Genera/reusa `X-Request-ID` por request (UUID v4 nativo). |
| `app/helpers/audit.php` | Captura `ip` (X-Forwarded-For limpiado), `user_agent`, `request_id`. Acepta `id_usuario` nullable. |
| `app/services/AuthService.php` | Distingue motivo de fallo (`USER_NOT_FOUND`, `USER_INACTIVE`, `BAD_PASSWORD`) sin exponerlo al cliente. |
| `app/controllers/AuthController.php` | Audita `LOGIN_OK` / `LOGIN_FALLIDO` con metadatos. Endpoint nuevo `logout` que registra `LOGOUT`. |
| `app/services/UserService.php` | Cambio de password usa acción `PASSWORD_CHANGE`. |
| `app/services/PasswordResetService.php` | Reset usa acción `PASSWORD_RESET`. |
| `app/services/ReporteService.php` | Exportaciones CSV usan acción `EXPORT`. |
| `app/controllers/ReporteController.php` | Consulta de historial usa acción `CONSULTA`. |
| `app/repositories/AuditoriaRepository.php` | NEW. `findPaginated`, `findForExport` (límite 50k), `distinctValues`. |
| `app/controllers/AuditoriaController.php` | NEW. Endpoints `list` y `exportCsv` (auto-audita). Filtros sanitizados con allow-list y regex. |
| `public/index.php` | Inicializa `RequestIdMiddleware::handle()` temprano. Registra rutas nuevas. |

### 2.3 Endpoints nuevos

| Método | Ruta | Rol | Descripción |
|---|---|---|---|
| `POST` | `/api/v1/auth/logout` | Autenticado | Registra `LOGOUT` en bitácora. |
| `GET` | `/api/v1/admin/auditoria` | ADMINISTRADOR | Listado paginado con filtros: `accion`, `tabla`, `id_usuario`, `request_id`, `from`, `to`, `page`, `limit` (max 200). |
| `GET` | `/api/v1/admin/auditoria/export.csv` | ADMINISTRADOR | Exporta resultado filtrado a CSV (BOM UTF-8). Auto-audita la propia exportación. |

Header de respuesta nuevo: `X-Request-ID: <uuid>` en todas las respuestas.

### 2.4 Frontend

- `frontend/admin/auditoria/index.html` — vista nueva con:
  - Filtros: acción, tabla, ID usuario, rango de fechas
  - KPIs: total, página actual, mostrando, filtros activos
  - Tabla con badges coloreados por tipo de acción
  - Modal de detalle con `valores_anteriores`/`valores_nuevos` formateados como JSON
  - Paginación prev/next
  - Botón "Exportar CSV"
- Enlace al módulo agregado en el sidebar de **10 vistas admin** (dashboard, convocatorias×2, proveedores, propuestas, evaluación, adjudicaciones, reportes, configuración, auditoría).

### 2.5 Tests E2E

`e2e/tests/admin-auditoria.spec.ts` — 5 casos:

1. ✅ `LOGIN_OK` queda registrado y consultable vía API con `request_id` válido
2. ✅ `LOGIN_FALLIDO` se registra con razón interna (`BAD_PASSWORD`/etc.) sin exponerla al cliente — el cliente sólo ve "Credenciales inválidas"
3. ✅ `/admin/auditoria` devuelve 403 para no-administradores (proveedor)
4. ✅ UI admin permite filtrar y limpiar la bitácora
5. ✅ `LOGOUT` queda registrado correctamente

## 3. Verificación

### Smoke producción

```bash
curl -fsSL https://sgplopypc.up.railway.app/healthz
# → ok (200)

curl -fsSL https://sgplopypc.up.railway.app/api/v1/health
# → {"app":{"status":"ok"},"db":{"status":"ok"}}
```

### Verificación manual del endpoint

```bash
TOKEN=$(curl -s -X POST https://sgplopypc.up.railway.app/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@sgplopypc.gob.mx","password":"admin123"}' \
  | jq -r .data.token)

curl -s "https://sgplopypc.up.railway.app/api/v1/admin/auditoria?accion=LOGIN_OK&limit=3" \
  -H "Authorization: Bearer $TOKEN" | jq .
```

Respuesta confirmada con `ip_origen`, `user_agent` y `request_id` correctamente capturados.

### Resultados E2E

```
admin-auditoria.spec.ts:           5 passed (13.9s)
admin-auth-and-navigation:         2 passed
proveedor-login-redirect:          1 passed
public-basic-flows:                2 passed
─────────────────────────────────────────────
Total smoke + fase1:              10 passed / 0 failed
```

## 4. Cobertura de eventos auditados

| Evento | Acción registrada | Origen |
|---|---|---|
| Login exitoso | `LOGIN_OK` | `AuthController::login` |
| Login fallido | `LOGIN_FALLIDO` | `AuthController::login` |
| Logout | `LOGOUT` | `AuthController::logout` |
| Cambio de contraseña | `PASSWORD_CHANGE` | `UserService::changePassword` |
| Reset de contraseña | `PASSWORD_RESET` | `PasswordResetService::reset` |
| Crear/editar/eliminar licitación | `CREAR`/`ACTUALIZAR` | `LicitacionService` (preexistente) |
| Validar/rechazar proveedor | `ACTUALIZAR` | `ProveedorService::cambiarEstatus` (preexistente) |
| Adjudicación | `ACTUALIZAR` | `LicitacionService::adjudicar` (preexistente) |
| Firma de contrato | `FIRMAR` | `ContratoService::firmar` (preexistente, ahora en enum) |
| Subida de documento | `CREAR` | `DocumentoService::create` (preexistente) |
| Exportación CSV | `EXPORT` | `ReporteService` / `AuditoriaController` |
| Consulta de historial | `CONSULTA` | `ReporteController::historialLicitacion` |

Todos los eventos quedan correlacionables vía `request_id` (header `X-Request-ID` ↔ columna `historial_cambio.request_id`).

## 5. Notas operativas

- **URL canónica del servicio:** `https://sgplopypc.up.railway.app` (la URL `sgplopypc-production.up.railway.app` dejó de responder durante esta fase y fue reemplazada por la nueva).
- **Migración aplicada:** sí, con éxito en BD productiva Railway via host público `zephyr.proxy.rlwy.net:51203`.
- **Compatibilidad regresiva:** los `auditLog(...)` preexistentes siguen funcionando; sólo se ampliaron tipos posibles.
- **Performance:** los nuevos índices están en columnas usadas por filtros típicos. Se evita JOIN pesado en el path de inserción (audit log es no-bloqueante por `try/catch`).

## 6. Próxima fase

Avanzar a **Fase 2 — Reportes con plantillas (PDF/DOCX + editor WYSIWYG)** según `docs/fases/mejoras/FASES_MEJORAS.md`.

---

## Anexo — Plantilla de cierre

```text
Commit:        d568d58240c464e37453ce07929ad1bca7acf567
Deployment:    a4f7a00b-0efe-48ff-85b6-b3bcedaaccb0
URL:           https://sgplopypc.up.railway.app
Healthcheck:   /healthz=200  /api/v1/health app=ok db=ok
E2E auditoría: 5 passed / 0 failed
E2E smoke:     5 passed / 0 failed
Tablas:        historial_cambio (extendida)
Endpoints:     POST /auth/logout
               GET  /admin/auditoria
               GET  /admin/auditoria/export.csv
```
