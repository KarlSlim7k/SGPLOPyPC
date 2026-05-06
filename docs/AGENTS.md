# AGENTS.md — Guía para Agentes de IA (SGPLOPyPC)

## 1) Propósito
Este documento proporciona a agentes de IA el contexto mínimo y los lineamientos prácticos para contribuir al proyecto **SGPLOPyPC** de forma consistente, segura y mantenible.

## 2) Contexto del sistema
SGPLOPyPC es un sistema web para la gestión integral del procedimiento de licitación de obra pública y contratación:
- Convocatorias y licitaciones
- Registro/validación de proveedores
- Recepción de propuestas
- Evaluación y dictamen
- Adjudicación y contratos
- Reportes, notificaciones y trazabilidad

Stack objetivo (según docs del proyecto):
- Frontend: HTML/CSS/JS vanilla
- Backend: PHP
- BD: MySQL/MariaDB
- Operación: Railway + phpMyAdmin

## 3) Objetivos al colaborar
Cuando un agente contribuya, debe priorizar:
1. **Trazabilidad**: cambios auditables y justificados.
2. **Consistencia**: respetar convenciones de nombres, estructura y estilo.
3. **Seguridad**: no introducir prácticas inseguras (SQL injection, exposición de secretos, etc.).
4. **Mantenibilidad**: preferir cambios pequeños, modulares y legibles.

## 4) Convenciones generales
- No asumir frameworks no existentes.
- Mantener compatibilidad con enfoque vanilla del repositorio.
- Evitar cambios masivos sin plan incremental.
- Si se agrega funcionalidad nueva, actualizar documentación relacionada en `docs/`.

## 5) Flujo recomendado para tareas
1. Leer primero:
   - `docs/contexto.md`
   - `docs/arquitectura_infraestructura.md`
   - `docs/modelado_base_de_datos.md`
2. Identificar módulo impactado.
3. Proponer cambios mínimos viables.
4. Ejecutar validaciones básicas (lint/tests/checks disponibles).
5. Documentar impacto y decisiones.

## 6) Seguridad y datos
- Nunca hardcodear secretos o credenciales.
- Validar entradas de usuario en backend.
- Usar consultas preparadas (PDO) para SQL.
- Mantener principios de mínimo privilegio en acceso a datos.
- Proteger rutas/archivos de documentos subidos.

## 7) Criterios de calidad para PR/entregas
- Cambio enfocado y con propósito único.
- Sin romper estructura existente.
- Con documentación actualizada.
- Mensajes de commit claros y trazables.

## 8) Qué no hacer
- No introducir dependencias pesadas sin justificación.
- No reestructurar carpetas críticas sin migración explícita.
- No mezclar refactors grandes con features en el mismo cambio.

## 9) Documentos complementarios
- `docs/ROADMAP.md`
- `docs/DESIGN.md`
- `docs/FRONTEND_GUIDELINES.md`
- `docs/DATABASE_GUIDELINES.md`

