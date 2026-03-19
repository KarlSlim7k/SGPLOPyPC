<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGPLOPyPC - Licitaciones y Contrataciones</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#eff6ff', 100: '#dbeafe', 500: '#3b82f6', 600: '#2563eb', 700: '#1d4ed8', 800: '#1e40af', 900: '#1e3a8a',
                        }
                    }
                }
            }
        }
    </script>
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
</head>
<body class="bg-slate-50 text-slate-900 font-sans antialiased flex flex-col min-h-screen">

    <!-- Navbar -->
    <nav class="bg-white border-b border-slate-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded bg-primary-700 flex items-center justify-center text-white font-bold text-lg">
                        SG
                    </div>
                    <div>
                        <h1 class="font-bold text-slate-800 leading-tight">SGPLOPyPC</h1>
                    </div>
                </div>
                <div class="hidden md:flex items-center space-x-8">
                    <a href="#inicio" class="text-slate-600 hover:text-primary-600 font-medium transition-colors">Inicio</a>
                    <a href="#convocatorias" class="text-slate-600 hover:text-primary-600 font-medium transition-colors">Convocatorias</a>
                    <a href="#como-participar" class="text-slate-600 hover:text-primary-600 font-medium transition-colors">Proveedores</a>
                    <a href="#transparencia" class="text-slate-600 hover:text-primary-600 font-medium transition-colors">Transparencia</a>
                </div>
                <div class="flex items-center gap-4">
                    <a href="/frontend/public/dashboard.html" class="hidden sm:flex text-slate-600 hover:text-primary-600 font-medium transition-colors items-center gap-2">
                        <i class="ph ph-sign-in text-xl"></i>
                        Ventanilla de Acceso
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="inicio" class="relative bg-white overflow-hidden">
        <div class="absolute inset-y-0 right-0 w-1/2 bg-slate-50 rounded-l-full opacity-50 -z-10 translate-x-1/3"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 lg:py-28 flex flex-col lg:flex-row items-center gap-12">
            <div class="lg:w-1/2 space-y-8">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-primary-50 text-primary-700 text-sm font-medium border border-primary-100">
                    <span class="w-2 h-2 rounded-full bg-primary-500 animate-pulse"></span>
                    Transparencia y Eficiencia
                </div>
                <h2 class="text-4xl md:text-5xl font-extrabold text-slate-900 leading-tight">
                    Sistema para la Gestión del Procedimiento de <span class="text-primary-600">Licitación de Obra Pública</span>
                </h2>
                <p class="text-xl text-slate-600 leading-relaxed">
                    Plataforma integral para gestionar, controlar y dar seguimiento a los procesos de contratación pública, asegurando transparencia y trazabilidad en cada etapa para proveedores y administradores.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 pt-4">
                    <a href="#convocatorias" class="inline-flex items-center justify-center gap-2 bg-primary-600 hover:bg-primary-700 text-white font-medium px-6 py-3 rounded-lg transition-colors shadow-md text-lg">
                        <i class="ph ph-magnifying-glass text-xl"></i>
                        Buscar Convocatorias
                    </a>
                    <a href="#registro" class="inline-flex items-center justify-center gap-2 bg-white border-2 border-slate-200 hover:border-primary-200 hover:bg-slate-50 text-slate-700 font-medium px-6 py-3 rounded-lg transition-all text-lg">
                        <i class="ph ph-user-plus text-xl"></i>
                        Registro de Proveedores
                    </a>
                </div>
            </div>
            <div class="lg:w-1/2 w-full lg:pl-10 relative">
                <div class="aspect-video bg-slate-100 rounded-2xl border border-slate-200 shadow-xl overflow-hidden relative flex items-center justify-center">
                    <div class="absolute inset-0 bg-gradient-to-tr from-primary-900/10 to-transparent"></div>
                    <i class="ph ph-buildings text-9xl text-slate-300"></i>
                    
                    <!-- Floating Cards to simulate UI -->
                    <div class="absolute -left-6 top-1/4 bg-white p-4 rounded-xl shadow-lg border border-slate-100 animate-bounce" style="animation-duration: 3s;">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600"><i class="ph ph-check-circle size-6"></i></div>
                            <div>
                                <p class="text-xs text-slate-500">Estado de Licitación</p>
                                <p class="text-sm font-bold text-slate-800">Evaluada exitosamente</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Funcionalidades Módulos -->
    <section class="py-20 bg-slate-50 border-t border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <h3 class="text-primary-600 font-semibold tracking-wide uppercase text-sm mb-2">Módulos Principales</h3>
                <h2 class="text-3xl font-bold text-slate-900 sm:text-4xl">Todo el ciclo de vida en un solo lugar</h2>
                <p class="mt-4 text-lg text-slate-600">Desde la publicación de la convocatoria hasta la adjudicación del contrato de obra pública.</p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Card 1 -->
                <div class="bg-white rounded-2xl p-8 border border-slate-200 shadow-sm hover:shadow-md transition-shadow group">
                    <div class="w-14 h-14 rounded-xl bg-primary-50 text-primary-600 flex items-center justify-center mb-6 border border-primary-100 group-hover:bg-primary-600 group-hover:text-white transition-colors">
                        <i class="ph ph-megaphone text-3xl"></i>
                    </div>
                    <h4 class="text-xl font-bold text-slate-800 mb-3">Convocatorias Publicadas</h4>
                    <p class="text-slate-600 leading-relaxed">Consulta las bases de licitación, fechas importantes, documentos técnicos y resultados de procesos adjudicados en tiempo real.</p>
                </div>
                <!-- Card 2 -->
                <div class="bg-white rounded-2xl p-8 border border-slate-200 shadow-sm hover:shadow-md transition-shadow group">
                    <div class="w-14 h-14 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center mb-6 border border-emerald-100 group-hover:bg-emerald-600 group-hover:text-white transition-colors">
                        <i class="ph ph-file-arrow-up text-3xl"></i>
                    </div>
                    <h4 class="text-xl font-bold text-slate-800 mb-3">Recepción de Propuestas</h4>
                    <p class="text-slate-600 leading-relaxed">Carga segura de propuestas técnicas y económicas mediante validación de requisitos y confirmación electrónica inmediata.</p>
                </div>
                <!-- Card 3 -->
                <div class="bg-white rounded-2xl p-8 border border-slate-200 shadow-sm hover:shadow-md transition-shadow group">
                    <div class="w-14 h-14 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center mb-6 border border-indigo-100 group-hover:bg-indigo-600 group-hover:text-white transition-colors">
                        <i class="ph ph-check-square-offset text-3xl"></i>
                    </div>
                    <h4 class="text-xl font-bold text-slate-800 mb-3">Evaluación y Dictamen</h4>
                    <p class="text-slate-600 leading-relaxed">Comparación transparente de ofertas mediante criterios técnicos y económicos que concluyen en una adjudicación documentada.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Transparencia -->
    <section id="transparencia" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-primary-900 rounded-3xl overflow-hidden shadow-2xl relative">
                <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZyBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxwYXRoIGQ9Ik0zNiAzNHYtNGwtMi0ydjRsMiAyeiIgZmlsbD0iIzFkNGVkOCIgZmlsbC1vcGFjaXR5PSIwLjQiLz48L2c+PC9zdmc+')]"></div>
                
                <div class="relative px-8 py-16 md:p-16 flex flex-col items-center text-center">
                    <h2 class="text-3xl md:text-4xl font-bold text-white mb-6">Compromiso de Transparencia Pública</h2>
                    <p class="text-primary-100 text-lg md:text-xl max-w-3xl mb-10">
                        Todos los procesos de adjudicación constan de actas públicas que validan técnica y económicamente a los proveedores ganadores, garantizando equidad y legalidad según los marcos normativos vigentes.
                    </p>
                    <div class="flex flex-wrap justify-center gap-6">
                        <div class="bg-white/10 backdrop-blur-md rounded-xl p-6 border border-white/20 text-white min-w-[200px]">
                            <p class="text-4xl font-extrabold mb-1">100%</p>
                            <p class="text-primary-200 font-medium text-sm">Trazabilidad Registrada</p>
                        </div>
                        <div class="bg-white/10 backdrop-blur-md rounded-xl p-6 border border-white/20 text-white min-w-[200px]">
                            <p class="text-4xl font-extrabold mb-1">+50</p>
                            <p class="text-primary-200 font-medium text-sm">Proyectos Abiertos</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-slate-900 text-slate-300 py-12 border-t border-slate-800 mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-8">
                <div>
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-8 h-8 rounded bg-primary-600 flex items-center justify-center text-white font-bold text-sm">
                            SG
                        </div>
                        <h1 class="font-bold text-white text-lg leading-tight">SGPLOPyPC</h1>
                    </div>
                    <p class="text-sm text-slate-400 max-w-xs">Plataforma digital en cumplimiento normativo para la contratación de obra y servicios.</p>
                </div>
                <div>
                    <h4 class="text-white font-semibold mb-4">Accesos Rápidos</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#" class="hover:text-primary-400 transition-colors">Convocatorias Vigentes</a></li>
                        <li><a href="#" class="hover:text-primary-400 transition-colors">Registro de Proveedores</a></li>
                        <li><a href="#" class="hover:text-primary-400 transition-colors">Normatividad y Bases</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-white font-semibold mb-4">Soporte Técnico</h4>
                    <ul class="space-y-2 text-sm">
                        <li class="flex items-center gap-2"><i class="ph ph-envelope-simple"></i> soporte@sgplopypc.gob</li>
                        <li class="flex items-center gap-2"><i class="ph ph-phone"></i> 01 800 123 4567</li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-slate-800 pt-8 flex flex-col md:flex-row justify-between items-center gap-4 text-xs text-slate-500">
                <p>&copy; 2026 Plataforma SGPLOPyPC. Todos los derechos reservados.</p>
                <div class="flex gap-4">
                    <a href="#" class="hover:text-white transition-colors">Términos de Uso</a>
                    <a href="#" class="hover:text-white transition-colors">Aviso de Privacidad</a>
                </div>
            </div>
        </div>
    </footer>

</body>
</html>
