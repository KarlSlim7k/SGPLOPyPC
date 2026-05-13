# Indice de documentacion

Esta carpeta agrupa la documentacion por proposito. Mantener `docs/` con solo este indice y ubicar cada documento en su subcarpeta correspondiente.

## Estructura

| Carpeta | Uso |
|---|---|
| `agentes/` | Guias y prompts para agentes IA. |
| `api/` | Contratos, endpoints y especificaciones API. |
| `arquitectura/` | Contexto, infraestructura y modelo de datos. |
| `fases/` | Evidencia y entregables por fase del proyecto. |
| `frontend/` | Matrices y documentacion especifica de frontend. |
| `guias/` | Lineamientos tecnicos transversales. |
| `operacion/` | Arranque, despliegue, cierre y operacion. |
| `producto/` | Roadmap y vision de producto. |
| `roles/` | Estado y analisis por rol de la plataforma. |

## Archivos

| Archivo | Proposito |
|---|---|
| `agentes/AGENTS.md` | Guia operativa para agentes IA dentro del proyecto. |
| `agentes/prompts/PHASE1_AGENT_PROMPT.md` | Prompt de ejecucion para fase 1. |
| `agentes/prompts/PHASE2_AGENT_PROMPT.md` | Prompt de ejecucion para fase 2. |
| `agentes/prompts/PHASE3_AGENT_PROMPT.md` | Prompt de ejecucion para fase 3. |
| `agentes/prompts/PHASE4_AGENT_PROMPT.md` | Prompt de ejecucion para fase 4. |
| `agentes/prompts/PHASE5_AGENT_PROMPT.md` | Prompt de ejecucion para fase 5. |
| `api/API_ENDPOINTS.md` | Referencia general de endpoints y payloads. |
| `api/openapi-public.yaml` | Contrato OpenAPI de endpoints publicos. |
| `arquitectura/contexto.md` | Contexto funcional del sistema. |
| `arquitectura/arquitectura_infraestructura.md` | Arquitectura e infraestructura propuesta. |
| `arquitectura/modelado_base_de_datos.md` | Modelo de base de datos. |
| `fases/fase5/FASE5_BACKUPS.md` | Backups y restauracion. |
| `fases/fase5/FASE5_OPTIMIZACION_BD.md` | Optimizacion de base de datos y consultas. |
| `fases/fase5/FASE5_PUBLICO_ACCESIBILIDAD.md` | Evidencia de accesibilidad publica. |
| `fases/fase5/FASE5_PUBLICO_PENTEST.md` | Evidencia de pentest publico basico. |
| `fases/fase5/FASE5_SEGURIDAD_OWASP.md` | Hardening y revision OWASP. |
| `frontend/FRONTEND_FUNCIONALIZACION_MATRIZ.md` | Matriz de funcionalizacion frontend admin. |
| `guias/DATABASE_GUIDELINES.md` | Lineamientos de base de datos. |
| `guias/DESIGN.md` | Guia de diseno de solucion. |
| `guias/FRONTEND_GUIDELINES.md` | Lineamientos frontend vanilla. |
| `operacion/ARRANQUE_LOCAL.md` | Guia de arranque local. |
| `operacion/FASE_CIERRE_VALIDACION.md` | Evidencia de cierre, validacion y estabilizacion. |
| `operacion/railway-deploy-operacion.md` | Flujo operativo de despliegue Railway. |
| `producto/ROADMAP.md` | Hoja de ruta tecnica y de producto. |
| `roles/proveedor/ANALISIS_ESTADO_ACTUAL.txt` | Analisis del estado actual del rol proveedor. |
| `roles/publico/ROL_PUBLICO_ESTADO.md` | Estado tecnico del rol publico. |

## Regla de ubicacion

- Prompts nuevos: `agentes/prompts/`.
- Contratos o endpoints: `api/`.
- Guias tecnicas generales: `guias/`.
- Operacion, despliegue o cierre: `operacion/`.
- Evidencia por fase: `fases/faseN/`.
- Estado por rol: `roles/<rol>/`.
