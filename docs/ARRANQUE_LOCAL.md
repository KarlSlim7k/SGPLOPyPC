# Arranque Local — Fase 1

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

## Endpoints disponibles (Fase 1)

| Método | Ruta | Descripción | Protección |
|--------|------|-------------|------------|
| GET | `/api/v1/health` | Estado de la API | Público |
| POST | `/api/v1/auth/login` | Inicio de sesión | Público |
| GET | `/api/v1/me` | Perfil del usuario autenticado | Autenticado |
| GET | `/api/v1/admin/dashboard` | Panel admin | Autenticado + ADMINISTRADOR |

## Usuarios de prueba

| Rol | Email | Contraseña |
|-----|-------|------------|
| ADMINISTRADOR | `admin@sgplopypc.gob.mx` | `admin123` |
| PROVEEDOR | `proveedor@demo.mx` | `proveedor123` |
| PUBLICO | `publico@demo.mx` | `publico123` |
