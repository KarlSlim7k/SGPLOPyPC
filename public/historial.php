<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Licitaciones — SGPLOPyPC</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/frontend/shared/tailwind-output.css">
<link rel="stylesheet" href="/frontend/shared/public-accessibility.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        html { scroll-behavior: smooth; }
        @media (prefers-reduced-motion: reduce) { html { scroll-behavior: auto; } }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 font-sans antialiased">

    <!-- Navbar -->
    <nav class="bg-white/95 backdrop-blur-sm border-b border-slate-100 sticky top-0 z-50 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <a href="/" class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-primary-700 flex items-center justify-center text-white font-bold text-sm">SG</div>
                    <div class="hidden sm:block">
                        <p class="font-bold text-slate-800 text-sm leading-tight">SGPLOPyPC</p>
                        <p class="text-xs text-slate-500 leading-tight">Contrataciones Públicas</p>
                    </div>
                </a>
                <div class="hidden md:flex items-center space-x-1">
                    <a href="/" class="px-4 py-2 text-slate-600 hover:text-primary-600 hover:bg-primary-50 font-medium text-sm rounded-lg transition-all duration-200">Inicio</a>
                    <a href="/#convocatorias" class="px-4 py-2 text-slate-600 hover:text-primary-600 hover:bg-primary-50 font-medium text-sm rounded-lg transition-all duration-200">Convocatorias</a>
                    <a href="/evaluacion.php" class="px-4 py-2 text-slate-600 hover:text-primary-600 hover:bg-primary-50 font-medium text-sm rounded-lg transition-all duration-200">Evaluación</a>
                    <a href="/#transparencia" class="px-4 py-2 text-slate-600 hover:text-primary-600 hover:bg-primary-50 font-medium text-sm rounded-lg transition-all duration-200">Transparencia</a>
                </div>
                <div class="flex items-center gap-3">
                    <a href="/frontend/auth/login.html" class="inline-flex items-center gap-2 bg-primary-600 hover:bg-primary-700 text-white font-semibold px-4 py-2 rounded-lg transition-colors duration-200 text-sm shadow-sm">
                        <i class="ph ph-sign-in text-base"></i>
                        <span class="hidden sm:inline">Acceso</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Breadcrumb -->
    <div class="bg-white border-b border-slate-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <nav class="text-sm text-slate-500">
                <a href="/" class="hover:text-primary-600 transition-colors">Inicio</a>
                <span class="mx-2">/</span>
                <span class="text-slate-700 font-medium">Historial de Licitaciones</span>
            </nav>
        </div>
    </div>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="mb-8">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 rounded-xl bg-slate-200 text-slate-600 flex items-center justify-center">
                    <i class="ph ph-archive text-xl"></i>
                </div>
                <h1 class="text-2xl lg:text-3xl font-bold text-slate-900">Historial de Licitaciones</h1>
            </div>
            <p class="text-slate-500">Registro completo de todos los procesos de licitación realizados en ejercicios fiscales anteriores y concluidos.</p>
        </div>

        <!-- Filters -->
        <div class="bg-white border border-slate-200 rounded-xl p-5 mb-8">
            <div class="flex flex-col md:flex-row gap-4">
                <div class="flex-1">
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Buscar por clave o descripción</label>
                    <input id="historial-search" type="text" placeholder="Ej: LO-902002994, Construcción..." class="w-full border border-slate-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
                <div>
                    <label for="historial-year" class="block text-xs font-semibold text-slate-600 mb-1.5">Ejercicio Fiscal</label>
                    <select id="historial-year" class="border border-slate-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="">Todos</option>
                        <option value="2026">2026</option>
                        <option value="2025">2025</option>
                        <option value="2024">2024</option>
                        <option value="2023">2023</option>
                    </select>
                </div>
                <div>
                    <label for="historial-tipo" class="block text-xs font-semibold text-slate-600 mb-1.5">Tipo</label>
                    <select id="historial-tipo" class="border border-slate-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="">Todos</option>
                        <option value="LICITACION_PUBLICA">Licitación Pública</option>
                        <option value="INVITACION_RESTRINGIDA">Invitación Restringida</option>
                        <option value="ADJUDICACION_DIRECTA">Adjudicación Directa</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Timeline -->
        <div id="historial-list" class="space-y-3">
            <div class="p-6 rounded-xl border border-slate-200 bg-slate-50 text-slate-500 text-sm">Cargando historial...</div>
        </div>

        <!-- Pagination -->
        <div class="mt-8 flex justify-center">
            <div class="flex gap-2">
                <button id="historial-prev" class="px-3 py-2 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 font-semibold text-sm transition-colors">Anterior</button>
                <span id="historial-page-info" class="px-3 py-2 text-sm text-slate-500">0 resultados</span>
                <button id="historial-next" class="px-3 py-2 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 font-semibold text-sm transition-colors">Siguiente</button>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-slate-950 text-slate-400 py-14 border-t border-slate-800/50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-10 mb-10">
                <div>
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-9 h-9 rounded-lg bg-primary-600 flex items-center justify-center text-white font-bold text-sm">SG</div>
                        <div>
                            <p class="font-bold text-white text-sm">SGPLOPyPC</p>
                            <p class="text-xs text-slate-500">Contrataciones Públicas</p>
                        </div>
                    </div>
                    <p class="text-sm text-slate-500 leading-relaxed">Plataforma digital oficial para la gestión de procedimientos de licitación de obra pública y procesos de contratación.</p>
                </div>
                <div>
                    <h4 class="text-white font-semibold text-sm mb-4">Convocatorias</h4>
                    <ul class="space-y-2.5 text-sm">
                        <li><a href="/#convocatorias" class="hover:text-primary-400 transition-colors">Convocatorias Vigentes</a></li>
                        <li><a href="/evaluacion.php" class="hover:text-primary-400 transition-colors">Procesos en Evaluación</a></li>
                        <li><a href="/contratos.php" class="hover:text-primary-400 transition-colors">Contratos Adjudicados</a></li>
                        <li><a href="/historial.php" class="hover:text-primary-400 transition-colors text-primary-400">Historial de Licitaciones</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-white font-semibold text-sm mb-4">Proveedores</h4>
                    <ul class="space-y-2.5 text-sm">
                        <li><a href="/registro.php" class="hover:text-primary-400 transition-colors">Registro de Empresa</a></li>
                        <li><a href="/#como-participar" class="hover:text-primary-400 transition-colors">Cómo Participar</a></li>
                        <li><a href="/requisitos.php" class="hover:text-primary-400 transition-colors">Requisitos de Documentación</a></li>
                        <li><a href="/faq.php" class="hover:text-primary-400 transition-colors">Preguntas Frecuentes</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-white font-semibold text-sm mb-4">Contacto</h4>
                    <ul class="space-y-3 text-sm">
                        <li class="flex items-start gap-2"><i class="ph ph-envelope-simple mt-0.5 shrink-0"></i><span>soporte@sgplopypc.gob.mx</span></li>
                        <li class="flex items-start gap-2"><i class="ph ph-phone mt-0.5 shrink-0"></i><span>01 800 123 4567<br><span class="text-xs">Lun–Vie 9:00–17:00 hrs</span></span></li>
                        <li class="flex items-start gap-2"><i class="ph ph-map-pin mt-0.5 shrink-0"></i><span>Palacio Municipal SN, Centro, 91500 Coatepec, Ver.</span></li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-slate-800 pt-8 flex flex-col md:flex-row justify-between items-center gap-4 text-xs text-slate-600">
                <p>&copy; 2026 SGPLOPyPC. Todos los derechos reservados.</p>
                <div class="flex gap-5">
                    <a href="/frontend/publico/terminos-de-uso.html" class="hover:text-slate-400 transition-colors">Términos de Uso</a>
                    <a href="/frontend/publico/aviso-de-privacidad.html" class="hover:text-slate-400 transition-colors">Aviso de Privacidad</a>
                </div>
            </div>
        </div>
    </footer>

    <script src="/frontend/shared/format.js"></script>
    <script src="/frontend/shared/public.js"></script>
</body>
</html>
