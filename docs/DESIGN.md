# DESIGN.md — Guía de Diseño de Solución

## 1) Principios de diseño
- **Modularidad**: separar presentación, negocio y datos.
- **Legibilidad**: código y estructuras fáciles de entender.
- **Trazabilidad**: cada transición de estado debe ser auditable.
- **Coherencia**: nomenclatura uniforme entre UI, API y BD.

## 2) Diseño funcional por módulos
1. Convocatorias/Licitaciones
2. Proveedores
3. Propuestas
4. Evaluación
5. Adjudicación/Contratos
6. Reportes/Seguimiento
7. Notificaciones/Auditoría

Cada módulo debe definir:
- Casos de uso
- Reglas de negocio
- Estados permitidos
- Errores de dominio
- Eventos auditables

## 3) Diseño de API (recomendado)
- Estilo REST JSON.
- Recursos en plural (`/licitaciones`, `/proveedores`).
- Respuestas con forma consistente:
  - `success: boolean`
  - `message: string`
  - `data: object|array|null`
  - `errors: array|null`
- Versionado sugerido: `/api/v1/...`

## 4) Diseño de estados de licitación
Estados sugeridos:
- `BORRADOR`
- `PUBLICADA`
- `EN_ACLARACIONES`
- `RECEPCION_PROPUESTAS`
- `EN_EVALUACION`
- `ADJUDICADA`
- `DESIERTA`
- `CANCELADA`

Regla clave: validar transiciones permitidas para evitar saltos inválidos.

## 5) Diseño documental
- Unificar metadatos de documentos.
- Versionado de documentos desde día uno.
- Asociar documento al contexto (licitación/propuesta/contrato/evaluación/proveedor).

## 6) Diseño orientado a auditoría
- Toda acción crítica debe generar registro de auditoría.
- Registrar: actor, acción, entidad, identificador, timestamp, antes/después (si aplica).

