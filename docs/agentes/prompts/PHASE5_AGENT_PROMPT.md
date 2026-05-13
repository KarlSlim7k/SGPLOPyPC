# Prompt para Agente IA — Ejecución Fase 5 (Hardening y Escalabilidad)

Copia y pega este prompt en tu agente IA para implementar la **Fase 5** del proyecto.

---

## PROMPT

Eres un agente de desarrollo senior trabajando en el repositorio **SGPLOPyPC**.

### Objetivo
Implementar la **Fase 5 — Hardening y Escalabilidad** para elevar seguridad, resiliencia y capacidad operativa del sistema:
1. Endurecimiento de seguridad (authz fina, validaciones extendidas, revisión OWASP).
2. Optimización SQL e índices con base en consultas reales.
3. Estrategia de respaldos y restauración probada.
4. Monitoreo y alertamiento operativo.

### Contexto obligatorio a leer antes de codificar
- `docs/arquitectura/contexto.md`
- `docs/arquitectura/arquitectura_infraestructura.md`
- `docs/arquitectura/modelado_base_de_datos.md`
- `docs/agentes/AGENTS.md`
- `docs/producto/ROADMAP.md`
- `docs/guias/DESIGN.md`
- `docs/guias/DATABASE_GUIDELINES.md`
- `docs/guias/FRONTEND_GUIDELINES.md`
- Revisión del resultado de Fase 4 (commit: `7b8975d8a62d7112555888ac998769327b4839ff`)

### Reglas de trabajo
1. Cambios por lotes pequeños y con rollback claro.
2. Evitar cambios disruptivos sin plan de migración.
3. Mantener compatibilidad con stack actual (PHP + MySQL/MariaDB + Railway + Docker).
4. No hardcodear secretos ni credenciales.
5. Aplicar principio de mínimo privilegio.
6. Documentar todas las decisiones de seguridad y performance.

### Formato estándar de respuesta API
- `success` (boolean)
- `message` (string)
- `data` (objeto/array/null)
- `errors` (array/null)

HTTP sugeridos:
- `200`, `201`, `400`, `401`, `403`, `404`, `409`, `422`, `429`, `500`

---

## Entregables mínimos esperados (Fase 5)

### A) Hardening de seguridad
Implementar/mejorar:
- Matriz de autorización por endpoint (rol + acción).
- Validaciones de entrada centralizadas (tipos, rangos, formato, obligatoriedad).
- Manejo de errores sin fuga de detalles sensibles.
- Cabeceras de seguridad HTTP (según aplique):
  - `X-Content-Type-Options`
  - `X-Frame-Options`
  - `Referrer-Policy`
  - `Content-Security-Policy` (si es viable en arquitectura actual)
- Rate limiting básico para endpoints críticos (auth, uploads, exportaciones).

Checklist OWASP mínimo:
- Inyección (SQL/command) mitigada.
- Broken Access Control revisado.
- Security Misconfiguration revisada.
- Logging/Monitoring de seguridad básico activo.

### B) Optimización de BD y consultas
Implementar:
- Identificación de consultas críticas (top N por costo/frecuencia).
- Revisión de planes de ejecución (`EXPLAIN`) para consultas principales.
- Índices adicionales o ajuste de índices existentes.
- Correcciones de N+1 o consultas redundantes en endpoints clave.

Entregable:
- Documento breve en `docs/` con:
  - consultas optimizadas,
  - índices creados/modificados,
  - impacto esperado.

### C) Backups y restauración
Implementar:
- Script(s) de respaldo de base de datos.
- Retención mínima configurable (por ejemplo 7/14/30 días).
- Procedimiento documentado de restauración.
- Prueba de restauración en entorno de prueba (si es posible) y evidencia en docs.

Mínimos:
- No guardar backups sin cifrado/protección si contienen datos sensibles.
- No versionar backups reales en el repositorio.

### D) Monitoreo y alertamiento
Implementar base operativa:
- Health checks ampliados (app + DB + dependencias críticas).
- Logs estructurados para eventos de error y seguridad.
- Métricas mínimas (latencia, errores 4xx/5xx, uso básico de recursos si disponible).
- Alertas mínimas (por ejemplo: error rate alto, caída de DB, fallos repetidos de login).

### E) Robustez operativa
Implementar/mejorar:
- Timeouts razonables en operaciones externas.
- Reintentos controlados solo donde aplique.
- Manejo de fallos parciales con mensajes consistentes.
- Revisión de configuración de entorno (`.env.example`) y defaults seguros.

---

## Criterios de aceptación
1. Seguridad reforzada en authz, validaciones y exposición de errores.
2. Controles OWASP mínimos aplicados y documentados.
3. Consultas críticas optimizadas y cambios de índices justificados.
4. Backups automatizables y restauración documentada/probada.
5. Monitoreo básico y alertas iniciales funcionales.
6. Endpoints críticos verificados con pruebas manuales.
7. Documentación técnica actualizada en `docs/`.

## Plan de ejecución (obligatorio)
Antes de codificar:
1. Resumir estado actual (máx 10 bullets).
2. Proponer plan de 7–12 pasos con prioridad por riesgo.
3. Ejecutar por iteraciones: seguridad → BD → backups → monitoreo → hardening final.

## Validaciones mínimas a ejecutar
- Sintaxis PHP de archivos nuevos/modificados.
- Pruebas manuales de endpoints críticos (auth, uploads, exportaciones, endpoints admin).
- Verificación de control de acceso por rol.
- Verificación de respuestas JSON estándar y códigos HTTP.
- Verificación básica de scripts de backup/restore.
- Validación de índices/consultas con evidencia (`EXPLAIN`/tiempos relativos).

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

