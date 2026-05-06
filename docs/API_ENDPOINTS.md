# API Endpoints — SGPLOPyPC

Documentación de endpoints REST JSON de la API backend (`/api/v1`).

## Formato de respuesta

Todas las respuestas siguen el formato estándar:

```json
{
  "success": boolean,
  "message": string,
  "data": object | array | null,
  "errors": array | null
}
```

Códigos HTTP usados:
- `200` — Operación exitosa
- `201` — Recurso creado
- `400` — Solicitud inválida
- `401` — No autenticado
- `403` — No autorizado
- `404` — Recurso no encontrado
- `409` — Conflicto de negocio
- `422` — Validación semántica
- `500` — Error interno

---

## Autenticación

### POST /api/v1/auth/login
Inicia sesión y devuelve token JWT.

**Body:**
```json
{
  "email": "string",
  "contrasena": "string"
}
```

**Response 200:**
```json
{
  "success": true,
  "message": "Inicio de sesión exitoso",
  "data": { "token": "jwt..." }
}
```

### GET /api/v1/me
Devuelve información del usuario autenticado.

**Headers:** `Authorization: Bearer <token>`

---

## Licitaciones

### GET /api/v1/licitaciones
Lista licitaciones con filtros opcionales (`estado`, `tipo`, `dependencia`).

### GET /api/v1/licitaciones/{id}
Obtiene una licitación por ID.

### POST /api/v1/licitaciones
Crea una nueva licitación (ADMINISTRADOR).

**Body:**
```json
{
  "numero_licitacion": "string",
  "id_dependencia": 1,
  "tipo_procedimiento": "LICITACION_PUBLICA | INVITACION_RESTRINGIDA | ADJUDICACION_DIRECTA",
  "descripcion_proyecto": "string",
  "presupuesto_estimado": 1000000.00,
  "ubicacion_proyecto": "string (opcional)",
  "estado_proceso": "BORRADOR (default)"
}
```

### PUT /api/v1/licitaciones/{id}
Actualiza datos de una licitación (ADMINISTRADOR).

### PATCH /api/v1/licitaciones/{id}/estado
Cambia el estado del proceso con validación de transiciones (ADMINISTRADOR).

**Body:**
```json
{ "estado_proceso": "EN_EVALUACION" }
```

Transiciones permitidas:
- `BORRADOR` → `PUBLICADA`, `CANCELADA`
- `PUBLICADA` → `EN_ACLARACIONES`, `RECEPCION_PROPUESTAS`, `CANCELADA`
- `EN_ACLARACIONES` → `RECEPCION_PROPUESTAS`, `CANCELADA`
- `RECEPCION_PROPUESTAS` → `EN_EVALUACION`, `DESIERTA`, `CANCELADA`
- `EN_EVALUACION` → `ADJUDICADA`, `DESIERTA`, `CANCELADA`
- `ADJUDICADA` → (sin salida)
- `DESIERTA` → (sin salida)
- `CANCELADA` → (sin salida)

### POST /api/v1/licitaciones/{id}/adjudicar
Adjudica la licitación seleccionando automáticamente la propuesta ganadora (mayor `puntaje_total` con dictamen `SOLVENTE`).

**Reglas:**
- Requiere estado `EN_EVALUACION`.
- Actualiza estados: `licitacion` → `ADJUDICADA`, participación ganadora → `GANADOR`, demás → `NO_GANADOR`, propuesta ganadora → `ACEPTADA`, demás → `RECHAZADA`.
- Genera registros de auditoría.

**Response 200:**
```json
{
  "success": true,
  "message": "Licitación adjudicada exitosamente",
  "data": { "id_propuesta_ganadora": 1 }
}
```

---

## Proveedores

### GET /api/v1/proveedores
Lista proveedores.

### GET /api/v1/proveedores/{id}
Obtiene un proveedor.

### POST /api/v1/proveedores
Registra un proveedor.

### PUT /api/v1/proveedores/{id}
Actualiza proveedor.

### PATCH /api/v1/proveedores/{id}/estatus
Cambia estatus de validación (`PENDIENTE`, `VALIDADO`, `RECHAZADO`, `SUSPENDIDO`).

---

## Participaciones y Propuestas

### GET /api/v1/licitaciones/{id}/participaciones
Lista participaciones de una licitación (ADMINISTRADOR).

### POST /api/v1/licitaciones/{id}/participaciones
Inscribe al proveedor autenticado en una licitación (PROVEEDOR).

### POST /api/v1/participaciones/{id}/propuesta
Envía propuesta para una participación (PROVEEDOR).

**Body:**
```json
{
  "monto_propuesta": 950000.00,
  "descripcion_tecnica": "string (opcional)"
}
```

### GET /api/v1/propuestas/{id}
Obtiene propuesta (ADMINISTRADOR o proveedor dueño).

---

## Evaluaciones

### POST /api/v1/evaluaciones
Crea una evaluación para una propuesta (ADMINISTRADOR).

**Body:**
```json
{
  "id_propuesta": 1,
  "puntaje_tecnico": 80.00,
  "puntaje_economico": 90.00,
  "observaciones": "string (opcional)"
}
```

**Reglas:**
- Una sola evaluación por propuesta (`uq_evaluacion_propuesta`).
- Puntajes entre `0` y `100`.
- `puntaje_total` se calcula automáticamente como `puntaje_tecnico + puntaje_economico`.

**Response 201:**
```json
{ "success": true, "message": "Evaluación creada exitosamente", "data": { "id_evaluacion": 1 } }
```

### GET /api/v1/evaluaciones/{id}
Obtiene una evaluación (ADMINISTRADOR).

### PUT /api/v1/evaluaciones/{id}
Ajusta puntajes u observaciones de una evaluación antes del cierre (ADMINISTRADOR).

**Body:**
```json
{
  "puntaje_tecnico": 85.00,
  "puntaje_economico": 88.00,
  "observaciones": "Ajuste fino"
}
```

### POST /api/v1/evaluaciones/{id}/dictamen
Emite el dictamen final de una evaluación (ADMINISTRADOR).

**Body:**
```json
{
  "dictamen": "SOLVENTE | NO_SOLVENTE | DESCALIFICADA",
  "observaciones": "string (opcional)"
}
```

---

## Contratos

### POST /api/v1/contratos
Crea un contrato a partir de una licitación adjudicada (ADMINISTRADOR).

**Body:**
```json
{
  "id_licitacion": 1,
  "id_proveedor": 1,
  "numero_contrato": "CT-2026-001",
  "monto_contrato": 950000.00,
  "fecha_adjudicacion": "2026-05-06",
  "fecha_inicio": "2026-05-10 (opcional)",
  "fecha_fin": "2026-12-31 (opcional)",
  "estatus": "EN_FORMALIZACION (default)"
}
```

**Reglas:**
- `id_licitacion` único en contrato (`uq_contrato_licitacion`).
- `numero_contrato` único (`uq_contrato_numero`).
- `monto_contrato > 0`.
- `fecha_fin >= fecha_inicio` (si ambas existen).
- La licitación debe estar en estado `ADJUDICADA`.

**Response 201:**
```json
{ "success": true, "message": "Contrato creado exitosamente", "data": { "id_contrato": 1 } }
```

### GET /api/v1/contratos/{id}
Obtiene un contrato (ADMINISTRADOR).

### PUT /api/v1/contratos/{id}
Ajusta metadatos del contrato (ADMINISTRADOR).

**Body (campos opcionales):**
```json
{
  "numero_contrato": "CT-2026-001-A",
  "monto_contrato": 960000.00,
  "fecha_adjudicacion": "2026-05-06",
  "fecha_inicio": "2026-05-10",
  "fecha_fin": "2026-11-30"
}
```

### PATCH /api/v1/contratos/{id}/estatus
Cambia el estatus del contrato (ADMINISTRADOR).

**Body:**
```json
{ "estatus": "VIGENTE | EN_EJECUCION | CONCLUIDO | RESCINDIDO" }
```

---

## Documentos

### POST /api/v1/documentos/upload
Sube un documento asociado a una entidad.

### GET /api/v1/documentos/{id}
Obtiene metadatos de un documento.

---

## Auditoría

Todas las operaciones críticas generan registros en `historial_cambio`:
- Creación/actualización de evaluación.
- Emisión de dictamen.
- Cambio de estado de licitación.
- Creación/actualización de contrato.
- Adjudicación del proveedor ganador.

Campos registrados: actor (`id_usuario`), acción (`CREAR`/`ACTUALIZAR`), tabla, id del registro, timestamp, snapshot antes/después, IP origen.
