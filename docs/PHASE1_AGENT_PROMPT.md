# Prompt para Agente IA — Ejecución Fase 1 (Fundación Operativa)

Copia y pega este prompt en tu agente IA para que implemente la **Fase 1** del proyecto.

---

## PROMPT

Eres un agente de desarrollo senior trabajando en el repositorio **SGPLOPyPC**.

### Objetivo
Implementar la **Fase 1 — Fundación operativa** con alcance mínimo viable y enfoque incremental:
1. Consolidar estructura backend mínima (router, controladores base, conexión BD).
2. Implementar autenticación básica y control por rol.
3. Definir convenciones API (JSON, códigos HTTP, manejo de errores).
4. Configurar entorno de desarrollo reproducible alineado con Docker/Railway.

### Contexto obligatorio a leer antes de codificar
- `docs/contexto.md`
- `docs/arquitectura_infraestructura.md`
- `docs/modelado_base_de_datos.md`
- `docs/AGENTS.md`
- `docs/ROADMAP.md`
- `docs/DESIGN.md`
- `docs/DATABASE_GUIDELINES.md`
- `docs/FRONTEND_GUIDELINES.md`

### Reglas de trabajo
1. Haz cambios **pequeños, atómicos y trazables**.
2. No introduzcas frameworks pesados si no existen en el repo.
3. Mantén compatibilidad con stack actual (PHP + MySQL/MariaDB + frontend vanilla).
4. No hardcodees secretos; usar variables de entorno.
5. Usa consultas preparadas (PDO) para acceso a datos.
6. Toda respuesta de API debe seguir formato estándar.

### Formato estándar de respuesta API
Para endpoints nuevos o ajustados, devolver JSON con:
- `success` (boolean)
- `message` (string)
- `data` (objeto/array/null)
- `errors` (array/null)

Y usar códigos HTTP consistentes:
- `200` éxito consulta/actualización
- `201` recurso creado
- `400` validación o request inválida
- `401` no autenticado
- `403` sin permisos
- `404` no encontrado
- `409` conflicto de negocio
- `500` error interno

### Entregables mínimos esperados (Fase 1)
Implementa y deja funcional lo siguiente:

#### A) Estructura backend mínima
- Router principal en `public/index.php` con dispatch básico por método/ruta.
- Estructura modular mínima (si no existe):
  - `app/controllers`
  - `app/services`
  - `app/repositories`
  - `app/middlewares`
  - `app/helpers`
  - `config`
- Capa de conexión BD centralizada (PDO) con variables de entorno.

#### B) Autenticación y roles (MVP)
- Endpoint de login básico (`/api/v1/auth/login`) con validación de credenciales.
- Sesión o token básico (elige el mecanismo más simple compatible con el estado actual del proyecto).
- Middleware de autenticación.
- Middleware/autorización por rol (`PUBLICO`, `PROVEEDOR`, `ADMINISTRADOR`).

#### C) Endpoints de salud y prueba
- `GET /api/v1/health` para validar que API está activa.
- Al menos 1 endpoint protegido por autenticación.
- Al menos 1 endpoint protegido por rol administrador.

#### D) Configuración y entorno
- Archivo de ejemplo de variables de entorno (por ejemplo `.env.example`) sin secretos reales.
- Ajustes necesarios de Docker para ejecutar localmente (si aplica).
- README o sección en docs con pasos de arranque local.

### Criterios de aceptación
- El proyecto arranca localmente con instrucciones claras.
- Hay separación básica por capas (router/controlador/servicio/repositorio).
- Login funcional y validaciones de acceso por rol.
- Respuestas API unificadas y códigos HTTP coherentes.
- Sin credenciales hardcodeadas.

### Plan de ejecución (obligatorio)
Antes de tocar código:
1. Resume estado actual (máx 10 bullets).
2. Propón plan en 5–8 pasos.
3. Ejecuta por iteraciones pequeñas.

### Validaciones mínimas a ejecutar
- Verificar sintaxis PHP de archivos nuevos/modificados.
- Probar rutas clave con curl (health/login/protegida).
- Confirmar que no se rompió estructura existente.

### Restricción de cierre (MUY IMPORTANTE)
Al finalizar:
1. Haz **un solo commit** con mensaje claro.
2. En tu respuesta final, muestra **únicamente**:
   - una línea confirmando que terminaste,
   - y el **hash completo** del commit.
3. No agregues texto adicional, ni checklist, ni explicación extra.

Formato exacto de salida final esperado:

Terminado.
Commit: <HASH_COMPLETO_DE_40_CARACTERES>

