<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Requisitos de Documentación — SGPLOPyPC</title>
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
                <span class="text-slate-700 font-medium">Requisitos de Documentación</span>
            </nav>
        </div>
    </div>

    <!-- Main Content -->
    <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="mb-8">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 rounded-xl bg-violet-100 text-violet-600 flex items-center justify-center">
                    <i class="ph ph-folder-open text-xl"></i>
                </div>
                <h1 class="text-2xl lg:text-3xl font-bold text-slate-900">Requisitos de Documentación</h1>
            </div>
            <p class="text-slate-500">Documentación necesaria para registrarte como proveedor y participar en procesos de licitación pública.</p>
        </div>

        <!-- Phase 1: Registro -->
        <div class="mb-10">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-8 h-8 rounded-full bg-primary-600 text-white flex items-center justify-center font-bold text-sm">1</div>
                <h2 class="text-xl font-bold text-slate-800">Documentos para Registro de Proveedor</h2>
            </div>

            <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wide">
                            <th class="text-left px-5 py-3 font-semibold">Documento</th>
                            <th class="text-left px-5 py-3 font-semibold hidden sm:table-cell">Formato</th>
                            <th class="text-left px-5 py-3 font-semibold hidden md:table-cell">Vigencia</th>
                            <th class="text-center px-5 py-3 font-semibold">Obligatorio</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-4">
                                <p class="font-semibold text-slate-800">Acta Constitutiva</p>
                                <p class="text-xs text-slate-400 mt-0.5">Escritura constitutiva de la empresa con modificaciones</p>
                            </td>
                            <td class="px-5 py-4 hidden sm:table-cell"><span class="text-slate-600">PDF</span></td>
                            <td class="px-5 py-4 hidden md:table-cell"><span class="text-slate-600">Sin vencimiento</span></td>
                            <td class="px-5 py-4 text-center"><span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-emerald-100 text-emerald-600"><i class="ph ph-check"></i></span></td>
                        </tr>
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-4">
                                <p class="font-semibold text-slate-800">Constancia de Situación Fiscal</p>
                                <p class="text-xs text-slate-400 mt-0.5">Emitida por el SAT con RFC, régimen y domicilio fiscal</p>
                            </td>
                            <td class="px-5 py-4 hidden sm:table-cell"><span class="text-slate-600">PDF</span></td>
                            <td class="px-5 py-4 hidden md:table-cell"><span class="text-slate-600">No mayor a 30 días</span></td>
                            <td class="px-5 py-4 text-center"><span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-emerald-100 text-emerald-600"><i class="ph ph-check"></i></span></td>
                        </tr>
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-4">
                                <p class="font-semibold text-slate-800">Identificación del Representante Legal</p>
                                <p class="text-xs text-slate-400 mt-0.5">INE, pasaporte o cédula profesional vigente</p>
                            </td>
                            <td class="px-5 py-4 hidden sm:table-cell"><span class="text-slate-600">PDF, JPG, PNG</span></td>
                            <td class="px-5 py-4 hidden md:table-cell"><span class="text-slate-600">Vigente</span></td>
                            <td class="px-5 py-4 text-center"><span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-emerald-100 text-emerald-600"><i class="ph ph-check"></i></span></td>
                        </tr>
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-4">
                                <p class="font-semibold text-slate-800">Poder Notarial del Representante</p>
                                <p class="text-xs text-slate-400 mt-0.5">Documento notarial que acredite la representación legal</p>
                            </td>
                            <td class="px-5 py-4 hidden sm:table-cell"><span class="text-slate-600">PDF</span></td>
                            <td class="px-5 py-4 hidden md:table-cell"><span class="text-slate-600">Sin vencimiento</span></td>
                            <td class="px-5 py-4 text-center"><span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-amber-100 text-amber-600 text-xs">Opcional</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Phase 2: Licitación -->
        <div class="mb-10">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-8 h-8 rounded-full bg-emerald-600 text-white flex items-center justify-center font-bold text-sm">2</div>
                <h2 class="text-xl font-bold text-slate-800">Documentos para Participar en Licitación</h2>
            </div>

            <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 mb-5 flex items-start gap-3">
                <i class="ph ph-info text-emerald-600 text-xl mt-0.5 shrink-0"></i>
                <div>
                    <p class="text-emerald-800 font-semibold text-sm">Requisitos adicionales por convocatoria</p>
                    <p class="text-emerald-700 text-sm mt-1">Cada licitación puede solicitar documentos complementarios según su naturaleza. Consulta las bases de cada convocatoria para conocer los requisitos específicos.</p>
                </div>
            </div>

            <div class="grid gap-4">
                <div class="bg-white border border-slate-200 rounded-xl p-5">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center shrink-0 mt-0.5">
                            <i class="ph ph-file-text text-lg"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-slate-800">Propuesta Técnica</h3>
                            <p class="text-slate-500 text-sm mt-1">Descripción detallada de la metodología, cronograma de ejecución, recursos humanos y materiales propuestos para el cumplimiento del proyecto.</p>
                            <ul class="mt-3 space-y-1.5">
                                <li class="text-xs text-slate-500 flex items-center gap-1.5"><i class="ph ph-check text-emerald-500"></i> Metodología de trabajo</li>
                                <li class="text-xs text-slate-500 flex items-center gap-1.5"><i class="ph ph-check text-emerald-500"></i> Cronograma de ejecución (Gantt)</li>
                                <li class="text-xs text-slate-500 flex items-center gap-1.5"><i class="ph ph-check text-emerald-500"></i> Currículum del equipo técnico</li>
                                <li class="text-xs text-slate-500 flex items-center gap-1.5"><i class="ph ph-check text-emerald-500"></i> Relación de proyectos similares realizados</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="bg-white border border-slate-200 rounded-xl p-5">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 mt-0.5">
                            <i class="ph ph-currency-dollar text-lg"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-slate-800">Propuesta Económica</h3>
                            <p class="text-slate-500 text-sm mt-1">Presupuesto detallado con costos unitarios, indirectos, utilidad y desglose de conceptos de trabajo conforme al catálogo de conceptos de la convocatoria.</p>
                            <ul class="mt-3 space-y-1.5">
                                <li class="text-xs text-slate-500 flex items-center gap-1.5"><i class="ph ph-check text-emerald-500"></i> Presupuesto desglosado por partida</li>
                                <li class="text-xs text-slate-500 flex items-center gap-1.5"><i class="ph ph-check text-emerald-500"></i> Análisis de precios unitarios</li>
                                <li class="text-xs text-slate-500 flex items-center gap-1.5"><i class="ph ph-check text-emerald-500"></i> Carta compromiso de precios</li>
                                <li class="text-xs text-slate-500 flex items-center gap-1.5"><i class="ph ph-check text-emerald-500"></i> Carta de seriedad de la oferta</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="bg-white border border-slate-200 rounded-xl p-5">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-lg bg-violet-50 text-violet-600 flex items-center justify-center shrink-0 mt-0.5">
                            <i class="ph ph-shield-check text-lg"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-slate-800">Documentos Legales y Fiscales</h3>
                            <p class="text-slate-500 text-sm mt-1">Documentos que acreditan la capacidad legal, financiera y fiscal de la empresa para cumplir con las obligaciones del contrato.</p>
                            <ul class="mt-3 space-y-1.5">
                                <li class="text-xs text-slate-500 flex items-center gap-1.5"><i class="ph ph-check text-emerald-500"></i> Opinión de cumplimiento fiscal (SAT) positiva</li>
                                <li class="text-xs text-slate-500 flex items-center gap-1.5"><i class="ph ph-check text-emerald-500"></i> Estados financieros auditados (último ejercicio)</li>
                                <li class="text-xs text-slate-500 flex items-center gap-1.5"><i class="ph ph-check text-emerald-500"></i> Constancia de no inhabilitación (SFP)</li>
                                <li class="text-xs text-slate-500 flex items-center gap-1.5"><i class="ph ph-check text-emerald-500"></i> Fianza de cumplimiento (en caso de adjudicación)</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Phase 3: Contrato -->
        <div class="mb-10">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-8 h-8 rounded-full bg-slate-600 text-white flex items-center justify-center font-bold text-sm">3</div>
                <h2 class="text-xl font-bold text-slate-800">Documentos para Formalización de Contrato</h2>
            </div>

            <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wide">
                            <th class="text-left px-5 py-3 font-semibold">Documento</th>
                            <th class="text-left px-5 py-3 font-semibold hidden sm:table-cell">Formato</th>
                            <th class="text-left px-5 py-3 font-semibold hidden md:table-cell">Entrega</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-4">
                                <p class="font-semibold text-slate-800">Fianza de Cumplimiento</p>
                                <p class="text-xs text-slate-400 mt-0.5">Emitida por institución afianzadora autorizada (10% del monto)</p>
                            </td>
                            <td class="px-5 py-4 hidden sm:table-cell"><span class="text-slate-600">PDF Original</span></td>
                            <td class="px-5 py-4 hidden md:table-cell"><span class="text-slate-600">Antes de firma</span></td>
                        </tr>
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-4">
                                <p class="font-semibold text-slate-800">Póliza de Seguro de Responsabilidad Civil</p>
                                <p class="text-xs text-slate-400 mt-0.5">Cobertura vigente durante la ejecución del contrato</p>
                            </td>
                            <td class="px-5 py-4 hidden sm:table-cell"><span class="text-slate-600">PDF</span></td>
                            <td class="px-5 py-4 hidden md:table-cell"><span class="text-slate-600">Antes de firma</span></td>
                        </tr>
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-4">
                                <p class="font-semibold text-slate-800">Comprobante de Domicilio de la Empresa</p>
                                <p class="text-xs text-slate-400 mt-0.5">No mayor a 3 meses de antigüedad</p>
                            </td>
                            <td class="px-5 py-4 hidden sm:table-cell"><span class="text-slate-600">PDF o imagen</span></td>
                            <td class="px-5 py-4 hidden md:table-cell"><span class="text-slate-600">Antes de firma</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- CTA -->
        <div class="bg-primary-600 rounded-2xl p-8 text-center text-white">
            <h3 class="text-xl font-bold mb-2">¿Listo para registrarte?</h3>
            <p class="text-blue-100 mb-5">Inicia el proceso de registro y comienza a participar en licitaciones públicas.</p>
            <a href="/registro.php" class="inline-flex items-center gap-2 bg-white text-primary-600 hover:bg-blue-50 font-semibold px-6 py-3 rounded-xl transition-colors duration-200 shadow-sm">
                <i class="ph ph-user-plus text-lg"></i>
                Iniciar Registro
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
                        <li><a href="/contratos.php" class="hover:text-primary-400 transition-colors">Contratos Adjudicados</a></li>
                        <li><a href="/historial.php" class="hover:text-primary-400 transition-colors">Historial de Licitaciones</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-white font-semibold text-sm mb-4">Proveedores</h4>
                    <ul class="space-y-2.5 text-sm">
                        <li><a href="/registro.php" class="hover:text-primary-400 transition-colors">Registro de Empresa</a></li>
                        <li><a href="/#como-participar" class="hover:text-primary-400 transition-colors">Cómo Participar</a></li>
                        <li><a href="/requisitos.php" class="hover:text-primary-400 transition-colors text-primary-400">Requisitos de Documentación</a></li>
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
