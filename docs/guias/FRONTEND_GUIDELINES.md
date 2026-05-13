# FRONTEND_GUIDELINES.md — Lineamientos de Frontend (Vanilla)

## 1) Alcance
Aplicable a vistas HTML/CSS/JS del proyecto.

## 2) Estructura y organización
- Mantener vistas por rol y módulo en `frontend/`.
- Evitar lógica compleja embebida en HTML.
- Centralizar utilidades JS reutilizables en archivos compartidos.

## 3) Convenciones UI/UX
- Consistencia visual entre módulos (tipografía, espaciado, componentes).
- Acciones críticas con confirmación explícita.
- Estados vacíos y de error siempre visibles para el usuario.
- Mensajes claros y accionables.

## 4) Accesibilidad básica
- Usar etiquetas semánticas HTML.
- Inputs con `label` asociado.
- Contraste de color suficiente.
- Navegación usable con teclado en formularios principales.

## 5) Integración con API
- Usar `fetch` con manejo uniforme de errores.
- Estandarizar parseo de respuestas JSON.
- Mostrar errores de red y de validación por separado.

## 6) Seguridad en frontend
- No almacenar secretos en JS.
- Escapar/renderizar de forma segura contenido dinámico.
- Validaciones frontend son complementarias; validación real en backend.

## 7) Rendimiento
- Minimizar manipulación innecesaria del DOM.
- Reutilizar componentes/patrones para tablas y formularios.
- Cargar solo recursos necesarios por pantalla.

