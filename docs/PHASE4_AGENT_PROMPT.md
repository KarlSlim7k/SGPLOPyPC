# Prompt para Agente IA — Ejecución Fase 4 (Reportes y Transparencia)

Copia y pega este prompt en tu agente IA para implementar la **Fase 4** del proyecto.

---

## PROMPT

Eres un agente de desarrollo senior trabajando en el repositorio **SGPLOPyPC**.

### Objetivo
Implementar la **Fase 4 — Reportes y Transparencia** con foco en visibilidad operativa, acceso público controlado y exportación de información:
1. Tableros e indicadores principales.
2. Exportaciones CSV/PDF/Excel.
3. Historial y consulta pública de procesos.
4. Notificaciones a proveedores.

### Contexto obligatorio a leer antes de codificar
- `docs/contexto.md`
- `docs/arquitectura_infraestructura.md`
- `docs/modelado_base_de_datos.md`
- `docs/AGENTS.md`
- `docs/ROADMAP.md`
- `docs/DESIGN.md`
- `docs/DATABASE_GUIDELINES.md`
- `docs/FRONTEND_GUIDELINES.md`
- Revisión del resultado de Fase 3 (commit: `6a7229cc6dd9868832513027c798db02c76df729`)

### Reglas de trabajo
1. Haz cambios incrementales y trazables por módulo.
2. Respeta stack actual (PHP + MySQL/MariaDB + frontend vanilla).
3. Mantén formato de respuesta API estándar en JSON.
4. Aplica control de acceso por rol en endpoints sensibles.
5. No exponer datos personales/sensibles en endpoints públicos.
6. Registrar auditoría en operaciones críticas de reportes/exportaciones/notificaciones.

### Formato estándar de respuesta API
- `success` (boolean)
- `message` (string)
- `data` (objeto/array/null)
- `errors` (array/null)

HTTP sugeridos:
- `200` operación exitosa
- `201` recurso creado
- `400` request inválido
- `401` no autenticado
- `403` no autorizado
- `404` no encontrado
- `409` conflicto
- `422` validación semántica
- `500` error interno

---

## Entregables mínimos esperados (Fase 4)

### A) Tablero e indicadores (admin)
Implementar endpoints en `/api/v1/reportes/dashboard`:
- `GET /api/v1/reportes/dashboard/resumen`
- `GET /api/v1/reportes/dashboard/licitaciones-por-estado`
- `GET /api/v1/reportes/dashboard/participacion-proveedores`
- `GET /api/v1/reportes/dashboard/adjudicaciones-por-periodo?from=YYYY-MM-DD&to=YYYY-MM-DD`

Indicadores mínimos sugeridos:
- Total de licitaciones por estado.
- Tasa de participación (proveedores inscritos vs propuestas enviadas).
- Licitaciones adjudicadas por periodo.
- Tiempo promedio entre publicación y adjudicación.

Reglas:
- Solo `ADMINISTRADOR` para métricas operativas internas.
- Validar rango de fechas y límites de consulta.

### B) Exportaciones
Implementar `/api/v1/reportes/export`:
- `GET /api/v1/reportes/export/licitaciones.csv`
- `GET /api/v1/reportes/export/licitaciones.xlsx` (si se habilita librería)
- `GET /api/v1/reportes/export/licitaciones.pdf` (si se habilita librería)

Mínimos:
- Exportar con filtros (estado, dependencia, rango fecha).
- Sanitizar parámetros para evitar abuso/inyección.
- Registrar en auditoría quién exportó, qué tipo y cuándo.
- Si PDF/XLSX no está listo, entregar CSV robusto + backlog documentado.

### C) Transparencia pública
Implementar endpoints públicos de consulta (sin datos sensibles):
- `GET /api/v1/public/convocatorias`
- `GET /api/v1/public/convocatorias/{id}`
- `GET /api/v1/public/resultados`
- `GET /api/v1/public/contratos`

Reglas:
- Solo mostrar campos permitidos para transparencia.
- Ocultar datos personales, credenciales, archivos restringidos y metadatos sensibles.
- Implementar paginación básica (`page`, `limit`) y ordenación segura.

### D) Historial del proceso
Implementar endpoint de historial trazable por licitación:
- `GET /api/v1/licitaciones/{id}/historial`

Debe incluir al menos:
- cambios de estado,
- eventos de evaluación/dictamen,
- adjudicación,
- creación de contrato,
- referencias de documentos públicos (si aplica).

### E) Notificaciones a proveedores
Implementar módulo básico `/api/v1/notificaciones`:
- `POST /api/v1/notificaciones` (creación por admin o eventos del sistema)
- `GET /api/v1/notificaciones/mias`
- `PATCH /api/v1/notificaciones/{id}/leida`

Eventos mínimos a notificar:
- convocatoria publicada,
- aclaración relevante,
- resultado de evaluación,
- adjudicación,
- cambio de estado significativo.

Reglas:
- Solo destinatario puede marcar como leída su notificación.
- Validar pertenencia y permisos.

### F) Auditoría
Registrar en `historial_cambio` operaciones críticas:
- generación de reportes/exportaciones,
- publicación/actualización de resultados públicos,
- creación/envío/lectura de notificaciones,
- accesos relevantes al historial de licitación (si política lo requiere).

---

## Criterios de aceptación
1. Dashboard funcional con métricas clave y filtros básicos.
2. Exportación CSV operativa (PDF/XLSX opcional según disponibilidad técnica documentada).
3. Endpoints públicos de transparencia funcionales y sin fuga de datos sensibles.
4. Historial por licitación disponible y consistente con eventos del proceso.
5. Notificaciones básicas funcionando por destinatario.
6. Endpoints probados con curl (o equivalente) para casos exitosos y de error.
7. Documentación de endpoints, parámetros y ejemplos actualizada en `docs/`.

## Plan de ejecución (obligatorio)
Antes de codificar:
1. Resumir estado actual (máx 10 bullets).
2. Proponer plan de 6–10 pasos.
3. Ejecutar por iteraciones: dashboard → exportaciones → público → historial → notificaciones → auditoría.

## Validaciones mínimas a ejecutar
- Sintaxis PHP de archivos nuevos/modificados.
- Pruebas manuales de endpoints:
  - dashboard,
  - exportaciones,
  - públicos,
  - historial,
  - notificaciones.
- Verificar control por rol y no exposición de datos sensibles.
- Validar formato JSON estándar y códigos HTTP.

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

