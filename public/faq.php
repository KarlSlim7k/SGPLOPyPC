<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preguntas Frecuentes — SGPLOPyPC</title>
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
        .faq-item { border-bottom: 1px solid #f1f5f9; }
        .faq-item:last-child { border-bottom: none; }
        .faq-answer { max-height: 0; overflow: hidden; transition: max-height 0.3s ease, padding 0.3s ease; }
        .faq-item.active .faq-answer { max-height: 500px; padding-top: 1rem; padding-bottom: 1.25rem; }
        .faq-item.active .faq-icon { transform: rotate(180deg); }
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
                <span class="text-slate-700 font-medium">Preguntas Frecuentes</span>
            </nav>
        </div>
    </div>

    <!-- Main Content -->
    <main class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="mb-8">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center">
                    <i class="ph ph-question text-xl"></i>
                </div>
                <h1 class="text-2xl lg:text-3xl font-bold text-slate-900">Preguntas Frecuentes</h1>
            </div>
            <p class="text-slate-500">Respuestas a las dudas más comunes sobre el registro, participación en licitaciones y uso del sistema SGPLOPyPC.</p>
        </div>

        <!-- Search -->
        <div class="mb-8">
            <div class="relative">
                <i class="ph ph-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-lg"></i>
                <input type="text" id="faq-search" placeholder="Buscar una pregunta..." class="w-full border border-slate-200 rounded-xl pl-12 pr-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent bg-white">
            </div>
        </div>

        <!-- Categories -->
        <div class="flex flex-wrap gap-2 mb-8">
            <button class="faq-filter active bg-primary-600 text-white px-4 py-2 rounded-lg text-sm font-semibold transition-colors" data-filter="all">Todas</button>
            <button class="faq-filter bg-white border border-slate-200 text-slate-600 px-4 py-2 rounded-lg text-sm font-semibold hover:bg-slate-50 transition-colors" data-filter="registro">Registro</button>
            <button class="faq-filter bg-white border border-slate-200 text-slate-600 px-4 py-2 rounded-lg text-sm font-semibold hover:bg-slate-50 transition-colors" data-filter="licitacion">Licitaciones</button>
            <button class="faq-filter bg-white border border-slate-200 text-slate-600 px-4 py-2 rounded-lg text-sm font-semibold hover:bg-slate-50 transition-colors" data-filter="documentos">Documentos</button>
            <button class="faq-filter bg-white border border-slate-200 text-slate-600 px-4 py-2 rounded-lg text-sm font-semibold hover:bg-slate-50 transition-colors" data-filter="pagos">Pagos</button>
        </div>

        <!-- FAQ List -->
        <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden">

            <!-- Registro -->
            <div class="faq-item" data-category="registro">
                <button class="faq-question w-full text-left px-6 py-5 flex items-center justify-between gap-4 hover:bg-slate-50 transition-colors">
                    <span class="font-semibold text-slate-800 text-sm">¿Quién puede registrarse como proveedor?</span>
                    <i class="ph ph-caret-down faq-icon text-slate-400 transition-transform duration-200 shrink-0"></i>
                </button>
                <div class="faq-answer px-6">
                    <p class="text-slate-600 text-sm leading-relaxed">Toda persona moral (empresa) o persona física con actividad empresarial puede registrarse en el sistema. Es necesario contar con RFC vigente, acta constitutiva (para personas morales) y documentación fiscal al día. El registro es gratuito y la validación automática toma menos de 5 minutos.</p>
                </div>
            </div>

            <div class="faq-item" data-category="registro">
                <button class="faq-question w-full text-left px-6 py-5 flex items-center justify-between gap-4 hover:bg-slate-50 transition-colors">
                    <span class="font-semibold text-slate-800 text-sm">¿Cuánto tiempo tarda la validación del registro?</span>
                    <i class="ph ph-caret-down faq-icon text-slate-400 transition-transform duration-200 shrink-0"></i>
                </button>
                <div class="faq-answer px-6">
                    <p class="text-slate-600 text-sm leading-relaxed">La validación inicial es automática e inmediata. Sin embargo, para tener la cuenta completamente habilitada y poder enviar propuestas, debes cargar toda la documentación requerida. La revisión documental por parte del equipo administrativo puede tomar de 24 a 48 horas hábiles.</p>
                </div>
            </div>

            <div class="faq-item" data-category="registro">
                <button class="faq-question w-full text-left px-6 py-5 flex items-center justify-between gap-4 hover:bg-slate-50 transition-colors">
                    <span class="font-semibold text-slate-800 text-sm">¿El registro tiene costo?</span>
                    <i class="ph ph-caret-down faq-icon text-slate-400 transition-transform duration-200 shrink-0"></i>
                </button>
                <div class="faq-answer px-6">
                    <p class="text-slate-600 text-sm leading-relaxed">No. El registro como proveedor en la plataforma SGPLOPyPC es completamente gratuito. No se cobra ningún derecho por dar de alta tu empresa ni por participar en las licitaciones publicadas. Únicamente los costos asociados a la obtención de documentos (fianzas, seguros, etc.) corren por cuenta del proveedor.</p>
                </div>
            </div>

            <!-- Licitaciones -->
            <div class="faq-item" data-category="licitacion">
                <button class="faq-question w-full text-left px-6 py-5 flex items-center justify-between gap-4 hover:bg-slate-50 transition-colors">
                    <span class="font-semibold text-slate-800 text-sm">¿Cómo me entero de nuevas licitaciones?</span>
                    <i class="ph ph-caret-down faq-icon text-slate-400 transition-transform duration-200 shrink-0"></i>
                </button>
                <div class="faq-answer px-6">
                    <p class="text-slate-600 text-sm leading-relaxed">Una vez registrado, puedes activar las notificaciones por correo electrónico en tu perfil. Además, todas las convocatorias se publican en la sección de <a href="/#convocatorias" class="text-primary-600 hover:underline">Convocatorias Vigentes</a> donde puedes filtrar por tipo de obra, municipio y estado del proceso.</p>
                </div>
            </div>

            <div class="faq-item" data-category="licitacion">
                <button class="faq-question w-full text-left px-6 py-5 flex items-center justify-between gap-4 hover:bg-slate-50 transition-colors">
                    <span class="font-semibold text-slate-800 text-sm">¿Qué pasa si no entrego mi propuesta a tiempo?</span>
                    <i class="ph ph-caret-down faq-icon text-slate-400 transition-transform duration-200 shrink-0"></i>
                </button>
                <div class="faq-answer px-6">
                    <p class="text-slate-600 text-sm leading-relaxed">El sistema cierra automáticamente la recepción de propuestas en la fecha y hora establecidas en la convocatoria. No es posible enviar documentos después del cierre. Si te encuentras con dificultades técnicas, contacta a soporte antes del cierre para que se pueda dar seguimiento.</p>
                </div>
            </div>

            <div class="faq-item" data-category="licitacion">
                <button class="faq-question w-full text-left px-6 py-5 flex items-center justify-between gap-4 hover:bg-slate-50 transition-colors">
                    <span class="font-semibold text-slate-800 text-sm">¿Puedo participar en más de una licitación al mismo tiempo?</span>
                    <i class="ph ph-caret-down faq-icon text-slate-400 transition-transform duration-200 shrink-0"></i>
                </button>
                <div class="faq-answer px-6">
                    <p class="text-slate-600 text-sm leading-relaxed">Sí, no existe límite de participación. Puedes enviar propuestas a todas las licitaciones que consideres convenientes, siempre y cuando cumplas con los requisitos específicos de cada una y entregues la documentación dentro del plazo establecido.</p>
                </div>
            </div>

            <div class="faq-item" data-category="licitacion">
                <button class="faq-question w-full text-left px-6 py-5 flex items-center justify-between gap-4 hover:bg-slate-50 transition-colors">
                    <span class="font-semibold text-slate-800 text-sm">¿Qué es una junta de aclaraciones?</span>
                    <i class="ph ph-caret-down faq-icon text-slate-400 transition-transform duration-200 shrink-0"></i>
                </button>
                <div class="faq-answer px-6">
                    <p class="text-slate-600 text-sm leading-relaxed">Es una reunión (presencial o virtual) convocada por el área requirente donde los proveedores pueden hacer preguntas sobre las bases de la licitación. Las respuestas se documentan en un acta que es vinculante para todos los participantes. Se recomienda asistir o estar atento a las aclaraciones publicadas.</p>
                </div>
            </div>

            <!-- Documentos -->
            <div class="faq-item" data-category="documentos">
                <button class="faq-question w-full text-left px-6 py-5 flex items-center justify-between gap-4 hover:bg-slate-50 transition-colors">
                    <span class="font-semibold text-slate-800 text-sm">¿En qué formato debo subir mis documentos?</span>
                    <i class="ph ph-caret-down faq-icon text-slate-400 transition-transform duration-200 shrink-0"></i>
                </button>
                <div class="faq-answer px-6">
                    <p class="text-slate-600 text-sm leading-relaxed">Los documentos legales y fiscales deben subirse en formato PDF. Las imágenes de identificación pueden ser JPG o PNG con resolución mínima de 300 DPI. El tamaño máximo por archivo es de 10 MB. Asegúrate de que los documentos sean legibles y estén completos.</p>
                </div>
            </div>

            <div class="faq-item" data-category="documentos">
                <button class="faq-question w-full text-left px-6 py-5 flex items-center justify-between gap-4 hover:bg-slate-50 transition-colors">
                    <span class="font-semibold text-slate-800 text-sm">¿Qué pasa si un documento está vencido?</span>
                    <i class="ph ph-caret-down faq-icon text-slate-400 transition-transform duration-200 shrink-0"></i>
                </button>
                <div class="faq-answer px-6">
                    <p class="text-slate-600 text-sm leading-relaxed">Los documentos con vigencia (como la Constancia de Situación Fiscal) deben estar actualizados. Si presentas un documento vencido, tu registro o tu propuesta podrán ser rechazados. El sistema te notificará antes del vencimiento de tus documentos para que los actualices.</p>
                </div>
            </div>

            <!-- Pagos -->
            <div class="faq-item" data-category="pagos">
                <button class="faq-question w-full text-left px-6 py-5 flex items-center justify-between gap-4 hover:bg-slate-50 transition-colors">
                    <span class="font-semibold text-slate-800 text-sm">¿Cuándo se realiza el pago de los contratos?</span>
                    <i class="ph ph-caret-down faq-icon text-slate-400 transition-transform duration-200 shrink-0"></i>
                </button>
                <div class="faq-answer px-6">
                    <p class="text-slate-600 text-sm leading-relaxed">Los pagos se realizan conforme al calendario establecido en cada contrato. Generalmente se efectúan contra entrega parcial verificada (estimaciones). Los pagos se realizan por transferencia electrónica en un plazo de 15 a 30 días naturales posteriores a la autorización de la estimación correspondiente.</p>
                </div>
            </div>

            <div class="faq-item" data-category="pagos">
                <button class="faq-question w-full text-left px-6 py-5 flex items-center justify-between gap-4 hover:bg-slate-50 transition-colors">
                    <span class="font-semibold text-slate-800 text-sm">¿Qué es la fianza de cumplimiento?</span>
                    <i class="ph ph-caret-down faq-icon text-slate-400 transition-transform duration-200 shrink-0"></i>
                </button>
                <div class="faq-answer px-6">
                    <p class="text-slate-600 text-sm leading-relaxed">Es un instrumento de garantía que protege a la dependencia pública en caso de incumplimiento del contrato. Generalmente representa el 10% del monto total del contrato y debe ser emitida por una institución de fianzas autorizada por la CNSF. Se entrega antes de la firma del contrato.</p>
                </div>
            </div>

            <div class="faq-item" data-category="pagos">
                <button class="faq-question w-full text-left px-6 py-5 flex items-center justify-between gap-4 hover:bg-slate-50 transition-colors">
                    <span class="font-semibold text-slate-800 text-sm">¿Puedo solicitar un anticipo?</span>
                    <i class="ph ph-caret-down faq-icon text-slate-400 transition-transform duration-200 shrink-0"></i>
                </button>
                <div class="faq-answer px-6">
                    <p class="text-slate-600 text-sm leading-relaxed">Sí, siempre que las bases de la licitación lo contemplen. El anticipo generalmente es del 10% al 30% del monto del contrato y se otorga contra entrega de fianza de anticipo. Los descuentos del anticipo se realizan proporcionalmente en cada estimación de pago.</p>
                </div>
            </div>
        </div>

        <!-- Contact CTA -->
        <div class="mt-10 bg-white border border-slate-200 rounded-2xl p-6 text-center">
            <div class="w-12 h-12 rounded-xl bg-primary-100 text-primary-600 flex items-center justify-center mx-auto mb-4">
                <i class="ph ph-headset text-2xl"></i>
            </div>
            <h3 class="font-bold text-slate-800 text-lg mb-2">¿No encontraste lo que buscabas?</h3>
            <p class="text-slate-500 mb-5">Nuestro equipo de soporte está disponible para resolver tus dudas.</p>
            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <a href="mailto:soporte@sgplopypc.gob.mx" class="inline-flex items-center justify-center gap-2 bg-primary-600 hover:bg-primary-700 text-white font-semibold px-5 py-2.5 rounded-xl transition-colors text-sm">
                    <i class="ph ph-envelope text-base"></i>
                    soporte@sgplopypc.gob.mx
                </a>
                <a href="tel:018001234567" class="inline-flex items-center justify-center gap-2 border border-slate-200 hover:bg-slate-50 text-slate-700 font-semibold px-5 py-2.5 rounded-xl transition-colors text-sm">
                    <i class="ph ph-phone text-base"></i>
                    01 800 123 4567
                </a>
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
                        <li><a href="/historial.php" class="hover:text-primary-400 transition-colors">Historial de Licitaciones</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-white font-semibold text-sm mb-4">Proveedores</h4>
                    <ul class="space-y-2.5 text-sm">
                        <li><a href="/registro.php" class="hover:text-primary-400 transition-colors">Registro de Empresa</a></li>
                        <li><a href="/#como-participar" class="hover:text-primary-400 transition-colors">Cómo Participar</a></li>
                        <li><a href="/requisitos.php" class="hover:text-primary-400 transition-colors">Requisitos de Documentación</a></li>
                        <li><a href="/faq.php" class="hover:text-primary-400 transition-colors text-primary-400">Preguntas Frecuentes</a></li>
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

    <script>
        // FAQ Accordion
        document.querySelectorAll('.faq-question').forEach(btn => {
            btn.addEventListener('click', () => {
                const item = btn.closest('.faq-item');
                const isActive = item.classList.contains('active');
                // Close all
                document.querySelectorAll('.faq-item').forEach(i => i.classList.remove('active'));
                // Toggle clicked
                if (!isActive) item.classList.add('active');
            });
        });

        // FAQ Filter
        document.querySelectorAll('.faq-filter').forEach(btn => {
            btn.addEventListener('click', () => {
                const filter = btn.dataset.filter;
                // Update active button
                document.querySelectorAll('.faq-filter').forEach(b => {
                    b.classList.remove('bg-primary-600', 'text-white');
                    b.classList.add('bg-white', 'border', 'border-slate-200', 'text-slate-600');
                });
                btn.classList.remove('bg-white', 'border', 'border-slate-200', 'text-slate-600');
                btn.classList.add('bg-primary-600', 'text-white');

                // Filter items
                document.querySelectorAll('.faq-item').forEach(item => {
                    if (filter === 'all' || item.dataset.category === filter) {
                        item.style.display = '';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });
        });

        // FAQ Search
        document.getElementById('faq-search').addEventListener('input', function(e) {
            const query = e.target.value.toLowerCase();
            document.querySelectorAll('.faq-item').forEach(item => {
                const question = item.querySelector('.faq-question span').textContent.toLowerCase();
                const answer = item.querySelector('.faq-answer')?.textContent.toLowerCase() || '';
                if (question.includes(query) || answer.includes(query)) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>
