# Arranque Local — Fase 1 + Fase 2

## Requisitos
- Docker y Docker Compose (opcional) o
- PHP 8.2+ con extensión `pdo_mysql` + Apache/Nginx + MySQL/MariaDB

## Opción A: Docker (recomendada)

1. Copiar variables de entorno:
   ```bash
   cp .env.example .env
   ```

2. Ajustar `.env` según tu entorno Docker (DB_HOST suele ser `db` o `localhost` si usas puerto expuesto).

3. Construir y levantar:
   ```bash
   docker build -t sgplopypc .
   docker run -d -p 8080:80 --env-file .env --name sgplopypc_app sgplopypc
   ```
   Nota: si usas un contenedor separado para MySQL, asegúrate de que ambos estén en la misma red y ajusta `DB_HOST`.

4. Inicializar la base de datos:
   - Importar `database/sql/if0_39815580_sgplopypc.sql`.
   - Importar `database/migrations/001_fase1_seed_usuarios.sql`.
   - Importar `database/migrations/002_fase2_seed_dependencias.sql`.

5. Verificar:
   - Landing: `http://localhost:8080/`
   - Health API: `curl http://localhost:8080/api/v1/health`

## Opción B: PHP nativo + MySQL local

1. Copiar variables de entorno:
   ```bash
   cp .env.example .env
   ```

2. Ajustar `.env` con credenciales locales.

3. Asegurar que el document root apunte a `public/`.

4. Inicializar la base de datos (mismo paso 4 de Opción A).

5. Verificar endpoints:
   ```bash
   curl http://localhost/api/v1/health
   curl -X POST http://localhost/api/v1/auth/login \
     -H "Content-Type: application/json" \
     -d '{"email":"admin@sgplopypc.gob.mx","password":"admin123"}'
   ```

## Endpoints disponibles (Fase 1 + Fase 2)

### Autenticación y salud

| Método | Ruta | Descripción | Protección |
|--------|------|-------------|------------|
| GET | `/api/v1/health` | Estado de la API | Público |
| POST | `/api/v1/auth/login` | Inicio de sesión | Público |
| GET | `/api/v1/me` | Perfil del usuario autenticado | Autenticado |
| GET | `/api/v1/admin/dashboard` | Panel admin | Autenticado + ADMINISTRADOR |

### Licitaciones / Convocatorias

| Método | Ruta | Descripción | Protección |
|--------|------|-------------|------------|
| GET | `/api/v1/licitaciones` | Listado con filtros (estado, tipo, dependencia) | Público |
| GET | `/api/v1/licitaciones/{id}` | Detalle de licitación | Público |
| POST | `/api/v1/licitaciones` | Crear licitación | Autenticado + ADMINISTRADOR |
| PUT | `/api/v1/licitaciones/{id}` | Editar licitación | Autenticado + ADMINISTRADOR |
| PATCH | `/api/v1/licitaciones/{id}/estado` | Cambiar estado con transiciones válidas | Autenticado + ADMINISTRADOR |

**Payload ejemplo (crear licitación):**
```json
{
  "numero_licitacion": "LIC-2026-001",
  "id_dependencia": 1,
  "tipo_procedimiento": "LICITACION_PUBLICA",
  "descripcion_proyecto": "Construcción de puente vehicular",
  "presupuesto_estimado": 1500000.50,
  "ubicacion_proyecto": "Av. Principal Km 5"
}
```

**Transiciones de estado válidas:**
- BORRADOR → PUBLICADA | CANCELADA
- PUBLICADA → EN_ACLARACIONES | RECEPCION_PROPUESTAS | CANCELADA
- EN_ACLARACIONES → RECEPCION_PROPUESTAS | CANCELADA
- RECEPCION_PROPUESTAS → EN_EVALUACION | DESIERTA | CANCELADA
- EN_EVALUACION → ADJUDICADA | DESIERTA | CANCELADA

### Proveedores

| Método | Ruta | Descripción | Protección |
|--------|------|-------------|------------|
| GET | `/api/v1/proveedores` | Listado de proveedores | Autenticado + ADMINISTRADOR |
| GET | `/api/v1/proveedores/{id}` | Detalle de proveedor | Autenticado |
| POST | `/api/v1/proveedores` | Registro de proveedor | Autenticado (cualquier rol) |
| PUT | `/api/v1/proveedores/{id}` | Editar proveedor | Autenticado (propietario o ADMIN) |
| PATCH | `/api/v1/proveedores/{id}/estatus` | Cambiar estatus (PENDIENTE/VALIDADO/RECHAZADO/SUSPENDIDO) | Autenticado + ADMINISTRADOR |

**Payload ejemplo (registrar proveedor):**
```json
{
  "nombre_empresa": "Constructora del Centro SA de CV",
  "representante_legal": "Juan Pérez",
  "registro_fiscal": "RFC123456789",
  "domicilio": "Calle Central #100, Centro",
  "telefono": "2281234567",
  "especialidad": "Construcción de puentes y carreteras"
}
```

### Participaciones y Propuestas

| Método | Ruta | Descripción | Protección |
|--------|------|-------------|------------|
| GET | `/api/v1/participaciones` | Listado general de participaciones (paginado/filtrable) | Autenticado + ADMINISTRADOR |
| GET | `/api/v1/participaciones/mias` | Historial de participaciones del proveedor autenticado | Autenticado + PROVEEDOR |
| GET | `/api/v1/licitaciones/{id}/participaciones` | Listar inscripciones de una licitación | Autenticado + ADMINISTRADOR |
| POST | `/api/v1/licitaciones/{id}/participaciones` | Inscribir proveedor en licitación | Autenticado + PROVEEDOR |
| POST | `/api/v1/participaciones/{id}/propuesta` | Enviar propuesta (una por participación) | Autenticado + PROVEEDOR |
| GET | `/api/v1/propuestas/{id}` | Ver propuesta | Autenticado (propietario o ADMIN) |
| GET | `/api/v1/propuestas/mias` | Historial de propuestas del proveedor autenticado | Autenticado + PROVEEDOR |

**Payload ejemplo (enviar propuesta):**
```json
{
  "monto_propuesta": 1450000.00,
  "descripcion_tecnica": "Propuesta técnica con materiales de alta resistencia"
}
```

### Contratos

| Método | Ruta | Descripción | Protección |
|--------|------|-------------|------------|
| GET | `/api/v1/contratos/mios` | Historial de contratos adjudicados al proveedor autenticado | Autenticado + PROVEEDOR |
| GET | `/api/v1/contratos` | Listado administrativo de contratos | Autenticado + ADMINISTRADOR |

### Documentos

| Método | Ruta | Descripción | Protección |
|--------|------|-------------|------------|
| POST | `/api/v1/documentos/upload` | Subir documento | Autenticado |
| GET | `/api/v1/documentos/mios` | Listar documentos del proveedor autenticado | Autenticado + PROVEEDOR |
| GET | `/api/v1/documentos/{id}` | Metadatos del documento | Autenticado (según permisos) |
| GET | `/api/v1/documentos/{id}/download` | Descargar archivo del documento | Autenticado (según permisos) |

**Restricciones de upload:**
- Tipos MIME permitidos: `application/pdf`, `application/vnd.openxmlformats-officedocument.wordprocessingml.document`, `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet`, `image/png`, `image/jpeg`
- Tamaño máximo: 10 MB
- Requiere asociación a al menos un contexto (`id_licitacion`, `id_propuesta`, `id_proveedor`, `id_contrato`, `id_evaluacion`)
- Para PROVEEDOR, `DOC_LEGAL_PROVEEDOR` se asocia automáticamente a su perfil y documentos de propuesta requieren una propuesta propia.

**Ejemplo curl (upload):**
```bash
curl -X POST http://localhost:8080/api/v1/documentos/upload \
  -H "Authorization: Bearer <TOKEN>" \
  -F "archivo=@/ruta/al/archivo.pdf" \
  -F "tipo_documento=BASES_LICITACION" \
  -F "id_licitacion=1"
```

## Usuarios de prueba

| Rol | Email | Contraseña |
|-----|-------|------------|
| ADMINISTRADOR | `admin@sgplopypc.gob.mx` | `admin123` |
| PROVEEDOR | `proveedor@demo.mx` | `proveedor123` |
| PUBLICO | `publico@demo.mx` | `publico123` |

Redirecciones de inicio de sesión:
- `ADMINISTRADOR` -> `/frontend/admin/dashboard.html`
- `PROVEEDOR` -> `/frontend/proveedor/centro.html`
- `PUBLICO` -> `/frontend/publico/centro.html`

Módulos frontend del rol proveedor:
- `/frontend/proveedor/centro.html`
- `/frontend/proveedor/convocatorias.html`
- `/frontend/proveedor/licitacion.html?id={id_licitacion}`
- `/frontend/proveedor/participaciones.html`
- `/frontend/proveedor/propuestas.html`
- `/frontend/proveedor/documentos.html`
- `/frontend/proveedor/contratos.html`

## Dependencias de prueba (Fase 2)

| ID | Nombre |
|----|--------|
| 1 | Secretaría de Obras Públicas |
| 2 | Secretaría de Educación |
| 3 | Secretaría de Salud |
| 4 | Instituto Municipal de Vivienda |
