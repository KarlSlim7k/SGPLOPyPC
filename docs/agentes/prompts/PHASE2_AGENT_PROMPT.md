# Prompt para Agente IA — Ejecución Fase 2 (Núcleo Transaccional)

Copia y pega este prompt en tu agente IA para implementar la **Fase 2** del proyecto.

---

## PROMPT

Eres un agente de desarrollo senior trabajando en el repositorio **SGPLOPyPC**.

### Objetivo
Implementar la **Fase 2 — Núcleo transaccional** con enfoque incremental, estable y trazable:
1. CRUD de licitaciones y convocatorias.
2. Registro y validación de proveedores.
3. Flujo de participación y envío de propuestas.
4. Gestión documental inicial (uploads controlados).

### Contexto obligatorio a leer antes de codificar
- `docs/arquitectura/contexto.md`
- `docs/arquitectura/arquitectura_infraestructura.md`
- `docs/arquitectura/modelado_base_de_datos.md`
- `docs/agentes/AGENTS.md`
- `docs/producto/ROADMAP.md`
- `docs/guias/DESIGN.md`
- `docs/guias/DATABASE_GUIDELINES.md`
- `docs/guias/FRONTEND_GUIDELINES.md`
- Revisión del resultado de Fase 1 (commit: `d009a4418b12f2a223aeb36bca0688ba5f4be3fa`)

### Reglas de trabajo
1. Haz cambios pequeños y atómicos por módulo.
2. Respeta stack actual (PHP + MySQL/MariaDB + frontend vanilla).
3. No hardcodees secretos; usar variables de entorno.
4. Usa PDO con consultas preparadas.
5. Mantén control de acceso por rol (`PUBLICO`, `PROVEEDOR`, `ADMINISTRADOR`).
6. Toda API debe devolver formato JSON estándar y códigos HTTP consistentes.

### Formato estándar de respuesta API
- `success` (boolean)
- `message` (string)
- `data` (objeto/array/null)
- `errors` (array/null)

HTTP sugeridos:
- `200` consulta/actualización exitosa
- `201` creación exitosa
- `400` validación/request inválido
- `401` no autenticado
- `403` no autorizado
- `404` no encontrado
- `409` conflicto de negocio
- `422` error de validación semántica
- `500` error interno

---

## Entregables mínimos esperados (Fase 2)

### A) Módulo de Licitaciones / Convocatorias
Implementar endpoints (o completar existentes) en `/api/v1/licitaciones`:
- `GET /api/v1/licitaciones` (listado con filtros básicos: estado, tipo, dependencia)
- `GET /api/v1/licitaciones/{id}`
- `POST /api/v1/licitaciones`
- `PUT /api/v1/licitaciones/{id}`
- `PATCH /api/v1/licitaciones/{id}/estado` (transiciones válidas)

Reglas mínimas:
- `numero_licitacion` único.
- `presupuesto_estimado > 0`.
- Validar estados permitidos.
- Solo `ADMINISTRADOR` puede crear/editar/cambiar estado.

### B) Módulo de Proveedores
Implementar `/api/v1/proveedores`:
- `POST /api/v1/proveedores` (registro)
- `GET /api/v1/proveedores/{id}`
- `PUT /api/v1/proveedores/{id}`
- `PATCH /api/v1/proveedores/{id}/estatus` (PENDIENTE/VALIDADO/RECHAZADO/SUSPENDIDO)
- `GET /api/v1/proveedores` (solo admin)

Reglas mínimas:
- `registro_fiscal` único.
- Un `usuario` solo puede tener un perfil de proveedor.
- Cambio de estatus solo por `ADMINISTRADOR`.

### C) Participación y Propuestas
Implementar:
- `POST /api/v1/licitaciones/{id}/participaciones` (proveedor se inscribe)
- `GET /api/v1/licitaciones/{id}/participaciones`
- `POST /api/v1/participaciones/{id}/propuesta` (una propuesta por participación)
- `GET /api/v1/propuestas/{id}`

Reglas mínimas:
- Un proveedor no puede inscribirse dos veces en misma licitación.
- Solo proveedor autenticado puede inscribirse/enviar su propuesta.
- Validar que licitación esté en estado que permita participación/propuesta.

### D) Gestión documental inicial
Implementar carga controlada de documentos (MVP):
- `POST /api/v1/documentos/upload`
- `GET /api/v1/documentos/{id}` (metadatos)

Mínimos de seguridad:
- Validar tipo MIME permitido y tamaño máximo.
- Sanitizar nombre de archivo y ruta.
- Guardar metadatos en BD (`documento`) con asociación a contexto.
- Bloquear acceso no autorizado a documentos sensibles.

### E) Auditoría básica
Para operaciones críticas (crear/editar licitación, alta/cambio estatus proveedor, alta propuesta):
- Registrar evento en `historial_cambio` (actor, acción, tabla, id, timestamp, valores relevantes).

---

## Criterios de aceptación
1. CRUD funcional de licitaciones con validaciones y permisos.
2. Registro/gestión de proveedores con reglas de unicidad y roles.
3. Inscripción y propuesta funcionando con reglas de negocio.
4. Upload documental inicial seguro y trazable.
5. Auditoría mínima en operaciones críticas.
6. Endpoints probados con curl o colección equivalente.
7. Documentación de endpoints y ejemplo de payloads actualizada en `docs/`.

## Plan de ejecución (obligatorio)
Antes de codificar:
1. Resumir estado actual (máx 10 bullets).
2. Proponer plan de 6–10 pasos.
3. Implementar por iteraciones pequeñas, una por módulo.

## Validaciones mínimas a ejecutar
- Sintaxis PHP de archivos nuevos/modificados.
- Pruebas manuales con curl para:
  - licitaciones
  - proveedores
  - participaciones/propuestas
  - upload documentos
- Verificar respuestas JSON estándar + códigos HTTP.
- Validar control de acceso por rol.

## Restricción de cierre (MUY IMPORTANTE)
Al finalizar:
1. Haz **un solo commit** con mensaje claro.
2. En tu respuesta final, muestra **únicamente**:
   - una línea confirmando que terminaste,
   - y el **hash completo** del commit.
3. No agregues texto adicional.

Formato exacto de salida final esperado:

Terminado.
Commit: <HASH_COMPLETO_DE_40_CARACTERES>

