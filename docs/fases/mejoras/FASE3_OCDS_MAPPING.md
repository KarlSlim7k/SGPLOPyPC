# OCDS Mapping — SGPLOPyPC → Open Contracting Data Standard 1.1

> Documento de referencia técnica que define cómo se proyectan las entidades del modelo SGPLOPyPC a la estructura de un **release** del Open Contracting Data Standard (OCDS) versión 1.1.
>
> Especificación OCDS oficial: https://standard.open-contracting.org/1.1/en/schema/release/

## 1. Visión general

OCDS describe un proceso de contratación como una **lista de releases** (eventos consecutivos identificados por un mismo `ocid`), y cada release tiene secciones opcionales: `tender`, `awards[]`, `contracts[]`, `parties[]`, `buyer`, `planning`, `implementation`.

### Identificadores

| OCDS field | Origen SGPLOPyPC | Ejemplo |
|---|---|---|
| `ocid` | `"ocds-sgplopypc-" + numero_licitacion` | `ocds-sgplopypc-LP-DEMO-2026-001` |
| `id` | `ocid + "-r" + timestamp_unix` | `ocds-sgplopypc-LP-DEMO-2026-001-r1748390000` |
| `tag[]` | derivado del `estado_proceso` | `["tender"]`, `["award", "contract"]`, etc. |
| `language` | constante | `"es"` |
| `date` | `licitacion.fecha_actualizacion` (ISO 8601) | `2026-05-27T22:30:00Z` |
| `initiationType` | constante | `"tender"` |

### Mapping de `tag` por `estado_proceso`

| `estado_proceso` (SGPLOPyPC) | OCDS `tag[]` |
|---|---|
| `BORRADOR` | `["planning"]` |
| `PUBLICADA`, `EN_ACLARACIONES`, `RECEPCION_PROPUESTAS`, `EN_EVALUACION` | `["tender"]` |
| `ADJUDICADA` | `["tender", "award"]` |
| Cualquiera con `contrato` asociado | `["tender", "award", "contract"]` |
| `DESIERTA`, `CANCELADA` | `["tender", "tenderUpdate"]` |

## 2. Sección `tender`

Mapeo principal: `licitacion` (+ `dependencia` + `fecha_proceso`).

| OCDS path | Origen SGPLOPyPC | Notas |
|---|---|---|
| `tender.id` | `licitacion.numero_licitacion` | |
| `tender.title` | `licitacion.descripcion_proyecto` (primeros 150 chars) | |
| `tender.description` | `licitacion.descripcion_proyecto` | |
| `tender.status` | derivado de `estado_proceso` (ver tabla abajo) | |
| `tender.procurementMethod` | `tipo_procedimiento` → `open` / `selective` / `direct` | |
| `tender.procurementMethodDetails` | `humanProcedimiento(tipo_procedimiento)` | "Licitación Pública", etc. |
| `tender.value.amount` | `presupuesto_estimado` | |
| `tender.value.currency` | constante `"MXN"` | |
| `tender.tenderPeriod.startDate` | `fecha_proceso` tipo `RECEPCION_PROPUESTAS` (programada) | |
| `tender.tenderPeriod.endDate` | `fecha_proceso` tipo `APERTURA_PROPUESTAS` | |
| `tender.enquiryPeriod.endDate` | `fecha_proceso` tipo `JUNTA_ACLARACIONES` | |
| `tender.awardPeriod.endDate` | `fecha_proceso` tipo `FALLO_ADJUDICACION` | |
| `tender.procuringEntity.id` | `"buyer-" + dependencia.id_dependencia` | |
| `tender.procuringEntity.name` | `dependencia.nombre` | |
| `tender.items[]` | `[{id:"item-1", description: descripcion_proyecto, classification: {...}}]` | Un solo ítem genérico (sin clasificación CPV específica). |

### `tender.status` derivado

| `estado_proceso` | OCDS `tender.status` |
|---|---|
| `BORRADOR` | `planning` |
| `PUBLICADA`, `EN_ACLARACIONES`, `RECEPCION_PROPUESTAS` | `active` |
| `EN_EVALUACION` | `active` |
| `ADJUDICADA` | `complete` |
| `DESIERTA` | `unsuccessful` |
| `CANCELADA` | `cancelled` |

### `tender.procurementMethod` derivado

| `tipo_procedimiento` | OCDS `procurementMethod` |
|---|---|
| `LICITACION_PUBLICA` | `open` |
| `INVITACION_RESTRINGIDA` | `selective` |
| `ADJUDICACION_DIRECTA` | `direct` |

## 3. Sección `awards[]`

Se incluye una entrada por contrato adjudicado (relación 1:1 actual).

| OCDS path | Origen | Notas |
|---|---|---|
| `awards[].id` | `"award-" + id_contrato` | |
| `awards[].title` | `"Adjudicación: " + descripcion_proyecto` | |
| `awards[].status` | derivado de `contrato.estatus` (ver tabla) | |
| `awards[].date` | `contrato.fecha_adjudicacion` | |
| `awards[].value.amount` | `contrato.monto_contrato` | |
| `awards[].value.currency` | `"MXN"` | |
| `awards[].suppliers[]` | `[{id: "supplier-" + id_proveedor, name: nombre_empresa}]` | |
| `awards[].contractPeriod.startDate` | `contrato.fecha_inicio` | |
| `awards[].contractPeriod.endDate` | `contrato.fecha_fin` | |

### `awards[].status` derivado

| `contrato.estatus` | OCDS `award.status` |
|---|---|
| `EN_FORMALIZACION`, `FIRMADO`, `EN_EJECUCION`, `FINALIZADO` | `active` |
| `RESCINDIDO`, `CANCELADO` | `cancelled` |

## 4. Sección `contracts[]`

| OCDS path | Origen | Notas |
|---|---|---|
| `contracts[].id` | `"contract-" + id_contrato` | |
| `contracts[].awardID` | `"award-" + id_contrato` | Match con `awards[].id` |
| `contracts[].title` | `contrato.numero_contrato` | |
| `contracts[].status` | derivado (ver tabla) | |
| `contracts[].value.amount` | `contrato.monto_contrato` | |
| `contracts[].value.currency` | `"MXN"` | |
| `contracts[].dateSigned` | `contrato.fecha_firma` (si existe) | |
| `contracts[].period.startDate` | `contrato.fecha_inicio` | |
| `contracts[].period.endDate` | `contrato.fecha_fin` | |

### `contracts[].status`

| `contrato.estatus` | OCDS `contract.status` |
|---|---|
| `EN_FORMALIZACION` | `pending` |
| `FIRMADO`, `EN_EJECUCION` | `active` |
| `FINALIZADO` | `terminated` |
| `RESCINDIDO`, `CANCELADO` | `cancelled` |

## 5. Sección `parties[]` y `buyer`

`parties[]` consolida todos los participantes mencionados en el release.

### Buyer (siempre presente)

```json
{
  "id": "buyer-{id_dependencia}",
  "name": "{dependencia.nombre}",
  "roles": ["buyer", "procuringEntity"]
}
```

### Procuring Entity

Misma `dependencia` con role `procuringEntity` (en este sistema buyer y procuringEntity coinciden).

### Suppliers

Una entrada por proveedor adjudicado:

```json
{
  "id": "supplier-{id_proveedor}",
  "name": "{proveedor.nombre_empresa}",
  "identifier": {
    "scheme": "MX-RFC",
    "id": "{proveedor.registro_fiscal}",
    "legalName": "{proveedor.nombre_empresa}"
  },
  "address": { "streetAddress": "{proveedor.domicilio}", "countryName": "Mexico" },
  "contactPoint": {
    "name": "{proveedor.representante_legal}",
    "telephone": "{proveedor.telefono}"
  },
  "roles": ["supplier"]
}
```

### Tenderers (si aplica)

Cualquier `proveedor` con `participacion` en la licitación se incluye con role `tenderer`. Si además fue adjudicado, lleva `["tenderer", "supplier"]`.

### `buyer` raíz

```json
"buyer": {
  "id": "buyer-{id_dependencia}",
  "name": "{dependencia.nombre}"
}
```

## 6. Release Package

Conforme a https://standard.open-contracting.org/1.1/en/schema/release_package/, la API expone también un paquete con:

```json
{
  "uri": "https://sgplopypc.up.railway.app/api/v1/datos-abiertos/release-package",
  "version": "1.1",
  "extensions": [],
  "publishedDate": "2026-05-27T22:30:00Z",
  "publisher": {
    "name": "SGPLOPyPC",
    "scheme": "MX-GOB",
    "uid": "SGPLOPyPC",
    "uri": "https://sgplopypc.up.railway.app"
  },
  "license": "https://creativecommons.org/licenses/by/4.0/",
  "publicationPolicy": "https://sgplopypc.up.railway.app/legal/datos-abiertos",
  "releases": [ /* array de releases */ ]
}
```

## 7. Endpoints expuestos

| Método | Ruta | Descripción |
|---|---|---|
| `GET` | `/api/v1/datos-abiertos/releases` | Lista paginada de releases (params: `page`, `limit`, `from`, `to`, `estado`). Respuesta envuelta en formato SGPLOPyPC estándar (`{success, message, data, errors}`) con `data.releases[]` y `data.pagination`. |
| `GET` | `/api/v1/datos-abiertos/releases/{ocid}` | Un release específico por OCID, formato OCDS puro (sin envoltura SGPLOPyPC) para máxima interoperabilidad. |
| `GET` | `/api/v1/datos-abiertos/release-package` | Paquete completo (todas las licitaciones publicadas) en formato OCDS Release Package puro. |

### Cabeceras

- `Access-Control-Allow-Origin: *` (CORS abierto)
- `Access-Control-Allow-Methods: GET, OPTIONS`
- `X-Content-Type-Options: nosniff`
- `Cache-Control: public, max-age=300` (5 min) en endpoints de lectura

### Rate limit

60 req/min por IP en cada endpoint público.

### Estados que se publican

Sólo se exponen licitaciones con estado **distinto de** `BORRADOR` (el borrador es interno).

## 8. Licencia y atribución

- Licencia de los datos: **CC BY 4.0** (Creative Commons Attribution).
- Publisher: **SGPLOPyPC** (SaaS de demostración / pruebas).
- Idioma: `es` (español).

## 9. Validación

Los releases se construyen para cumplir el schema oficial OCDS 1.1. Validación opcional con `jsonschema` contra:
- https://standard.open-contracting.org/schema/1__1__5/release-schema.json
- https://standard.open-contracting.org/schema/1__1__5/release-package-schema.json

## 10. Limitaciones conocidas

- No se incluyen items con clasificación CPV/CPC (se usa un único item genérico).
- No hay `documents[]` (los documentos asociados a una licitación viven en `storage/documents/` y aún no se exponen vía URL pública).
- No hay `milestones[]` derivados (sólo se reflejan fechas en `tender.*Period`).
- No hay extensiones OCDS aplicadas; se usa el core 1.1 sin extras.

Estas mejoras quedan como iteración futura.
