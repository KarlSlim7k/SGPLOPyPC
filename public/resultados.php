<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultados de Adjudicación — SGPLOPyPC</title>
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
    <link rel="stylesheet" href="/frontend/shared/public-accessibility.css">
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
                    <a href="/#convocatorias" class="px-4 py-2 text-slate-600 hover:text-primary-600 hover:bg-primary-50 font-medium text-sm rounded-lg transition-all duration-200">Convocatorias</a>
                    <a href="/resultados.php" class="px-4 py-2 text-primary-600 bg-primary-50 font-medium text-sm rounded-lg transition-all duration-200">Resultados</a>
                    <a href="/historial.php" class="px-4 py-2 text-slate-600 hover:text-primary-600 hover:bg-primary-50 font-medium text-sm rounded-lg transition-all duration-200">Historial</a>
                </div>
                <a href="/frontend/auth/login.html" class="inline-flex items-center gap-2 bg-primary-600 hover:bg-primary-700 text-white font-semibold px-4 py-2 rounded-lg transition-colors duration-200 text-sm shadow-sm">
                    <i class="ph ph-sign-in text-base"></i>
                    <span class="hidden sm:inline">Acceso</span>
                </a>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="mb-8">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center">
                    <i class="ph ph-trophy text-xl"></i>
                </div>
                <h1 class="text-2xl lg:text-3xl font-bold text-slate-900">Resultados de Adjudicación</h1>
            </div>
            <p class="text-slate-500">Consulta los fallos emitidos y empresas adjudicatarias de los procedimientos públicos.</p>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl p-5 mb-8">
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Buscar por clave, descripción o empresa</label>
            <input id="resultados-search" type="text" placeholder="Ej: LO-..., pavimentación, Constructora..." class="w-full border border-slate-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100">
                <h2 class="font-bold text-slate-800">Adjudicaciones publicadas</h2>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wide">
                            <th class="text-left px-6 py-3 font-semibold">Licitación / Contrato</th>
                            <th class="text-left px-6 py-3 font-semibold">Proyecto</th>
                            <th class="text-left px-6 py-3 font-semibold">Empresa</th>
                            <th class="text-left px-6 py-3 font-semibold hidden lg:table-cell">Monto</th>
                            <th class="text-left px-6 py-3 font-semibold hidden md:table-cell">Fecha Fallo</th>
                            <th class="text-left px-6 py-3 font-semibold">Estado</th>
                        </tr>
                    </thead>
                    <tbody id="resultados-list" class="divide-y divide-slate-100">
                        <tr>
                            <td colspan="6" class="px-6 py-6 text-center text-slate-500">Cargando resultados...</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4 border-t border-slate-100 flex justify-between items-center">
                <p id="resultados-page-info" class="text-sm text-slate-500">0 resultados</p>
                <div class="flex gap-2">
                    <button id="resultados-prev" class="px-3 py-1.5 border border-slate-200 rounded-lg text-sm text-slate-700 hover:bg-slate-50 transition-colors">Anterior</button>
                    <button id="resultados-next" class="px-3 py-1.5 border border-slate-200 rounded-lg text-sm text-slate-700 hover:bg-slate-50 transition-colors">Siguiente</button>
                </div>
            </div>
        </div>
    </main>

    <script src="/frontend/shared/format.js"></script>
    <script src="/frontend/shared/public.js"></script>
</body>
</html>
