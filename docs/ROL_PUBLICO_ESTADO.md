# Estado Técnico del Rol Público - SGPLOPyPC

> Actualizado: 2026-05-10 14:14 CST.
> Verificación realizada contra producción Railway y base de datos MySQL.

## 1. Resumen Ejecutivo

El rol PUBLICO esta operativo en produccion. Las fases 1 a 5 quedaron implementadas y verificadas en plataforma:

- Las paginas publicas principales responden correctamente.
- El rol PUBLICO puede autenticarse y entrar a su centro.
- Los endpoints administrativos y de proveedor rechazan tokens PUBLICO con 403.
- `/api/v1/licitaciones` y `/api/v1/licitaciones/{id}` ya no exponen informacion interna al rol PUBLICO.
- Los endpoints publicos de lectura tienen rate limiting.
- Los datos E2E siguen en la base productiva, pero la API publica los filtra mediante `licitacion.is_test`.
- El error 500 en filtros `q` publicos fue corregido y desplegado por Railway CLI.

## 2. Entorno Verificado

| Elemento | Estado |
|---|---|
| Proyecto Railway | `miraculous-perception` |
| Servicio app | `SGPLOPyPC` online |
| URL produccion | `https://sgplopypc-production.up.railway.app` |
| Base de datos | MySQL `mysql-volume` online |
| Runtime | PHP 8.2 + Apache |
| Ultimo deploy CLI verificado | `bf08e9ba-af3d-4af2-9419-bc7bc6934d5b` |

Variables verificadas:

| Variable | Estado |
|---|---|
| `APP_ENV` | `production` |
| `JWT_TTL` | `86400` |
| `MAIL_ENABLED` | `1` |
| `APP_BASE_URL` | configurada |
| `SUPPORT_EMAIL_TO` | configurada |
| `RATE_LIMIT_PUBLIC_READ_*` | usa defaults del codigo |

## 3. Funcionalidad Publica

| Ruta | Resultado |
|---|---|
| `/` | 200 |
| `/evaluacion.php` | 200 |
| `/historial.php` | 200 |
| `/contratos.php` | 200 |
| `/resultados.php` | 200 |
| `/convocatoria.php?id=13` | 200 |
| `/registro.php` | 200 |
| `/faq.php` | 200 |
| `/requisitos.php` | 200 |
| `/frontend/publico/` | 200, redirige a `centro.html` |
| `/healthz` | 200 |

Funcionalidades disponibles:

- Landing publica con estadisticas y convocatorias.
- Procesos en evaluacion.
- Historial con filtros.
- Contratos adjudicados.
- Resultados de adjudicacion.
- Detalle publico de convocatoria.
- Descarga/listado de documentos publicos cuando existan documentos visibles.
- Registro publico de proveedor.
- Ticket publico de soporte.
- Centro autenticado del rol PUBLICO con resumen y accesos.

## 4. API y Seguridad

Endpoints publicos verificados:

| Endpoint | Resultado |
|---|---|
| `GET /api/v1/public/estadisticas` | 200 |
| `GET /api/v1/public/convocatorias?limit=2` | 200 |
| `GET /api/v1/public/convocatorias?q=seed` | 200 |
| `GET /api/v1/public/historial?q=seed` | 200 |
| `GET /api/v1/public/resultados?q=seed` | 200 |
| `GET /api/v1/public/convocatorias/13/documentos` | 200 |
| `GET /api/v1/public/documentos/999999/download` | 404 |

Pentest basico verificado despues del fix:

| Prueba | Resultado |
|---|---|
| SQL-like en convocatorias `q=' OR 1=1 --` | 200, lista vacia |
| SQL-like en historial `q=' UNION SELECT 1 --` | 200, lista vacia |
| SQL-like en resultados `q=' OR 'a'='a` | 200, lista vacia |
| Path traversal en descarga de documento | 404 |
| Enumeracion de documento inexistente | 404 |
| Rate limit de lectura publica | 429 al exceder ventana |

Permisos con token PUBLICO:

| Endpoint | Resultado |
|---|---|
| `GET /api/v1/me` | 200 |
| `GET /api/v1/notificaciones/mias` | 200 |
| `GET /api/v1/admin/dashboard` | 403 |
| `GET /api/v1/proveedores` | 403 |
| `GET /api/v1/participaciones/mias` | 403 |
| `GET /api/v1/contratos` | 403 |
| `GET /api/v1/soporte/tickets` | 403 |
| `GET /api/v1/licitaciones` | 403 |
| `GET /api/v1/licitaciones/13` | 403 |

## 5. Base de Datos

Conteos verificados en MySQL despues de pruebas E2E:

| Dato | Valor |
|---|---:|
| Licitaciones totales | 15 |
| Licitaciones marcadas/detectadas E2E | 12 |
| Licitaciones no test (`is_test = 0`) | 3 |
| Licitaciones activas totales | 5 |
| Licitaciones adjudicadas totales | 2 |
| Proveedores | 8 |
| Proveedores activos | 8 |
| Contratos | 2 |
| Monto total de contratos | 2599000.00 |
| Documentos | 7 |
| Notificaciones usuario demo PUBLICO | 0 |

Integridad e indices verificados:

- `licitacion.is_test` existe.
- `fecha_proceso` tiene UNIQUE `uq_fecha_proceso_licitacion_tipo(id_licitacion, tipo_fecha)`.
- `notificacion` tiene indice `idx_notificacion_usuario_leida_envio(id_usuario_destino, leida, fecha_envio)`.
- No hay duplicados actuales por `(id_licitacion, tipo_fecha)` en `fecha_proceso`.

Nota de datos: los registros E2E permanecen en produccion, pero ya no contaminan la API publica porque las consultas filtran `is_test` o patrones E2E legacy.

## 6. Pruebas Ejecutadas

E2E contra produccion:

```bash
cd e2e && E2E_BASE_URL='https://sgplopypc-production.up.railway.app' \
  npx playwright test tests/public-basic-flows.spec.ts tests/public-fase5-flows.spec.ts --reporter=list
```

Resultado: 6/6 passed.

Accesibilidad:

```bash
cd e2e && E2E_BASE_URL='https://sgplopypc-production.up.railway.app' \
  npx playwright test tests/public-accessibility.spec.ts --reporter=list
```

Resultado: 6/6 passed como auditoria automatizada.

Validacion PHP:

```bash
php -l app/repositories/PublicRepository.php
```

Resultado: sin errores de sintaxis.

## 7. Documentacion Generada

| Documento | Proposito |
|---|---|
| `docs/openapi-public.yaml` | Contrato OpenAPI de la API publica |
| `docs/FASE5_PUBLICO_PENTEST.md` | Evidencia de pentest basico |
| `docs/FASE5_PUBLICO_ACCESIBILIDAD.md` | Auditoria de accesibilidad |
| `docs/API_ENDPOINTS.md` | Referencia general con enlace al OpenAPI publico |

## 8. Pendientes Reales

1. Corregir hallazgos de accesibilidad documentados en `docs/FASE5_PUBLICO_ACCESIBILIDAD.md` si se quiere convertir la auditoria en gate estricto WCAG AA.
2. Decidir si se eliminan fisicamente los datos E2E de produccion o si se conserva el filtrado por `is_test`.
3. Agregar automatizacion formal para ejecutar migraciones en deploy; actualmente las migraciones se aplican manualmente cuando cambia el esquema.
4. Evaluar refresh tokens o mecanismo de revocacion si el riesgo operativo de JWT de 24h aumenta.

## 9. Estado Final

El rol PUBLICO queda verificado y funcional en produccion. La discrepancia encontrada durante esta revision fue el error 500 en busquedas publicas con `q`, corregido en `PublicRepository` y desplegado por Railway CLI. Tambien se aplico la migracion 009 en MySQL para completar el indice compuesto de notificaciones.
