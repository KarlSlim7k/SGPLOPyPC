# Fase 5 — Hardening de Seguridad y Revisión OWASP

## Resumen de cambios aplicados

### 1. Cabeceras de seguridad HTTP
Se implementó `app/helpers/security.php` con cabeceras enviadas en cada respuesta API:
- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: DENY`
- `Referrer-Policy: strict-origin-when-cross-origin`
- `Permissions-Policy` restrictiva
- `Content-Security-Policy` básica viable para el stack vanilla (permite CDN de fuentes, Tailwind, Phosphor)

### 2. Manejo de errores sin fuga de detalles
- En `public/index.php`, el bloque `catch (Throwable)` ya no expone `$e->getMessage()` en entorno `production`.
- Los detalles completos se registran en logs estructurados (`storage/logs/`).

### 3. Rate limiting
- Implementado en `app/helpers/RateLimiter.php` basado en archivos por IP + ventana de tiempo.
- Aplicado a endpoints críticos:
  - `/auth/login`: 5 intentos / 60s
  - `/documentos/upload`: 10 cargas / 60s
  - `/reportes/export/licitaciones.csv`: 5 exportaciones / 60s
- Respuesta HTTP 429 con mensaje claro cuando se excede.

### 4. Matriz de autorización por endpoint
Se corrigieron controles de acceso rotos (Broken Access Control — OWASP A01):
- Todos los endpoints de lectura/escritura sensibles ahora requieren `AuthMiddleware`.
- `EvaluacionController` y `ContratoController` ahora exigen `ADMINISTRADOR` en el router.
- `LicitacionController::list/get` requieren autenticación; proveedores solo ven estados públicos.
- `ProveedorController::get` requiere autenticación; proveedores solo ven su propio perfil.
- `ParticipacionController` requiere `PROVEEDOR` para inscripción/envío de propuesta.

### 5. Validaciones de entrada centralizadas
- Nuevo `app/helpers/Validator.php` con reglas: required, email, max, int, float, in, date.
- Integrado en `AuthController::login()` como ejemplo; escalable a otros controladores.

### 6. Configuración segura de entorno
- `.env.example` actualizado sin defaults inseguros (sin contraseñas vacías ni secrets por defecto).
- `config/database.php` ya no usa fallbacks inseguros; valida presencia de variables críticas y devuelve error 500 controlado si faltan.
- `PDO::ATTR_TIMEOUT` configurado a 5 segundos.

### 7. Logs de seguridad
- Nuevo `app/helpers/Logger.php` con salida estructurada JSONL.
- Eventos de seguridad (rate limit excedido, errores no manejados) se registran con nivel SECURITY/ERROR.

### Checklist OWASP mínimo
| Riesgo | Estado | Mitigación |
|--------|--------|------------|
| A01 — Broken Access Control | ✅ Aplicado | AuthMiddleware + RoleMiddleware en todos los endpoints sensibles |
| A03 — Injection (SQL) | ✅ Mitigado | Todas las consultas usan PDO prepared statements |
| A05 — Security Misconfiguration | ✅ Revisado | .env.example seguro, cabeceras HTTP, timeouts de BD |
| A07 — Identification and Authentication Failures | ✅ Mitigado | Rate limiting en login, validaciones de entrada, JWT con expiración |
| A09 — Security Logging and Monitoring Failures | ✅ Activo | Logs estructurados, auditoría de acciones críticas en `historial_cambio` |

## Rollback
- Revertir `public/index.php` al commit anterior para eliminar rate limiting y cabeceras.
- Revertir `config/database.php` para restaurar fallbacks (no recomendado).
- Los índices de BD se revierten ejecutando `DROP INDEX` correspondientes.
