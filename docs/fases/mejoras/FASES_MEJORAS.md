# FASES_MEJORAS.md — Plan de Implementación de Mejoras (SGPLOPyPC)

> Documento dirigido a **agentes de IA** que ejecutarán las fases de mejora del sistema **SGPLOPyPC**.
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
- `docs/api/openapi-public.yaml` — OpenAPI público
- `docs/operacion/railway-deploy-operacion.md` — flujo operativo Railway
- `docs/operacion/ARRANQUE_LOCAL.md` — arranque local + credenciales demo
- `docs/operacion/FASE_CIERRE_VALIDACION.md` — estado al cierre Fase 5
- `docs/guias/DESIGN.md`, `docs/guias/FRONTEND_GUIDELINES.md`, `docs/guias/DATABASE_GUIDELINES.md`
- `docs/producto/ROADMAP.md`

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

# Establecer variables nuevas
railway variables --set "CLAVE=valor" -s SGPLOPyPC

# Ver logs de despliegue/runtime
railway logs -s SGPLOPyPC

# Ejecutar comandos contra producción (con vars inyectadas)
railway run -s SGPLOPyPC php scripts/algun_script.php

# Confirmar dominio
railway domain
```

> **No usar `railway up`**. El despliegue se hace por `git push` (auto-deploy).

#### Acceso directo a MySQL (cuando se necesite migrar/seed)

```bash
# Host público (desde fuera de Railway)
mysql -h zephyr.proxy.rlwy.net -P 51203 -u root \
  -p"$MYSQL_ROOT_PASSWORD" --ssl-verify-server-cert=false railway < archivo.sql
```

> El password está en `MYSQLPASSWORD` (variable del servicio MySQL). **Nunca commitear**.

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
   - Si tocó BD: aplicar migración local o vía host público y confirmar.
2. **Commit único por fase**
   - Mensaje formato: `feat(faseX-mejora): <resumen breve>` (o `fix:` / `chore:` según aplique).
   - Incluir documentación tocada en el mismo commit.
3. **Push a GitHub** — `git push origin main`
4. **Esperar deploy de Railway** — 60–180s. Confirmar:
   - `railway logs -s SGPLOPyPC | tail -50`
   - `curl -fsSL https://sgplopypc.up.railway.app/healthz` → `ok`
   - `curl -fsSL https://sgplopypc.up.railway.app/api/v1/health` → `app.status=ok`, `db.status=ok`
5. **Pruebas E2E con navegador (modo incógnito / sin cache)**
   - Herramienta primaria: **Playwright** (`e2e/`).
   - Herramienta alterna autorizada: **Webwright** (https://github.com/microsoft/Webwright) cuando se requiera control conversacional del navegador.
   - Si se usa Chrome directo, **siempre modo incógnito** (`--incognito`) para evitar caché y sesiones residuales.
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

## 1. Lista priorizada de fases

| # | Fase | Prioridad | Riesgo | Dependencias |
|---|------|-----------|--------|--------------|
| 1 | Auditoría y bitácora de acciones | 🔴 Alta | Bajo | Ninguna |
| 2 | Reportes con plantillas (PDF/DOCX + editor WYSIWYG) | 🔴 Alta | Medio | Fase 1 (audit log) |
| 3 | API pública de datos abiertos (OCDS) | 🟠 Media-Alta | Bajo | Fase 1 |
| 4 | Dashboard analítico con métricas | 🟠 Media-Alta | Bajo | Fase 1 |
| 5 | Autenticación multifactor (MFA/2FA) | 🟠 Media | Medio | Ninguna |
| 6 | Notificaciones en tiempo real (SSE) | 🟡 Media | Medio | Ninguna |
| 7 | Firma electrónica avanzada (e.firma/FIEL) | 🟡 Media-Baja | Alto | Fase 1 |
| 8 | Calificación y reputación de proveedores | 🟢 Baja | Bajo | Fase 4 |

---

## FASE 1 — Auditoría y bitácora de acciones

### Objetivo
Registro inmutable y consultable de todas las acciones críticas (login, cambios de estado, adjudicaciones, firmas, exports). Base para transparencia gubernamental y auditorías.

### Contexto técnico
- Ya existe `app/helpers/audit.php` con `auditLog()` y la tabla `historial_cambio`.
- Ampliar/estandarizar uso, exponer endpoint admin de consulta, agregar exportación.

### Entregables
1. **Esquema BD ampliado** (`database/migrations/012_auditoria_extendida.sql`):
   - Agregar columnas: `ip_origen VARCHAR(45)`, `user_agent VARCHAR(500)`, `request_id VARCHAR(40)`.
   - Índices: `(id_usuario, fecha)`, `(tabla_afectada, accion, fecha)`.
2. **Helper `audit.php` actualizado** — captura `$_SERVER['REMOTE_ADDR']`, `HTTP_USER_AGENT` y `request_id`.
3. **Middleware `RequestIdMiddleware`** — inyecta `X-Request-ID` en cada request.
4. **Endpoints admin nuevos**:
   - `GET /api/v1/admin/auditoria` — paginado, filtros (`usuario`, `accion`, `tabla`, `from`, `to`).
   - `GET /api/v1/admin/auditoria/export.csv` — exporta resultado filtrado.
5. **Cobertura mínima** — verificar que estos eventos se auditen:
   - login OK / login fallido / logout
   - cambio de contraseña
   - creación/edición/cambio de estado de licitación
   - validación/rechazo de proveedor
   - adjudicación
   - firma de contrato
   - subida/eliminación de documento
6. **Frontend admin** — nueva vista `frontend/admin/auditoria/index.html` con tabla filtrable.
7. **Tests E2E** — `e2e/tests/admin-auditoria.spec.ts`.

### Verificación
```bash
# Provocar un evento auditable
curl -X POST https://sgplopypc.up.railway.app/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@sgplopypc.gob.mx","password":"admin123"}'

# Confirmar registro
mysql ... -e "SELECT accion, fecha, ip_origen FROM historial_cambio ORDER BY fecha DESC LIMIT 5;"
```

### Cierre
Commit + push + deploy + E2E `admin-auditoria.spec.ts`.

---

## FASE 2 — Reportes con plantillas (PDF/DOCX + editor WYSIWYG)

### Objetivo
Sustituir/ampliar el módulo actual de reportes (solo CSV) por un sistema con:
- Plantillas oficiales predefinidas conforme a estándares.
- Exportación a PDF, DOCX y Markdown.
- Editor WYSIWYG para que los usuarios modifiquen plantillas (logo, encabezado, secciones).

### Estándares aplicables (referenciar en código y docs)

**Nacional (México):**
- **LAASSP** — Ley de Adquisiciones, Arrendamientos y Servicios del Sector Público (acta de junta de aclaraciones, acta de apertura, acta de fallo, dictamen).
- **LGTAIP** — Ley General de Transparencia (formato accesible, datos obligatorios).
- **NOM-151-SCFI** — conservación de mensajes de datos.
- **CompraNet** — formato de publicación federal.

**Internacional:**
- **OCDS** (Open Contracting Data Standard) — para datos estructurados.
- **UNCITRAL Model Law on Public Procurement (2011)**.
- **ISO 10845-2** — formato de documentación de procurement.
- **Banco Mundial / BID** — plantillas de licitación.

### Stack a integrar

| Capa | Librería | Uso |
|------|----------|-----|
| PDF | **Dompdf** (composer: `dompdf/dompdf`) | HTML→PDF en backend |
| DOCX | **PHPWord** (composer: `phpoffice/phpword`) | Generar Word editable |
| Markdown | nativo PHP | Texto plano estructurado |
| Editor visual | **pdfme** (https://pdfme.com/) | WYSIWYG en frontend |
| Almacén plantillas | MySQL (tabla nueva) | Templates JSON reutilizables |

### Entregables

1. **Composer + dependencias** — agregar `composer.json` (si no existe) o instalar localmente y commitear `vendor/` solo si se confirma. Alternativa: incluir las libs como autoload manual si se evita composer.
2. **Esquema BD** (`database/migrations/013_reportes_plantillas.sql`):
   ```sql
   CREATE TABLE plantilla_reporte (
     id_plantilla INT AUTO_INCREMENT PRIMARY KEY,
     nombre VARCHAR(150) NOT NULL,
     tipo ENUM('ACTA_APERTURA','ACTA_ACLARACIONES','ACTA_FALLO','DICTAMEN','CONTRATO','RESUMEN_LICITACION','PERSONALIZADA') NOT NULL,
     contenido_json LONGTEXT NOT NULL,    -- estructura pdfme
     contenido_html LONGTEXT NULL,         -- fallback HTML para PDF
     id_usuario_creador INT NOT NULL,
     activa TINYINT(1) DEFAULT 1,
     fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
     fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
     FOREIGN KEY (id_usuario_creador) REFERENCES usuario(id_usuario)
   );
   CREATE TABLE plantilla_asset (
     id_asset INT AUTO_INCREMENT PRIMARY KEY,
     id_plantilla INT NOT NULL,
     tipo ENUM('LOGO','FIRMA','SELLO','OTRO') NOT NULL,
     nombre VARCHAR(255) NOT NULL,
     ruta VARCHAR(500) NOT NULL,
     FOREIGN KEY (id_plantilla) REFERENCES plantilla_reporte(id_plantilla) ON DELETE CASCADE
   );
   ```
3. **Plantillas predefinidas (seed)** — insertar 5 plantillas base conforme a LAASSP:
   - Acta de Junta de Aclaraciones
   - Acta de Apertura de Proposiciones
   - Acta de Fallo
   - Dictamen Técnico-Económico
   - Resumen de Licitación (datos abiertos)
4. **Servicios PHP**:
   - `app/services/ReporteRenderService.php` — toma `id_plantilla` + `id_licitacion/contrato` y emite PDF/DOCX/MD.
   - `app/services/PlantillaService.php` — CRUD de plantillas y assets.
5. **Endpoints**:
   - `GET /api/v1/admin/plantillas` — listar
   - `POST /api/v1/admin/plantillas` — crear
   - `PUT /api/v1/admin/plantillas/{id}` — editar
   - `DELETE /api/v1/admin/plantillas/{id}`
   - `POST /api/v1/admin/plantillas/{id}/assets` — subir logo/firma
   - `POST /api/v1/reportes/generar` — body: `{ id_plantilla, id_entidad, formato: pdf|docx|md, parametros }`
   - Mantener endpoints CSV existentes (no romper).
6. **Frontend**:
   - `frontend/admin/plantillas/index.html` — listado.
   - `frontend/admin/plantillas/editor.html` — integra **pdfme designer** (CDN o npm).
   - `frontend/admin/reportes/index.html` — agregar selector de plantilla y botones (PDF/DOCX/MD).
7. **Storage** — crear `storage/templates/` (logos, assets) protegido por `.htaccess`.
8. **Tests E2E** — `e2e/tests/admin-plantillas.spec.ts` y `admin-reportes-export.spec.ts`.
9. **Documentación** — `docs/fases/mejoras/FASE2_REPORTES_PLANTILLAS.md` con:
   - referencias a estándares,
   - mapping campo→variable de plantilla,
   - guía rápida del editor.

### Verificación
- Generar PDF de un acta de fallo de licitación demo y abrir en navegador.
- Generar DOCX, abrir en LibreOffice/Word y editar.
- Cambiar logo en plantilla, regenerar PDF, verificar reemplazo.

### Cierre
Commit único + push + deploy + E2E.

---

## FASE 3 — API pública de datos abiertos (OCDS)

### Objetivo
Endpoint público (sin auth) que expone licitaciones, adjudicaciones y contratos en formato **Open Contracting Data Standard 1.1**.

### Entregables
1. **Mapeo OCDS** — documentar en `docs/fases/mejoras/FASE3_OCDS_MAPPING.md` cómo se mapea cada tabla a OCDS:
   - `licitacion` → `tender`
   - `contrato` → `award` + `contract`
   - `proveedor` → `parties`
   - `dependencia` → `buyer`
2. **Endpoints**:
   - `GET /api/v1/datos-abiertos/releases` — lista paginada (formato OCDS).
   - `GET /api/v1/datos-abiertos/releases/{ocid}` — release individual.
   - `GET /api/v1/datos-abiertos/release-package` — paquete completo (descarga JSON).
3. **Servicio** — `app/services/OcdsService.php` que construye estructuras conforme al schema OCDS.
4. **Validación** — incluir schema JSON OCDS en `docs/api/ocds-1.1-schema.json` (descargar de https://standard.open-contracting.org/).
5. **Rate limiting suave** — 60 req/min por IP.
6. **Cabeceras CORS** — permitir lectura desde cualquier origen (`Access-Control-Allow-Origin: *`).
7. **Tests E2E** — `e2e/tests/datos-abiertos.spec.ts`.

### Verificación
```bash
curl -fsSL https://sgplopypc.up.railway.app/api/v1/datos-abiertos/releases?limit=5 | jq .
# Validar contra schema OCDS con jsonschema (opcional, en CI)
```

### Cierre
Commit + push + deploy + E2E.

---

## FASE 4 — Dashboard analítico con métricas

### Objetivo
Panel administrativo con visualizaciones interactivas de KPIs operativos.

### Entregables
1. **Endpoints nuevos** (extender `ReporteController`):
   - `GET /api/v1/admin/metricas/tiempo-ciclo` — días promedio publicación→adjudicación, por tipo.
   - `GET /api/v1/admin/metricas/proveedores-top` — top N por participación/adjudicación.
   - `GET /api/v1/admin/metricas/montos-mensuales` — series de tiempo.
   - `GET /api/v1/admin/metricas/cumplimiento` — % licitaciones que cumplieron fechas programadas.
2. **Frontend** — `frontend/admin/dashboard.html` ampliado con:
   - Chart.js (CDN) para gráficas
   - Filtros por rango de fecha y dependencia
   - Botón "Exportar dashboard como PDF" (usa Fase 2)
3. **Cache** — implementar cache simple (file-based, TTL 5 min) para queries pesadas.
4. **Tests E2E** — `e2e/tests/admin-dashboard-metricas.spec.ts`.

### Cierre
Commit + push + deploy + E2E.

---

## FASE 5 — Autenticación multifactor (MFA/2FA)

### Objetivo
Capa adicional de seguridad para roles ADMINISTRADOR y opcional para PROVEEDOR.

### Entregables
1. **Esquema BD** (`database/migrations/014_mfa.sql`):
   ```sql
   ALTER TABLE usuario
     ADD COLUMN mfa_secret VARCHAR(64) NULL,
     ADD COLUMN mfa_enabled TINYINT(1) DEFAULT 0,
     ADD COLUMN mfa_backup_codes TEXT NULL;
   ```
2. **Lib TOTP** — incluir `pragmarx/google2fa` (PHP) o implementar RFC 6238 manualmente.
3. **Endpoints**:
   - `POST /api/v1/me/mfa/enroll` — devuelve `secret` + URL `otpauth://`
   - `POST /api/v1/me/mfa/confirm` — body `{code}`, activa MFA y devuelve códigos de respaldo.
   - `POST /api/v1/me/mfa/disable` — requiere password + `code`.
   - Modificar `POST /api/v1/auth/login`:
     - si user tiene MFA, devuelve `{requires_mfa: true, mfa_token: ...}` sin token JWT final.
   - `POST /api/v1/auth/login/mfa` — body `{mfa_token, code}` → devuelve JWT.
4. **Frontend**:
   - `frontend/auth/mfa-enroll.html` — muestra QR code (lib `qrious` CDN).
   - `frontend/auth/mfa-challenge.html` — pide código.
5. **Política** — forzar MFA para todos los usuarios `ADMINISTRADOR` después de 7 días de gracia.
6. **Tests E2E** — `e2e/tests/auth-mfa.spec.ts`.

### Cierre
Commit + push + deploy + E2E.

---

## FASE 6 — Notificaciones en tiempo real (SSE)

### Objetivo
Push de eventos en tiempo real (sin WebSocket pesado): cambios de estado, nuevas aclaraciones, adjudicaciones.

### Entregables
1. **Endpoint SSE** — `GET /api/v1/notificaciones/stream` (Server-Sent Events):
   - autenticado por token (query param o cookie de sesión corta).
   - emite `event: notificacion\ndata: {...json...}` cuando hay nuevas filas en `notificacion`.
   - heartbeat cada 30s.
2. **Polling de respaldo** — fallback a poll cada 30s si SSE no disponible.
3. **Frontend**:
   - hook JS `frontend/shared/notif-stream.js` que conecta `EventSource`.
   - badge en navbar con contador en tiempo real.
4. **Tests E2E** — `e2e/tests/notif-realtime.spec.ts` (verifica que llega push tras crear notificación).

### Cierre
Commit + push + deploy + E2E.

---

## FASE 7 — Firma electrónica avanzada (e.firma/FIEL)

### Objetivo
Validar contratos firmados con e.firma del SAT (NOM-151-SCFI).

### Entregables
1. **Validador de certificado** — `app/services/EfirmaValidatorService.php`:
   - Parsear `.cer` (X.509).
   - Validar contra cadena SAT (CA root pública del SAT).
   - Verificar firma `.key` sobre hash del documento (PKCS#1).
   - Validar vigencia y revocación (CRL del SAT).
2. **Endpoint**:
   - `POST /api/v1/contratos/{id}/firma-efirma` — multipart: `cer`, `key`, `password_key`, `documento_hash`.
3. **Almacén** — guardar en `contrato`:
   - `firma_efirma_cer_subject`, `firma_efirma_serial`, `firma_efirma_fecha`, `firma_efirma_hash_documento`.
4. **Frontend** — `frontend/proveedor/contratos/firma-efirma.html` con flujo de subida segura.
5. **NUNCA** persistir el `.key` ni el password — usar solo en memoria, descartar al instante.
6. **Tests E2E** — usar e.firma de prueba del SAT (sandbox) en `e2e/tests/contrato-firma-efirma.spec.ts`.
7. **Documentación** — `docs/fases/mejoras/FASE7_EFIRMA_NOM151.md` con referencias y diagrama.

### Riesgos
- Alta complejidad criptográfica.
- Requiere certificados de prueba del SAT.
- Cumplimiento legal (asesoría externa recomendada antes de uso productivo real).

### Cierre
Commit + push + deploy + E2E (con cert de prueba).

---

## FASE 8 — Calificación y reputación de proveedores

### Objetivo
Score histórico de cumplimiento por proveedor visible en próximas licitaciones.

### Entregables
1. **Esquema BD** (`database/migrations/015_proveedor_reputacion.sql`):
   ```sql
   CREATE TABLE proveedor_evaluacion_postcontrato (
     id_eval INT AUTO_INCREMENT PRIMARY KEY,
     id_contrato INT NOT NULL,
     id_proveedor INT NOT NULL,
     puntualidad TINYINT NOT NULL,    -- 1-5
     calidad TINYINT NOT NULL,
     comunicacion TINYINT NOT NULL,
     cumplimiento_alcance TINYINT NOT NULL,
     comentarios TEXT,
     id_usuario_evaluador INT NOT NULL,
     fecha_evaluacion DATETIME DEFAULT CURRENT_TIMESTAMP,
     FOREIGN KEY (id_contrato) REFERENCES contrato(id_contrato),
     FOREIGN KEY (id_proveedor) REFERENCES proveedor(id_proveedor),
     FOREIGN KEY (id_usuario_evaluador) REFERENCES usuario(id_usuario)
   );
   ALTER TABLE proveedor ADD COLUMN score_reputacion DECIMAL(3,2) DEFAULT NULL;
   ```
2. **Trigger / job** — recalcular `score_reputacion` al insertar evaluación.
3. **Endpoints**:
   - `POST /api/v1/contratos/{id}/evaluacion-postcontrato`
   - `GET /api/v1/proveedores/{id}/reputacion`
4. **Frontend**:
   - `frontend/admin/contratos/evaluacion.html` — formulario al cierre del contrato.
   - badge de score en perfil público de proveedor.
5. **Tests E2E** — `e2e/tests/proveedor-reputacion.spec.ts`.

### Cierre
Commit + push + deploy + E2E.

---

## Anexo A — Plantilla de cierre de fase (copiar al final del doc específico)

```markdown
## Cierre de Fase X

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

# E2E completo (smoke)
cd e2e
E2E_BROWSER_CHANNEL=chrome \
E2E_BASE_URL='https://sgplopypc.up.railway.app' \
npx playwright test --reporter=line

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
