# Arquitectura e Infraestructura Propuesta  
## Sistema de Gestión del Procedimiento de Licitación de Obra Pública y Procesos de Contratación

## 1. Objetivo de la arquitectura
Definir una arquitectura web clara, escalable y mantenible para soportar los módulos del sistema de licitaciones (convocatorias, proveedores, propuestas, evaluación, adjudicación, reportes y trazabilidad), usando tecnologías **vanilla** y una operación **totalmente centralizada en Railway**:

- Frontend: **HTML + CSS + JavaScript puro** (servido desde Railway)
- Backend: **PHP puro** (Railway)
- Base de datos: **MySQL/MariaDB** (Railway)
- Administración de BD: **phpMyAdmin** (Railway)
- Despliegue y operación: **Railway**

## 1.1 ¿Qué es Railway y por qué se eligió?
Railway es una plataforma cloud de despliegue que permite ejecutar y administrar servicios web, bases de datos y herramientas operativas desde un mismo entorno. Se eligió para este proyecto porque centraliza frontend, backend, MySQL/MariaDB y phpMyAdmin en una sola plataforma, reduciendo problemas de sincronización entre proveedores, simplificando la colaboración entre varios programadores y facilitando la operación diaria (configuración, despliegues y mantenimiento) en un flujo único.

---

## 2. Vista general (alto nivel)

La solución se organiza en 3 dominios conectados dentro del mismo ecosistema:
1. **Canal de acceso**: usuarios interactúan con el frontend web publicado en Railway.
2. **Núcleo transaccional**: el backend PHP en Railway aplica reglas de negocio, validaciones y seguridad.
3. **Persistencia y administración**: MySQL/MariaDB almacena datos del proceso y phpMyAdmin facilita la operación técnica.

Los documentos y exportaciones se gestionan desde backend para mantener control, trazabilidad y consistencia institucional.

```mermaid
flowchart LR
    U[Usuarios<br/>Público / Proveedor / Administrador]
    A[Administrador técnico]

    subgraph RLY["Railway (Plataforma centralizada)"]
      FE[Frontend<br/>HTML + CSS + JS]
      BE[Backend PHP<br/>API REST/JSON]
      DB[(MySQL / MariaDB)]
      PMA[phpMyAdmin]
      FS[(Almacenamiento de documentos)]
      REP[Motor de exportación<br/>PDF / CSV / Excel]
    end

    U -->|HTTPS| FE
    FE -->|HTTPS| BE
    BE -->|SQL| DB
    BE --> FS
    BE --> REP
    A -->|HTTPS| PMA
    PMA --> DB
```

---

## 3. Arquitectura lógica por capas

### 3.1 Capa de presentación (Frontend - Railway)
- Aplicación web responsiva construida en HTML, CSS y JS.
- Interfaz separada por vistas/módulos:
  - Convocatorias
  - Registro de proveedores
  - Recepción de propuestas
  - Evaluación
  - Adjudicación y contratos
  - Reportes y tablero
- Consumo de API vía `fetch` sobre HTTPS (JSON).
- Manejo de sesión/token según mecanismo definido por backend.
- Renderizado de gráficos en cliente.

### 3.2 Capa de negocio (Backend PHP - Railway)
- API REST en PHP puro para exponer operaciones CRUD y flujos del proceso de licitación.
- Implementación de reglas de negocio:
  - Control de acceso por rol.
  - Validaciones obligatorias por módulo.
  - Estado y transición del proceso de licitación.
  - Auditoría y trazabilidad de acciones.
- Gestión de documentos asociados (bases, propuestas, contratos, actas).
- Generación de reportes y exportaciones.

### 3.3 Capa de datos (MySQL/MariaDB - Railway)
- Motor relacional para consistencia transaccional del proceso.
- Entidades principales (alineadas al análisis funcional):
  - `usuarios`, `roles`, `permisos`
  - `licitaciones`, `convocatorias`
  - `proveedores`
  - `propuestas_tecnicas`, `propuestas_economicas`
  - `evaluaciones`, `dictamenes`
  - `adjudicaciones`, `contratos`
  - `documentos`
  - `historial_cambios` (auditoría)
- Administración operativa mediante phpMyAdmin.

---

## 4. Infraestructura de despliegue (física/lógica)

### 4.1 Plataforma centralizada (Railway)
- Frontend y backend desplegados en Railway para mantener una sola plataforma operativa.
- Base de datos MySQL/MariaDB en Railway, cercana al backend para reducir latencia y simplificar conectividad.
- phpMyAdmin desplegado en Railway para administración remota controlada.
- Variables seguras para credenciales y configuración (`DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`, `APP_ENV`).
- Uso de red interna entre servicios de Railway cuando aplique.

### 4.2 Operación colaborativa y control de cambios
- Flujo recomendado por ramas (`main`, `develop`, `feature/*`) para múltiples programadores.
- Integración de repositorio GitHub con Railway para despliegues consistentes desde ramas definidas.

### 4.3 Herramientas de administración
- phpMyAdmin para operación de base de datos:
  - Consulta y soporte de datos.
  - Mantenimiento operativo.
  - Verificación rápida de integridad de registros.

---

## 5. Flujo principal de operación

```mermaid
sequenceDiagram
    participant User as Usuario (web)
    participant FE as Frontend (Railway)
    participant API as Backend PHP (Railway)
    participant DB as MySQL/MariaDB (Railway)

    User->>FE: Interacción en módulo (ej. enviar propuesta)
    FE->>API: Request HTTPS (JSON + token/sesión)
    API->>API: Validación de permisos y reglas
    API->>DB: Inserción/actualización transaccional
    DB-->>API: Resultado SQL
    API-->>FE: Respuesta JSON (estado/mensaje/datos)
    FE-->>User: Confirmación y actualización visual
```

---

## 6. Reportes, gráficos y exportaciones (excepciones permitidas)
Dado que se mantiene enfoque vanilla, se recomienda incorporar librerías puntuales únicamente para estas capacidades:

- **Gráficos**: Chart.js o equivalente.
- **PDF**: jsPDF / TCPDF / Dompdf (según se genere en frontend o backend).
- **Excel/CSV**:
  - CSV: generación directa (backend/frontend).
  - Excel: PhpSpreadsheet (backend) o SheetJS (frontend).

> Recomendación práctica: centralizar exportaciones oficiales (PDF/Excel) en backend PHP para mejor control de formato, seguridad y trazabilidad.

---

## 7. Seguridad y gobierno de datos
- Autenticación y autorización basada en roles (Público, Proveedor, Administrador).
- Hash seguro de contraseñas (`password_hash` en PHP).
- Validación de entradas y consultas preparadas (PDO) para mitigar SQL Injection.
- Protección de archivos cargados (validación de tipo/tamaño y rutas controladas).
- Registro de auditoría por operación crítica (alta, edición, evaluación, adjudicación).
- Uso obligatorio de HTTPS entre componentes públicos y privados en Railway.
- Respaldos periódicos de MySQL/MariaDB.

---

## 8. Estructura sugerida de componentes backend (PHP puro)

```text
/public
  index.php
/app
  /controllers
  /services
  /repositories
  /models
  /middlewares
  /helpers
/storage
  /documents
    /uploads
      /2026
        /enero /febrero /marzo /abril /mayo /junio /julio /agosto /septiembre /octubre /noviembre /diciembre
      /2025
        /enero /febrero /marzo /abril /mayo /junio /julio /agosto /septiembre /octubre /noviembre /diciembre
      ...
      /2020
        /enero /febrero /marzo /abril /mayo /junio /julio /agosto /septiembre /octubre /noviembre /diciembre
    /exports
      /2026
        /enero /febrero /marzo /abril /mayo /junio /julio /agosto /septiembre /octubre /noviembre /diciembre
      /2025
        /enero /febrero /marzo /abril /mayo /junio /julio /agosto /septiembre /octubre /noviembre /diciembre
      ...
      /2020
        /enero /febrero /marzo /abril /mayo /junio /julio /agosto /septiembre /octubre /noviembre /diciembre
/config
  database.php
  app.php
```

Donde:
- `uploads/` almacenará documentos cargados al sistema (PDF, Excel, CSV, etc.).
- `exports/` almacenará documentos generados por el sistema (reportes PDF, archivos Excel/CSV, etc.).
- En ambos casos, la organización será histórica por tiempo: **año/mes** (de 2020 en adelante), para facilitar trazabilidad, búsquedas y respaldo.

Esta organización permite separar responsabilidades sin depender de frameworks, facilitando pruebas, mantenimiento y crecimiento del sistema.

### 8.1 Estructura sugerida de componentes frontend (HTML/CSS/JS puro)

```text
/frontend
  /admin
  /auth
  /proveedor
  /public
    index.html
    login.html
    dashboard.html
  /assets
    /css
      styles.css
      components.css
    /js
      app.js
      api-client.js
      auth.js
      validators.js
      charts.js
    /img
  /views
    /convocatorias
    /proveedores
    /propuestas
    /evaluacion
    /adjudicaciones
    /reportes
```

Esta estructura separa recursos estáticos, lógica JavaScript y vistas por módulo, manteniendo orden y mantenibilidad en un enfoque vanilla.

---

## 9. Escalabilidad y evolución esperada
- **Corto plazo**: monolito modular en Railway (frontend + API PHP + MySQL/MariaDB + phpMyAdmin).
- **Mediano plazo**:
  - Separar almacenamiento de documentos a servicio especializado.
  - Implementar cola para tareas pesadas (reportes masivos).
  - Réplicas de lectura para reportes analíticos.
- **Largo plazo**:
  - Descomponer módulos críticos en servicios independientes si la carga lo requiere.

---

## 10. Resumen ejecutivo
La arquitectura propuesta implementa un esquema **cliente-servidor de 3 capas**, completamente centralizado en **Railway** para frontend, backend, base de datos y administración operativa con **phpMyAdmin**.  
Este ajuste elimina la dependencia de Vercel para evitar problemas de sincronización entre plataformas y facilita el trabajo colaborativo de múltiples programadores bajo un único entorno de despliegue y operación.
