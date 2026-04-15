<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contratos Adjudicados — SGPLOPyPC</title>
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
                <span class="text-slate-700 font-medium">Contratos Adjudicados</span>
            </nav>
        </div>
    </div>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="mb-8">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center">
                    <i class="ph ph-handshake text-xl"></i>
                </div>
                <h1 class="text-2xl lg:text-3xl font-bold text-slate-900">Contratos Adjudicados</h1>
            </div>
            <p class="text-slate-500">Relación de contratos formalizados como resultado de procesos de licitación concluidos exitosamente.</p>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-10">
            <div class="bg-white border border-slate-200 rounded-xl p-5">
                <p class="text-xs text-slate-400 mb-1">Total Ejercicio 2026</p>
                <p class="text-2xl font-extrabold text-slate-900">145</p>
                <p class="text-emerald-600 text-xs font-medium mt-1"><i class="ph ph-arrow-up"></i> +12 vs 2025</p>
            </div>
            <div class="bg-white border border-slate-200 rounded-xl p-5">
                <p class="text-xs text-slate-400 mb-1">Monto Adjudicado</p>
                <p class="text-2xl font-extrabold text-slate-900">$280M</p>
                <p class="text-slate-500 text-xs mt-1">MXN</p>
            </div>
            <div class="bg-white border border-slate-200 rounded-xl p-5">
                <p class="text-xs text-slate-400 mb-1">En Ejecución</p>
                <p class="text-2xl font-extrabold text-slate-900">78</p>
                <p class="text-blue-600 text-xs font-medium mt-1">54% del total</p>
            </div>
            <div class="bg-white border border-slate-200 rounded-xl p-5">
                <p class="text-xs text-slate-400 mb-1">Concluidos</p>
                <p class="text-2xl font-extrabold text-slate-900">67</p>
                <p class="text-emerald-600 text-xs font-medium mt-1">46% del total</p>
            </div>
        </div>

        <!-- Contracts Table -->
        <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                <h2 class="font-bold text-slate-800">Contratos Recientes</h2>
                <div class="flex items-center gap-2 text-sm">
                    <span class="text-slate-500">Filtrar:</span>
                    <select class="border border-slate-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option>Todos los estados</option>
                        <option>En ejecución</option>
                        <option>Concluido</option>
                        <option>Suspendido</option>
                    </select>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wide">
                            <th class="text-left px-6 py-3 font-semibold">Contrato</th>
                            <th class="text-left px-6 py-3 font-semibold">Proveedor</th>
                            <th class="text-left px-6 py-3 font-semibold hidden md:table-cell">Monto</th>
                            <th class="text-left px-6 py-3 font-semibold hidden lg:table-cell">Inicio</th>
                            <th class="text-left px-6 py-3 font-semibold">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4">
                                <p class="font-mono text-xs text-slate-400">CT-2026-001</p>
                                <p class="font-semibold text-slate-800">Construcción Centro Comunitario Sur</p>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-slate-700">Constructora del Valle S.A. de C.V.</p>
                                <p class="text-xs text-slate-400">RFC: CVA-180523-XX8</p>
                            </td>
                            <td class="px-6 py-4 hidden md:table-cell">
                                <p class="font-semibold text-slate-800">$8,500,000</p>
                            </td>
                            <td class="px-6 py-4 hidden lg:table-cell">
                                <p class="text-slate-600">15 Ene 2026</p>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700 border border-blue-200">En ejecución</span>
                            </td>
                        </tr>
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4">
                                <p class="font-mono text-xs text-slate-400">CT-2026-002</p>
                                <p class="font-semibold text-slate-800">Pavimentación Av. Principal Tramo 2</p>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-slate-700">Infraestructura y Proyectos MX</p>
                                <p class="text-xs text-slate-400">RFC: IPR-190812-XX3</p>
                            </td>
                            <td class="px-6 py-4 hidden md:table-cell">
                                <p class="font-semibold text-slate-800">$14,200,000</p>
                            </td>
                            <td class="px-6 py-4 hidden lg:table-cell">
                                <p class="text-slate-600">01 Feb 2026</p>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700 border border-blue-200">En ejecución</span>
                            </td>
                        </tr>
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4">
                                <p class="font-mono text-xs text-slate-400">CT-2025-089</p>
                                <p class="font-semibold text-slate-800">Remodelación Escuela Benito Juárez</p>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-slate-700">Edificaciones del Bajío S.C.</p>
                                <p class="text-xs text-slate-400">RFC: EBA-170305-XX1</p>
                            </td>
                            <td class="px-6 py-4 hidden md:table-cell">
                                <p class="font-semibold text-slate-800">$4,750,000</p>
                            </td>
                            <td class="px-6 py-4 hidden lg:table-cell">
                                <p class="text-slate-600">15 Sep 2025</p>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700 border border-emerald-200">Concluido</span>
                            </td>
                        </tr>
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4">
                                <p class="font-mono text-xs text-slate-400">CT-2025-078</p>
                                <p class="font-semibold text-slate-800">Sistema de Agua Potable Colonia Centro</p>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-slate-700">Hidroingeniería del Centro</p>
                                <p class="text-xs text-slate-400">RFC: HIC-160928-XX7</p>
                            </td>
                            <td class="px-6 py-4 hidden md:table-cell">
                                <p class="font-semibold text-slate-800">$22,100,000</p>
                            </td>
                            <td class="px-6 py-4 hidden lg:table-cell">
                                <p class="text-slate-600">01 Ago 2025</p>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700 border border-emerald-200">Concluido</span>
                            </td>
                        </tr>
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4">
                                <p class="font-mono text-xs text-slate-400">CT-2026-015</p>
                                <p class="font-semibold text-slate-800">Electrificación Zona Rural Sector 7</p>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-slate-700">Energy Solutions Guanajuato</p>
                                <p class="text-xs text-slate-400">RFC: ESG-200115-XX4</p>
                            </td>
                            <td class="px-6 py-4 hidden md:table-cell">
                                <p class="font-semibold text-slate-800">$6,800,000</p>
                            </td>
                            <td class="px-6 py-4 hidden lg:table-cell">
                                <p class="text-slate-600">10 Mar 2026</p>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700 border border-blue-200">En ejecución</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4 border-t border-slate-100 flex justify-between items-center">
                <p class="text-sm text-slate-500">Mostrando 5 de 145 contratos</p>
                <div class="flex gap-2">
                    <button class="px-3 py-1.5 border border-slate-200 rounded-lg text-sm text-slate-400 cursor-not-allowed" disabled>Anterior</button>
                    <button class="px-3 py-1.5 border border-slate-200 rounded-lg text-sm text-slate-700 hover:bg-slate-50 transition-colors">Siguiente</button>
                </div>
            </div>
        </div>

        <!-- CTA -->
        <div class="mt-10 bg-white border border-slate-200 rounded-2xl p-6 flex flex-col md:flex-row items-center justify-between gap-4">
            <div>
                <h3 class="font-bold text-slate-800">¿Interesado en los detalles de un contrato?</h3>
                <p class="text-slate-500 text-sm">Puedes solicitar acceso a documentos completos mediante transparencia.</p>
            </div>
            <a href="/#transparencia" class="inline-flex items-center gap-2 bg-slate-900 hover:bg-slate-700 text-white font-semibold px-5 py-2.5 rounded-xl transition-colors text-sm shrink-0">
                <i class="ph ph-file-text text-base"></i>
                Solicitar Información
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
                        <li><a href="/evaluacion.php" class="hover:text-primary-400 transition-colors">Procesos en Evaluación</a></li>
                        <li><a href="/contratos.php" class="hover:text-primary-400 transition-colors text-primary-400">Contratos Adjudicados</a></li>
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
