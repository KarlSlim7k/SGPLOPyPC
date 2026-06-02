# Cierre de Fase P1 — Dashboard con KPIs para proveedor

- **Commit:** 7bc8ce02815974dee1b9c50f48d9039f67cd0d2c
- **Deployment Railway:** 3402b27d-290d-475e-a47a-f0fb8cab922f
- **URL:** https://sgplopypc.up.railway.app
- **Healthcheck post-deploy:** `/healthz` → 200, `/api/v1/health` → app=ok, db=ok
- **E2E:** 6 passed / 0 skipped / 0 failed

## Endpoints nuevos

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/v1/proveedores/{id}/metricas` | KPIs del proveedor con cache 5 min |
| GET | `/api/v1/proveedores/{id}/metricas/tendencia` | Serie trimestral de participaciones/montos |

## Archivos creados

| Archivo | Descripción |
|---------|-------------|
| `app/repositories/ProveedorMetricasRepository.php` | Queries SQL para métricas del proveedor |
| `app/services/ProveedorMetricasService.php` | Lógica de negocio con cache SimpleCache (TTL 300s) |
| `app/controllers/ProveedorMetricasController.php` | Controlador con auth y validación de permisos |
| `e2e/tests/proveedor-dashboard.spec.ts` | 6 tests E2E (API + UI) |

## Archivos modificados

| Archivo | Descripción |
|---------|-------------|
| `public/index.php` | Registro del controlador y rutas |
| `frontend/proveedor/centro.html` | Dashboard KPI: 4 stat cards, Chart.js (barras + dona), tabla últimas participaciones |
| `docs/api/API_ENDPOINTS.md` | Documentación de los 2 endpoints nuevos |

## Tablas/columnas nuevas

Ninguna (solo queries sobre tablas existentes).

## Notas

- La query de tendencia usa CTE (MySQL 8) para compatibilidad con `ONLY_FULL_GROUP_BY`.
- Cache file-based en `storage/cache/proveedor_metricas/` con TTL de 5 minutos.
- Permisos: proveedor solo accede a sus propias métricas; administrador puede acceder a cualquier proveedor.
