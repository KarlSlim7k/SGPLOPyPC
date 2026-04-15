<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Licitaciones — SGPLOPyPC</title>
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
                    <input type="text" placeholder="Ej: LO-902002994, Construcción..." class="w-full border border-slate-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Ejercicio Fiscal</label>
                    <select class="border border-slate-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option>2026</option>
                        <option>2025</option>
                        <option>2024</option>
                        <option>2023</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Tipo</label>
                    <select class="border border-slate-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option>Todos</option>
                        <option>Licitación Pública</option>
                        <option>Invitación Restringida</option>
                        <option>Adjudicación Directa</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button class="bg-primary-600 hover:bg-primary-700 text-white font-semibold px-6 py-2.5 rounded-lg transition-colors text-sm">
                        <i class="ph ph-magnifying-glass"></i> Buscar
                    </button>
                </div>
            </div>
        </div>

        <!-- Timeline -->
        <div class="space-y-6">
            <!-- 2026 -->
            <div>
                <h2 class="text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-primary-600"></span>
                    Ejercicio Fiscal 2026
                </h2>
                <div class="space-y-3">
                    <div class="bg-white border border-slate-200 rounded-xl p-5 hover:shadow-md transition-all">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="font-mono text-xs text-slate-400">LO-902002994-E1-2026</span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">Adjudicada</span>
                                </div>
                                <h3 class="font-semibold text-slate-800">Infraestructura Educativa Fase II</h3>
                                <p class="text-slate-500 text-sm">Municipio de Guanajuato · 3 escuelas primarias</p>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="font-bold text-slate-800">$15,300,000 MXN</p>
                                <p class="text-xs text-slate-400">Adjudicada: 12 Mar 2026</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white border border-slate-200 rounded-xl p-5 hover:shadow-md transition-all">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="font-mono text-xs text-slate-400">LO-902002994-E2-2026</span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">Adjudicada</span>
                                </div>
                                <h3 class="font-semibold text-slate-800">Vialidades Zona Norte</h3>
                                <p class="text-slate-500 text-sm">Municipio de Silao · Pavimentación 2.5 km</p>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="font-bold text-slate-800">$9,750,000 MXN</p>
                                <p class="text-xs text-slate-400">Adjudicada: 28 Feb 2026</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white border border-slate-200 rounded-xl p-5 hover:shadow-md transition-all">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="font-mono text-xs text-slate-400">IR-902002994-E4-2026</span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-700">Desierta</span>
                                </div>
                                <h3 class="font-semibold text-slate-800">Suministro de Materiales Eléctricos</h3>
                                <p class="text-slate-500 text-sm">Municipio de León · Material para red trifásica</p>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="font-bold text-slate-800">$2,400,000 MXN</p>
                                <p class="text-xs text-slate-400">Declarada desierta: 15 Feb 2026</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2025 -->
            <div>
                <h2 class="text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-slate-400"></span>
                    Ejercicio Fiscal 2025
                </h2>
                <div class="space-y-3">
                    <div class="bg-white border border-slate-200 rounded-xl p-5 hover:shadow-md transition-all">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="font-mono text-xs text-slate-400">LO-902002994-E12-2025</span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">Concluida</span>
                                </div>
                                <h3 class="font-semibold text-slate-800">Construcción de Puente Vehicular Río Verde</h3>
                                <p class="text-slate-500 text-sm">Municipio de Irapuato · Estructura de 45m</p>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="font-bold text-slate-800">$32,500,000 MXN</p>
                                <p class="text-xs text-slate-400">Concluida: 20 Dic 2025</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white border border-slate-200 rounded-xl p-5 hover:shadow-md transition-all">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="font-mono text-xs text-slate-400">LO-902002994-E10-2025</span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">Concluida</span>
                                </div>
                                <h3 class="font-semibold text-slate-800">Remodelación Palacio Municipal</h3>
                                <p class="text-slate-500 text-sm">Municipio de Salamanca · Restauración fachada</p>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="font-bold text-slate-800">$7,800,000 MXN</p>
                                <p class="text-xs text-slate-400">Concluida: 15 Nov 2025</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white border border-slate-200 rounded-xl p-5 hover:shadow-md transition-all">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="font-mono text-xs text-slate-400">AD-902002994-E8-2025</span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">Concluida</span>
                                </div>
                                <h3 class="font-semibold text-slate-800">Adjudicación Directa — Servicio de Limpieza</h3>
                                <p class="text-slate-500 text-sm">Múltiples municipios · Contrato anual</p>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="font-bold text-slate-800">$4,200,000 MXN</p>
                                <p class="text-xs text-slate-400">Concluida: 01 Oct 2025</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2024 -->
            <div>
                <h2 class="text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-slate-400"></span>
                    Ejercicio Fiscal 2024
                </h2>
                <div class="space-y-3">
                    <div class="bg-white border border-slate-200 rounded-xl p-5 hover:shadow-md transition-all">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="font-mono text-xs text-slate-400">LO-902002994-E15-2024</span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">Concluida</span>
                                </div>
                                <h3 class="font-semibold text-slate-800">Construcción Sistema de Drenaje Sanitario Sector 4</h3>
                                <p class="text-slate-500 text-sm">Municipio de Celaya · 8.5 km de red</p>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="font-bold text-slate-800">$28,900,000 MXN</p>
                                <p class="text-xs text-slate-400">Concluida: 18 Dic 2024</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white border border-slate-200 rounded-xl p-5 hover:shadow-md transition-all">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="font-mono text-xs text-slate-400">LO-902002994-E11-2024</span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">Concluida</span>
                                </div>
                                <h3 class="font-semibold text-slate-800">Rehabilitación de Unidades Deportivas Municipales</h3>
                                <p class="text-slate-500 text-sm">Municipio de Dolores Hidalgo · 5 unidades</p>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="font-bold text-slate-800">$11,600,000 MXN</p>
                                <p class="text-xs text-slate-400">Concluida: 30 Nov 2024</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <div class="mt-8 flex justify-center">
            <div class="flex gap-2">
                <button class="w-10 h-10 rounded-lg bg-primary-600 text-white font-semibold text-sm">1</button>
                <button class="w-10 h-10 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 font-semibold text-sm transition-colors">2</button>
                <button class="w-10 h-10 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 font-semibold text-sm transition-colors">3</button>
                <button class="w-10 h-10 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 font-semibold text-sm transition-colors">4</button>
                <button class="w-10 h-10 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 font-semibold text-sm transition-colors"><i class="ph ph-caret-right"></i></button>
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
                    <a href="#" class="hover:text-slate-400 transition-colors">Términos de Uso</a>
                    <a href="#" class="hover:text-slate-400 transition-colors">Aviso de Privacidad</a>
                </div>
            </div>
        </div>
    </footer>

</body>
</html>
