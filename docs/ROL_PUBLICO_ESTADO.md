# Estado Técnico del Rol Público — SGPLOPyPC

## 1. Resumen Ejecutivo

El rol **PÚBLICO** de SGPLOPyPC está **funcionalmente operativo pero con alcance mínimo**. Su propósito es permitir la consulta de información de transparencia (convocatorias, contratos, historial, evaluaciones) sin autenticación, y ofrecer un centro personalizado muy básico una vez que el usuario inicia sesión.

**Estado general:** Estable en producción. No se detectan errores críticos que impidan el acceso ni la navegación. Sin embargo, el rol carece de funcionalidades diferenciadas para usuarios autenticados versus anónimos, presenta deuda técnica en el backend y oportunidades claras de mejora en UX.

---

## 2. Alcance del Análisis

| Área | Alcance |
|------|---------|
| **Frontend** | Páginas públicas (`/`, `evaluacion.php`, `contratos.php`, `historial.php`, `registro.php`, `faq.php`, `requisitos.php`) y centro autenticado (`frontend/publico/centro.html`) |
| **Backend / API** | `PublicController`, `PublicService`, `PublicRepository`, `PublicAccountService`, `AuthController`, `UserController`, `NotificacionController`, enrutador en `public/index.php` |
| **Base de datos** | Esquema MySQL en Railway: tablas `usuario`, `licitacion`, `dependencia`, `proveedor`, `contrato`, `notificacion`, `fecha_proceso`, `soporte_ticket`, `password_reset_token` |
| **Infraestructura** | Servicio Docker en Railway (`SGPLOPyPC`), base de datos MySQL (`mysql-volume`), PhpMyAdmin auxiliar |
| **Pruebas** | E2E básicas (`e2e/tests/public-basic-flows.spec.ts`) |

---

## 3. Estado Actual del Rol Público

### 3.1 Funcionalidades disponibles

| Funcionalidad | Requiere login | Estado |
|---------------|----------------|--------|
| Landing / inicio (`/`) | No | Operativa |
| Listado de convocatorias públicas | No | Operativa |
| Procesos en evaluación | No | Operativa |
| Contratos adjudicados | No | Operativa |
| Historial de licitaciones (con filtros) | No | Operativa |
| Registro de proveedores | No | Operativa |
| Ticket de soporte | No | Operativa |
| Centro del rol público | **Sí** | Operativo (mínimo) |
| Mis notificaciones | **Sí** | Operativo (siempre vacío para la cuenta demo) |

### 3.2 Flujo de navegación observado

1. Usuario anónico llega a `/` (landing pública).
2. Puede navegar por convocatorias, evaluación, contratos, historial y registro de proveedores.
3. Si accede a **Ventanilla de Acceso → Iniciar sesión**, ingresa credenciales y el backend devuelve un JWT con `rol: PUBLICO`.
4. El frontend (`frontend/auth/login.html`) redirige a `frontend/publico/centro.html`.
5. El centro carga `/api/v1/me` y `/api/v1/notificaciones/mias` vía `SGPLAdmin.authFetch`.

---

## 4. Mapa Funcional del Rol

```
┌─────────────────────────────────────────────────────────────┐
│                        ANÓNIMO                              │
│  / (landing) → convocatorias, evaluación, contratos,        │
│  historial, registro de proveedores, FAQ, requisitos        │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                      AUTENTICADO                            │
│  frontend/publico/centro.html                               │
│  ├─ Bienvenida personalizada (nombre del JWT /me)          │
│  ├─ Enlaces a páginas públicas (/, /evaluacion.php, …)     │
│  └─ Mis notificaciones (/api/v1/notificaciones/mias)       │
└─────────────────────────────────────────────────────────────┘
```

---

## 5. Análisis por Capa

### 5.1 Frontend

#### Archivos relevantes
- `public/landing.php` — Landing principal.
- `public/evaluacion.php`, `contratos.php`, `historial.php`, `registro.php`, `faq.php`, `requisitos.php` — Páginas de transparencia.
- `frontend/publico/centro.html` — Centro autenticado del rol público.
- `frontend/shared/public.js` — Lógica de consumo de API pública.
- `frontend/shared/admin.js` — Utilidades compartidas (auth, formatos, logout).

#### Observaciones

**Diseño y consistencia visual**
- Las páginas públicas comparten navbar, tipografía (Plus Jakarta Sans) y paleta de colores mediante Tailwind CSS vía CDN. La consistencia es **buena**.
- El centro público (`centro.html`) replica el navbar pero con un botón de cerrar sesión; el logo cambia a "Centro Público".

**Estados de carga, vacío y error**
- Las páginas públicas muestran mensajes de carga genéricos ("Cargando convocatorias…").
- Si la API falla, `public.js` reemplaza el contenedor con un mensaje de error amigable.
- El centro público maneja errores de red en notificaciones mostrando un mensaje rojo.

**Responsividad**
- Uso extensivo de clases responsive de Tailwind (`sm:`, `md:`, `lg:`).
- El menú móvil está implementado en `landing.php` mediante un script inline.

**Validaciones**
- El registro de proveedores valida campos obligatorios, formato de correo, longitud de contraseña (≥ 8) y aceptación de términos (`accepted_terms`).
- No hay validación de fortaleza de contraseña en el registro público (sí existe en `UserService::isStrongPassword` para cambio de contraseña).

**Bugs y comportamientos incompletos**
1. **Flash de datos hardcodeados:** En `landing.php` el HTML inicial muestra valores estáticos (ej. 12 licitaciones activas, 84 proveedores) que luego son sobrescritos por JavaScript. Si el JS falla o tarda, el usuario ve datos inconsistentes.
2. **Enlace "Convocatorias" en centro.html apunta a `/`:** Técnicamente correcto, pero podría confundir al usuario porque lo lleva de vuelta al landing.
3. **Progreso simulado en evaluación:** El porcentaje de avance del proceso es un valor estático (`70` o `30`) calculado en JS según el estado; no refleja datos reales del backend.
4. **Subida de documentos en registro sin manejo de errores:** En `public.js` (`initRegistro`), si falla la subida de documentos legales (`/api/v1/documentos/upload`), el error se ignora y se muestra éxito igualmente.

### 5.2 Backend / API

#### Endpoints públicos (sin autenticación)
| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/v1/public/estadisticas` | Totales de licitaciones, proveedores, contratos |
| GET | `/api/v1/public/convocatorias` | Listado paginado y filtrable |
| GET | `/api/v1/public/convocatorias/:id` | Detalle de convocatoria |
| GET | `/api/v1/public/evaluaciones` | Procesos en evaluación / recepción |
| GET | `/api/v1/public/contratos` | Contratos adjudicados paginados |
| GET | `/api/v1/public/historial` | Historial de licitaciones concluidas |
| GET | `/api/v1/public/resultados` | Resultados de adjudicación |
| GET | `/api/v1/public/convocatorias/:id/documentos` | Documentos públicos de una licitación |
| GET | `/api/v1/public/documentos/:id/download` | Descarga de documento público |
| POST | `/api/v1/public/proveedores/registro` | Registro de nuevo proveedor |
| POST | `/api/v1/public/soporte` | Crear ticket de soporte |

#### Endpoints autenticados usados por el rol público
| Método | Endpoint | Middleware | Descripción |
|--------|----------|------------|-------------|
| POST | `/api/v1/auth/login` | Rate limit 5/60s | Autenticación |
| GET | `/api/v1/me` | `AuthMiddleware` | Datos del usuario |
| GET | `/api/v1/notificaciones/mias` | `AuthMiddleware` | Notificaciones del usuario |

#### Permisos y restricciones
- `AuthMiddleware` verifica JWT y que el usuario esté activo. **No restringe por rol** en `/me` ni `/notificaciones/mias`.
- `RoleMiddleware` se usa para endpoints administrativos y de proveedor; el rol `PUBLICO` no tiene acceso a ninguno de ellos.
- Los endpoints de transparencia **no requieren autenticación**, lo cual es correcto por diseño.

#### Manejo de errores
- El router (`public/index.php`) captura excepciones no manejadas y responde JSON con código 500.
- En producción (`APP_ENV=production`) no se expone el stack trace al cliente.
- `PublicController` devuelve 404 cuando no encuentra convocatorias o documentos.

#### Riesgos técnicos y de seguridad
1. **Path traversal potencial en descarga de documentos:** `downloadDocumentoPublico` usa `realpath()` y valida prefijo, lo cual mitiga el riesgo, pero depende de que `storage/` nunca sea un symlink peligroso.
2. **Rate limiting insuficiente en lectura:** Los endpoints de lectura pública (`estadisticas`, `convocatorias`, etc.) **no tienen rate limit**. Un actor malicioso podría saturar la base de datos.
3. **JWT sin `aud` ni `iss`:** El token solo contiene `sub` y `rol`. No hay revocación ni lista negra.
4. **Inyección SQL mitigada:** Todos los queries usan prepared statements con PDO. No se detectaron concatenaciones directas de entrada del usuario.
5. **Información sensible en logs:** Los logs de Railway incluyen IPs reales y user-agents, pero no contraseñas ni tokens.

### 5.3 Base de Datos

#### Tablas relacionadas con el rol público
| Tabla | Relación | Observaciones |
|-------|----------|---------------|
| `usuario` | Almacena cuenta `PUBLICO` | `rol ENUM('ADMINISTRADOR','PROVEEDOR','PUBLICO')` |
| `licitacion` | Datos de convocatorias | Estados filtrados en API pública excluyen `BORRADOR` y `CANCELADA` |
| `dependencia` | Relacionada con licitación | JOIN en casi todas las queries públicas |
| `contrato` | Adjudicaciones formalizadas | Relacionada con `licitacion` y `proveedor` |
| `proveedor` | Empresas registradas | Registro público crea entrada con `estatus='PENDIENTE'` |
| `fecha_proceso` | Fechas programadas | LEFT JOIN para mostrar cierre y fallo |
| `notificacion` | Notificaciones por usuario | Vacío para `id_usuario=4` (cuenta demo) |
| `soporte_ticket` | Tickets de contacto | Creada en migración `004_fase6_publico_completo.sql` |
| `password_reset_token` | Recuperación de contraseña | Creada en migración `004` |

#### Datos observados en producción
- Existen **7 licitaciones** en total; 5 activas, 2 adjudicadas.
- Existen **5 proveedores** registrados, todos activos.
- Existen **2 contratos** adjudicados, monto total **$2,599,000 MXN**.
- El usuario público demo tiene **0 notificaciones**.
- Hay datos de prueba E2E marcados con `[E2E_TEST_DATASET]` en descripciones de licitaciones.

#### Posibles inconsistencias y mejoras de modelado
1. **Sin tabla `perfil_publico`:** El rol `PUBLICO` solo existe como fila en `usuario`. No hay metadatos adicionales (preferencias, suscripciones, alertas).
2. **Notificaciones sin índice compuesto:** La query de `NotificacionRepository::findByUsuario` realiza múltiples LEFT JOINs. Si el volumen crece, podría degradarse.
3. **Campos legacy en `proveedor`:** `regimen_fiscal`, `contacto_cargo`, `contacto_email` fueron añadidos condicionalmente en migración 004. El código (`PublicAccountService::insertProveedorPublico`) intenta dos queries diferentes para mantener compatibilidad, lo cual es una fuente de deuda técnica.
4. **Datos E2E mezclados con producción:** Las licitaciones de prueba E2E están en la misma base de datos productiva. Deberían estar en un esquema o dataset separado.

### 5.4 Infraestructura

#### Despliegue en Railway
- **Servicio:** `SGPLOPyPC` (Dockerfile)
- **Runtime:** PHP 8.2 + Apache (mpm_prefork)
- **Base de datos:** MySQL (`mysql.railway.internal:3306`, base `railway`)
- **Salud:** Healthcheck en `/healthz` (archivo estático `ok`)
- **Redirecciones:** Apache reescribe todo a `index.php` excepto `/frontend/` y archivos existentes.

#### Variables de entorno relevantes
| Variable | Valor observado | Estado |
|----------|-----------------|--------|
| `APP_ENV` | `production` | Correcto |
| `DB_HOST` | `mysql.railway.internal` | Correcto |
| `JWT_SECRET` | Presente | Correcto |
| `JWT_TTL` | `86400` | Correcto |
| `MAIL_ENABLED` | No visible en variables expuestas | Pendiente de verificar |
| `SUPPORT_EMAIL_TO` | No visible | Pendiente; si está vacío, no se envían mails de soporte |

#### Problemas de configuración
1. **Sin `APP_BASE_URL` configurada:** La variable `APP_BASE_URL` en `.env.example` está vacía. Puede afectar a URLs en correos de recuperación de contraseña.
2. **Error 500 en soporte administrativo:** Los logs muestran `GET /api/v1/soporte/tickets 500` cuando un administrador accede a configuración. Esto indica un bug en `SupportTicketRepository` o `SupportTicketController` no relacionado directamente con el rol público, pero sí con la funcionalidad de soporte que el público puede crear.

---

## 6. Hallazgos

### 6.1 Funcionalidades correctas
- Landing pública carga estadísticas y convocatorias vía API correctamente.
- Filtros de historial (año, tipo, búsqueda) funcionan y actualizan resultados.
- Paginación en contratos e historial opera sin errores.
- Login del rol público redirige correctamente a `frontend/publico/centro.html`.
- El centro protege la ruta: si no hay token, redirige a login.
- El registro de proveedores crea usuario y proveedor en transacción.

### 6.2 Errores detectados

| ID | Error | Ubicación | Impacto | Propuesta de solución |
|----|-------|-----------|---------|----------------------|
| **E1** | **Error 500 en `/api/v1/soporte/tickets`** (admin) | `SupportTicketController` / `SupportTicketRepository` | Alto para administradores; el público puede crear tickets que no se visualizan | Revisar query de listado; probable falta de JOIN o columna inexistente |
| **E2** | **Subida de documentos en registro ignora errores** | `frontend/shared/public.js` (`initRegistro`) | Medio; el usuario cree que todo se cargó cuando no | Agregar `await` con manejo de `response.ok` y mostrar advertencia si falla algún documento |
| **E3** | **Flash de datos hardcodeados en landing** | `public/landing.php` (HTML estático) | Bajo; confusión visual momentánea | Inicializar los contadores con guiones o skeleton loaders en lugar de números fijos |

### 6.3 Funcionalidades incompletas

| ID | Funcionalidad | Estado actual | Qué falta |
|----|---------------|---------------|-----------|
| **I1** | **Centro público diferenciado** | Solo muestra notificaciones | No hay diferencia real entre usuario público anónimo y autenticado más allá del saludo personalizado |
| **I2** | **Alertas / suscripciones** | No existe | El rol público no puede suscribirse a licitaciones por dependencia o palabra clave |
| **I3** | **Resultados de adjudicación** | Endpoint existe (`/public/resultados`) pero no hay página dedicada | Ninguna vista consume este endpoint |
| **I4** | **Descarga de documentos públicos** | API lista | No hay botón ni enlace visible en el frontend público para descargar documentos de una convocatoria |

### 6.4 Riesgos técnicos
- **R1 — Rate limit faltante en lectura pública:** Puede causar degradación de rendimiento o costos elevados en Railway si se recibe tráfico masivo.
- **R2 — Deuda técnica en registro de proveedores:** El fallback de dos queries en `PublicAccountService` dificulta el mantenimiento y puede ocultar errores de esquema.
- **R3 — Mezcla de datos E2E y producción:** Contamina estadísticas públicas y puede exponer información de prueba.
- **R4 — JWT simple:** Sin blacklist ni refresh tokens; si se filtra, es válido por 24h.

### 6.5 Oportunidades de mejora
- Implementar skeleton loaders en lugar de "Cargando…" para mejorar percepción de velocidad.
- Añadir paginación real en el frontend de convocatorias (actualmente solo se muestran 3 en landing).
- Permitir al rol público descargar documentos desde la landing o el detalle de convocatoria.
- Crear un dashboard más útil para el rol público: favoritos, alertas, historial de consultas.

---

## 7. Evidencias Técnicas

### Archivos relevantes
```
public/index.php                    → Router monolítico (API + front controller)
public/landing.php                  → Landing principal
public/evaluacion.php               → Procesos en evaluación
public/contratos.php                → Contratos adjudicados
public/historial.php                → Historial con filtros
public/registro.php                 → Registro de proveedores
frontend/publico/centro.html        → Centro autenticado del rol público
frontend/shared/public.js           → Lógica de consumo de API pública
frontend/shared/admin.js            → Utilidades compartidas
app/controllers/PublicController.php
app/services/PublicService.php
app/services/PublicAccountService.php
app/repositories/PublicRepository.php
app/middlewares/AuthMiddleware.php
app/middlewares/RoleMiddleware.php
config/database.php                 → Conexión PDO MySQL
Dockerfile                          → PHP 8.2 + Apache
docker/apache-site.conf             → VirtualHost con rewrites
docker/entrypoint.sh                → Ajuste de puerto para Railway
railway.json                        → Configuración de despliegue
```

### Endpoints implicados
- `/api/v1/public/*` — Toda la API de transparencia.
- `/api/v1/auth/login` — Autenticación.
- `/api/v1/me` — Perfil del usuario autenticado.
- `/api/v1/notificaciones/mias` — Bandeja de notificaciones.

### Tablas relacionadas
- `usuario` (rol `PUBLICO`)
- `licitacion`, `dependencia`, `contrato`, `proveedor`
- `fecha_proceso`, `notificacion`
- `soporte_ticket`, `password_reset_token`

### Comportamientos observados
- Login con `publico@demo.mx` devuelve JWT con `rol: PUBLICO`.
- `/me` devuelve datos básicos sin objeto `proveedor` (correcto, ya que no es proveedor).
- `/notificaciones/mias` devuelve `[]` para este usuario.
- `/api/v1/public/estadisticas` responde en ~100ms con datos reales.

---

## 8. Plan de Desarrollo por Fases

### Fase 1: Correcciones críticas (1–2 días)
- [ ] **E1:** Diagnosticar y corregir error 500 en `/api/v1/soporte/tickets`.
- [ ] **E2:** Agregar manejo de errores en la subida de documentos del registro público.
- [ ] **R1:** Implementar rate limiting en endpoints de lectura pública (`estadisticas`, `convocatorias`, `historial`, `evaluaciones`, `contratos`).

### Fase 2: Estabilización funcional (2–3 días)
- [ ] Separar datos E2E de producción (agregar flag `is_test` o usar base de datos diferente).
- [ ] Revisar y unificar el mecanismo de inserción de proveedor en `PublicAccountService` (eliminar query legacy).
- [ ] Verificar que `APP_BASE_URL` y `SUPPORT_EMAIL_TO` estén configuradas en producción.

### Fase 3: Mejoras UX/UI (3–5 días)
- [ ] Reemplazar datos hardcodeados en landing por skeleton loaders.
- [ ] Añadir vista de detalle de convocatoria accesible desde landing (actualmente el API de detalle existe pero no hay página).
- [ ] Habilitar descarga de documentos públicos en la vista de convocatoria.
- [ ] Mejorar el centro público: agregar resumen de licitaciones seguidas, alertas configurables o lista de últimas convocatorias consultadas.

### Fase 4: Optimización técnica y deuda (3–5 días)
- [ ] Refactorizar el router monolítico (`public/index.php`) hacia un sistema de rutas más mantenible.
- [ ] Extraer helpers de formato duplicados entre `public.js` y `admin.js` a un módulo compartido.
- [ ] Agregar índices en `notificacion(id_usuario_destino, leida, fecha_envio)` si el volumen crece.
- [ ] Evaluar migración del frontend a un framework ligero o build step para mejorar mantenibilidad.

### Fase 5: Pruebas, documentación y cierre (2–3 días)
- [ ] Ampliar E2E para cubrir flujo completo del rol público: login, centro, navegación a contratos, registro de proveedor (mock de email).
- [ ] Documentar contratos de API pública (OpenAPI/Swagger o similar).
- [ ] Revisión de seguridad: pentest básico de endpoints públicos (inyección, path traversal, rate limiting).

---

## 9. Backlog Priorizado

### Alta prioridad
1. **Corregir error 500 en soporte administrativo** — afecta la trazabilidad de tickets creados por el público.
2. **Rate limiting en endpoints públicos de lectura** — riesgo de abuso y costos.
3. **Manejo de errores en subida de documentos del registro** — evita falsos positivos.

### Media prioridad
4. **Eliminar query legacy en registro de proveedores** — deuda técnica.
5. **Separar datos E2E de producción** — calidad de datos y estadísticas.
6. **Skeleton loaders en landing** — mejora de UX.
7. **Vista de detalle de convocatoria pública** — funcionalidad incompleta.

### Baja prioridad
8. **Alertas/suscripciones para rol público** — valor agregado diferenciador.
9. **Refactorizar router monolítico** — mantenibilidad a largo plazo.
10. **Framework de frontend** — escalabilidad futura.

---

## 10. Criterios de Aceptación Sugeridos

### Para correcciones críticas
- Dado que un administrador accede a configuración, cuando carga la bandeja de tickets, entonces no debe aparecer error 500.
- Dado que un usuario registra un proveedor y sube documentos, cuando falla la subida de algún archivo, entonces debe ver una advertencia clara.
- Dado que un cliente realiza 100 peticiones por minuto a `/api/v1/public/convocatorias`, entonces debe recibir código 429 después del límite configurado.

### Para mejoras funcionales
- Dado que un usuario público autenticado entra a su centro, cuando no tiene notificaciones, entonces debe ver una invitación a explorar convocatorias o configurar alertas.
- Dado que un visitante está en la landing, cuando carga la página, entonces los contadores deben mostrar skeletons en lugar de números fijos hasta recibir datos reales.

---

## 11. Recomendaciones para el Siguiente Agente o Desarrollador

1. **Entender el router antes de tocar cualquier endpoint:** Todas las rutas API están en el `switch` gigante de `public/index.php`. Agregar o modificar rutas requiere editar ese archivo.
2. **No confundir la base de datos:** El proyecto usa **MySQL** en Railway. Si usas herramientas de Supabase en este entorno, apuntarán a un esquema PostgreSQL irrelevante.
3. **Frontend vanilla JS:** No hay React/Vue/Angular. Todo el consumo de API está en `public.js` y `admin.js`. Si vas a agregar funcionalidad, sigue ese patrón o propón una migración controlada.
4. **Pruebas con datos reales:** El token JWT expira en 24h. Para pruebas locales, usa `publico@demo.mx` (la contraseña está documentada en el entorno de pruebas, no en producción).
5. **Revisar migraciones:** Las migraciones están en `database/migrations/` y son idempotentes donde es posible. Si necesitas modificar el esquema, crea una nueva migración numerada, no edites las existentes.
6. **Docker local:** Puedes levantar el proyecto con `docker build` y exponer el puerto que Railway inyecta vía `$PORT`.

---

## 12. Limitaciones del Análisis

1. **No se realizó acceso directo a MySQL en producción:** Se inspeccionaron las variables de entorno y las respuestas de la API, pero no se ejecutaron queries directos contra la base de datos productiva. El esquema se infirió del código fuente y las migraciones.
2. **No se ejecutaron pruebas E2E durante el análisis:** Solo se revisó el código fuente de las pruebas existentes. No se corrió Playwright contra producción.
3. **No se profundizó en el módulo de correo:** No se verificó si `Mailer` y las variables `MAIL_ENABLED` / `SUPPORT_EMAIL_TO` están operativas; solo se constató que `SUPPORT_EMAIL_TO` no aparece en las variables visibles de Railway.
4. **Análisis estático limitado:** No se utilizó un linter ni un analizador de seguridad automatizado (como Psalm, PHPStan o SonarQube). Las observaciones de código se basan en revisión manual.
5. **Scope restringido al rol público:** No se analizaron en profundidad los roles `ADMINISTRADOR` ni `PROVEEDOR`, salvo cuando interactúan directamente con el flujo público.

---

*Documento generado el 2026-05-10. Versión 1.0.*
