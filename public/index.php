<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGPLOPyPC — Licitaciones y Contrataciones Públicas</title>
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
<body class="bg-white text-slate-900 font-sans antialiased">

    <!-- Skip to content -->
    <a href="#inicio" class="sr-only focus:not-sr-only focus:fixed focus:top-4 focus:left-4 z-[100] bg-primary-600 text-white px-4 py-2 rounded-lg font-medium">Saltar al contenido</a>

    <!-- Navbar -->
    <nav class="bg-white/95 backdrop-blur-sm border-b border-slate-100 sticky top-0 z-50 shadow-sm" role="navigation" aria-label="Navegación principal">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <a href="/" class="flex items-center gap-3 cursor-pointer" aria-label="SGPLOPyPC inicio">
                    <div class="w-9 h-9 rounded-lg bg-primary-700 flex items-center justify-center text-white font-bold text-sm" aria-hidden="true">SG</div>
                    <div class="hidden sm:block">
                        <p class="font-bold text-slate-800 text-sm leading-tight">SGPLOPyPC</p>
                        <p class="text-xs text-slate-500 leading-tight">Contrataciones Públicas</p>
                    </div>
                </a>

                <div class="hidden md:flex items-center space-x-1">
                    <a href="#inicio" class="px-4 py-2 text-slate-600 hover:text-primary-600 hover:bg-primary-50 font-medium text-sm rounded-lg transition-all duration-200">Inicio</a>
                    <a href="#convocatorias" class="px-4 py-2 text-slate-600 hover:text-primary-600 hover:bg-primary-50 font-medium text-sm rounded-lg transition-all duration-200">Convocatorias</a>
                    <a href="#como-participar" class="px-4 py-2 text-slate-600 hover:text-primary-600 hover:bg-primary-50 font-medium text-sm rounded-lg transition-all duration-200">Proveedores</a>
                    <a href="#transparencia" class="px-4 py-2 text-slate-600 hover:text-primary-600 hover:bg-primary-50 font-medium text-sm rounded-lg transition-all duration-200">Transparencia</a>
                </div>

                <div class="flex items-center gap-3">
                    <a href="/frontend/public/login.html" class="inline-flex items-center gap-2 bg-primary-600 hover:bg-primary-700 text-white font-semibold px-4 py-2 rounded-lg transition-colors duration-200 text-sm shadow-sm cursor-pointer">
                        <i class="ph ph-sign-in text-base" aria-hidden="true"></i>
                        <span class="hidden sm:inline">Ventanilla de Acceso</span>
                        <span class="sm:hidden">Acceso</span>
                    </a>
                    <!-- Mobile menu button -->
                    <button id="mobile-menu-btn" class="md:hidden p-2 text-slate-500 hover:text-slate-700 hover:bg-slate-100 rounded-lg transition-colors cursor-pointer" aria-label="Abrir menú" aria-expanded="false">
                        <i class="ph ph-list text-xl" aria-hidden="true"></i>
                    </button>
                </div>
            </div>

            <!-- Mobile menu -->
            <div id="mobile-menu" class="md:hidden hidden border-t border-slate-100 py-3 space-y-1">
                <a href="#inicio" class="block px-4 py-2.5 text-slate-700 hover:bg-primary-50 hover:text-primary-700 font-medium text-sm rounded-lg transition-colors">Inicio</a>
                <a href="#convocatorias" class="block px-4 py-2.5 text-slate-700 hover:bg-primary-50 hover:text-primary-700 font-medium text-sm rounded-lg transition-colors">Convocatorias</a>
                <a href="#como-participar" class="block px-4 py-2.5 text-slate-700 hover:bg-primary-50 hover:text-primary-700 font-medium text-sm rounded-lg transition-colors">Proveedores</a>
                <a href="#transparencia" class="block px-4 py-2.5 text-slate-700 hover:bg-primary-50 hover:text-primary-700 font-medium text-sm rounded-lg transition-colors">Transparencia</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="inicio" class="relative bg-gradient-to-br from-slate-900 via-slate-800 to-primary-900 overflow-hidden">
        <!-- Background pattern -->
        <div class="absolute inset-0 opacity-10" aria-hidden="true">
            <svg width="60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg">
                <pattern id="grid" width="60" height="60" patternUnits="userSpaceOnUse">
                    <path d="M 60 0 L 0 0 0 60" fill="none" stroke="white" stroke-width="1"/>
                </pattern>
                <rect width="100%" height="100%" fill="url(#grid)"/>
            </svg>
        </div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 lg:py-28 flex flex-col lg:flex-row items-center gap-12">
            <div class="lg:w-1/2 space-y-7">
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-blue-500/20 text-blue-300 text-xs font-semibold border border-blue-500/30 uppercase tracking-wide">
                    <span class="w-1.5 h-1.5 rounded-full bg-blue-400 animate-pulse" aria-hidden="true"></span>
                    Sistema Oficial · Ejercicio Fiscal 2026
                </div>
                <h1 class="text-4xl md:text-5xl lg:text-[3.25rem] font-extrabold text-white leading-tight">
                    Gestión de <span class="text-blue-400">Licitaciones</span> y Obra Pública
                </h1>
                <p class="text-lg text-slate-300 leading-relaxed max-w-xl">
                    Plataforma digital que centraliza los procesos de contratación pública: convocatorias, evaluación de propuestas y adjudicación con trazabilidad total.
                </p>
                <div class="flex flex-col sm:flex-row gap-3 pt-2">
                    <a href="#convocatorias" class="inline-flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-500 text-white font-semibold px-6 py-3 rounded-xl transition-all duration-200 shadow-lg shadow-blue-900/40 text-sm cursor-pointer">
                        <i class="ph ph-magnifying-glass text-base" aria-hidden="true"></i>
                        Consultar Convocatorias
                    </a>
                    <a href="#como-participar" class="inline-flex items-center justify-center gap-2 bg-white/10 hover:bg-white/20 border border-white/20 text-white font-semibold px-6 py-3 rounded-xl transition-all duration-200 text-sm cursor-pointer backdrop-blur-sm">
                        <i class="ph ph-user-plus text-base" aria-hidden="true"></i>
                        Registro de Proveedores
                    </a>
                </div>
            </div>

            <!-- UI Preview Card -->
            <div class="lg:w-1/2 w-full">
                <div class="bg-slate-800/60 backdrop-blur-sm rounded-2xl border border-slate-700/50 shadow-2xl overflow-hidden">
                    <!-- Mini dashboard header -->
                    <div class="bg-slate-900/80 px-4 py-3 flex items-center gap-3 border-b border-slate-700/50">
                        <div class="flex gap-1.5" aria-hidden="true">
                            <div class="w-2.5 h-2.5 rounded-full bg-red-500/70"></div>
                            <div class="w-2.5 h-2.5 rounded-full bg-amber-500/70"></div>
                            <div class="w-2.5 h-2.5 rounded-full bg-emerald-500/70"></div>
                        </div>
                        <div class="flex-1 bg-slate-700/50 rounded-md h-5 mx-auto max-w-[200px] flex items-center justify-center">
                            <span class="text-slate-400 text-xs">sgplopypc.gob.mx</span>
                        </div>
                    </div>
                    <!-- Mini stats -->
                    <div class="p-4 grid grid-cols-2 gap-3">
                        <div class="bg-slate-900/60 rounded-xl p-3 border border-slate-700/40">
                            <p class="text-slate-400 text-xs mb-1">Licitaciones Activas</p>
                            <p class="text-white font-bold text-2xl">12</p>
                            <p class="text-emerald-400 text-xs mt-1 flex items-center gap-1"><i class="ph ph-arrow-up" aria-hidden="true"></i> +3 este mes</p>
                        </div>
                        <div class="bg-slate-900/60 rounded-xl p-3 border border-slate-700/40">
                            <p class="text-slate-400 text-xs mb-1">Proveedores Activos</p>
                            <p class="text-white font-bold text-2xl">84</p>
                            <p class="text-blue-400 text-xs mt-1 flex items-center gap-1"><i class="ph ph-arrow-up" aria-hidden="true"></i> +7 nuevos</p>
                        </div>
                        <div class="bg-slate-900/60 rounded-xl p-3 border border-slate-700/40">
                            <p class="text-slate-400 text-xs mb-1">Propuestas Recibidas</p>
                            <p class="text-white font-bold text-2xl">26</p>
                            <p class="text-amber-400 text-xs mt-1 flex items-center gap-1"><i class="ph ph-clock" aria-hidden="true"></i> Por evaluar</p>
                        </div>
                        <div class="bg-slate-900/60 rounded-xl p-3 border border-slate-700/40">
                            <p class="text-slate-400 text-xs mb-1">Contratos Adjudicados</p>
                            <p class="text-white font-bold text-2xl">145</p>
                            <p class="text-emerald-400 text-xs mt-1 flex items-center gap-1"><i class="ph ph-check-circle" aria-hidden="true"></i> Ejercicio 2026</p>
                        </div>
                    </div>
                    <!-- Mini table -->
                    <div class="px-4 pb-4">
                        <div class="bg-slate-900/60 rounded-xl border border-slate-700/40 overflow-hidden">
                            <div class="px-3 py-2 border-b border-slate-700/40">
                                <p class="text-slate-300 text-xs font-semibold">Últimas Licitaciones</p>
                            </div>
                            <div class="divide-y divide-slate-700/30">
                                <div class="px-3 py-2 flex items-center justify-between">
                                    <div>
                                        <p class="text-white text-xs font-medium">LO-902002994-E1-2026</p>
                                        <p class="text-slate-400 text-xs">Infraestructura Educativa Fase II</p>
                                    </div>
                                    <span class="text-xs bg-emerald-500/20 text-emerald-400 px-2 py-0.5 rounded-full border border-emerald-500/30">Publicada</span>
                                </div>
                                <div class="px-3 py-2 flex items-center justify-between">
                                    <div>
                                        <p class="text-white text-xs font-medium">IR-902002994-E2-2026</p>
                                        <p class="text-slate-400 text-xs">Vialidades Zona Norte</p>
                                    </div>
                                    <span class="text-xs bg-amber-500/20 text-amber-400 px-2 py-0.5 rounded-full border border-amber-500/30">Evaluación</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Strip -->
    <section class="bg-white border-b border-slate-100" aria-label="Estadísticas generales">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="text-center">
                    <p class="text-3xl font-extrabold text-slate-900">12</p>
                    <p class="text-sm text-slate-500 mt-1 font-medium">Licitaciones Activas</p>
                </div>
                <div class="text-center">
                    <p class="text-3xl font-extrabold text-slate-900">84</p>
                    <p class="text-sm text-slate-500 mt-1 font-medium">Proveedores Registrados</p>
                </div>
                <div class="text-center">
                    <p class="text-3xl font-extrabold text-slate-900">145</p>
                    <p class="text-sm text-slate-500 mt-1 font-medium">Contratos Adjudicados</p>
                </div>
                <div class="text-center">
                    <p class="text-3xl font-extrabold text-slate-900">$280M</p>
                    <p class="text-sm text-slate-500 mt-1 font-medium">MXN en Contratos 2026</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Módulos Principales -->
    <section class="py-20 bg-slate-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-14">
                <p class="text-primary-600 font-semibold tracking-widest uppercase text-xs mb-3">Módulos del Sistema</p>
                <h2 class="text-3xl lg:text-4xl font-bold text-slate-900">Todo el ciclo de contratación en un solo lugar</h2>
                <p class="mt-4 text-lg text-slate-500">Desde la publicación de la convocatoria hasta la firma del contrato, con auditoría total.</p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div class="bg-white rounded-2xl p-7 border border-slate-200 hover:border-primary-200 hover:shadow-lg transition-all duration-300 group cursor-default">
                    <div class="w-12 h-12 rounded-xl bg-primary-50 text-primary-600 flex items-center justify-center mb-5 group-hover:bg-primary-600 group-hover:text-white transition-colors duration-300" aria-hidden="true">
                        <i class="ph ph-megaphone text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-800 mb-2">Convocatorias</h3>
                    <p class="text-slate-500 text-sm leading-relaxed">Publica y consulta convocatorias, bases técnicas y documentos de licitaciones de obra pública en tiempo real.</p>
                </div>
                <div class="bg-white rounded-2xl p-7 border border-slate-200 hover:border-emerald-200 hover:shadow-lg transition-all duration-300 group cursor-default">
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center mb-5 group-hover:bg-emerald-600 group-hover:text-white transition-colors duration-300" aria-hidden="true">
                        <i class="ph ph-users text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-800 mb-2">Registro de Proveedores</h3>
                    <p class="text-slate-500 text-sm leading-relaxed">Registro digital de empresas con validación de capacidad técnica, financiera y legal para participar en licitaciones.</p>
                </div>
                <div class="bg-white rounded-2xl p-7 border border-slate-200 hover:border-blue-200 hover:shadow-lg transition-all duration-300 group cursor-default">
                    <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center mb-5 group-hover:bg-blue-600 group-hover:text-white transition-colors duration-300" aria-hidden="true">
                        <i class="ph ph-file-arrow-up text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-800 mb-2">Recepción de Propuestas</h3>
                    <p class="text-slate-500 text-sm leading-relaxed">Carga segura de propuestas técnicas y económicas con validación automática de requisitos y acuse de recibo.</p>
                </div>
                <div class="bg-white rounded-2xl p-7 border border-slate-200 hover:border-indigo-200 hover:shadow-lg transition-all duration-300 group cursor-default">
                    <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center mb-5 group-hover:bg-indigo-600 group-hover:text-white transition-colors duration-300" aria-hidden="true">
                        <i class="ph ph-check-square-offset text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-800 mb-2">Evaluación y Dictamen</h3>
                    <p class="text-slate-500 text-sm leading-relaxed">Comparación objetiva de ofertas con criterios técnicos y económicos ponderados que generan un dictamen documentado.</p>
                </div>
                <div class="bg-white rounded-2xl p-7 border border-slate-200 hover:border-violet-200 hover:shadow-lg transition-all duration-300 group cursor-default">
                    <div class="w-12 h-12 rounded-xl bg-violet-50 text-violet-600 flex items-center justify-center mb-5 group-hover:bg-violet-600 group-hover:text-white transition-colors duration-300" aria-hidden="true">
                        <i class="ph ph-handshake text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-800 mb-2">Adjudicación y Contrato</h3>
                    <p class="text-slate-500 text-sm leading-relaxed">Formalización del fallo de adjudicación y generación del contrato con firma electrónica y registro en el sistema.</p>
                </div>
                <div class="bg-white rounded-2xl p-7 border border-slate-200 hover:border-slate-300 hover:shadow-lg transition-all duration-300 group cursor-default">
                    <div class="w-12 h-12 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center mb-5 group-hover:bg-slate-800 group-hover:text-white transition-colors duration-300" aria-hidden="true">
                        <i class="ph ph-chart-bar text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-800 mb-2">Reportes y Trazabilidad</h3>
                    <p class="text-slate-500 text-sm leading-relaxed">Historial completo de cada proceso, bitácoras de auditoría y reportes ejecutivos descargables en PDF y Excel.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Convocatorias Vigentes -->
    <section id="convocatorias" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-10">
                <div>
                    <p class="text-primary-600 font-semibold tracking-widest uppercase text-xs mb-2">Ejercicio 2026</p>
                    <h2 class="text-3xl font-bold text-slate-900">Convocatorias Vigentes</h2>
                    <p class="text-slate-500 mt-1 text-sm">Licitaciones abiertas disponibles para participar</p>
                </div>
                <a href="/frontend/views/convocatorias/index.html" class="inline-flex items-center gap-2 text-primary-600 hover:text-primary-700 font-semibold text-sm border border-primary-200 hover:border-primary-300 px-4 py-2 rounded-lg hover:bg-primary-50 transition-all cursor-pointer">
                    Ver todas <i class="ph ph-arrow-right" aria-hidden="true"></i>
                </a>
            </div>

            <div class="space-y-4">
                <!-- Convocatoria 1 -->
                <article class="bg-white border border-slate-200 rounded-2xl p-6 hover:border-primary-200 hover:shadow-md transition-all duration-200 cursor-pointer group">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="flex-1">
                            <div class="flex flex-wrap items-center gap-2 mb-2">
                                <span class="text-xs font-bold text-slate-400 font-mono">LO-902002994-E5-2026</span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700 border border-emerald-200">Publicada</span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">Licitación Pública Nacional</span>
                            </div>
                            <h3 class="font-bold text-slate-800 text-base group-hover:text-primary-700 transition-colors">Construcción de Centro Comunitario Zona Sur — Etapa 1</h3>
                            <p class="text-slate-500 text-sm mt-1">Municipio de Salamanca, Guanajuato · Obra de infraestructura social comunitaria</p>
                        </div>
                        <div class="flex flex-row md:flex-col items-start md:items-end gap-3 md:gap-1 shrink-0">
                            <div class="text-right">
                                <p class="text-xs text-slate-400">Presupuesto estimado</p>
                                <p class="font-bold text-slate-800 text-sm">$8,500,000 MXN</p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs text-slate-400">Recepción de prop.</p>
                                <p class="font-semibold text-slate-700 text-sm">05 May 2026</p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-slate-100 flex flex-wrap gap-4 text-xs text-slate-500">
                        <span class="flex items-center gap-1.5"><i class="ph ph-calendar-check text-slate-400" aria-hidden="true"></i> Junta Aclaraciones: 22 Abr 2026</span>
                        <span class="flex items-center gap-1.5"><i class="ph ph-folder-open text-slate-400" aria-hidden="true"></i> Apertura Prop.: 05 May 2026</span>
                        <span class="flex items-center gap-1.5"><i class="ph ph-gavel text-slate-400" aria-hidden="true"></i> Fallo: 20 May 2026</span>
                    </div>
                </article>

                <!-- Convocatoria 2 -->
                <article class="bg-white border border-slate-200 rounded-2xl p-6 hover:border-primary-200 hover:shadow-md transition-all duration-200 cursor-pointer group">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="flex-1">
                            <div class="flex flex-wrap items-center gap-2 mb-2">
                                <span class="text-xs font-bold text-slate-400 font-mono">IR-902002994-E6-2026</span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700 border border-emerald-200">Publicada</span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">Invitación Restringida</span>
                            </div>
                            <h3 class="font-bold text-slate-800 text-base group-hover:text-primary-700 transition-colors">Rehabilitación de Caminos Rurales Tramo 3 — Región Norte</h3>
                            <p class="text-slate-500 text-sm mt-1">Municipio de Dolores Hidalgo, Guanajuato · Mantenimiento y pavimentación</p>
                        </div>
                        <div class="flex flex-row md:flex-col items-start md:items-end gap-3 md:gap-1 shrink-0">
                            <div class="text-right">
                                <p class="text-xs text-slate-400">Presupuesto estimado</p>
                                <p class="font-bold text-slate-800 text-sm">$3,200,000 MXN</p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs text-slate-400">Recepción de prop.</p>
                                <p class="font-semibold text-slate-700 text-sm">28 Abr 2026</p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-slate-100 flex flex-wrap gap-4 text-xs text-slate-500">
                        <span class="flex items-center gap-1.5"><i class="ph ph-calendar-check text-slate-400" aria-hidden="true"></i> Junta Aclaraciones: 15 Abr 2026</span>
                        <span class="flex items-center gap-1.5"><i class="ph ph-folder-open text-slate-400" aria-hidden="true"></i> Apertura Prop.: 28 Abr 2026</span>
                        <span class="flex items-center gap-1.5"><i class="ph ph-gavel text-slate-400" aria-hidden="true"></i> Fallo: 12 May 2026</span>
                    </div>
                </article>

                <!-- Convocatoria 3 -->
                <article class="bg-white border border-slate-200 rounded-2xl p-6 hover:border-primary-200 hover:shadow-md transition-all duration-200 cursor-pointer group">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="flex-1">
                            <div class="flex flex-wrap items-center gap-2 mb-2">
                                <span class="text-xs font-bold text-slate-400 font-mono">LO-902002994-E7-2026</span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700 border border-emerald-200">Publicada</span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">Licitación Pública Nacional</span>
                            </div>
                            <h3 class="font-bold text-slate-800 text-base group-hover:text-primary-700 transition-colors">Construcción Sistema de Drenaje Pluvial Colonia Norte</h3>
                            <p class="text-slate-500 text-sm mt-1">Municipio de León, Guanajuato · Infraestructura hidráulica y sanitaria</p>
                        </div>
                        <div class="flex flex-row md:flex-col items-start md:items-end gap-3 md:gap-1 shrink-0">
                            <div class="text-right">
                                <p class="text-xs text-slate-400">Presupuesto estimado</p>
                                <p class="font-bold text-slate-800 text-sm">$12,750,000 MXN</p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs text-slate-400">Recepción de prop.</p>
                                <p class="font-semibold text-slate-700 text-sm">15 May 2026</p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-slate-100 flex flex-wrap gap-4 text-xs text-slate-500">
                        <span class="flex items-center gap-1.5"><i class="ph ph-calendar-check text-slate-400" aria-hidden="true"></i> Junta Aclaraciones: 02 May 2026</span>
                        <span class="flex items-center gap-1.5"><i class="ph ph-folder-open text-slate-400" aria-hidden="true"></i> Apertura Prop.: 15 May 2026</span>
                        <span class="flex items-center gap-1.5"><i class="ph ph-gavel text-slate-400" aria-hidden="true"></i> Fallo: 30 May 2026</span>
                    </div>
                </article>
            </div>

            <div class="mt-8 text-center">
                <a href="/frontend/views/convocatorias/index.html" class="inline-flex items-center gap-2 bg-slate-900 hover:bg-slate-700 text-white font-semibold px-6 py-3 rounded-xl transition-colors duration-200 text-sm cursor-pointer">
                    Ver todas las convocatorias
                    <i class="ph ph-arrow-right" aria-hidden="true"></i>
                </a>
            </div>
        </div>
    </section>

    <!-- Cómo Participar -->
    <section id="como-participar" class="py-20 bg-slate-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto mb-14">
                <p class="text-primary-600 font-semibold tracking-widest uppercase text-xs mb-3">Para Proveedores</p>
                <h2 class="text-3xl lg:text-4xl font-bold text-slate-900">¿Cómo participar en una licitación?</h2>
                <p class="mt-4 text-slate-500">Sigue estos cuatro pasos para registrarte y presentar tu propuesta de forma segura.</p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="relative">
                    <div class="bg-white rounded-2xl p-6 border border-slate-200 h-full">
                        <div class="w-10 h-10 rounded-xl bg-primary-600 text-white flex items-center justify-center font-bold text-lg mb-5" aria-hidden="true">1</div>
                        <h3 class="font-bold text-slate-800 mb-2">Regístrate</h3>
                        <p class="text-slate-500 text-sm leading-relaxed">Crea tu cuenta con RFC, razón social y datos de contacto. La validación es automática y toma menos de 5 minutos.</p>
                    </div>
                    <div class="hidden lg:block absolute top-10 -right-3 text-slate-300" aria-hidden="true">
                        <i class="ph ph-arrow-right text-2xl"></i>
                    </div>
                </div>
                <div class="relative">
                    <div class="bg-white rounded-2xl p-6 border border-slate-200 h-full">
                        <div class="w-10 h-10 rounded-xl bg-primary-600 text-white flex items-center justify-center font-bold text-lg mb-5" aria-hidden="true">2</div>
                        <h3 class="font-bold text-slate-800 mb-2">Documenta tu empresa</h3>
                        <p class="text-slate-500 text-sm leading-relaxed">Adjunta acta constitutiva, estados financieros, clasificación de obra y capacidad técnica para validación del área.</p>
                    </div>
                    <div class="hidden lg:block absolute top-10 -right-3 text-slate-300" aria-hidden="true">
                        <i class="ph ph-arrow-right text-2xl"></i>
                    </div>
                </div>
                <div class="relative">
                    <div class="bg-white rounded-2xl p-6 border border-slate-200 h-full">
                        <div class="w-10 h-10 rounded-xl bg-primary-600 text-white flex items-center justify-center font-bold text-lg mb-5" aria-hidden="true">3</div>
                        <h3 class="font-bold text-slate-800 mb-2">Consulta las bases</h3>
                        <p class="text-slate-500 text-sm leading-relaxed">Descarga las bases de la licitación, asiste a la junta de aclaraciones y prepara tus propuestas técnica y económica.</p>
                    </div>
                    <div class="hidden lg:block absolute top-10 -right-3 text-slate-300" aria-hidden="true">
                        <i class="ph ph-arrow-right text-2xl"></i>
                    </div>
                </div>
                <div>
                    <div class="bg-white rounded-2xl p-6 border border-slate-200 h-full">
                        <div class="w-10 h-10 rounded-xl bg-primary-600 text-white flex items-center justify-center font-bold text-lg mb-5" aria-hidden="true">4</div>
                        <h3 class="font-bold text-slate-800 mb-2">Envía tu propuesta</h3>
                        <p class="text-slate-500 text-sm leading-relaxed">Carga tus documentos antes del cierre, recibe acuse electrónico y consulta resultados en tiempo real en la plataforma.</p>
                    </div>
                </div>
            </div>

            <div class="mt-10 text-center">
                <a href="#registro" class="inline-flex items-center gap-2 bg-primary-600 hover:bg-primary-700 text-white font-semibold px-6 py-3 rounded-xl transition-colors duration-200 text-sm shadow-md cursor-pointer">
                    <i class="ph ph-user-plus text-base" aria-hidden="true"></i>
                    Iniciar Registro de Proveedor
                </a>
            </div>
        </div>
    </section>

    <!-- Transparencia -->
    <section id="transparencia" class="py-20 bg-slate-900">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto mb-14">
                <p class="text-blue-400 font-semibold tracking-widest uppercase text-xs mb-3">Gobierno Abierto</p>
                <h2 class="text-3xl lg:text-4xl font-bold text-white">Compromiso con la Transparencia</h2>
                <p class="mt-4 text-slate-400">Todos los procesos son auditables. Consulta actas, dictámenes y contratos de forma pública y gratuita.</p>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-12">
                <div class="bg-white/5 rounded-2xl p-6 border border-white/10 text-center">
                    <p class="text-4xl font-extrabold text-white mb-1">100%</p>
                    <p class="text-slate-400 text-sm font-medium">Trazabilidad Registrada</p>
                </div>
                <div class="bg-white/5 rounded-2xl p-6 border border-white/10 text-center">
                    <p class="text-4xl font-extrabold text-white mb-1">+50</p>
                    <p class="text-slate-400 text-sm font-medium">Proyectos Activos</p>
                </div>
                <div class="bg-white/5 rounded-2xl p-6 border border-white/10 text-center">
                    <p class="text-4xl font-extrabold text-white mb-1">$280M</p>
                    <p class="text-slate-400 text-sm font-medium">MXN Adjudicados</p>
                </div>
                <div class="bg-white/5 rounded-2xl p-6 border border-white/10 text-center">
                    <p class="text-4xl font-extrabold text-white mb-1">0</p>
                    <p class="text-slate-400 text-sm font-medium">Procesos Impugnados</p>
                </div>
            </div>

            <!-- Normatividad -->
            <div>
                <h3 class="text-white font-bold text-lg mb-5">Normatividad Aplicable</h3>
                <div class="grid md:grid-cols-2 gap-3">
                    <a href="#" class="flex items-center gap-4 bg-white/5 hover:bg-white/10 border border-white/10 rounded-xl p-4 transition-all duration-200 group cursor-pointer">
                        <div class="w-10 h-10 rounded-lg bg-red-500/20 text-red-400 flex items-center justify-center shrink-0" aria-hidden="true">
                            <i class="ph ph-file-pdf text-xl"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-white font-semibold text-sm group-hover:text-blue-400 transition-colors truncate">Ley de Obras Públicas y Servicios Relacionados</p>
                            <p class="text-slate-400 text-xs mt-0.5">LOPSRM — DOF 04/01/2000 (última reforma 2024)</p>
                        </div>
                        <i class="ph ph-arrow-square-out text-slate-500 group-hover:text-blue-400 shrink-0 transition-colors" aria-hidden="true"></i>
                    </a>
                    <a href="#" class="flex items-center gap-4 bg-white/5 hover:bg-white/10 border border-white/10 rounded-xl p-4 transition-all duration-200 group cursor-pointer">
                        <div class="w-10 h-10 rounded-lg bg-red-500/20 text-red-400 flex items-center justify-center shrink-0" aria-hidden="true">
                            <i class="ph ph-file-pdf text-xl"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-white font-semibold text-sm group-hover:text-blue-400 transition-colors truncate">Reglamento de la LOPSRM</p>
                            <p class="text-slate-400 text-xs mt-0.5">DOF 28/07/2010 (última reforma 2022)</p>
                        </div>
                        <i class="ph ph-arrow-square-out text-slate-500 group-hover:text-blue-400 shrink-0 transition-colors" aria-hidden="true"></i>
                    </a>
                    <a href="#" class="flex items-center gap-4 bg-white/5 hover:bg-white/10 border border-white/10 rounded-xl p-4 transition-all duration-200 group cursor-pointer">
                        <div class="w-10 h-10 rounded-lg bg-blue-500/20 text-blue-400 flex items-center justify-center shrink-0" aria-hidden="true">
                            <i class="ph ph-file-doc text-xl"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-white font-semibold text-sm group-hover:text-blue-400 transition-colors truncate">Lineamientos de Contratación Pública</p>
                            <p class="text-slate-400 text-xs mt-0.5">Ejercicio Fiscal 2026 — Versión 3.1</p>
                        </div>
                        <i class="ph ph-arrow-square-out text-slate-500 group-hover:text-blue-400 shrink-0 transition-colors" aria-hidden="true"></i>
                    </a>
                    <a href="#" class="flex items-center gap-4 bg-white/5 hover:bg-white/10 border border-white/10 rounded-xl p-4 transition-all duration-200 group cursor-pointer">
                        <div class="w-10 h-10 rounded-lg bg-blue-500/20 text-blue-400 flex items-center justify-center shrink-0" aria-hidden="true">
                            <i class="ph ph-file-doc text-xl"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-white font-semibold text-sm group-hover:text-blue-400 transition-colors truncate">Código de Ética y Conducta</p>
                            <p class="text-slate-400 text-xs mt-0.5">Secretaría de la Función Pública — 2025</p>
                        </div>
                        <i class="ph ph-arrow-square-out text-slate-500 group-hover:text-blue-400 shrink-0 transition-colors" aria-hidden="true"></i>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-slate-950 text-slate-400 py-14 border-t border-slate-800/50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-10 mb-10">
                <div class="lg:col-span-1">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-9 h-9 rounded-lg bg-primary-600 flex items-center justify-center text-white font-bold text-sm" aria-hidden="true">SG</div>
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
                        <li><a href="#convocatorias" class="hover:text-primary-400 transition-colors">Convocatorias Vigentes</a></li>
                        <li><a href="#" class="hover:text-primary-400 transition-colors">Procesos en Evaluación</a></li>
                        <li><a href="#" class="hover:text-primary-400 transition-colors">Contratos Adjudicados</a></li>
                        <li><a href="#" class="hover:text-primary-400 transition-colors">Historial de Licitaciones</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-white font-semibold text-sm mb-4">Proveedores</h4>
                    <ul class="space-y-2.5 text-sm">
                        <li><a href="#registro" class="hover:text-primary-400 transition-colors">Registro de Empresa</a></li>
                        <li><a href="#como-participar" class="hover:text-primary-400 transition-colors">Cómo Participar</a></li>
                        <li><a href="#" class="hover:text-primary-400 transition-colors">Requisitos de Documentación</a></li>
                        <li><a href="#" class="hover:text-primary-400 transition-colors">Preguntas Frecuentes</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-white font-semibold text-sm mb-4">Contacto</h4>
                    <ul class="space-y-3 text-sm">
                        <li class="flex items-start gap-2">
                            <i class="ph ph-envelope-simple mt-0.5 shrink-0" aria-hidden="true"></i>
                            <span>soporte@sgplopypc.gob.mx</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="ph ph-phone mt-0.5 shrink-0" aria-hidden="true"></i>
                            <span>01 800 123 4567<br><span class="text-xs">Lun–Vie 9:00–17:00 hrs</span></span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="ph ph-map-pin mt-0.5 shrink-0" aria-hidden="true"></i>
                            <span>Blvd. Insurgentes 101, Guanajuato, Gto.</span>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-slate-800 pt-8 flex flex-col md:flex-row justify-between items-center gap-4 text-xs text-slate-600">
                <p>&copy; 2026 SGPLOPyPC. Todos los derechos reservados. Plataforma oficial del Gobierno del Estado.</p>
                <div class="flex gap-5">
                    <a href="#" class="hover:text-slate-400 transition-colors">Términos de Uso</a>
                    <a href="#" class="hover:text-slate-400 transition-colors">Aviso de Privacidad</a>
                    <a href="#" class="hover:text-slate-400 transition-colors">Accesibilidad</a>
                </div>
            </div>
        </div>
    </footer>

    <script>
        const btn = document.getElementById('mobile-menu-btn');
        const menu = document.getElementById('mobile-menu');
        btn.addEventListener('click', () => {
            const isOpen = !menu.classList.contains('hidden');
            menu.classList.toggle('hidden');
            btn.setAttribute('aria-expanded', String(!isOpen));
        });
        menu.querySelectorAll('a').forEach(a => {
            a.addEventListener('click', () => { menu.classList.add('hidden'); btn.setAttribute('aria-expanded', 'false'); });
        });
    </script>
</body>
</html>
