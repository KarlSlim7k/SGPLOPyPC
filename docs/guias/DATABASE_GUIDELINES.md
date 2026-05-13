# DATABASE_GUIDELINES.md — Lineamientos de Base de Datos

## 1) Principios
- Priorizar integridad referencial.
- Diseñar para trazabilidad y auditoría.
- Evitar duplicidad de datos de negocio.

## 2) Convenciones de modelado
- Tablas en `snake_case`.
- PK numéricas (`id_*`) por entidad.
- FKs explícitas y nombradas consistentemente.
- Campos de estado con ENUM controlado cuando aplique.

## 3) Integridad y restricciones
- Definir `NOT NULL` en campos obligatorios.
- Usar `UNIQUE` para identificadores de negocio (email, número licitación, RFC, contrato).
- Usar `CHECK` para reglas críticas (montos > 0, coherencia de fechas, etc.).

## 4) Auditoría
- Mantener tabla de `historial_cambio` para operaciones críticas.
- Registrar actor, acción, timestamp y snapshots antes/después cuando sea viable.

## 5) Índices
- Indexar columnas de filtros frecuentes (estado, tipo, fechas, FK de relación).
- Revisar índices según carga real y planes de ejecución.

## 6) Migraciones y cambios de esquema
- No alterar esquema en producción sin script reversible.
- Documentar cada cambio con objetivo y riesgo.
- Probar migraciones en entorno de staging antes de producción.

## 7) Seguridad de datos
- Principio de mínimo privilegio para usuarios de BD.
- Respaldos periódicos y pruebas de restauración.
- Evitar exponer BD públicamente sin controles de red/autenticación.

