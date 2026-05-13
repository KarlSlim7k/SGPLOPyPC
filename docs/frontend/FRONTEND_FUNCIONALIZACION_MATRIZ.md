# Matriz de Funcionalización Frontend (Admin)

## Objetivo
Convertir las vistas de `frontend/admin` de mockup visual a flujos operativos conectados a `/api/v1`.

## Convenciones de estimación
- S: 0.5-1 día
- M: 1-2 días
- L: 2-4 días

## Priorización por pantalla

| Prioridad | Pantalla | Estado actual | Endpoints backend | Brechas principales | Esfuerzo |
|---|---|---|---|---|---|
| P0 | `auth/login.html` | Parcialmente funcional | `POST /auth/login`, `GET /me` | Falta guardia de sesión global, renovación/expiración de token, logout transversal | S |
| P0 | `admin/convocatorias/create.html` | Mockup | `POST /licitaciones`, `POST /documentos/upload`, `PATCH /licitaciones/{id}/estado` | Form submit sin JS, mapeo de campos UI -> payload API, manejo de adjuntos, manejo de errores/422, feedback de éxito | M |
| P0 | `admin/convocatorias/index.html` | Mockup | `GET /licitaciones`, `PATCH /licitaciones/{id}/estado`, `GET /licitaciones/{id}` | Tabla estática, filtros/búsqueda local no conectados, acciones de cambio de estado inexistentes, paginación client-side | M |
| P1 | `admin/dashboard.html` | Mockup | `GET /reportes/dashboard/resumen`, `GET /reportes/dashboard/licitaciones-por-estado`, `GET /reportes/dashboard/participacion-proveedores`, `GET /reportes/dashboard/adjudicaciones-por-periodo` | KPIs y tablas hardcodeadas, sin loading/error/empty, sin sincronización con filtros de fecha | M |
| P1 | `admin/proveedores/index.html` | Mockup | `GET /proveedores`, `PATCH /proveedores/{id}/estatus`, `GET /proveedores/{id}` | Listado estático, búsqueda/filtros no conectados, acciones de estatus sin wiring, modales de detalle inexistentes | M |
| P1 | `admin/evaluacion/index.html` | Mockup | `POST /evaluaciones`, `PUT /evaluaciones/{id}`, `POST /evaluaciones/{id}/dictamen`, `POST /licitaciones/{id}/adjudicar` | Cuadro comparativo estático, cálculo/registro de evaluación sin lógica, emisión de dictamen no implementada | L |
| P2 | `admin/adjudicaciones/index.html` | Mockup | `POST /contratos`, `PUT /contratos/{id}`, `PATCH /contratos/{id}/estatus`, `GET /contratos/{id}` | Tabla hardcodeada, flujo de formalización de contrato ausente, descarga documental sin integración | M |
| P2 | `admin/reportes/index.html` | Mockup | `GET /reportes/dashboard/*`, `GET /reportes/export/licitaciones.csv`, `GET /licitaciones/{id}/historial` | KPIs/gráficas estáticas, exportación no cableada, historial no integrado, placeholder de meses futuros | M |
| P3 | `admin/propuestas/index.html` | Mockup | `GET /propuestas/{id}`, `GET /licitaciones/{id}/participaciones` | No existe endpoint agregado para listado global de propuestas; requiere vista por licitación o nuevo endpoint backend | L |

## Gaps backend detectados para completar UX admin

1. **Listado global de propuestas** para `propuestas/index.html` no está expuesto de forma directa.
2. **Listado global de contratos/adjudicaciones** tampoco está expuesto como colección (solo `GET /contratos/{id}` y operaciones puntuales).
3. **Catálogos auxiliares** (dependencias, estados normalizados, tipos de procedimiento) no aparecen como endpoints dedicados para poblar filtros del UI.

## Orden recomendado de ejecución (siguiente fase)

1. Base transversal de sesión:
   - helper JS común (`token`, `authFetch`, `redirect 401`, `logout`).
2. Convocatorias (crear + listar):
   - permite tener datos reales para alimentar módulos posteriores.
3. Dashboard + Proveedores:
   - lectura y operación administrativa básica.
4. Evaluación + Adjudicaciones:
   - flujo core de negocio (evaluar -> adjudicar -> contrato).
5. Reportes + Propuestas:
   - cerrar analítica/export y resolver gaps backend de listados globales.

## Criterios de salida por pantalla

- Datos cargan desde API real (sin hardcode).
- Estados `loading`, `error`, `empty` visibles.
- Validación cliente + manejo de `4xx/5xx`.
- Botones críticos conectados (crear/editar/cambiar estado/exportar).
- Navegación protegida por sesión y rol.

## Estado actual (cierre de fase)

- `auth/login.html`: funcional (login + sesión local).
- `admin/convocatorias/index.html`: funcional con listado real, filtros, cambio de estado y export.
- `admin/convocatorias/create.html`: funcional con alta de licitación y carga de documentos.
- `admin/dashboard.html`: funcional con KPIs y tabla desde API.
- `admin/proveedores/index.html`: funcional con listado, filtros y cambio de estatus.
- `admin/propuestas/index.html`: funcional con listado global, filtros y métricas (endpoint agregado).
- `admin/evaluacion/index.html`: funcional con carga por licitación, dictamen y adjudicación.
- `admin/adjudicaciones/index.html`: funcional con listado de contratos y cambio de estatus (endpoint agregado).
- `admin/reportes/index.html`: funcional con KPIs dinámicos, desglose y export.

Pendiente de cierre total:
- Ejecución E2E completa en entorno con BD configurada (`DB_*`), documentada en `docs/operacion/FASE_CIERRE_VALIDACION.md`.
