# ROADMAP.md — Hoja de Ruta Técnica y de Producto

## Estado actual resumido
- Requerimientos funcionales bien documentados.
- Modelo de datos ampliamente definido.
- Vistas frontend base presentes.
- Backend modular descrito en arquitectura, con implementación parcial visible.

## Fase 1 — Fundación operativa (0–4 semanas)
- [ ] Consolidar estructura backend mínima (router, controladores base, conexión BD).
- [ ] Implementar autenticación básica y control por rol.
- [ ] Definir convenciones API (JSON, códigos HTTP, manejo de errores).
- [ ] Configurar entorno de desarrollo reproducible (Docker/Railway local parity).

## Fase 2 — Núcleo transaccional (4–8 semanas)
- [ ] CRUD de licitaciones y convocatorias.
- [ ] Registro/validación de proveedores.
- [ ] Flujo de participación y envío de propuestas.
- [ ] Gestión documental inicial (uploads controlados).

## Fase 3 — Evaluación y adjudicación (8–12 semanas)
- [ ] Módulo de evaluación técnica/económica.
- [ ] Dictámenes y estado del proceso.
- [ ] Adjudicación y generación de contrato.
- [ ] Auditoría de acciones críticas.

## Fase 4 — Reportes y transparencia (12–16 semanas)
- [ ] Tableros e indicadores principales.
- [ ] Exportaciones CSV/PDF/Excel.
- [ ] Historial y consulta pública de procesos.
- [ ] Notificaciones a proveedores.

## Fase 5 — Hardening y escalabilidad (continuo)
- [ ] Endurecimiento de seguridad (authz fina, validaciones extendidas, revisión OWASP).
- [ ] Optimización SQL e índices por carga real.
- [ ] Backups y restauración probada.
- [ ] Monitoreo y alertamiento operativo.

## KPIs sugeridos
- Tiempo promedio de publicación de licitación.
- Tiempo promedio de evaluación por propuesta.
- % propuestas con validación completa al primer intento.
- Tiempo de respuesta de endpoints críticos (p95).
- Errores 5xx por día/semana.

