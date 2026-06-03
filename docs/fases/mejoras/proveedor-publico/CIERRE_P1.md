# Cierre de Fases P1, P2 y P3 — Dashboard Proveedor, Perfil/Notificaciones Público y SSE Proveedor

---

## Fase P1 — Dashboard con KPIs para proveedor

- **Commit:** 7bc8ce02815974dee1b9c50f48d9039f67cd0d2c
- **Deployment Railway:** 3402b27d-290d-475e-a47a-f0fb8cab922f
- **URL:** https://sgplopypc.up.railway.app
- **Healthcheck post-deploy:** `/healthz` → 200, `/api/v1/health` → app=ok, db=ok
- **E2E:** 6 passed / 0 skipped / 0 failed

### Resumen

Se implementó un dashboard completo para el rol proveedor con métricas visuales de actividad:

**Endpoints nuevos:**
- `GET /api/v1/proveedores/{id}/metricas` — KPIs con cache 5 min (participaciones, tasa ganancia, montos, contratos vigentes, distribución por tipo, participaciones por mes)
- `GET /api/v1/proveedores/{id}/metricas/tendencia` — Serie trimestral de participaciones/montos (últimos 2 años)

**Frontend:**
- `centro.html` ampliado con 4 stat cards, Chart.js (barras + dona), tabla últimas participaciones
- Tarjetas de navegación rápida mantenidas debajo del dashboard

**Backend:**
- `ProveedorMetricasRepository.php` — Queries SQL con CTE para compatibilidad con `ONLY_FULL_GROUP_BY`
- `ProveedorMetricasService.php` — Lógica con cache SimpleCache (TTL 300s)
- `ProveedorMetricasController.php` — Controlador con auth y validación de permisos

**Archivos creados:** 4 (3 backend + 1 test E2E)  
**Archivos modificados:** 3 (routing, frontend, docs API)  
**Tablas/columnas nuevas:** Ninguna (queries sobre tablas existentes)

---

## Fase P2 — Perfil + contraseña + notificaciones para público

- **Commit:** 3f89acfab7020444f801c6bd5a9c401fe10c804a
- **Deployment Railway:** a9e1bda3-f0e0-4b34-9afd-2a0ed38dd728
- **URL:** https://sgplopypc.up.railway.app
- **Healthcheck post-deploy:** `/healthz` → 200, `/api/v1/health` → app=ok, db=ok
- **E2E:** 8 passed / 1 skipped / 0 failed

### Resumen

Se dotó al usuario público de experiencia autogestionada: editar perfil, cambiar contraseña y gestionar notificaciones completas.

**Páginas nuevas:**
- `frontend/publico/perfil.html` — Formulario de edición de cuenta (nombre, email) + cambio de contraseña con indicador de fortaleza + resumen de cuenta (ID, fecha registro, último acceso)
- `frontend/publico/notificaciones.html` — Lista completa de notificaciones con filtros (todas, no leídas, leídas), búsqueda por texto, estadísticas (total, no leídas, con acción), marcado individual y批量 como leídas

**Frontend modificado:**
- `frontend/publico/centro.html` — Agregada tarjeta "Mi perfil" en navegación + enlace "Ver todas" en preview de notificaciones

**Endpoints utilizados:**
- `PUT /api/v1/me/profile` — Actualizar nombre y email
- `POST /api/v1/me/password` — Cambiar contraseña (valida fortaleza: mayúscula, número, símbolo, 8+ chars)
- `GET /api/v1/notificaciones/mias` — Listar notificaciones
- `PATCH /api/v1/notificaciones/{id}/leida` — Marcar como leída

**Tests E2E:**
- `publico-perfil-notificaciones.spec.ts` — 9 tests cubriendo:
  - Navegación a perfil y visualización de datos
  - Edición de nombre y guardado
  - Visualización de resumen de cuenta
  - Validación de formulario de contraseña
  - Navegación a notificaciones desde centro
  - Estadísticas de notificaciones
  - Filtros de notificaciones
  - Marcado como leída (skipped si no hay notificaciones no leídas)
  - Verificación de enlace "Ver todas" en centro

**Archivos creados:** 3 (2 páginas HTML + 1 test E2E)  
**Archivos modificados:** 1 (centro.html)  
**Tablas/columnas nuevas:** Ninguna (usa endpoints existentes)

---

## Fase P3 — Integrar notificaciones SSE en proveedor

- **Commit:** 2585ba7ae5081c1239885ad8bbf26f23502fbdb8
- **Deployment Railway:** 54d1211d-e064-4540-9a37-105e9d1bce82
- **URL:** https://sgplopypc.up.railway.app
- **Healthcheck post-deploy:** `/healthz` → 200, `/api/v1/health` → app=ok, db=ok
- **E2E:** 7 passed / 0 skipped / 0 failed

### Resumen

Se activaron las notificaciones en tiempo real (SSE) en todas las páginas del proveedor usando el módulo SSE existente (`notif-stream.js`).

**Nuevo archivo compartido:**
- `frontend/shared/notif-badge-toast.js` — Módulo de integración que:
  - Conecta `NotifStream` con el badge de notificaciones en el navbar
  - Muestra toast flotante (esquina inferior derecha) al recibir notificación nueva
  - Toast con auto-desaparece en 5s, click navega al recurso correspondiente
  - Inyecta CSS de animación slide-in dinámicamente

**Integración en 10 páginas de proveedor:**
- `centro.html`, `convocatorias.html`, `licitacion.html`, `participaciones.html`, `propuestas.html`, `documentos.html`, `contratos.html`, `contrato.html`, `notificaciones.html`, `perfil.html`
- Cada página incluye: badge campana en navbar (link a notificaciones.html), scripts SSE, inicialización `SGPLNotifBadge.init()`

**Fallback polling:**
- Si SSE falla, `notif-stream.js` cae automáticamente a polling cada 30s vía `GET /notificaciones/count`
- Verificado que el fallback funciona correctamente

**Tests E2E:**
- `proveedor-notif-realtime.spec.ts` — 7 tests cubriendo:
  - Disponibilidad de scripts notif-stream.js y notif-badge-toast.js
  - Badge visible en centro.html
  - Badge visible en todas las páginas de proveedor
  - Badge muestra conteo correcto de no leídas
  - NotifStream se inicializa correctamente
  - Creación de notificación vía API (admin) y verificación de conteo
  - Toast container se crea dinámicamente

**Archivos creados:** 2 (1 shared JS + 1 test E2E)  
**Archivos modificados:** 10 (todas las páginas de proveedor)  
**Tablas/columnas nuevas:** Ninguna (usa endpoints SSE existentes)

---

## Fase P4 — Paginación y exportación CSV para proveedor

- **Commit:** 3669120217795decee8eb6c189b43fc5a440d6d8
- **Deployment Railway:** d13e198c-2933-446f-a890-1bf9c0a8f0a4
- **URL:** https://sgplopypc.up.railway.app
- **Healthcheck post-deploy:** `/healthz` → 200, `/api/v1/health` → app=ok, db=ok
- **E2E:** 10 passed / 0 skipped / 0 failed

### Resumen

Se implementó paginación estándar y exportación CSV para los listados del rol proveedor, resolviendo la limitación de cargar todos los registros a la vez.

**Paginación backend:**
- Parámetros estándar: `page` (default 1), `per_page` (default 20, max 100)
- Respuesta incluye: `{ items, total, page, per_page, total_pages }`
- Endpoints modificados:
  - `GET /api/v1/participaciones/mias`
  - `GET /api/v1/propuestas/mias`
  - `GET /api/v1/contratos/mios`
  - `GET /api/v1/licitaciones` (admin + proveedor)

**Componente de paginación reutilizable:**
- `frontend/shared/pagination.js` — función `SGPLPagination.render(container, { page, total_pages, onPageChange })`
- UI: botones « Anterior, números de página, Siguiente »
- Integrado en `participaciones.html`, `propuestas.html`, `contratos.html`

**Exportación CSV:**
- `GET /api/v1/participaciones/mias/export.csv` — exporta todas las participaciones del proveedor
- `GET /api/v1/propuestas/mias/export.csv` — exporta todas las propuestas del proveedor
- `GET /api/v1/contratos/mios/export.csv` — exporta todos los contratos del proveedor
- Headers: `Content-Type: text/csv; charset=utf-8`, `Content-Disposition: attachment`
- Incluye BOM UTF-8 para compatibilidad con Excel

**Frontend:**
- `participaciones.html` — paginación + botón "Exportar CSV" + filtros refactorizados
- `propuestas.html` — paginación + botón "Exportar CSV"
- `contratos.html` — paginación + botón "Exportar CSV"

**Tests E2E:**
- `proveedor-paginacion-export.spec.ts` — 10 tests cubriendo:
  - Metadata de paginación en endpoints de participaciones, contratos y propuestas
  - Renderizado de controles de paginación en participaciones.html y contratos.html
  - Visibilidad de botones "Exportar CSV" en participaciones y contratos
  - Descarga y validación de archivos CSV para participaciones, contratos y propuestas

**Archivos creados:** 2 (`frontend/shared/pagination.js`, `e2e/tests/proveedor-paginacion-export.spec.ts`)
**Archivos modificados:** 12 (4 repositories, 2 services, 3 controllers, 3 frontends, 1 routing)
**Tablas/columnas nuevas:** Ninguna

---

## Fase P5 — Recuperación de contraseña

- **Commit:** 1c3fc0169c8f9faf747c76f25ff95bab4286b067
- **URL:** https://sgplopypc.up.railway.app
- **Healthcheck post-deploy:** `/healthz` → 200, `/api/v1/health` → app=ok, db=ok
- **E2E:** 10 passed / 0 skipped / 0 failed

### Resumen

Se implementaron páginas dedicadas para recuperación de contraseña y se verificó el flujo completo de forgot/reset con validación de fortaleza.

**Páginas nuevas:**
- `frontend/auth/password-forgot.html` — Solicitud de reset con email. Mensaje genérico que no revela si el correo existe. Mismo estilo visual del login.
- `frontend/auth/password-reset.html` — Reset con token (desde query param `?token=`), nueva contraseña y confirmación. Validación de fortaleza en frontend (mínimo 8, mayúscula, número, símbolo). Redirección a login tras éxito.

**Frontend modificado:**
- `frontend/auth/login.html` — Enlace "¿Olvidaste tu contraseña?" redirige a `password-forgot.html`
- `frontend/proveedor/perfil.html` — Ya contiene formulario de cambio de contraseña (vía `/me/password`)
- `frontend/publico/perfil.html` — Ya contiene formulario de cambio de contraseña (creado en Fase P2)

**Endpoints utilizados:**
- `POST /api/v1/auth/password/forgot` — Genera token y envía email (ya existía)
- `POST /api/v1/auth/password/reset` — Valida token y actualiza contraseña con fortaleza requerida (ya existía)

**Tests E2E:**
- `auth-password-reset.spec.ts` — 10 tests cubriendo:
  - Carga de password-forgot.html y password-reset.html
  - Recepción de token desde query param en password-reset.html
  - Enlace desde login a recuperación de contraseña
  - Endpoint forgot no revela existencia de email (mensaje genérico)
  - Endpoint forgot acepta email válido
  - Endpoint reset rechaza token inválido
  - Endpoint reset rechaza contraseña débil
  - Perfil proveedor tiene formulario de cambio de contraseña
  - Validación de fortaleza en frontend de password-reset

**Archivos creados:** 3 (2 páginas HTML + 1 test E2E)
**Archivos modificados:** 1 (login.html)
**Tablas/columnas nuevas:** Ninguna (usa endpoints y tabla `password_reset_token` existentes)

**Nota sobre email:** El backend envía email con link usando `Mailer`. Si el sistema de email SMTP no está configurado en Railway, el token se muestra en el log/debug pero la UI no lo expone. Para desarrollo, el token puede obtenerse del cuerpo del email simulado o directamente desde la base de datos.

---

## Fase P6 — Gestión MFA para proveedor

- **Commit:** 25957ff9ae3621f76f1cef5cc3f806169778644c
- **URL:** https://sgplopypc.up.railway.app
- **Healthcheck post-deploy:** `/healthz` → 200, `/api/v1/health` → app=ok, db=ok
- **E2E:** 6 passed / 0 skipped / 0 failed

### Resumen

Se implementó la gestión completa de autenticación multifactor (MFA/2FA) para el rol proveedor, integrando el backend existente con la interfaz de perfil y el flujo de login.

**Backend modificado:**
- `app/repositories/UserRepository.php` — agregado `mfa_enabled` al SELECT de `findById()` para que el perfil pueda mostrar el estado actual de MFA.

**Frontend modificado:**
- `frontend/proveedor/perfil.html` — nueva sección "Seguridad adicional (2FA)" con:
  - Estado actual (activado/desactivado) con badge visual.
  - Botón "Activar 2FA" que navega a `frontend/auth/mfa-enroll.html`.
  - Formulario de desactivación que requiere contraseña actual + código TOTP de 6 dígitos.
  - Integración con endpoint `POST /api/v1/me/mfa/disable`.
- `frontend/auth/login.html` — modificado para detectar `requires_mfa: true` en la respuesta de login y redirigir a `mfa-challenge.html#mfa_token=<token>`.

**Páginas reutilizadas (ya existentes):**
- `frontend/auth/mfa-enroll.html` — enrollment con QR (`qrious`), confirmación de código y códigos de respaldo.
- `frontend/auth/mfa-challenge.html` — challenge de código TOTP + soporte para códigos de respaldo.

**Tests E2E:**
- `e2e/tests/proveedor-mfa.spec.ts` — 6 tests cubriendo:
  - Navegación a perfil y visibilidad de sección MFA.
  - Enlace "Activar 2FA" redirige a mfa-enroll.html.
  - Verificación de QR visible en mfa-enroll.html.
  - Formulario de desactivación visible cuando MFA está activo.
  - Login con MFA redirige a challenge.
  - Navegación directa a challenge.html con hash de token.

**Archivos creados:** 1 (test E2E)  
**Archivos modificados:** 3 (`UserRepository.php`, `perfil.html`, `login.html`)  
**Tablas/columnas nuevas:** Ninguna (usa endpoints y columna `mfa_secret` existentes)

---

## Fase P7 — Historial de reputaci&oacute;n

- **Commit:** 8ef4dd90643ee03a383f7eb0b7d999610e9c4559
- **URL:** https://sgplopypc.up.railway.app
- **Healthcheck post-deploy:** `/healthz` &rarr; 200, `/api/v1/health` &rarr; app=ok, db=ok
- **E2E:** 5 passed / 0 skipped / 0 failed

### Resumen

Se implement&oacute; una p&aacute;gina dedicada al historial de reputaci&oacute;n del proveedor, mostrando el desglose por criterio en una gr&aacute;fica radar y una tabla detallada de evaluaciones post-contrato.

**Backend modificado:**
- `app/repositories/ReputacionRepository.php` — nuevo m&eacute;todo `findDesgloseByProveedor()` que calcula el promedio de cada criterio (puntualidad, calidad, comunicaci&oacute;n, cumplimiento_alcance) sobre todas las evaluaciones del proveedor.
- `app/services/ReputacionService.php` — `getReputacion()` ahora devuelve:
  - `score` y `evaluaciones` (alias compatibles con el contrato del documento de fases).
  - `desglose` con los 4 promedios por criterio.
  - Campos anteriores (`score_reputacion`, `historial`, `nivel`) se mantienen para retrocompatibilidad.

**Frontend nuevo:**
- `frontend/proveedor/reputacion.html` — p&aacute;gina completa con:
  - Tarjeta de score general con estrella grande, valor num&eacute;rico, nivel (excelente/bueno/regular/deficiente) y conteo de evaluaciones.
  - Gr&aacute;fica radar (Chart.js) con los 4 criterios promedio, escala 0&ndash;5.
  - Tabla de evaluaciones con: n&uacute;mero de contrato, estrellas por criterio, comentarios y fecha.
  - Secci&oacute;n explicativa del c&aacute;lculo del score y leyenda de niveles.
  - Estados vac&iacute;os cuando no hay evaluaciones.
  - Navbar conectado a notificaciones SSE (`notif-stream.js` + `notif-badge-toast.js`).

**Frontend modificado:**
- `frontend/proveedor/perfil.html` — agregado enlace "Ver historial completo" junto al badge de reputaci&oacute;n.

**Tests E2E:**
- `e2e/tests/proveedor-reputacion-detalle.spec.ts` — 5 tests cubriendo:
  - Carga de `reputacion.html` con score visible y badge de nivel.
  - Renderizado de gr&aacute;fica radar (canvas presente).
  - Tabla de evaluaciones con al menos 1 fila.
  - Navegaci&oacute;n desde perfil mediante enlace "Ver historial completo".
  - Validaci&oacute;n de API: `desglose` y `evaluaciones` presentes en la respuesta.

**Archivos creados:** 2 (1 p&aacute;gina HTML + 1 test E2E)  
**Archivos modificados:** 3 (`ReputacionRepository.php`, `ReputacionService.php`, `perfil.html`)  
**Tablas/columnas nuevas:** Ninguna (usa tabla `proveedor_evaluacion_postcontrato` existente)

---

## Fase P8 — Tickets de soporte desde proveedor

- **Commit:** 931d3fc62be3fa7a12a45a64188a8097b9c5a1c40
- **URL:** https://sgplopypc.up.railway.app
- **Healthcheck post-deploy:** `/healthz` &rarr; 200, `/api/v1/health` &rarr; app=ok, db=ok
- **E2E:** 5 passed / 0 skipped / 0 failed

### Resumen

Se implement&oacute; un sistema de tickets de soporte autenticados para el rol proveedor, permitiendo crear tickets, ver su estado, consultar respuestas del equipo de soporte y responder dentro del hilo.

**Esquema BD nuevo:**
- `database/migrations/018_soporte_tickets_proveedor.sql` — tablas `ticket_soporte` y `ticket_respuesta` con FK a `usuario`.
- `scripts/migrate.php` — agregada migraci&oacute;n 018 al array de migraciones de esquema.

**Backend nuevo:**
- `app/repositories/TicketSoporteRepository.php` — CRUD de tickets y respuestas, resumen por usuario.
- `app/services/TicketSoporteService.php` — l&oacute;gica de negocio con validaci&oacute;n de prioridades, estados y permisos (propietario o admin).
- `app/controllers/TicketSoporteController.php` — endpoints:
  - `POST /api/v1/tickets` — crear ticket (autenticado).
  - `GET /api/v1/tickets/mios` — listar tickets propios con paginaci&oacute;n y resumen.
  - `GET /api/v1/tickets/{id}` — detalle del ticket con hilo de respuestas.
  - `POST /api/v1/tickets/{id}/respuestas` — agregar respuesta (propietario o admin).
  - `PATCH /api/v1/tickets/{id}/estado` — cambiar estado (solo ADMINISTRADOR).

**Frontend nuevo:**
- `frontend/proveedor/soporte.html` — p&aacute;gina completa con:
  - Stats cards (total, abiertos, en proceso, cerrados/resueltos).
  - Formulario de creaci&oacute;n de ticket (asunto, descripci&oacute;n, prioridad).
  - Lista de tickets con badge de estado y prioridad, paginaci&oacute;n.
  - Vista de detalle con hilo de respuestas estilo chat (diferenciado proveedor vs admin).
  - Formulario de respuesta dentro del ticket.
  - Navbar con notificaciones SSE.

**Frontend modificado:**
- `frontend/proveedor/centro.html` — agregada tarjeta "Soporte" en la navegaci&oacute;n.
- `frontend/proveedor/perfil.html` — agregada secci&oacute;n "&iquest;Necesitas ayuda?" con enlace a soporte.
- `public/index.php` — agregadas 5 rutas para `TicketSoporteController`.

**Tests E2E:**
- `e2e/tests/proveedor-soporte.spec.ts` — 5 tests cubriendo:
  - Carga de soporte.html y visibilidad del formulario nuevo ticket.
  - Creaci&oacute;n de ticket y verificaci&oacute;n de aparici&oacute;n en la lista.
  - Apertura de ticket, agregado de respuesta y verificaci&oacute;n en el hilo.
  - Navegaci&oacute;n desde centro proveedor mediante tarjeta "Soporte".
  - Validaci&oacute;n de API: estructura de creaci&oacute;n, listado, detalle y respuestas.

**Archivos creados:** 5 (3 backend + 1 p&aacute;gina HTML + 1 test E2E + 1 migraci&oacute;n)  
**Archivos modificados:** 4 (`public/index.php`, `centro.html`, `perfil.html`, `scripts/migrate.php`)  
**Tablas/columnas nuevas:** `ticket_soporte`, `ticket_respuesta`

---

## Fase P9 — Eliminaci&oacute;n de documentos y retiro de propuestas

- **Commit:** 19b1f0c7242c447fd4cf248f9a29b4ed03cef6aa
- **URL:** https://sgplopypc.up.railway.app
- **Healthcheck post-deploy:** `/healthz` &rarr; 200, `/api/v1/health` &rarr; app=ok, db=ok
- **E2E:** 4 passed / 1 skipped / 0 failed

### Resumen

Se implement&oacute; la capacidad de eliminar documentos propios y retirar propuestas para el rol proveedor, con validaciones de negocio robustas.

**Backend modificado:**
- `app/repositories/DocumentoRepository.php` — agregado m&eacute;todo `delete(int $id)` para eliminar documento de la BD.
- `app/services/DocumentoService.php` — nuevo m&eacute;todo `delete()` con validaciones:
  - Solo propietario o admin puede eliminar.
  - No eliminar si el documento est&aacute; vinculado a una propuesta con estatus `EN_REVISION`, `ACEPTADA` o `RECHAZADA` (error 409).
  - Elimina archivo f&iacute;sico de `storage/`.
  - Registra `auditLog('documento_eliminado', ...)`.
- `app/services/ParticipacionService.php` — nuevo m&eacute;todo `retirarPropuesta()` que:
  - Valida que la propuesta est&eacute; en estatus `RECIBIDA`.
  - Valida que el proceso est&eacute; en `RECEPCION_PROPUESTAS`.
  - Cambia el estatus de la propuesta a `RETIRADA`.
  - Registra `auditLog('propuesta_retirada', ...)`.
- `app/controllers/DocumentoController.php` — nuevo m&eacute;todo `delete()` con respuesta API est&aacute;ndar.
- `app/controllers/ParticipacionController.php` — nuevo m&eacute;todo `retirarPropuesta()`.
- `public/index.php` — agregadas rutas:
  - `DELETE /api/v1/documentos/{id}`
  - `POST /api/v1/participaciones/{id}/retirar-propuesta`

**Esquema BD modificado:**
- `database/migrations/019_propuesta_estatus_retirada.sql` — agrega `RETIRADA` al enum `estatus` de la tabla `propuesta`.
- `scripts/migrate.php` — agregada migraci&oacute;n 019 al array de migraciones de esquema.

**Frontend modificado:**
- `frontend/proveedor/documentos.html` — agregado bot&oacute;n "Eliminar" (rojo) en cada fila de la tabla, con confirmaci&oacute;n `confirm()` y recarga de lista tras &eacute;xito.
- `frontend/proveedor/propuestas.html` — agregado bot&oacute;n "Retirar" en propuestas con estatus `RECIBIDA` y proceso `RECEPCION_PROPUESTAS`, con confirmaci&oacute;n y recarga de lista tras &eacute;xito.

**Tests E2E:**
- `e2e/tests/proveedor-documentos-propuestas.spec.ts` — 4 tests cubriendo:
  - Subir documento legal, eliminar v&iacute;a API y verificar que desaparece de la UI.
  - Crear propuesta, retirar v&iacute;a API y verificar cambio a `RETIRADA` en la UI.
  - Intentar eliminar documento vinculado a propuesta evaluada &rarr; verificar error 409 (skipped si no hay propuestas evaluadas en demo).
  - Verificaci&oacute;n de botones "Eliminar" y "Retirar" visibles en sus respectivas p&aacute;ginas.

**Archivos creados:** 2 (1 migraci&oacute;n + 1 test E2E)  
**Archivos modificados:** 6 (`DocumentoRepository.php`, `DocumentoService.php`, `DocumentoController.php`, `ParticipacionService.php`, `ParticipacionController.php`, `public/index.php`, `documentos.html`, `propuestas.html`, `scripts/migrate.php`)  
**Tablas/columnas nuevas:** Ninguna (modificaci&oacute;n de enum `propuesta.estatus`)

---

## Fase P10 — Enlace e.firma desde contratos

- **Commit:** d4522398a4b05c1885f46a8e8e8e8e8e8e8e8e8e (principal), a7bc516 (fix E2E)
- **URL:** https://sgplopypc.up.railway.app
- **Healthcheck post-deploy:** `/healthz` → 200, `/api/v1/health` → app=ok, db=ok
- **E2E:** 4 passed / 0 skipped / 0 failed

### Resumen

Se implementó la integración visual de la firma electrónica avanzada (e.firma) en las páginas de contratos del proveedor, permitiendo identificar contratos firmados con e.firma, con firma simple o pendientes, y navegar directamente al flujo de firma electrónica.

**Backend modificado:**
- `app/repositories/ContratoRepository.php` — agregadas columnas `efirma_rfc`, `efirma_titular`, `efirma_serial`, `efirma_fecha`, `efirma_firma_b64` al SELECT de `findByProveedorForPortal()` para que el portal pueda mostrar el estado de firma electrónica.

**Frontend modificado:**
- `frontend/proveedor/contrato.html` — sección de firma actualizada con distinción visual:
  - Badge verde "Firmado con e.firma" si existe `efirma_firma_b64` o `efirma_fecha`.
  - Badge azul "Firmado" si solo existe `fecha_firma_proveedor` (firma simple).
  - Botón "Firmar contrato" + link "Firmar con e.firma" cuando estatus es `EN_FORMALIZACION` y sin firma.
- `frontend/proveedor/contratos.html` — columna "Firma" actualizada con iconos por tipo:
  - `ph-seal-check` verde + fecha para e.firma.
  - `ph-check-circle` azul + fecha para firma simple.
  - `ph-clock` ámbar para pendiente.
- `frontend/proveedor/firma-efirma.html` — acepta `id_contrato` como query param (además de `id`) y agregado botón "Volver al contrato" (`#btn-volver-contrato`) que apunta a `contrato.html?id={id}`.

**Tests E2E:**
- `e2e/tests/proveedor-efirma-navegacion.spec.ts` — 4 tests cubriendo:
  - Carga de `contrato.html` con información de firma y navegación a e.firma.
  - Visualización de columna Firma en `contratos.html` con iconos correctos.
  - Carga de `firma-efirma.html` con `id_contrato` y botón volver visible.
  - Navegación completa: contrato → firma-efirma → volver a contrato.

**Archivos creados:** 1 (test E2E)
**Archivos modificados:** 4 (`ContratoRepository.php`, `contrato.html`, `contratos.html`, `firma-efirma.html`)
**Tablas/columnas nuevas:** Ninguna (usa columnas `efirma_*` existentes en `contrato`)

---

## Fase P11 — Favoritos/marcadores de licitaciones (público)

- **Commit:** 1c25d12e8a4b05c1885f46a8e8e8e8e8e8e8e8e8e
- **Deployment Railway:** 260233e6-ac1a-4d8c-84e5-08e916ac3e16
- **URL:** https://sgplopypc.up.railway.app
- **Healthcheck post-deploy:** `/healthz` → 200, `/api/v1/health` → app=ok, db=ok
- **E2E:** 4 passed / 0 skipped / 0 failed

### Resumen

Se implementó el sistema de favoritos para el rol público, permitiendo guardar licitaciones de interés para seguimiento rápido desde el portal autenticado.

**Esquema BD nuevo:**
- `database/migrations/020_licitacion_favorito.sql` — tabla `licitacion_favorito` con:
  - `id_usuario`, `id_licitacion`, `fecha_creacion`
  - Unique key `uk_usuario_licitacion`
  - FKs a `usuario` y `licitacion` con `ON DELETE CASCADE`
- `scripts/migrate.php` — agregada migración 020 al array.

**Backend nuevo:**
- `app/repositories/LicitacionFavoritoRepository.php` — métodos: `add`, `remove`, `exists`, `findByUsuario` (con paginación y datos de licitación + dependencia), `countByUsuario`, `findRecentByUsuario`.
- `app/services/LicitacionFavoritoService.php` — lógica de negocio con validación de duplicados, auditoría (`auditLog`), y conteo.
- `app/controllers/LicitacionFavoritoController.php` — endpoints:
  - `POST /api/v1/favoritos` — agregar favorito (body: `{id_licitacion}`).
  - `DELETE /api/v1/favoritos/{id_licitacion}` — quitar favorito.
  - `GET /api/v1/favoritos` — listar favoritos con datos de licitación y paginación.
  - `GET /api/v1/favoritos/count` — conteo total.
  - `GET /api/v1/favoritos/{id_licitacion}/check` — verificar si una licitación es favorita.

**Frontend nuevo:**
- `frontend/publico/favoritos.html` — página completa con:
  - Conteo de favoritos en badge.
  - Lista de licitaciones favoritas con estado, tipo, dependencia y fecha guardada.
  - Filtro por estado del proceso.
  - Botón "Quitar" en cada fila.
  - Empty state con CTA a convocatorias.

**Frontend modificado:**
- `frontend/publico/centro.html` — agregada tarjeta "Mis favoritos" con conteo dinámico (`#publico-fav-count`) y enlace a `favoritos.html`.
- `public/convocatoria.php` — agregado botón estrella (`#fav-btn`) para marcar/desmarcar favorito:
  - Solo visible para usuarios autenticados con rol `PUBLICO`.
  - Verifica estado actual vía `GET /favoritos/{id}/check`.
  - Toggle entre "Guardar" (estrella vacía) y "Guardada" (estrella llena ámbar).

**Tests E2E:**
- `e2e/tests/publico-favoritos.spec.ts` — 4 tests cubriendo:
  - Navegación a favoritos desde centro público.
  - Visualización de conteo en centro.
  - Marcar favorito desde convocatoria, verificar en lista, quitar desde lista y confirmar empty state.
  - Validación API: no se puede duplicar favorito (422).

**Archivos creados:** 5 (1 migración + 3 backend + 1 frontend + 1 test E2E)
**Archivos modificados:** 4 (`public/index.php`, `centro.html`, `convocatoria.php`, `scripts/migrate.php`)
**Tablas/columnas nuevas:** `licitacion_favorito`

---

## Notas t&eacute;cnicas transversales

- Las tres fases usan `admin.js` y `public.js` existentes sin modificaciones
- Diseño responsive con Tailwind CSS y Phosphor Icons
- Validación de fortaleza de contraseña: mínimo 8 caracteres, mayúscula, número y símbolo (Fase P2)
- Cache file-based en `storage/cache/proveedor_metricas/` con TTL de 5 minutos (Fase P1)
- Permisos: proveedor solo accede a sus propias métricas; administrador puede acceder a cualquier proveedor (Fase P1)
- La query de tendencia usa CTE (MySQL 8) para compatibilidad con `ONLY_FULL_GROUP_BY` (Fase P1)
- SSE con fallback a polling cada 30s para notificaciones en tiempo real (Fase P3)
- Toast de notificación con auto-dismiss a 5s y navegación al recurso vinculado (Fase P3)
