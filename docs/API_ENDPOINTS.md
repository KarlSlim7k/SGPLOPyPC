# API Endpoints — SGPLOPyPC

Documentación de endpoints REST JSON de la API backend (`/api/v1`).

Contrato OpenAPI público (Fase 5):
- `docs/openapi-public.yaml`

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

### PUT /api/v1/me/profile
Actualiza nombre y correo del usuario autenticado.

**Headers:** `Authorization: Bearer <token>`

### POST /api/v1/me/password
Cambia la contraseña del usuario autenticado.

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

### GET /api/v1/participaciones
Lista general de participaciones (ADMINISTRADOR) con paginación y filtros.

Query params opcionales:
- `page`, `limit`
- `licitacion` (id_licitacion)
- `estatus`
- `q` (razón social, RFC o número de licitación)

### GET /api/v1/participaciones/mias
Lista participaciones del proveedor autenticado (PROVEEDOR) con paginación y filtros.

Query params opcionales:
- `page`, `limit`
- `estatus`
- `q` (número de licitación, descripción o dependencia)

Incluye datos de licitación, estatus de participación y, si existe, datos básicos de propuesta.

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

### GET /api/v1/propuestas/mias
Lista propuestas del proveedor autenticado (PROVEEDOR) con paginación y filtros.

Query params opcionales:
- `page`, `limit`
- `estatus`
- `q` (número de licitación, descripción o dependencia)

Incluye datos de la licitación, estatus de participación y seguimiento de propuesta.

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

### GET /api/v1/contratos/mios
Lista contratos del proveedor autenticado (PROVEEDOR) con paginación y filtros.

**Query params:**
- `page`: número de página (default `1`)
- `limit`: tamaño de página, máximo `100` (default `20`)
- `estatus`: `EN_FORMALIZACION | VIGENTE | EN_EJECUCION | CONCLUIDO | RESCINDIDO`
- `q`: búsqueda por número de contrato, número de licitación, descripción o dependencia
- `id_contrato`: filtra un contrato propio y agrega `documentos` al primer item para la vista de detalle

**Response 200:**
```json
{
  "success": true,
  "message": "Mis contratos",
  "data": {
    "items": [
      {
        "id_contrato": 1,
        "id_licitacion": 1,
        "numero_contrato": "CT-2026-001",
        "monto_contrato": "950000.00",
        "fecha_adjudicacion": "2026-05-06",
        "fecha_inicio": "2026-05-10",
        "fecha_fin": "2026-12-31",
        "estatus": "VIGENTE",
        "numero_licitacion": "LO-001-2026",
        "descripcion_proyecto": "Obra pública",
        "estado_proceso": "ADJUDICADA",
        "dependencia_nombre": "Secretaría de Obras",
        "documentos": []
      }
    ],
    "total": 1,
    "page": 1,
    "limit": 20
  }
}
```

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

Reglas para PROVEEDOR:
- `DOC_LEGAL_PROVEEDOR` se asocia automáticamente al proveedor autenticado si no se envía `id_proveedor`.
- `PROPUESTA_TECNICA`, `PROPUESTA_ECONOMICA` y `DOC_COMPLEMENTARIA` requieren `id_propuesta` propia.

### GET /api/v1/documentos/mios
Lista documentos del proveedor autenticado (PROVEEDOR).

Query params opcionales:
- `page`, `limit`
- `context` (`proveedor` o `propuesta`)
- `id_propuesta`
- `tipo_documento`

### GET /api/v1/documentos/{id}
Obtiene metadatos de un documento.

### GET /api/v1/documentos/{id}/download
Descarga autenticada del archivo si el usuario tiene acceso.

---

## Transparencia Pública (sin autenticación)

### GET /api/v1/public/convocatorias
Lista convocatorias públicas con paginación y filtros.

Query params opcionales:
- `page`, `limit`
- `sort` (`fecha_creacion`, `numero_licitacion`, `tipo_procedimiento`, `presupuesto_estimado`)
- `order` (`ASC`, `DESC`)
- `q`, `estado`, `tipo`, `dependencia`, `year`

### GET /api/v1/public/convocatorias/{id}
Obtiene detalle de convocatoria pública.

### GET /api/v1/public/convocatorias/{id}/documentos
Lista documentos públicos de una convocatoria.

### GET /api/v1/public/documentos/{id}/download
Descarga un documento público.

### GET /api/v1/public/resultados
Resultados de adjudicación (con paginación, `q`).

### GET /api/v1/public/contratos
Contratos públicos (con paginación y filtros `estatus`, `year`).

### GET /api/v1/public/evaluaciones
Procesos públicos en `RECEPCION_PROPUESTAS` y `EN_EVALUACION`.

### GET /api/v1/public/historial
Historial de licitaciones concluidas con filtros (`year`, `tipo`, `q`).

### GET /api/v1/public/estadisticas
Devuelve KPIs públicos principales.

Incluye:
- `proveedores_registrados_total`: total de proveedores registrados (todos los estatus).
- `proveedores_activos`: proveedores en `PENDIENTE` o `VALIDADO`.
- `proveedores_registrados`: alias de compatibilidad mapeado a `proveedores_registrados_total`.

### POST /api/v1/public/proveedores/registro
Registro público transaccional:
- crea `usuario` rol `PROVEEDOR`
- crea `proveedor` con estatus `PENDIENTE`
- devuelve JWT + usuario + proveedor

Body mínimo relevante:
```json
{
  "nombre_empresa": "Constructora Demo SA de CV",
  "representante_legal": "Nombre Apellido",
  "registro_fiscal": "AAA010101AAA",
  "regimen_fiscal": "601",
  "domicilio": "Calle 1, Ciudad, Estado",
  "nombre_contacto": "Nombre Apellido",
  "cargo": "Director",
  "email": "proveedor@demo.mx",
  "telefono": "5551234567",
  "password": "passwordSegura123",
  "accepted_terms": true
}
```

Compatibilidad legacy:
- Se acepta `terms: true` si `accepted_terms` no viene en el payload.
- Si ambos vienen, prevalece `accepted_terms`.
- `terms` queda deprecado y se recomienda usar únicamente `accepted_terms`.

### POST /api/v1/public/soporte
Registra ticket de soporte público y devuelve `folio`.

---

## Recuperación de contraseña

### POST /api/v1/auth/password/forgot
Solicita recuperación por correo/token con respuesta neutra.

Body:
```json
{ "email": "usuario@dominio.mx" }
```

### POST /api/v1/auth/password/reset
Restablece contraseña usando token válido.

Body:
```json
{
  "token": "token-recibido",
  "password": "NuevaPassword#2026"
}
```


## Auditoría

Todas las operaciones críticas generan registros en `historial_cambio`:
- Creación/actualización de evaluación.
- Emisión de dictamen.
- Cambio de estado de licitación.
- Creación/actualización de contrato.
- Adjudicación del proveedor ganador.

Campos registrados: actor (`id_usuario`), acción (`CREAR`/`ACTUALIZAR`), tabla, id del registro, timestamp, snapshot antes/después, IP origen.

---

## Reportes y Dashboard (ADMINISTRADOR)

### GET /api/v1/reportes/dashboard/resumen
Devuelve métricas clave del sistema.

**Headers:** `Authorization: Bearer <token>`

**Response 200:**
```json
{
  "success": true,
  "message": "Resumen del dashboard",
  "data": {
    "total_licitaciones": 50,
    "total_adjudicadas": 12,
    "total_publicadas": 8,
    "total_proveedores": 30,
    "total_contratos": 12,
    "total_participaciones": 80,
    "total_propuestas": 65,
    "tiempo_promedio_publicacion_adjudicacion_dias": 45.5
  }
}
```

### GET /api/v1/reportes/dashboard/licitaciones-por-estado
Devuelve conteo de licitaciones agrupadas por estado.

**Response 200:**
```json
{
  "success": true,
  "message": "Licitaciones por estado",
  "data": [
    { "estado_proceso": "PUBLICADA", "cantidad": 8 },
    { "estado_proceso": "ADJUDICADA", "cantidad": 12 }
  ]
}
```

### GET /api/v1/reportes/dashboard/participacion-proveedores
Indicadores de participación.

**Response 200:**
```json
{
  "success": true,
  "message": "Participación de proveedores",
  "data": {
    "proveedores_inscritos": 80,
    "propuestas_enviadas": 65,
    "tasa_participacion_pct": 81.25
  }
}
```

### GET /api/v1/reportes/dashboard/adjudicaciones-por-periodo
Adjudicaciones en un rango de fechas.

**Query params:** `from=YYYY-MM-DD&to=YYYY-MM-DD`

**Response 200:**
```json
{
  "success": true,
  "message": "Adjudicaciones por periodo",
  "data": [
    { "fecha": "2026-04-15", "cantidad": 2, "monto_total": 1500000.00 }
  ]
}
```

---

## Exportaciones (ADMINISTRADOR)

### GET /api/v1/reportes/export/licitaciones.csv
Exporta licitaciones a CSV con filtros.

**Query params:**
- `estado` — filtrar por estado del proceso
- `dependencia` — ID de dependencia
- `from` — fecha creación desde (YYYY-MM-DD)
- `to` — fecha creación hasta (YYYY-MM-DD)

**Headers:** `Authorization: Bearer <token>`

**Response 200:** archivo CSV con BOM UTF-8.

**Auditoría:** se registra quién exportó, tipo y cantidad de registros.

> PDF y XLSX quedan documentados como backlog técnico futuro.

---

## Transparencia Pública (sin autenticación)

### GET /api/v1/public/convocatorias
Lista pública de convocatorias publicadas.

**Query params:**
- `page` (default: 1)
- `limit` (default: 20, max: 100)
- `sort` — `fecha_creacion` | `numero_licitacion` | `tipo_procedimiento`
- `order` — `ASC` | `DESC`

**Response 200:**
```json
{
  "success": true,
  "message": "Listado público de convocatorias",
  "data": {
    "items": [...],
    "total": 40,
    "page": 1,
    "limit": 20
  }
}
```

### GET /api/v1/public/convocatorias/{id}
Detalle público de una convocatoria.

### GET /api/v1/public/resultados
Resultados de adjudicación públicos.

**Query params:** `page`, `limit`

### GET /api/v1/public/contratos
Contratos públicos (sin datos sensibles de proveedor).

**Query params:** `page`, `limit`

**Reglas:**
- No expone datos personales, credenciales ni archivos restringidos.
- Oculta licitaciones en `BORRADOR` y `CANCELADA`.

---

## Historial de Licitación

### GET /api/v1/licitaciones/{id}/historial
Devuelve eventos trazables de una licitación.

**Headers:** `Authorization: Bearer <token>`

**Incluye:**
- Cambios de estado (auditoría)
- Evaluaciones y dictámenes
- Creación de contrato
- Documentos públicos asociados

**Response 200:**
```json
{
  "success": true,
  "message": "Historial de la licitación",
  "data": [
    {
      "id_historial": 1,
      "usuario_nombre": "Admin",
      "accion": "ACTUALIZAR",
      "valores_nuevos": { "estado_proceso": "ADJUDICADA" },
      "fecha_accion": "2026-04-10 14:30:00",
      "tipo_evento": "AUDITORIA"
    }
  ]
}
```

**Auditoría:** se registra el acceso al historial.

---

## Notificaciones

### POST /api/v1/notificaciones
Crea una notificación (ADMINISTRADOR o eventos del sistema).

**Headers:** `Authorization: Bearer <token>`

**Body:**
```json
{
  "id_usuario_destino": 2,
  "id_licitacion": 5,
  "tipo_notificacion": "CONVOCATORIA_PUBLICADA | ACLARACION | RESULTADO_EVALUACION | ADJUDICACION | CAMBIO_ESTADO | GENERAL",
  "titulo": "string",
  "mensaje": "string"
}
```

### GET /api/v1/notificaciones/mias
Lista las notificaciones del usuario autenticado con datos relacionados y `accion_principal` cuando se puede enlazar a licitación, propuesta o contrato.

**Headers:** `Authorization: Bearer <token>`

### PATCH /api/v1/notificaciones/{id}/leida
Marca una notificación como leída.

**Reglas:**
- Solo el destinatario puede marcarla como leída.
- Valida pertenencia.

**Response 200 / 403 / 404**

**Auditoría:** se registra la lectura.

---

## Soporte (ADMINISTRADOR)

### GET /api/v1/soporte/tickets
Lista tickets de soporte con paginación y filtros.

Query params opcionales:
- `page`, `limit`
- `estado` (`NUEVO`, `EN_PROCESO`, `CERRADO`)
- `q` (folio, nombre, correo o asunto)

Devuelve también `resumen` global por estado.

### GET /api/v1/soporte/tickets/{id}
Obtiene el detalle completo de un ticket de soporte.

### PATCH /api/v1/soporte/tickets/{id}/estado
Actualiza el estado operativo de un ticket.

Body:
```json
{ "estado": "NUEVO | EN_PROCESO | CERRADO" }
```

**Auditoría:** registra cambios de estado en `historial_cambio`.

**Notificación opcional por correo:** si `MAIL_ENABLED=1` y `SUPPORT_NOTIFY_STATUS_CHANGE=1`, se envía correo al solicitante cuando cambia el estado.

### Flujo operativo recomendado (SLA)
- `NUEVO`: ticket recibido, pendiente de primera revisión.
- `EN_PROCESO`: ticket en análisis/atención activa.
- `CERRADO`: solicitud resuelta o concluida.

Objetivos sugeridos de atención:
- Primera respuesta (mover a `EN_PROCESO`): <= 1 día hábil.
- Resolución objetivo (mover a `CERRADO`): <= 3 días hábiles.
