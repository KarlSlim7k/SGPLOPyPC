# Fase 2 — Reportes con plantillas (PDF/DOCX/MD + editor WYSIWYG)

**Estado:** ✅ Completada — 2026-05-27
**Commit:** `6b7b03889f42706a302cb4bf003903b72bde316e`
**Deployment Railway:** `9aede7d0-2522-41a8-833a-4efe67f63c9f`
**URL producción:** https://sgplopypc.up.railway.app

## 1. Objetivo

Sustituir el módulo de reportes (que sólo exportaba CSV) por un sistema completo de plantillas oficiales con exportación a **PDF**, **DOCX** y **Markdown**, alineado con estándares nacionales (LAASSP, LGTAIP) e internacionales (OCDS, UNCITRAL).

## 2. Estándares aplicados

### Nacional (México)
- **LAASSP** (Ley de Adquisiciones, Arrendamientos y Servicios del Sector Público):
  - Art. 33 Bis — Junta de Aclaraciones
  - Art. 35 — Apertura de Proposiciones
  - Art. 36 — Dictamen Técnico-Económico
  - Art. 37 — Fallo
- **LGTAIP** — formato accesible para transparencia
- **NOM-151-SCFI** — conservación de mensajes de datos

### Internacional
- **OCDS 1.1** (Open Contracting Data Standard) — incorporado en plantilla "Resumen de Licitación"
- **UNCITRAL Model Law on Public Procurement (2011)** — estructura de actas
- **ISO 10845-2** — formato de documentación de procurement

## 3. Cambios entregados

### 3.1 Dependencias

| Lib | Versión | Uso |
|---|---|---|
| `dompdf/dompdf` | 3.1.5 | HTML → PDF |
| `phpoffice/phpword` | 1.4.0 | HTML → DOCX |

`Dockerfile` modificado para:
- Instalar extensiones: `zip`, `gd` (con freetype/jpeg), `dom`, `xml`
- Instalar Composer (binario oficial)
- Ejecutar `composer install --no-dev --optimize-autoloader` durante el build

### 3.2 Base de datos

`database/migrations/013_reportes_plantillas.sql`:

| Tabla | Columnas | Notas |
|---|---|---|
| `plantilla_reporte` | id, nombre, descripcion, tipo (enum 7), contenido_html, contenido_json, variables_esperadas, id_usuario_creador (FK), activa, **es_predefinida**, fechas | Las predefinidas son inmutables |
| `plantilla_asset` | id, id_plantilla (FK CASCADE), tipo (LOGO/FIRMA/SELLO/OTRO), nombre, ruta_relativa, pos_x, pos_y, ancho_mm, alto_mm | Para logos institucionales y firmas |

Índices: `idx_plantilla_tipo_activa`, `idx_asset_plantilla_tipo`.

`database/migrations/014_seed_plantillas_predefinidas.sql`:

5 plantillas oficiales sembradas con `es_predefinida=1`:

| ID | Nombre | Tipo | Fundamento |
|---|---|---|---|
| 1 | Acta de Junta de Aclaraciones (LAASSP) | ACTA_ACLARACIONES | Art. 33 Bis |
| 2 | Acta de Apertura de Proposiciones (LAASSP) | ACTA_APERTURA | Art. 35 |
| 3 | Acta de Fallo (LAASSP) | ACTA_FALLO | Art. 37 |
| 4 | Dictamen Técnico-Económico (LAASSP) | DICTAMEN | Art. 36 |
| 5 | Resumen de Licitación (OCDS / Datos Abiertos) | RESUMEN_LICITACION | LGTAIP + OCDS |

### 3.3 Backend

| Archivo | Responsabilidad |
|---|---|
| `app/repositories/PlantillaRepository.php` | CRUD de `plantilla_reporte` y `plantilla_asset`. |
| `app/services/PlantillaService.php` | Validaciones, sanitización de filtros, upload de assets (PNG/JPG/SVG, max 5MB), protección anti-edición de predefinidas. |
| `app/services/ReporteRenderService.php` | Render a PDF (Dompdf), DOCX (PHPWord HTMLImporter), MD (HTML→MD nativo + front-matter YAML). Inyección de variables desde licitación/contrato. |
| `app/controllers/PlantillaController.php` | Endpoints HTTP + auditoría. |

#### Engine de placeholders

- Sintaxis: `{{variable}}` (regex `[a-zA-Z_][a-zA-Z0-9_]*`)
- Escapado HTML por defecto excepto para variables que producen filas tabulares (`licitantes_filas`, `evaluacion_tecnica_filas`, `evaluacion_economica_filas`)
- Variables no provistas → vacío (no falla, no expone marcador)

#### Variables auto-derivadas (entidad: licitación)

`numero_licitacion`, `tipo_procedimiento`, `descripcion_proyecto`, `presupuesto_estimado`, `ubicacion_proyecto`, `estado_proceso`, `dependencia_nombre`, `responsable_nombre`, `fecha_publicacion_convocatoria`, `fecha_junta_aclaraciones`, `fecha_recepcion_propuestas`, `fecha_apertura_propuestas`, `fecha_fallo_adjudicacion`, `licitantes_filas` (auto-tabla), `hitos`, `adjudicacion_resumen`.

### 3.4 Endpoints nuevos

| Método | Ruta | Rol | Descripción |
|---|---|---|---|
| `GET` | `/api/v1/admin/plantillas` | ADMINISTRADOR | Lista (filtros: `tipo`, `activa`, `solo_predefinidas`). |
| `POST` | `/api/v1/admin/plantillas` | ADMINISTRADOR | Crear plantilla personalizada. |
| `GET` | `/api/v1/admin/plantillas/{id}` | ADMINISTRADOR | Detalle (`?with_content=1` incluye HTML). |
| `PUT` | `/api/v1/admin/plantillas/{id}` | ADMINISTRADOR | Actualizar. **409** en predefinidas. |
| `DELETE` | `/api/v1/admin/plantillas/{id}` | ADMINISTRADOR | Eliminar. **409** en predefinidas. |
| `POST` | `/api/v1/admin/plantillas/{id}/assets` | ADMINISTRADOR | Subir asset (multipart: `tipo`, `nombre`, `archivo`). |
| `DELETE` | `/api/v1/admin/plantillas/assets/{idAsset}` | ADMINISTRADOR | Eliminar asset. |
| `POST` | `/api/v1/reportes/generar` | ADMINISTRADOR | Genera PDF/DOCX/MD y devuelve binario como `attachment`. |

### 3.5 Frontend

- `frontend/admin/plantillas/index.html` — listado filtrable, botones editar/eliminar bloqueados en predefinidas, badges (Activa/Predefinida).
- `frontend/admin/plantillas/editor.html` — formulario completo + **vista previa en `<iframe sandbox>`** con datos de ejemplo, debounce 700ms, gestión de assets multipart, botón "Cargar plantilla mínima".
- `frontend/admin/reportes/index.html` — sección nueva "Generar reporte desde plantilla" con select dinámico y 3 botones de formato.
- Enlace "Plantillas" en sidebar de las **12 vistas admin**.

### 3.6 Storage

- `storage/templates/` con `.htaccess`: `Deny from all` + `FilesMatch` que bloquea ejecución de scripts.

### 3.7 Tests E2E

| Archivo | Casos | Resultado |
|---|---|---|
| `e2e/tests/admin-plantillas.spec.ts` | 6 | ✅ |
| `e2e/tests/admin-reportes-export.spec.ts` | 6 | ✅ |

Cobertura:
- Lista incluye las 5 predefinidas
- 409 al intentar editar/eliminar una predefinida
- CRUD completo de personalizada
- 403 para rol PROVEEDOR
- UI navega y abre editor en modo lectura
- PDF magic bytes (`%PDF`)
- DOCX magic bytes (`PK\x03\x04` ZIP/OOXML)
- MD con front-matter YAML
- Validación de formato inválido (422)
- Entidad inexistente (404)

## 4. Verificación en producción

```bash
# Login
TOKEN=$(curl -s -X POST https://sgplopypc.up.railway.app/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@sgplopypc.gob.mx","password":"admin123"}' | jq -r .data.token)

# Listar plantillas → 5 predefinidas confirmadas
curl -s "https://sgplopypc.up.railway.app/api/v1/admin/plantillas?activa=1" \
  -H "Authorization: Bearer $TOKEN" | jq '.data.items | length'
# → 5

# Generar PDF
curl -s -X POST https://sgplopypc.up.railway.app/api/v1/reportes/generar \
  -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"id_plantilla":5,"entidad":"licitacion","id_entidad":1,"formato":"pdf"}' \
  -o test.pdf

file test.pdf
# → PDF document, version 1.7
```

## 5. Resultados de pruebas

```
Fase 2:
  admin-plantillas.spec.ts:        6 passed
  admin-reportes-export.spec.ts:   6 passed
                                  ─────────
  Subtotal Fase 2:                12 passed / 0 failed (15.9s)

Smoke regresivo (Fase 1):
  admin-auditoria.spec.ts:         5 passed
  admin-auth-and-navigation:       2 passed
  public-basic-flows:              2 passed
                                  ─────────
  Subtotal regresión:              9 passed / 0 failed (45.0s)

────────────────────────────────────────────────
TOTAL:                            21 passed / 0 failed
```

## 6. Tamaño y performance

- `vendor/` Composer: ~17 MB (sólo runtime, sin dev deps)
- Build de Docker: agregó ~60s al tiempo de despliegue por `composer install`
- Generación de PDF: ~3.7 KB para "Resumen de Licitación", < 200 ms en producción
- Generación de DOCX: ~7.9 KB, < 300 ms
- Generación de MD: ~1.8 KB, < 50 ms

## 7. Decisiones técnicas

### Editor HTML simple en lugar de pdfme
La opción inicial (pdfme con builder visual) requería un pipeline de build npm/React que rompía la consistencia del frontend vanilla actual. Se optó por un editor con `<textarea>` + vista previa en `<iframe sandbox>` que renderiza con datos de ejemplo. Es funcional, accesible y compatible con el stack existente. Migración a pdfme queda como mejora futura opcional.

### Dompdf con `isRemoteEnabled=false`
Por seguridad, Dompdf no descarga recursos remotos durante el render. Las imágenes (logos/firmas) se sirven desde `storage/templates/` mediante rutas relativas que el render resuelve internamente.

### Variables tabulares sin escapado
`licitantes_filas` y similares contienen filas `<tr>...<tr>` generadas por el backend desde la BD, no por usuarios. Se inyectan tal cual; las columnas individuales se escapan con `htmlspecialchars` en el momento de generación.

### Plantillas predefinidas como `es_predefinida=1`
Bloqueadas en backend (409 en PUT/DELETE) y en UI (modo lectura). Para personalizar, los usuarios crean una plantilla nueva (en el futuro: botón "Duplicar").

## 8. Próxima fase

Avanzar a **Fase 3 — API pública de datos abiertos (OCDS)** según `docs/fases/mejoras/FASES_MEJORAS.md`. La plantilla "Resumen de Licitación" ya cumple con la estructura OCDS 1.1, lo que facilita la integración.

---

## Anexo — Plantilla de cierre

```text
Commit:        6b7b03889f42706a302cb4bf003903b72bde316e
Deployment:    9aede7d0-2522-41a8-833a-4efe67f63c9f
URL:           https://sgplopypc.up.railway.app
Healthcheck:   /healthz=200  /api/v1/health app=ok db=ok
E2E fase 2:    12 passed / 0 failed
E2E regresión: 9 passed / 0 failed
Tablas:        plantilla_reporte, plantilla_asset
Endpoints:     GET    /admin/plantillas
               POST   /admin/plantillas
               GET    /admin/plantillas/{id}
               PUT    /admin/plantillas/{id}
               DELETE /admin/plantillas/{id}
               POST   /admin/plantillas/{id}/assets
               DELETE /admin/plantillas/assets/{idAsset}
               POST   /reportes/generar
Plantillas:    5 predefinidas (LAASSP + OCDS)
```
