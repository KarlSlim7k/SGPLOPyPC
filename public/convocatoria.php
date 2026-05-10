<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de Convocatoria — SGPLOPyPC</title>
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
</head>
<body class="bg-slate-50 text-slate-900 font-sans antialiased">
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
                    <a href="/#convocatorias" class="px-4 py-2 text-primary-600 bg-primary-50 font-medium text-sm rounded-lg transition-all duration-200">Convocatorias</a>
                    <a href="/resultados.php" class="px-4 py-2 text-slate-600 hover:text-primary-600 hover:bg-primary-50 font-medium text-sm rounded-lg transition-all duration-200">Resultados</a>
                    <a href="/historial.php" class="px-4 py-2 text-slate-600 hover:text-primary-600 hover:bg-primary-50 font-medium text-sm rounded-lg transition-all duration-200">Historial</a>
                </div>
                <a href="/frontend/auth/login.html" class="inline-flex items-center gap-2 bg-primary-600 hover:bg-primary-700 text-white font-semibold px-4 py-2 rounded-lg transition-colors duration-200 text-sm shadow-sm">
                    <i class="ph ph-sign-in text-base"></i>
                    <span class="hidden sm:inline">Acceso</span>
                </a>
            </div>
        </div>
    </nav>

    <div class="bg-white border-b border-slate-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <nav class="text-sm text-slate-500">
                <a href="/" class="hover:text-primary-600 transition-colors">Inicio</a>
                <span class="mx-2">/</span>
                <a href="/#convocatorias" class="hover:text-primary-600 transition-colors">Convocatorias</a>
                <span class="mx-2">/</span>
                <span class="text-slate-700 font-medium">Detalle</span>
            </nav>
        </div>
    </div>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8">
        <section class="bg-white border border-slate-200 rounded-2xl p-6">
            <p class="text-xs uppercase tracking-wide text-primary-700 font-semibold">Convocatoria pública</p>
            <h1 id="convocatoria-titulo" class="text-2xl font-bold mt-1">Cargando...</h1>
            <p id="convocatoria-subtitulo" class="text-slate-500 text-sm mt-2">Obteniendo información del proceso...</p>
        </section>

        <section class="bg-white border border-slate-200 rounded-2xl p-6">
            <h2 class="font-bold text-slate-800 mb-4">Información del proceso</h2>
            <div id="convocatoria-detalle" class="text-sm text-slate-500">Cargando detalle...</div>
        </section>

        <section class="bg-white border border-slate-200 rounded-2xl overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <h2 class="font-bold text-slate-800">Documentos públicos</h2>
                <span class="text-xs text-slate-500">Descarga directa</span>
            </div>
            <div id="convocatoria-documentos" class="divide-y divide-slate-100">
                <div class="px-6 py-5 text-sm text-slate-500">Cargando documentos...</div>
            </div>
        </section>
    </main>

    <script src="/frontend/shared/format.js"></script>
    <script src="/frontend/shared/public.js"></script>
</body>
</html>
