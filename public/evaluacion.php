<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Procesos en Evaluación — SGPLOPyPC</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Plus Jakarta Sans', 'sans-serif'] },
                    colors: {
                        primary: {
                            50: '#eff6ff', 100: '#dbeafe', 200: '#bfdbfe',
                            500: '#3b82f6', 600: '#2563eb', 700: '#1d4ed8',
                            800: '#1e40af', 900: '#1e3a8a',
                        }
                    }
                }
            }
        }
    </script>
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
                    <a href="/evaluacion.php" class="px-4 py-2 text-primary-600 bg-primary-50 font-medium text-sm rounded-lg transition-all duration-200">Evaluación</a>
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
                <span class="text-slate-700 font-medium">Procesos en Evaluación</span>
            </nav>
        </div>
    </div>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="mb-8">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center">
                    <i class="ph ph-clock text-xl"></i>
                </div>
                <h1 class="text-2xl lg:text-3xl font-bold text-slate-900">Procesos en Evaluación</h1>
            </div>
            <p class="text-slate-500">Licitaciones cuyas propuestas han sido recibidas y se encuentran en fase de análisis por el Comité de Adquisiciones.</p>
        </div>

        <!-- Info Banner -->
        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-8 flex items-start gap-3">
            <i class="ph ph-info text-amber-600 text-xl mt-0.5 shrink-0"></i>
            <div>
                <p class="text-amber-800 font-semibold text-sm">¿Qué significa "En Evaluación"?</p>
                <p class="text-amber-700 text-sm mt-1">Las propuestas recibidas están siendo revisadas por el comité técnico. Se evalúan aspectos técnicos, económicos y de capacidad del proveedor. Los resultados se publicarán una vez emitido el dictamen.</p>
            </div>
        </div>

        <!-- Processes List -->
        <div class="space-y-4">
            <!-- Process 1 -->
            <article class="bg-white border border-slate-200 rounded-2xl p-6 hover:shadow-md transition-all">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="text-xs font-bold text-slate-400 font-mono">IR-902002994-E6-2026</span>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-700 border border-amber-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 mr-1.5 animate-pulse"></span>
                                En Evaluación
                            </span>
                        </div>
                        <h3 class="font-bold text-slate-800 text-lg">Rehabilitación de Caminos Rurales Tramo 3 — Región Norte</h3>
                        <p class="text-slate-500 text-sm mt-1">Municipio de Dolores Hidalgo, Guanajuato</p>
                    </div>
                </div>
                <div class="mt-5 pt-5 border-t border-slate-100 grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div>
                        <p class="text-xs text-slate-400 mb-1">Propuestas Recibidas</p>
                        <p class="font-bold text-slate-800 text-lg">4</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400 mb-1">Presupuesto</p>
                        <p class="font-bold text-slate-800 text-sm">$3,200,000 MXN</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400 mb-1">Cierre de Recepción</p>
                        <p class="font-semibold text-slate-700 text-sm">28 Abr 2026</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400 mb-1">Fallo Estimado</p>
                        <p class="font-semibold text-slate-700 text-sm">12 May 2026</p>
                    </div>
                </div>
                <div class="mt-4">
                    <p class="text-xs text-slate-500 mb-2">Avance del proceso de evaluación</p>
                    <div class="w-full bg-slate-100 rounded-full h-2">
                        <div class="bg-amber-500 h-2 rounded-full" style="width: 60%"></div>
                    </div>
                    <p class="text-xs text-amber-600 font-medium mt-1">60% — Revisión técnica en curso</p>
                </div>
            </article>

            <!-- Process 2 -->
            <article class="bg-white border border-slate-200 rounded-2xl p-6 hover:shadow-md transition-all">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="text-xs font-bold text-slate-400 font-mono">LO-902002994-E3-2026</span>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-700 border border-amber-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 mr-1.5 animate-pulse"></span>
                                En Evaluación
                            </span>
                        </div>
                        <h3 class="font-bold text-slate-800 text-lg">Mantenimiento de Unidades Habitacionales Fase IV</h3>
                        <p class="text-slate-500 text-sm mt-1">Municipio de Irapuato, Guanajuato</p>
                    </div>
                </div>
                <div class="mt-5 pt-5 border-t border-slate-100 grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div>
                        <p class="text-xs text-slate-400 mb-1">Propuestas Recibidas</p>
                        <p class="font-bold text-slate-800 text-lg">6</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400 mb-1">Presupuesto</p>
                        <p class="font-bold text-slate-800 text-sm">$5,100,000 MXN</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400 mb-1">Cierre de Recepción</p>
                        <p class="font-semibold text-slate-700 text-sm">20 Abr 2026</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400 mb-1">Fallo Estimado</p>
                        <p class="font-semibold text-slate-700 text-sm">05 May 2026</p>
                    </div>
                </div>
                <div class="mt-4">
                    <p class="text-xs text-slate-500 mb-2">Avance del proceso de evaluación</p>
                    <div class="w-full bg-slate-100 rounded-full h-2">
                        <div class="bg-amber-500 h-2 rounded-full" style="width: 85%"></div>
                    </div>
                    <p class="text-xs text-amber-600 font-medium mt-1">85% — Dictamen en firma</p>
                </div>
            </article>

            <!-- Process 3 -->
            <article class="bg-white border border-slate-200 rounded-2xl p-6 hover:shadow-md transition-all">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="text-xs font-bold text-slate-400 font-mono">LO-902002994-E8-2026</span>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700 border border-blue-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-blue-500 mr-1.5 animate-pulse"></span>
                                Recepción de Propuestas
                            </span>
                        </div>
                        <h3 class="font-bold text-slate-800 text-lg">Ampliación de Red Eléctrica Zona Industrial</h3>
                        <p class="text-slate-500 text-sm mt-1">Municipio de Celaya, Guanajuato</p>
                    </div>
                </div>
                <div class="mt-5 pt-5 border-t border-slate-100 grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div>
                        <p class="text-xs text-slate-400 mb-1">Propuestas Recibidas</p>
                        <p class="font-bold text-slate-800 text-lg">2</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400 mb-1">Presupuesto</p>
                        <p class="font-bold text-slate-800 text-sm">$18,400,000 MXN</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400 mb-1">Cierre de Recepción</p>
                        <p class="font-semibold text-slate-700 text-sm">10 May 2026</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400 mb-1">Fallo Estimado</p>
                        <p class="font-semibold text-slate-700 text-sm">28 May 2026</p>
                    </div>
                </div>
                <div class="mt-4">
                    <p class="text-xs text-slate-500 mb-2">Avance del proceso de evaluación</p>
                    <div class="w-full bg-slate-100 rounded-full h-2">
                        <div class="bg-blue-500 h-2 rounded-full" style="width: 25%"></div>
                    </div>
                    <p class="text-xs text-blue-600 font-medium mt-1">25% — Recepción abierta hasta 10 May</p>
                </div>
            </article>
        </div>

        <!-- CTA -->
        <div class="mt-12 bg-white border border-slate-200 rounded-2xl p-8 text-center">
            <h3 class="text-xl font-bold text-slate-800 mb-2">¿Quieres participar en futuros procesos?</h3>
            <p class="text-slate-500 mb-5">Registra tu empresa y mantente al tanto de las nuevas convocatorias publicadas.</p>
            <a href="/registro.php" class="inline-flex items-center gap-2 bg-primary-600 hover:bg-primary-700 text-white font-semibold px-6 py-3 rounded-xl transition-colors duration-200 shadow-sm">
                <i class="ph ph-user-plus text-lg"></i>
                Registro de Proveedor
            </a>
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
                        <li><a href="/evaluacion.php" class="hover:text-primary-400 transition-colors text-primary-400">Procesos en Evaluación</a></li>
                        <li><a href="/contratos.php" class="hover:text-primary-400 transition-colors">Contratos Adjudicados</a></li>
                        <li><a href="/historial.php" class="hover:text-primary-400 transition-colors">Historial de Licitaciones</a></li>
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
                    <a href="#" class="hover:text-slate-400 transition-colors">Términos de Uso</a>
                    <a href="#" class="hover:text-slate-400 transition-colors">Aviso de Privacidad</a>
                </div>
            </div>
        </div>
    </footer>

</body>
</html>
