<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Proveedores — SGPLOPyPC</title>
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
                <span class="text-slate-700 font-medium">Registro de Proveedores</span>
            </nav>
        </div>
    </div>

    <!-- Main Content -->
    <main class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="mb-8">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 rounded-xl bg-primary-100 text-primary-600 flex items-center justify-center">
                    <i class="ph ph-user-plus text-xl"></i>
                </div>
                <h1 class="text-2xl lg:text-3xl font-bold text-slate-900">Registro de Proveedor</h1>
            </div>
            <p class="text-slate-500">Completa el formulario para registrarte como proveedor autorizado y participar en las licitaciones públicas del ejercicio fiscal 2026.</p>
        </div>

        <!-- Info -->
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-8 flex items-start gap-3">
            <i class="ph ph-info text-blue-600 text-xl mt-0.5 shrink-0"></i>
            <div>
                <p class="text-blue-800 font-semibold text-sm">Información importante</p>
                <p class="text-blue-700 text-sm mt-1">El registro es gratuito y tiene una validación automática que toma menos de 5 minutos. Una vez registrado, podrás consultar convocatorias, descargar bases y enviar propuestas.</p>
            </div>
        </div>

        <!-- Form -->
        <form class="space-y-6">
            <!-- Section: Datos Fiscales -->
            <div class="bg-white border border-slate-200 rounded-2xl p-6">
                <h2 class="font-bold text-slate-800 text-lg mb-1 flex items-center gap-2">
                    <i class="ph ph-buildings text-primary-600"></i>
                    Datos Fiscales
                </h2>
                <p class="text-slate-500 text-sm mb-5">Información fiscal de la empresa o persona moral</p>

                <div class="space-y-4">
                    <div>
                        <label for="razon-social" class="block text-sm font-semibold text-slate-700 mb-1.5">Razón Social <span class="text-red-500">*</span></label>
                        <input type="text" id="razon-social" required placeholder="Ej: Constructora del Valle S.A. de C.V." class="w-full border border-slate-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="rfc" class="block text-sm font-semibold text-slate-700 mb-1.5">RFC <span class="text-red-500">*</span></label>
                            <input type="text" id="rfc" required placeholder="CVA180523XX8" maxlength="13" class="w-full border border-slate-200 rounded-lg px-4 py-2.5 text-sm uppercase focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                            <p class="text-xs text-slate-400 mt-1">Sin espacios ni caracteres especiales</p>
                        </div>
                        <div>
                            <label for="regimen" class="block text-sm font-semibold text-slate-700 mb-1.5">Régimen Fiscal <span class="text-red-500">*</span></label>
                            <select id="regimen" required class="w-full border border-slate-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                                <option value="">Seleccionar...</option>
                                <option>Persona Moral - General</option>
                                <option>Persona Moral - CONSTRUCTORA</option>
                                <option>Persona Moral - SERVICIOS</option>
                                <option>Persona Moral - COMERCIALIZADORA</option>
                                <option>Persona Física - Actividad Empresarial</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label for="domicilio-fiscal" class="block text-sm font-semibold text-slate-700 mb-1.5">Domicilio Fiscal <span class="text-red-500">*</span></label>
                        <input type="text" id="domicilio-fiscal" required placeholder="Calle, Número, Colonia, C.P., Ciudad, Estado" class="w-full border border-slate-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                    </div>
                </div>
            </div>

            <!-- Section: Contacto -->
            <div class="bg-white border border-slate-200 rounded-2xl p-6">
                <h2 class="font-bold text-slate-800 text-lg mb-1 flex items-center gap-2">
                    <i class="ph ph-address-book text-primary-600"></i>
                    Datos de Contacto
                </h2>
                <p class="text-slate-500 text-sm mb-5">Persona responsable de la comunicación con el sistema</p>

                <div class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="nombre-contacto" class="block text-sm font-semibold text-slate-700 mb-1.5">Nombre Completo <span class="text-red-500">*</span></label>
                            <input type="text" id="nombre-contacto" required placeholder="Juan Pérez García" class="w-full border border-slate-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                        </div>
                        <div>
                            <label for="cargo" class="block text-sm font-semibold text-slate-700 mb-1.5">Cargo <span class="text-red-500">*</span></label>
                            <input type="text" id="cargo" required placeholder="Representante Legal" class="w-full border border-slate-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="email" class="block text-sm font-semibold text-slate-700 mb-1.5">Correo Electrónico <span class="text-red-500">*</span></label>
                            <input type="email" id="email" required placeholder="contacto@empresa.com" class="w-full border border-slate-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                        </div>
                        <div>
                            <label for="telefono" class="block text-sm font-semibold text-slate-700 mb-1.5">Teléfono <span class="text-red-500">*</span></label>
                            <input type="tel" id="telefono" required placeholder="(477) 123-4567" class="w-full border border-slate-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section: Clasificación -->
            <div class="bg-white border border-slate-200 rounded-2xl p-6">
                <h2 class="font-bold text-slate-800 text-lg mb-1 flex items-center gap-2">
                    <i class="ph ph-tag text-primary-600"></i>
                    Clasificación de Obra
                </h2>
                <p class="text-slate-500 text-sm mb-5">Áreas de especialización de tu empresa</p>

                <div class="space-y-3">
                    <label class="flex items-center gap-3 p-3 border border-slate-200 rounded-lg hover:bg-slate-50 cursor-pointer transition-colors">
                        <input type="checkbox" class="w-4 h-4 text-primary-600 rounded border-slate-300 focus:ring-primary-500">
                        <div>
                            <p class="font-semibold text-slate-700 text-sm">Construcción de Edificaciones</p>
                            <p class="text-xs text-slate-400">Vivienda, comercial, institucional, industrial</p>
                        </div>
                    </label>
                    <label class="flex items-center gap-3 p-3 border border-slate-200 rounded-lg hover:bg-slate-50 cursor-pointer transition-colors">
                        <input type="checkbox" class="w-4 h-4 text-primary-600 rounded border-slate-300 focus:ring-primary-500">
                        <div>
                            <p class="font-semibold text-slate-700 text-sm">Infraestructura Vial</p>
                            <p class="text-xs text-slate-400">Caminos, puentes, pavimentación, señalización</p>
                        </div>
                    </label>
                    <label class="flex items-center gap-3 p-3 border border-slate-200 rounded-lg hover:bg-slate-50 cursor-pointer transition-colors">
                        <input type="checkbox" class="w-4 h-4 text-primary-600 rounded border-slate-300 focus:ring-primary-500">
                        <div>
                            <p class="font-semibold text-slate-700 text-sm">Infraestructura Hidráulica</p>
                            <p class="text-xs text-slate-400">Drenaje, agua potable, sistemas de riego</p>
                        </div>
                    </label>
                    <label class="flex items-center gap-3 p-3 border border-slate-200 rounded-lg hover:bg-slate-50 cursor-pointer transition-colors">
                        <input type="checkbox" class="w-4 h-4 text-primary-600 rounded border-slate-300 focus:ring-primary-500">
                        <div>
                            <p class="font-semibold text-slate-700 text-sm">Instalaciones Eléctricas y Especiales</p>
                            <p class="text-xs text-slate-400">Electrificación, telecomunicaciones, alumbrado</p>
                        </div>
                    </label>
                    <label class="flex items-center gap-3 p-3 border border-slate-200 rounded-lg hover:bg-slate-50 cursor-pointer transition-colors">
                        <input type="checkbox" class="w-4 h-4 text-primary-600 rounded border-slate-300 focus:ring-primary-500">
                        <div>
                            <p class="font-semibold text-slate-700 text-sm">Mantenimiento y Conservación</p>
                            <p class="text-xs text-slate-400">Mantenimiento de edificios, áreas verdes, limpieza</p>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Section: Documentos -->
            <div class="bg-white border border-slate-200 rounded-2xl p-6">
                <h2 class="font-bold text-slate-800 text-lg mb-1 flex items-center gap-2">
                    <i class="ph ph-file-archive text-primary-600"></i>
                    Documentos Iniciales
                </h2>
                <p class="text-slate-500 text-sm mb-5">Carga los documentos requeridos para completar tu registro</p>

                <div class="space-y-4">
                    <div class="border-2 border-dashed border-slate-200 rounded-xl p-6 text-center hover:border-primary-300 transition-colors cursor-pointer">
                        <i class="ph ph-upload-simple text-2xl text-slate-400 mb-2"></i>
                        <p class="font-semibold text-slate-700 text-sm">Acta Constitutiva</p>
                        <p class="text-xs text-slate-400 mt-1">Arrastra o <span class="text-primary-600 font-medium">selecciona un archivo</span> (PDF)</p>
                        <input type="file" accept=".pdf" class="hidden">
                    </div>
                    <div class="border-2 border-dashed border-slate-200 rounded-xl p-6 text-center hover:border-primary-300 transition-colors cursor-pointer">
                        <i class="ph ph-upload-simple text-2xl text-slate-400 mb-2"></i>
                        <p class="font-semibold text-slate-700 text-sm">Constancia de Situación Fiscal (SAT)</p>
                        <p class="text-xs text-slate-400 mt-1">Arrastra o <span class="text-primary-600 font-medium">selecciona un archivo</span> (PDF)</p>
                        <input type="file" accept=".pdf" class="hidden">
                    </div>
                    <div class="border-2 border-dashed border-slate-200 rounded-xl p-6 text-center hover:border-primary-300 transition-colors cursor-pointer">
                        <i class="ph ph-upload-simple text-2xl text-slate-400 mb-2"></i>
                        <p class="font-semibold text-slate-700 text-sm">Identificación del Representante Legal</p>
                        <p class="text-xs text-slate-400 mt-1">Arrastra o <span class="text-primary-600 font-medium">selecciona un archivo</span> (PDF o imagen)</p>
                        <input type="file" accept=".pdf,.jpg,.png" class="hidden">
                    </div>
                </div>

                <div class="mt-4 bg-amber-50 border border-amber-200 rounded-lg p-3 flex items-start gap-2">
                    <i class="ph ph-warning text-amber-600 mt-0.5 shrink-0"></i>
                    <p class="text-amber-700 text-xs">Puedes completar el registro sin documentos y cargarlos posteriormente. Sin embargo, tu cuenta permanecerá como "Pendiente" hasta que se valide toda la documentación.</p>
                </div>
            </div>

            <!-- Terms -->
            <div class="flex items-start gap-3">
                <input type="checkbox" id="terms" required class="w-4 h-4 mt-0.5 text-primary-600 rounded border-slate-300 focus:ring-primary-500">
                <label for="terms" class="text-sm text-slate-600">
                    Acepto los <a href="#" class="text-primary-600 hover:underline">Términos de Uso</a> y el <a href="#" class="text-primary-600 hover:underline">Aviso de Privacidad</a> del sistema SGPLOPyPC. Confirmo que la información proporcionada es verídica y está completa.
                </label>
            </div>

            <!-- Submit -->
            <div class="flex flex-col sm:flex-row gap-3 pt-2">
                <button type="submit" class="flex-1 bg-primary-600 hover:bg-primary-700 text-white font-semibold px-6 py-3 rounded-xl transition-colors duration-200 shadow-sm text-sm">
                    <i class="ph ph-check-circle mr-1"></i> Enviar Registro
                </button>
                <a href="/" class="flex-1 text-center border border-slate-200 hover:bg-slate-50 text-slate-700 font-semibold px-6 py-3 rounded-xl transition-colors duration-200 text-sm">
                    Cancelar
                </a>
            </div>
        </form>
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
                        <li><a href="/registro.php" class="hover:text-primary-400 transition-colors text-primary-400">Registro de Empresa</a></li>
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
