# Cierre de Fases P1 y P2 — Dashboard Proveedor y Perfil/Notificaciones Público

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

## Notas técnicas transversales

- Ambas fases usan `admin.js` y `public.js` existentes sin modificaciones
- Diseño responsive con Tailwind CSS y Phosphor Icons
- Validación de fortaleza de contraseña: mínimo 8 caracteres, mayúscula, número y símbolo
- Cache file-based en `storage/cache/proveedor_metricas/` con TTL de 5 minutos (Fase P1)
- Permisos: proveedor solo accede a sus propias métricas; administrador puede acceder a cualquier proveedor (Fase P1)
- La query de tendencia usa CTE (MySQL 8) para compatibilidad con `ONLY_FULL_GROUP_BY` (Fase P1)
