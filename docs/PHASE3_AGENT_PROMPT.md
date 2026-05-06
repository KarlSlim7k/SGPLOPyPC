# Prompt para Agente IA — Ejecución Fase 3 (Evaluación y Adjudicación)

Copia y pega este prompt en tu agente IA para implementar la **Fase 3** del proyecto.

---

## PROMPT

Eres un agente de desarrollo senior trabajando en el repositorio **SGPLOPyPC**.

### Objetivo
Implementar la **Fase 3 — Evaluación y Adjudicación** con enfoque incremental, seguro y auditable:
1. Módulo de evaluación técnica/económica.
2. Dictámenes y control de estado del proceso.
3. Adjudicación y generación/registro de contrato.
4. Auditoría en operaciones críticas del flujo.

### Contexto obligatorio a leer antes de codificar
- `docs/contexto.md`
- `docs/arquitectura_infraestructura.md`
- `docs/modelado_base_de_datos.md`
- `docs/AGENTS.md`
- `docs/ROADMAP.md`
- `docs/DESIGN.md`
- `docs/DATABASE_GUIDELINES.md`
- `docs/FRONTEND_GUIDELINES.md`
- Revisión del resultado de Fase 2 (commit: `600737638dc91cc454b6d7eb10575bbecb5ad34a`)

### Reglas de trabajo
1. Haz cambios pequeños y trazables por módulo.
2. Respeta stack actual (PHP + MySQL/MariaDB + frontend vanilla).
3. Usa variables de entorno para configuración sensible.
4. Acceso a BD exclusivamente con PDO y sentencias preparadas.
5. Mantén y valida control de acceso por rol (`PUBLICO`, `PROVEEDOR`, `ADMINISTRADOR`).
6. Conserva formato de respuesta JSON estándar en toda la API.

### Formato estándar de respuesta API
- `success` (boolean)
- `message` (string)
- `data` (objeto/array/null)
- `errors` (array/null)

HTTP sugeridos:
- `200` operación exitosa
- `201` recurso creado
- `400` solicitud inválida
- `401` no autenticado
- `403` no autorizado
- `404` recurso no encontrado
- `409` conflicto de negocio
- `422` validación semántica
- `500` error interno

---

## Entregables mínimos esperados (Fase 3)

### A) Módulo de Evaluación
Implementar/completar endpoints en `/api/v1/evaluaciones`:
- `POST /api/v1/evaluaciones` (crear evaluación de propuesta)
- `GET /api/v1/evaluaciones/{id}`
- `PUT /api/v1/evaluaciones/{id}` (ajuste de evaluación antes de cierre)
- `POST /api/v1/evaluaciones/{id}/dictamen` (emitir dictamen final)

Reglas mínimas:
- Una propuesta solo puede tener una evaluación principal (unicidad por `id_propuesta`).
- Solo `ADMINISTRADOR` puede evaluar o dictaminar.
- Validar rangos de puntajes (no negativos y dentro del rango definido por el sistema).
- Calcular o validar `puntaje_total` de forma consistente.

### B) Flujo y estado del proceso
Implementar transición controlada de licitación durante evaluación:
- Permitir pasar a `EN_EVALUACION` cuando corresponda.
- Permitir pasar a `ADJUDICADA` únicamente con evaluación/dictamen válidos.
- Permitir marcar `DESIERTA` o `CANCELADA` con motivo registrado.

Reglas mínimas:
- No permitir adjudicar sin propuesta válida y dictamen solvente.
- Validar transiciones permitidas para evitar saltos inválidos de estado.

### C) Adjudicación y Contrato
Implementar `/api/v1/contratos` (o completar existente):
- `POST /api/v1/contratos` (crear contrato desde licitación adjudicada)
- `GET /api/v1/contratos/{id}`
- `PUT /api/v1/contratos/{id}` (ajustes de metadatos permitidos)
- `PATCH /api/v1/contratos/{id}/estatus`

Reglas mínimas:
- Una licitación solo puede generar un contrato (`id_licitacion` único en contrato).
- `numero_contrato` único.
- `monto_contrato > 0`.
- Coherencia de fechas (`fecha_fin >= fecha_inicio` si ambas existen).
- Solo `ADMINISTRADOR` puede crear o cambiar estatus de contrato.

### D) Integración evaluación ↔ adjudicación
Implementar lógica transaccional:
- Selección de propuesta ganadora según reglas definidas (dictamen/puntaje/criterio establecido).
- Actualizar estatus relacionados (`participacion`, `propuesta`, `licitacion`) de forma consistente.
- Evitar estados huérfanos o inconsistentes ante errores (usar transacciones DB cuando aplique).

### E) Auditoría reforzada
Registrar en `historial_cambio` al menos:
- creación/actualización de evaluación,
- emisión de dictamen,
- cambio de estado de licitación,
- creación/actualización de contrato,
- adjudicación del proveedor ganador.

Campos mínimos: actor, acción, tabla, id, timestamp, resumen de cambios.

---

## Criterios de aceptación
1. Flujo de evaluación funcional con permisos por rol.
2. Dictamen final registrado y utilizable para adjudicación.
3. Adjudicación consistente con reglas de negocio.
4. Contrato generado con validaciones de unicidad e integridad.
5. Transiciones de estado de licitación controladas y auditadas.
6. Endpoints probados manualmente (curl o equivalente) con casos felices y errores esperados.
7. Documentación de endpoints y payloads actualizada en `docs/`.

## Plan de ejecución (obligatorio)
Antes de codificar:
1. Resumir estado actual (máx 10 bullets).
2. Proponer plan de 6–10 pasos.
3. Ejecutar por iteraciones pequeñas (evaluación → dictamen → adjudicación → contrato → auditoría).

## Validaciones mínimas a ejecutar
- Sintaxis PHP de archivos nuevos/modificados.
- Pruebas manuales de endpoints:
  - evaluaciones,
  - dictamen,
  - cambio de estado de licitación,
  - contratos,
  - flujo completo de adjudicación.
- Verificar respuestas JSON estándar + códigos HTTP.
- Verificar restricciones de negocio (unicidad, integridad, roles).

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

