# MEJORAS.md — Plan de Mejoras para Roles Proveedor y Público (SGPLOPyPC)

> Documento dirigido a **agentes de IA** que ejecutarán las fases de mejora de los roles **PROVEEDOR** y **PÚBLICO** del sistema **SGPLOPyPC**.
> Cada fase es un entregable autocontenido con commit + push + deploy + verificación E2E.

---

## 0. Contexto operativo y obligatorio para el agente

### 0.1 Lecturas previas (NO saltar)

Antes de codificar cualquier fase, el agente DEBE leer:

- `docs/agentes/AGENTS.md` — convenciones generales para agentes
- `docs/arquitectura/contexto.md` — contexto de negocio
- `docs/arquitectura/arquitectura_infraestructura.md` — stack y despliegue
- `docs/arquitectura/modelado_base_de_datos.md` — esquema BD completo
- `docs/api/API_ENDPOINTS.md` — contrato API actual
- `docs/operacion/railway-deploy-operacion.md` — flujo operativo Railway
- `docs/operacion/ARRANQUE_LOCAL.md` — arranque local + credenciales demo
- `docs/guias/DESIGN.md`, `docs/guias/FRONTEND_GUIDELINES.md`, `docs/guias/DATABASE_GUIDELINES.md`
- `docs/producto/ROADMAP.md`
- `docs/fases/mejoras/FASES_MEJORAS.md` — plan maestro de mejoras (fases 1–8 ya ejecutadas)

### 0.2 Stack y entorno

| Componente | Tecnología |
|------------|------------|
| Backend | PHP 8.2 + Apache (Dockerfile en raíz) |
| BD | MySQL 8 (Railway, servicio `MySQL`) |
| Frontend | HTML/CSS/JS vanilla + Tailwind |
| E2E | Playwright (`e2e/`) |
| Despliegue | Railway (auto-deploy desde GitHub `main`) |
| Repositorio | `KarlSlim7k/SGPLOPyPC` |
| URL producción | https://sgplopypc.up.railway.app |

### 0.3 Railway — proyecto vinculado

- **Proyecto:** `abundant-endurance`
- **Project ID:** `9da8c92b-bc6c-4e28-91d0-873a336e1fe6`
- **Environment:** `production`
- **Servicios:** `SGPLOPyPC` (app) + `MySQL` (db)

#### Comandos Railway permitidos al agente

```bash
# Estado del proyecto y servicios
railway status

# Ver variables de entorno (sin exponerlas en logs)
railway variables -s SGPLOPyPC --json
railway variables -s MySQL --json

# Ver logs de despliegue/runtime
railway logs -s SGPLOPyPC

# Confirmar dominio
railway domain
```

> **No usar `railway up`**. El despliegue se hace por `git push` (auto-deploy).

### 0.4 Credenciales demo (para pruebas E2E)

| Rol | Email | Password |
|-----|-------|----------|
| ADMINISTRADOR | `admin@sgplopypc.gob.mx` | `admin123` |
| PROVEEDOR | `proveedor@demo.mx` | `proveedor123` |
| PUBLICO | `publico@demo.mx` | `publico123` |

### 0.5 Ciclo obligatorio al cierre de cada fase

Cada fase termina así, sin excepciones:

1. **Validación local previa**
   - `find app public -name '*.php' -print0 | xargs -0 -n1 php -l` (sin errores de sintaxis)
   - Si tocó BD: aplicar migración y confirmar.
2. **Commit único por fase**
   - Mensaje formato: `feat(proveedor-publico/faseX): <resumen breve>` (o `fix:` / `chore:` según aplique).
   - Incluir documentación tocada en el mismo commit.
3. **Push a GitHub** — `git push origin main`
4. **Esperar deploy de Railway** — 60–180s. Confirmar:
   - `railway logs -s SGPLOPyPC | tail -50`
   - `curl -fsSL https://sgplopypc.up.railway.app/healthz` → `ok`
   - `curl -fsSL https://sgplopypc.up.railway.app/api/v1/health` → `app.status=ok`, `db.status=ok`
5. **Pruebas E2E con Playwright**
   - Comando estándar:
     ```bash
     cd e2e
     E2E_BROWSER_CHANNEL=chrome \
     E2E_BASE_URL='https://sgplopypc.up.railway.app' \
     npx playwright test <specs-relevantes-de-la-fase> --reporter=line
     ```
6. **Evidencia de cierre** (al final de cada fase, en doc de la fase):
   - hash completo del commit (40 chars)
   - deployment ID de Railway
   - resultado E2E (passed/failed/skipped)
   - URL de captura/log si aplica

### 0.6 Reglas de oro

- **Nunca** hardcodear secretos. Usar `env()` y `.env.example`.
- **Nunca** romper formato de respuesta API (`{success, message, data, errors}`).
- **Nunca** mezclar refactor masivo con feature.
- **Siempre** usar PDO con consultas preparadas.
- **Siempre** registrar acción crítica con `auditLog(...)`.
- **Siempre** mantener compatibilidad con datos demo y E2E existentes.

---

## 1. Inventario actual de archivos por rol

### 1.1 Rol Proveedor (11 páginas)

| Archivo | Propósito |
|---------|-----------|
| `frontend/proveedor/centro.html` | Dashboard hub con status, 9 tarjetas de navegación, preview de notificaciones |
| `frontend/proveedor/convocatorias.html` | Explorar licitaciones abiertas e inscribirse |
| `frontend/proveedor/licitacion.html` | Detalle de licitación + aclaraciones |
| `frontend/proveedor/participaciones.html` | Historial de inscripciones con stats y filtros |
| `frontend/proveedor/propuestas.html` | Enviar/editar propuestas y subir documentos |
| `frontend/proveedor/documentos.html` | Subir/descargar documentos legales y de propuesta |
| `frontend/proveedor/contratos.html` | Listar contratos adjudicados con stats |
| `frontend/proveedor/contrato.html` | Detalle de contrato + firma simple |
| `frontend/proveedor/notificaciones.html` | Inbox con filtros y marcado como leídas |
| `frontend/proveedor/perfil.html` | Datos fiscales, cuenta, contraseña, reputación |
| `frontend/proveedor/firma-efirma.html` | Firma electrónica con e.firma/FIEL |

### 1.2 Rol Público (4 páginas)

| Archivo | Propósito |
|---------|-----------|
| `frontend/publico/index.html` | Redirige a centro.html |
| `frontend/publico/centro.html` | Hub con stats, tarjetas de navegación, preview de notificaciones |
| `frontend/publico/aviso-de-privacidad.html` | Página legal de privacidad |
| `frontend/publico/terminos-de-uso.html` | Página legal de términos de uso |

### 1.3 Páginas PHP públicas (renderizadas por `public.js`)

| Página | Propósito |
|--------|-----------|
| `/` (landing) | Estadísticas, convocatorias preview, resumen de transparencia |
| `/evaluacion.php` | Procesos en evaluación/recepción con barras de progreso |
| `/historial.php` | Búsqueda paginada con filtros de año/tipo |
| `/contratos.php` | Contratos públicos con filtros y paginación |
| `/resultados.php` | Resultados de adjudicación |
| `/convocatoria.php?id=N` | Detalle de licitación pública |
| `/registro.php` | Formulario de registro público de proveedores |

### 1.4 Endpoints backend relevantes

**Proveedor (autenticado):**

| Método | Endpoint | Controlador |
|--------|----------|-------------|
| GET | `/api/v1/me` | UserController |
| PUT | `/api/v1/me/profile` | UserController |
| POST | `/api/v1/me/password` | UserController |
| GET | `/api/v1/proveedores/{id}` | ProveedorController |
| PUT | `/api/v1/proveedores/{id}` | ProveedorController |
| GET | `/api/v1/proveedores/{id}/reputacion` | ReputacionController |
| GET | `/api/v1/licitaciones` | LicitacionController |
| POST | `/api/v1/licitaciones/{id}/participaciones` | ParticipacionController |
| GET | `/api/v1/participaciones/mias` | ParticipacionController |
| POST | `/api/v1/participaciones/{id}/propuesta` | ParticipacionController |
| PUT | `/api/v1/participaciones/{id}/propuesta` | ParticipacionController |
| POST | `/api/v1/documentos/upload` | DocumentoController |
| GET | `/api/v1/documentos/mios` | DocumentoController |
| GET | `/api/v1/contratos/mios` | ContratoController |
| POST | `/api/v1/contratos/{id}/firma` | ContratoController |
| POST | `/api/v1/contratos/{id}/firma-efirma` | EfirmaController |
| GET | `/api/v1/notificaciones/mias` | NotificacionController |
| PATCH | `/api/v1/notificaciones/{id}/leida` | NotificacionController |
| GET | `/api/v1/notificaciones/stream` | NotificacionStreamController |

**Público (sin auth):**

| Método | Endpoint | Controlador |
|--------|----------|-------------|
| GET | `/api/v1/public/estadisticas` | PublicController |
| GET | `/api/v1/public/convocatorias` | PublicController |
| GET | `/api/v1/public/convocatorias/{id}` | PublicController |
| GET | `/api/v1/public/resultados` | PublicController |
| GET | `/api/v1/public/contratos` | PublicController |
| GET | `/api/v1/public/evaluaciones` | PublicController |
| GET | `/api/v1/public/historial` | PublicController |
| POST | `/api/v1/public/soporte` | PublicController |

---

## 2. Lista priorizada de fases

| # | Fase | Rol | Prioridad | Esfuerzo | Dependencias |
|---|------|-----|-----------|----------|--------------|
| P1 | Dashboard con KPIs para proveedor | Proveedor | 🔴 Alta | Medio | Ninguna |
| P2 | Perfil + contraseña + notificaciones para público | Público | 🔴 Alta | Bajo | Ninguna |
| P3 | Integrar notificaciones SSE en proveedor | Proveedor | 🟠 Media-Alta | Bajo | Fase 6 (SSE) ya completada |
| P4 | Paginación y exportación CSV para proveedor | Proveedor | 🟠 Media-Alta | Medio | Ninguna |
| P5 | Recuperación de contraseña | Proveedor | 🟠 Media | Bajo | Endpoint ya existe |
| P6 | Gestión MFA para proveedor | Proveedor | 🟡 Media | Medio | Fase 5 (MFA) ya completada |
| P7 | Historial de reputación | Proveedor | 🟡 Media-Baja | Medio | Fase 8 (reputación) ya completada |
| P8 | Tickets de soporte desde proveedor | Proveedor | 🟡 Media-Baja | Bajo | Endpoint ya existe |
| P9 | Eliminación de documentos y retiro de propuestas | Proveedor | 🟢 Baja | Bajo | Ninguna |
| P10 | Enlace e.firma desde contratos | Proveedor | 🟢 Baja | Bajo | Fase 7 (e.firma) ya completada |
| P11 | Favoritos/marcadores de licitaciones | Público | 🟡 Media | Medio | Nuevo esquema BD |
| P12 | Descarga de datos abiertos (OCDS) | Público | 🟡 Media-Baja | Bajo | Fase 3 (OCDS) ya completada |
| P13 | Unificación arquitectónica y transversales | Ambos | 🟢 Baja | Medio-Alto | Ninguna |

---

## FASE P1 — Dashboard con KPIs para proveedor

### Objetivo
Dotar al proveedor de un dashboard con métricas visuales de su actividad: tasa de ganancia, montos propuestos, participaciones por período, y contratos activos.

### Entregables

1. **Endpoints nuevos** (extender `ProveedorController` o crear `ProveedorMetricasController`):
   - `GET /api/v1/proveedores/{id}/metricas` — responde:
     ```json
     {
       "total_participaciones": 12,
       "total_propuestas": 8,
       "total_ganadas": 3,
       "tasa_ganancia": 37.5,
       "monto_total_propuesto": 4500000,
       "monto_total_adjudicado": 1200000,
       "contratos_vigentes": 2,
       "participaciones_por_mes": [{"mes": "2026-01", "count": 3}, ...],
       "distribucion_por_tipo": {"ACTA_FALLO": 5, "CONTRATO": 3, ...}
     }
     ```
   - `GET /api/v1/proveedores/{id}/metricas/tendencia` — serie de tiempo de participaciones/montos por trimestre.
2. **Frontend** — `frontend/proveedor/centro.html` ampliado:
   - Reemplazar las 9 tarjetas estáticas por un dashboard con:
     - 4 stat cards con KPIs principales (participaciones, tasa ganancia, monto adjudicado, contratos vigentes).
     - Gráfica de barras: participaciones por mes (Chart.js CDN).
     - Gráfica de dona: distribución por tipo de licitación.
     - Sección "Últimas participaciones" (tabla compacta con badges de estado).
   - Mantener tarjetas de navegación rápida debajo del dashboard.
3. **Cache** — cache simple (file-based, TTL 5 min) para queries de métricas.
4. **Tests E2E** — `e2e/tests/proveedor-dashboard.spec.ts`:
   - Verificar que el dashboard carga con datos.
   - Verificar que las gráficas se renderizan (canvas presente).
   - Verificar stat cards con valores > 0.

### Verificación
```bash
curl -fsSL https://sgplopypc.up.railway.app/api/v1/proveedores/1/metricas \
  -H "Authorization: Bearer <token-proveedor>" | jq .
```

### Cierre
Commit + push + deploy + E2E `proveedor-dashboard.spec.ts`.

---

## FASE P2 — Perfil + contraseña + notificaciones para público

### Objetivo
Dar al usuario público una experiencia autogestionada: editar perfil, cambiar contraseña y gestionar notificaciones.

### Entregables

1. **Página de perfil** — `frontend/publico/perfil.html`:
   - Formulario con: nombre, email (read-only si no hay verificación).
   - Cambio de contraseña: actual, nueva (con indicador de fortaleza), confirmación.
   - Usa los endpoints existentes: `PUT /api/v1/me/profile` y `POST /api/v1/me/password`.
   - Mostrar: ID usuario, fecha registro, última conexión.
2. **Página de notificaciones** — `frontend/publico/notificaciones.html`:
   - Lista completa de notificaciones (usa `GET /api/v1/notificaciones/mias`).
   - Filtros: todas, no leídas.
   - Botón "Marcar como leída" individual y "Marcar visibles como leídas".
   - Link contextual por tipo de notificación.
3. **Actualizar centro.html**:
   - Agregar tarjeta "Mi perfil" en la sección de navegación.
   - Agregar enlace "Ver todas" en el preview de notificaciones que lleve a `notificaciones.html`.
   - Botón de cerrar sesión visible en el header.
4. **Tests E2E** — `e2e/tests/publico-perfil-notificaciones.spec.ts`:
   - Login como público → navegar a perfil → editar nombre → guardar.
   - Login como público → ver notificaciones → marcar una como leída.

### Verificación
```bash
# Login como público
curl -X POST https://sgplopypc.up.railway.app/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"publico@demo.mx","password":"publico123"}'

# Verificar notificaciones
curl -fsSL https://sgplopypc.up.railway.app/api/v1/notificaciones/mias \
  -H "Authorization: Bearer <token-publico>" | jq .
```

### Cierre
Commit + push + deploy + E2E `publico-perfil-notificaciones.spec.ts`.

---

## FASE P3 — Integrar notificaciones SSE en proveedor

### Objetivo
Activar notificaciones en tiempo real en todas las páginas del proveedor usando el módulo SSE existente (`notif-stream.js`).

### Contexto técnico
- El endpoint `GET /api/v1/notificaciones/stream` ya existe (Fase 6 SSE completada).
- El cliente `frontend/shared/notif-stream.js` ya existe.
- **Ninguna página de proveedor lo integra actualmente** — las notificaciones solo se cargan al render inicial.

### Entregables

1. **Integración de `notif-stream.js`** en todas las páginas de proveedor:
   - `centro.html`, `convocatorias.html`, `licitacion.html`, `participaciones.html`, `propuestas.html`, `documentos.html`, `contratos.html`, `contrato.html`, `notificaciones.html`, `perfil.html`.
   - Incluir el script: `<script src="/frontend/shared/notif-stream.js"></script>`.
   - Inicializar con el token del usuario: `SGPLNotifStream.init(token, onNotification)`.
2. **Badge de notificaciones en navbar**:
   - Agregar contador de no leídas en el header de todas las páginas.
   - Actualización en tiempo real vía SSE (sin recargar).
   - Click → navega a `notificaciones.html`.
3. **Toast de notificación nueva**:
   - Cuando llega un evento SSE, mostrar toast flotante (esquina inferior derecha) con título y tipo.
   - Auto-desaparece en 5s. Click → navega al recurso correspondiente.
4. **Fallback polling**:
   - Si SSE falla, `notif-stream.js` ya tiene fallback a polling cada 30s.
   - Verificar que el fallback funciona correctamente.
5. **Tests E2E** — `e2e/tests/proveedor-notif-realtime.spec.ts`:
   - Login como proveedor → verificar que el badge muestra el conteo correcto.
   - Crear notificación vía API → verificar que el badge se actualiza sin recargar (timeout 15s).

### Verificación
- Abrir `centro.html` como proveedor en modo incógnito.
- Desde otra sesión (admin), crear una notificación para el proveedor.
- Verificar que el badge se actualiza y aparece toast en < 5s.

### Cierre
Commit + push + deploy + E2E `proveedor-notif-realtime.spec.ts`.

---

## FASE P4 — Paginación y exportación CSV para proveedor

### Objetivo
Resolver la limitación de cargar todos los registros a la vez (actualmente `limit=100`) y permitir exportar datos propios.

### Entregables

1. **Paginación backend** — extender endpoints existentes con parámetros estándar:
   - `page` (default 1), `per_page` (default 20, max 100).
   - Respuesta incluir: `{ items: [...], total, page, per_page, total_pages }`.
   - Endpoints a modificar:
     - `GET /api/v1/licitaciones`
     - `GET /api/v1/participaciones/mias`
     - `GET /api/v1/propuestas/mias`
     - `GET /api/v1/documentos/mios`
     - `GET /api/v1/contratos/mios`
     - `GET /api/v1/notificaciones/mias`
2. **Componente de paginación reutilizable** — `frontend/shared/pagination.js`:
   - Función `renderPagination(container, { page, total_pages, onPageChange })`.
   - UI: botones « Anterior, números de página, Siguiente ».
   - Integrar en todas las páginas de listado del proveedor.
3. **Exportación CSV** — agregar endpoints:
   - `GET /api/v1/participaciones/mias/export.csv`
   - `GET /api/v1/propuestas/mias/export.csv`
   - `GET /api/v1/contratos/mios/export.csv`
   - Headers: `Content-Type: text/csv`, `Content-Disposition: attachment`.
   - Usar helper `csvExport()` de `admin.js`.
4. **Frontend** — agregar botón "Exportar CSV" en:
   - `participaciones.html`, `propuestas.html`, `contratos.html`.
   - Solo visible cuando hay datos para exportar.
5. **Tests E2E** — `e2e/tests/proveedor-paginacion-export.spec.ts`:
   - Verificar que la paginación renderiza los botones correctos.
   - Verificar que cambiar de página actualiza la tabla.
   - Verificar que exportar CSV descarga un archivo válido.

### Verificación
```bash
curl -fsSL "https://sgplopypc.up.railway.app/api/v1/participaciones/mias?page=1&per_page=5" \
  -H "Authorization: Bearer <token-proveedor>" | jq '.pagination'

curl -fsSL "https://sgplopypc.up.railway.app/api/v1/contratos/mios/export.csv" \
  -H "Authorization: Bearer <token-proveedor>" | head -5
```

### Cierre
Commit + push + deploy + E2E `proveedor-paginacion-export.spec.ts`.

---

## FASE P5 — Recuperación de contraseña

### Objetivo
Permitir al proveedor (y público) recuperar su contraseña cuando la olvida, usando el endpoint backend existente.

### Contexto técnico
- El backend ya tiene `POST /auth/password/forgot` y `POST /auth/password/reset`.
- **No existe frontend** para estos endpoints.

### Entregables

1. **Página de solicitud** — `frontend/auth/password-forgot.html`:
   - Input: email.
   - Submit → `POST /api/v1/auth/password/forgot`.
   - Mensaje de confirmación: "Si el email existe, recibirás instrucciones para restablecer tu contraseña." (no revelar si existe o no).
2. **Página de reset** — `frontend/auth/password-reset.html`:
   - Input: token (desde URL query), nueva contraseña (con fortaleza), confirmación.
   - Submit → `POST /api/v1/auth/password/reset`.
   - Validación: mínimo 8 chars, mayúscula, número, símbolo.
   - Éxito → redirigir a login con mensaje flash.
3. **Enlace desde login** — agregar "¿Olvidaste tu contraseña?" en la página de login existente.
4. **Enlace desde perfil** — agregar "Cambiar contraseña" en `proveedor/perfil.html` y `publico/perfil.html` (creada en Fase P2).
5. **Email template** — verificar que el backend envía email con link a `password-reset.html?token=...`.
   - Si el sistema de email no está configurado en Railway, documentar en el cierre de fase y mostrar el token en la respuesta (solo para desarrollo).
6. **Tests E2E** — `e2e/tests/auth-password-reset.spec.ts`:
   - Solicitar reset → verificar que redirige a la página correcta.
   - Intentar reset con token inválido → verificar error.
   - Reset exitoso → verificar que puede hacer login con nueva contraseña.

### Verificación
```bash
# Solicitar reset
curl -X POST https://sgplopypc.up.railway.app/api/v1/auth/password/forgot \
  -H "Content-Type: application/json" \
  -d '{"email":"proveedor@demo.mx"}'

# Reset con token
curl -X POST https://sgplopypc.up.railway.app/api/v1/auth/password/reset \
  -H "Content-Type: application/json" \
  -d '{"token":"...","password":"NuevaPass123!"}'
```

### Cierre
Commit + push + deploy + E2E `auth-password-reset.spec.ts`.

---

## FASE P6 — Gestión MFA para proveedor

### Objetivo
Permitir al proveedor activar, confirmar y desactivar la autenticación multifactor desde su perfil.

### Contexto técnico
- El backend ya soporta MFA (Fase 5 completada): endpoints `enroll`, `confirm`, `disable`.
- **No existe UI** para que el proveedor gestione MFA.

### Entregables

1. **Sección MFA en perfil** — ampliar `frontend/proveedor/perfil.html`:
   - Nueva sección "Seguridad adicional (2FA)" debajo del cambio de contraseña.
   - Si MFA está desactivado: botón "Activar 2FA" → navega a `mfa-enroll.html`.
   - Si MFA está activado: badge verde "2FA activado", botón "Desactivar 2FA" (requiere password + código actual).
2. **Página de enrollment** — `frontend/auth/mfa-enroll.html`:
   - Mostrar QR code con `qrious` (CDN) para escanear con Google Authenticator/Authy.
   - Input: código de 6 dígitos para confirmar.
   - Submit → `POST /api/v1/me/mfa/confirm`.
   - Mostrar códigos de respaldo (8 códigos) con botón "Copiar todos" y "Descargar .txt".
   - Advertencia: "Guarda estos códigos en un lugar seguro. No se mostrarán de nuevo."
3. **Página de challenge** — `frontend/auth/mfa-challenge.html`:
   - Si el login devuelve `{requires_mfa: true}`, redirigir aquí.
   - Input: código de 6 dígitos.
   - Submit → `POST /api/v1/auth/login/mfa`.
   - Link "Usar código de respaldo" → input alternativo.
4. **Integración con login**:
   - Modificar la lógica de login para detectar `requires_mfa` y redirigir a `mfa-challenge.html`.
5. **Tests E2E** — `e2e/tests/proveedor-mfa.spec.ts`:
   - Activar MFA → verificar QR code se renderiza.
   - Confirmar con código → verificar que se activa.
   - Login con MFA → verificar challenge aparece.
   - Desactivar MFA → verificar que el login funciona sin challenge.

### Verificación
- Login como proveedor → perfil → activar 2FA → escanear QR con app de autenticación.
- Cerrar sesión → login → verificar que pide código 2FA.

### Cierre
Commit + push + deploy + E2E `proveedor-mfa.spec.ts`.

---

## FASE P7 — Historial de reputación

### Objetivo
Mostrar al proveedor el detalle de sus evaluaciones post-contrato y cómo se calcula su score de reputación.

### Contexto técnico
- El score ya existe en `proveedor.score_reputacion` y las evaluaciones en `proveedor_evaluacion_postcontrato` (Fase 8).
- `perfil.html` muestra el score pero **no el historial**.

### Entregables

1. **Endpoint ampliado** — modificar `GET /api/v1/proveedores/{id}/reputacion`:
   ```json
   {
     "score": 4.2,
     "total_evaluaciones": 5,
     "evaluaciones": [
       {
         "id_eval": 1,
         "id_contrato": 3,
         "contrato_numero": "CONT-2026-001",
         "puntualidad": 5,
         "calidad": 4,
         "comunicacion": 4,
         "cumplimiento_alcance": 4,
         "comentarios": "Buen cumplimiento general",
         "fecha_evaluacion": "2026-04-15"
       }
     ],
     "desglose": {
       "puntualidad_promedio": 4.5,
       "calidad_promedio": 4.0,
       "comunicacion_promedio": 3.8,
       "cumplimiento_alcance_promedio": 4.2
     }
   }
   ```
2. **Página de reputación** — `frontend/proveedor/reputacion.html`:
   - Score general con estrella grande y color (verde ≥4, ámbar ≥3, rojo <3).
   - Gráfica radar (Chart.js) con los 4 criterios promedio.
   - Tabla de evaluaciones con: contrato, criterios (1-5 con estrellas), comentarios, fecha.
   - Explicación de cómo se calcula el score.
3. **Enlace desde perfil** — agregar "Ver historial completo" junto al badge de reputación en `perfil.html`.
4. **Tests E2E** — `e2e/tests/proveedor-reputacion-detalle.spec.ts`:
   - Verificar que la página carga con datos.
   - Verificar que la gráfica radar se renderiza.
   - Verificar que la tabla muestra evaluaciones.

### Verificación
```bash
curl -fsSL https://sgplopypc.up.railway.app/api/v1/proveedores/1/reputacion \
  -H "Authorization: Bearer <token-proveedor>" | jq '.evaluaciones'
```

### Cierre
Commit + push + deploy + E2E `proveedor-reputacion-detalle.spec.ts`.

---

## FASE P8 — Tickets de soporte desde proveedor

### Objetivo
Permitir al proveedor crear y consultar tickets de soporte desde su panel.

### Contexto técnico
- El endpoint `POST /api/v1/public/soporte` ya existe.
- **No hay UI** en el frontend de proveedor para usarlo.

### Entregables

1. **Esquema BD ampliado** (`database/migrations/0XX_soporte_tickets.sql`):
   ```sql
   CREATE TABLE soporte_ticket (
     id_ticket INT AUTO_INCREMENT PRIMARY KEY,
     id_usuario INT NOT NULL,
     asunto VARCHAR(200) NOT NULL,
     descripcion TEXT NOT NULL,
     prioridad ENUM('BAJA','MEDIA','ALTA','URGENTE') DEFAULT 'MEDIA',
     estado ENUM('ABIERTO','EN_PROCESO','RESUELTO','CERRADO') DEFAULT 'ABIERTO',
     fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
     fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
     FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
   );
   CREATE TABLE soporte_respuesta (
     id_respuesta INT AUTO_INCREMENT PRIMARY KEY,
     id_ticket INT NOT NULL,
     id_usuario INT NOT NULL,
     mensaje TEXT NOT NULL,
     fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
     FOREIGN KEY (id_ticket) REFERENCES soporte_ticket(id_ticket) ON DELETE CASCADE,
     FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
   );
   ```
2. **Endpoints nuevos**:
   - `POST /api/v1/soporte/tickets` — crear ticket.
   - `GET /api/v1/soporte/tickets/mios` — listar tickets propios.
   - `GET /api/v1/soporte/tickets/{id}` — detalle de ticket con respuestas.
   - `POST /api/v1/soporte/tickets/{id}/respuestas` — agregar respuesta.
   - `PATCH /api/v1/soporte/tickets/{id}/estado` — admin cambia estado.
3. **Frontend** — `frontend/proveedor/soporte.html`:
   - Lista de tickets con: asunto, prioridad, estado, fecha.
   - Botón "Nuevo ticket" → formulario con asunto, descripción, prioridad.
   - Vista de detalle con hilo de respuestas.
   - Input para agregar respuesta.
4. **Tests E2E** — `e2e/tests/proveedor-soporte.spec.ts`:
   - Crear ticket → verificar que aparece en la lista.
   - Agregar respuesta → verificar que se muestra en el hilo.

### Verificación
```bash
# Crear ticket
curl -X POST https://sgplopypc.up.railway.app/api/v1/soporte/tickets \
  -H "Authorization: Bearer <token-proveedor>" \
  -H "Content-Type: application/json" \
  -d '{"asunto":"No puedo subir documentos","descripcion":"Error 500 al subir PDF","prioridad":"ALTA"}'
```

### Cierre
Commit + push + deploy + E2E `proveedor-soporte.spec.ts`.

---

## FASE P9 — Eliminación de documentos y retiro de propuestas

### Objetivo
Dar al proveedor control completo sobre sus documentos y propuestas: poder eliminar documentos y retirar propuestas.

### Entregables

1. **Eliminación de documentos** — endpoint nuevo:
   - `DELETE /api/v1/documentos/{id}` — elimina documento propio (solo si no está vinculado a una propuesta ya evaluada).
   - Validar: no eliminar si el documento está en una propuesta con estatus `EN_REVISION`, `ACEPTADA` o `RECHAZADA`.
   - Eliminar archivo físico de `storage/`.
   - Auditoría: `auditLog('documento_eliminado', ...)`.
2. **Retiro de propuestas** — endpoint nuevo:
   - `POST /api/v1/propuestas/{id}/retirar` — retira propuesta propia.
   - Validar: solo si estatus es `RECIBIDA` y proceso está en `RECEPCION_PROPUESTAS`.
   - Cambiar estatus a `RETIRADA`.
   - Auditoría: `auditLog('propuesta_retirada', ...)`.
3. **Frontend** — modificaciones:
   - `documentos.html`: agregar botón "Eliminar" (rojo) en cada fila, con confirmación.
   - `propuestas.html`: agregar botón "Retirar propuesta" con confirmación (solo visible cuando aplica).
4. **Tests E2E** — `e2e/tests/proveedor-documentos-propuestas.spec.ts`:
   - Subir documento → eliminar → verificar que desaparece.
   - Crear propuesta → retirar → verificar cambio de estatus.
   - Intentar eliminar documento vinculado a propuesta evaluada → verificar error 409.

### Verificación
```bash
# Eliminar documento
curl -X DELETE https://sgplopypc.up.railway.app/api/v1/documentos/5 \
  -H "Authorization: Bearer <token-proveedor>"

# Retirar propuesta
curl -X POST https://sgplopypc.up.railway.app/api/v1/propuestas/3/retirar \
  -H "Authorization: Bearer <token-proveedor>"
```

### Cierre
Commit + push + deploy + E2E `proveedor-documentos-propuestas.spec.ts`.

---

## FASE P10 — Enlace e.firma desde contratos

### Objetivo
Conectar la página de detalle de contrato con la página de firma electrónica e.firma, actualmente aislada.

### Contexto técnico
- `frontend/proveedor/firma-efirma.html` existe pero no hay navegación hacia ella desde `contrato.html` ni `contratos.html`.

### Entregables

1. **Modificar `contrato.html`**:
   - Si el contrato está en `EN_FORMALIZACION` y no está firmado:
     - Mostrar botón "Firmar con e.firma" (además del botón "Firmar contrato" simple existente).
     - Link → `/frontend/proveedor/firma-efirma.html?id_contrato={id}`.
   - Si ya está firmado con e.firma:
     - Mostrar badge "Firmado con e.firma" con datos del certificado.
2. **Modificar `contratos.html`**:
   - En la columna "Firma", si el contrato tiene e.firma, mostrar ícono de sello verde.
   - Tooltip: "Firmado con e.firma el {fecha}".
3. **Modificar `firma-efirma.html`**:
   - Aceptar `id_contrato` como query param.
   - Botón "Volver al contrato" → `contrato.html?id={id_contrato}`.
4. **Tests E2E** — `e2e/tests/proveedor-efirma-navegacion.spec.ts`:
   - Navegar a contrato en formalización → verificar que aparece botón "Firmar con e.firma".
   - Click → verificar que navega a `firma-efirma.html` con el parámetro correcto.

### Verificación
- Login como proveedor → contratos → abrir contrato en formalización → verificar botones de firma.

### Cierre
Commit + push + deploy + E2E `proveedor-efirma-navegacion.spec.ts`.

---

## FASE P11 — Favoritos/marcadores de licitaciones (público)

### Objetivo
Permitir al usuario público guardar licitaciones de interés para seguimiento rápido.

### Entregables

1. **Esquema BD** (`database/migrations/0XX_favoritos.sql`):
   ```sql
   CREATE TABLE licitacion_favorito (
     id_favorito INT AUTO_INCREMENT PRIMARY KEY,
     id_usuario INT NOT NULL,
     id_licitacion INT NOT NULL,
     fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
     UNIQUE KEY uk_usuario_licitacion (id_usuario, id_licitacion),
     FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario),
     FOREIGN KEY (id_licitacion) REFERENCES licitacion(id_licitacion) ON DELETE CASCADE
   );
   ```
2. **Endpoints nuevos**:
   - `POST /api/v1/favoritos` — agregar a favoritos. Body: `{id_licitacion}`.
   - `DELETE /api/v1/favoritos/{id_licitacion}` — quitar de favoritos.
   - `GET /api/v1/favoritos` — listar favoritos del usuario con datos de la licitación.
3. **Frontend**:
   - En `/convocatoria.php?id=N`: botón estrella (☆/★) para marcar/desmarcar.
   - Nueva página `frontend/publico/favoritos.html`: lista de licitaciones favoritas con filtros de estado y link a detalle.
   - En `centro.html`: tarjeta "Mis favoritos" con conteo y últimas 3 guardadas.
4. **Tests E2E** — `e2e/tests/publico-favoritos.spec.ts`:
   - Marcar licitación como favorita → verificar que aparece en la lista.
   - Desmarcar → verificar que desaparece.

### Verificación
```bash
# Agregar favorito
curl -X POST https://sgplopypc.up.railway.app/api/v1/favoritos \
  -H "Authorization: Bearer <token-publico>" \
  -H "Content-Type: application/json" \
  -d '{"id_licitacion": 1}'

# Listar favoritos
curl -fsSL https://sgplopypc.up.railway.app/api/v1/favoritos \
  -H "Authorization: Bearer <token-publico>" | jq .
```

### Cierre
Commit + push + deploy + E2E `publico-favoritos.spec.ts`.

---

## FASE P12 — Descarga de datos abiertos (OCDS) para público

### Objetivo
Permitir al usuario público acceder y descargar datos en formato OCDS desde el panel autenticado.

### Contexto técnico
- El endpoint `GET /api/v1/datos-abiertos/releases` ya existe (Fase 3 OCDS).
- **No hay UI** para acceder desde el panel de público.

### Entregables

1. **Página de datos abiertos** — `frontend/publico/datos-abiertos.html`:
   - Explicación de OCDS y su propósito de transparencia.
   - Tabla resumen de releases con paginación.
   - Filtros: año, tipo de procedimiento, estado.
   - Botón "Descargar paquete completo (JSON)" → `GET /api/v1/datos-abiertos/release-package`.
   - Botón "Descargar CSV" → genera CSV desde los datos OCDS.
   - Link a documentación OCDS estándar.
2. **Enlace desde centro.html**:
   - Tarjeta "Datos abiertos" con ícono de descarga y descripción.
3. **Tests E2E** — `e2e/tests/publico-datos-abiertos.spec.ts`:
   - Verificar que la página carga.
   - Verificar que la tabla muestra datos.
   - Verificar que la descarga JSON funciona.

### Verificación
```bash
curl -fsSL https://sgplopypc.up.railway.app/api/v1/datos-abiertos/releases?limit=5 | jq .
```

### Cierre
Commit + push + deploy + E2E `publico-datos-abiertos.spec.ts`.

---

## FASE P13 — Unificación arquitectónica y transversales

### Objetivo
Resolver problemas transversales de arquitectura y calidad que afectan a ambos roles.

### Entregables

1. **Tailwind CSS compilado** (reemplazar CDN):
   - Crear `tailwind.config.js` en raíz con contenido de todas las páginas.
   - Compilar con `npx tailwindcss -i ./frontend/shared/tailwind-input.css -o ./frontend/shared/tailwind-output.css --minify`.
   - Reemplazar `<script src="https://cdn.tailwindcss.com"></script>` por `<link href="/frontend/shared/tailwind-output.css">` en todas las páginas.
   - Agregar al Dockerfile: `npx tailwindcss build` durante el build.
   - **Impacto**: reducir ~300KB de payload por página.

2. **Unificar carga de scripts**:
   - `firma-efirma.html` usa `public.js` → cambiar a `admin.js` (como el resto de proveedor).
   - `centro.html` de público carga 3 scripts → consolidar en uno solo con imports selectivos.
   - Verificar que no hay funciones duplicadas entre `admin.js` y `public.js`.

3. **Error boundary global**:
   - Crear `frontend/shared/error-handler.js`:
     ```javascript
     window.addEventListener('unhandledrejection', function(event) {
       // Log a servicio de monitoreo (o console.error)
       // Mostrar toast de error genérico sin romper UX
     });
     window.addEventListener('error', function(event) {
       // Mismo manejo
     });
     ```
   - Incluir en todas las páginas.

4. **Empty states mejorados**:
   - Componente reutilizable `<div class="empty-state">` con:
     - Ícono Phosphor grande y gris.
     - Título "Sin datos aún".
     - Descripción contextual.
     - CTA (call to action) si aplica.
   - Aplicar en: convocatorias sin resultados, participaciones vacías, propuestas sin enviar, contratos sin adjudicar, documentos sin subir, notificaciones sin leer.

5. **Breadcrumbs**:
   - Agregar breadcrumbs en todas las páginas de proveedor y público.
   - Ejemplo: `Inicio > Mis participaciones > LP-DEMO-2026-001`.
   - Componente reutilizable en `frontend/shared/breadcrumbs.js`.

6. **Lazy loading de imágenes**:
   - Agregar `loading="lazy"` a todas las imágenes de páginas públicas (logos de convocatorias, etc.).
   - Verificar con Lighthouse.

7. **Tests E2E** — `e2e/tests/transversales-calidad.spec.ts`:
   - Verificar que Tailwind CSS carga (no CDN).
   - Verificar que no hay errores JS en consola al cargar cada página.
   - Verificar empty states en páginas sin datos.

### Verificación
```bash
# Verificar que Tailwind no carga desde CDN
curl -fsSL https://sgplopypc.up.railway.app/frontend/proveedor/centro.html | grep -c 'cdn.tailwindcss.com'
# Debe regresar 0

# Verificar error handler
# Abrir consola del navegador → provocar error → verificar que se captura
```

### Cierre
Commit + push + deploy + E2E `transversales-calidad.spec.ts`.

---

## Anexo A — Plantilla de cierre de fase

```markdown
## Cierre de Fase PX

- **Commit:** <hash de 40 chars>
- **Deployment Railway:** <deployment id>
- **URL:** https://sgplopypc.up.railway.app
- **Healthcheck post-deploy:** `/healthz` → 200, `/api/v1/health` → app=ok, db=ok
- **E2E:** <X passed / Y skipped / Z failed>
- **Endpoints nuevos:** lista
- **Tablas/columnas nuevas:** lista
- **Documentación creada/actualizada:** lista
```

---

## Anexo B — Comandos de verificación rápida

```bash
# Sintaxis PHP
find app public -name '*.php' -print0 | xargs -0 -n1 php -l

# Healthcheck producción
curl -fsSL https://sgplopypc.up.railway.app/healthz
curl -fsSL https://sgplopypc.up.railway.app/api/v1/health | jq .

# E2E de proveedor
cd e2e
E2E_BROWSER_CHANNEL=chrome \
E2E_BASE_URL='https://sgplopypc.up.railway.app' \
npx playwright test tests/proveedor-*.spec.ts --reporter=line

# E2E de público
E2E_BROWSER_CHANNEL=chrome \
E2E_BASE_URL='https://sgplopypc.up.railway.app' \
npx playwright test tests/publico-*.spec.ts --reporter=line

# Logs Railway
railway logs -s SGPLOPyPC | tail -100

# Estado deployment
railway status
```

---

## Anexo C — Manejo de errores y rollback

Si después del push el deploy falla o el smoke (`/healthz`) regresa error:

1. Revisar `railway logs -s SGPLOPyPC` — buscar stack trace.
2. Si el error es de migración, revertir manualmente la migración fallida en MySQL.
3. Si el error es de código, hacer `git revert <hash>` + `git push` (NO `git reset --hard` sobre `main` remoto).
4. Documentar el incidente en el documento de la fase.

---

**Fin del documento.**
