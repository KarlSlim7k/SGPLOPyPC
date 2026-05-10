(function () {
  const API_BASE = '/api/v1/public';

  function qs(selector, root) {
    return (root || document).querySelector(selector);
  }

  function qsa(selector, root) {
    return Array.from((root || document).querySelectorAll(selector));
  }

  async function fetchJson(path, options) {
    const response = await fetch(path, options || {});
    const contentType = response.headers.get('content-type') || '';
    const payload = contentType.includes('application/json') ? await response.json() : null;
    if (!response.ok || !payload || !payload.success) {
      throw new Error((payload && payload.message) || 'No se pudo completar la solicitud.');
    }
    return payload.data;
  }

  function formatCurrency(amount) {
    const n = Number(amount || 0);
    return new Intl.NumberFormat('es-MX', {
      style: 'currency',
      currency: 'MXN',
      maximumFractionDigits: 0,
    }).format(n);
  }

  function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    if (Number.isNaN(date.getTime())) return 'N/A';
    return new Intl.DateTimeFormat('es-MX', {
      day: '2-digit',
      month: 'short',
      year: 'numeric',
    }).format(date);
  }

  function escapeHtml(value) {
    return String(value || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function buildConvocatoriaHref(id) {
    return '/convocatoria.php?id=' + encodeURIComponent(String(id));
  }

  function tipoLegible(tipo) {
    return {
      LICITACION_PUBLICA: 'Licitación Pública',
      INVITACION_RESTRINGIDA: 'Invitación Restringida',
      ADJUDICACION_DIRECTA: 'Adjudicación Directa',
    }[tipo] || tipo || 'N/A';
  }

  function estadoBadgeClass(estado) {
    if (estado === 'ADJUDICADA' || estado === 'CONCLUIDO' || estado === 'PUBLICADA') {
      return 'bg-emerald-100 text-emerald-700 border-emerald-200';
    }
    if (estado === 'EN_EVALUACION' || estado === 'RECEPCION_PROPUESTAS' || estado === 'EN_EJECUCION') {
      return 'bg-amber-100 text-amber-700 border-amber-200';
    }
    return 'bg-slate-100 text-slate-700 border-slate-200';
  }

  function mountMessage(container, message, tone) {
    container.innerHTML = '<div class="p-6 rounded-xl border text-sm ' +
      (tone === 'error'
        ? 'bg-red-50 border-red-200 text-red-700'
        : 'bg-slate-50 border-slate-200 text-slate-600') +
      '">' + message + '</div>';
  }

  async function initLanding() {
    const listEl = qs('#landing-convocatorias-list');
    if (!listEl) return;

    try {
      const [stats, conv] = await Promise.all([
        fetchJson(API_BASE + '/estadisticas'),
        fetchJson(API_BASE + '/convocatorias?limit=3&sort=fecha_creacion&order=DESC'),
      ]);

      const licActivas = qs('#landing-stat-licitaciones');
      const provRegs = qs('#landing-stat-proveedores');
      const contratos = qs('#landing-stat-contratos');
      const monto = qs('#landing-stat-monto');
      const tActivos = qs('#landing-tr-activas');
      const tMonto = qs('#landing-tr-monto');
      const pLic = qs('#landing-preview-licitaciones');
      const pProv = qs('#landing-preview-proveedores');
      const pEval = qs('#landing-preview-evaluacion');
      const pContratos = qs('#landing-preview-contratos');
      const previewRows = qs('#landing-preview-rows');

      if (licActivas) licActivas.textContent = String(stats.licitaciones_activas || 0);
      if (provRegs) {
        const totalProveedores = stats.proveedores_registrados_total ?? stats.proveedores_registrados ?? 0;
        provRegs.textContent = String(totalProveedores);
      }
      if (contratos) contratos.textContent = String(stats.contratos_adjudicados || 0);
      if (monto) monto.textContent = formatCurrency(stats.monto_total_contratado || 0);
      if (tActivos) tActivos.textContent = '+' + String(stats.licitaciones_activas || 0);
      if (tMonto) tMonto.textContent = formatCurrency(stats.monto_total_contratado || 0);
      if (pLic) pLic.textContent = String(stats.licitaciones_activas || 0);
      if (pProv) pProv.textContent = String(stats.proveedores_registrados_total ?? stats.proveedores_registrados ?? 0);
      if (pEval) pEval.textContent = String(stats.licitaciones_en_evaluacion || 0);
      if (pContratos) pContratos.textContent = String(stats.contratos_adjudicados || 0);

      if (!conv.items || conv.items.length === 0) {
        mountMessage(listEl, 'No hay convocatorias públicas disponibles en este momento.');
        if (previewRows) {
          previewRows.innerHTML = '<div class="px-3 py-3 text-slate-400 text-xs">Sin convocatorias públicas por mostrar.</div>';
        }
        return;
      }

      listEl.innerHTML = conv.items.map(function (item) {
        const detailHref = buildConvocatoriaHref(item.id_licitacion);
        return '<article class="bg-white border border-slate-200 rounded-2xl p-6 hover:border-primary-200 hover:shadow-md transition-all duration-200">'
          + '<div class="flex flex-col md:flex-row md:items-center justify-between gap-4">'
          + '<div class="flex-1">'
          + '<div class="flex flex-wrap items-center gap-2 mb-2">'
          + '<span class="text-xs font-bold text-slate-400 font-mono">' + (item.numero_licitacion || 'N/A') + '</span>'
          + '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold border ' + estadoBadgeClass(item.estado_proceso) + '">' + (item.estado_proceso || 'N/A') + '</span>'
          + '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">' + tipoLegible(item.tipo_procedimiento) + '</span>'
          + '</div>'
          + '<h3 class="font-bold text-slate-800 text-base">' + (item.descripcion_proyecto || 'Sin descripción') + '</h3>'
          + '<p class="text-slate-500 text-sm mt-1">' + (item.dependencia_nombre || 'Dependencia no disponible') + '</p>'
          + '</div>'
          + '<div class="text-right shrink-0">'
          + '<p class="text-xs text-slate-400">Presupuesto estimado</p>'
          + '<p class="font-bold text-slate-800 text-sm">' + formatCurrency(item.presupuesto_estimado) + '</p>'
          + '<p class="text-xs text-slate-400 mt-1">Fallo: ' + formatDate(item.fecha_fallo_adjudicacion) + '</p>'
          + '</div>'
          + '</div>'
          + '<div class="mt-4 pt-4 border-t border-slate-100 flex justify-end">'
          + '<a href="' + detailHref + '" class="inline-flex items-center gap-1.5 text-sm font-semibold text-primary-700 hover:text-primary-800">'
          + 'Ver detalle <i class="ph ph-arrow-right"></i>'
          + '</a>'
          + '</div>'
          + '</article>';
      }).join('');

      if (previewRows) {
        previewRows.innerHTML = conv.items.slice(0, 2).map(function (item) {
          return '<div class="px-3 py-2 flex items-center justify-between gap-2">'
            + '<div>'
            + '<p class="text-white text-xs font-medium">' + escapeHtml(item.numero_licitacion || 'N/A') + '</p>'
            + '<p class="text-slate-400 text-xs truncate max-w-[220px]">' + escapeHtml(item.descripcion_proyecto || 'Sin descripción') + '</p>'
            + '</div>'
            + '<span class="text-xs px-2 py-0.5 rounded-full border ' + (item.estado_proceso === 'PUBLICADA'
              ? 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30'
              : 'bg-amber-500/20 text-amber-400 border-amber-500/30') + '">'
            + escapeHtml(item.estado_proceso || 'N/A')
            + '</span>'
            + '</div>';
        }).join('');
      }
    } catch (error) {
      mountMessage(listEl, 'No se pudieron cargar las convocatorias públicas.', 'error');
      if (previewRows) {
        previewRows.innerHTML = '<div class="px-3 py-3 text-red-300 text-xs">No se pudieron cargar los datos del resumen.</div>';
      }
    }
  }

  async function initEvaluacion() {
    const listEl = qs('#evaluacion-list');
    if (!listEl) return;

    try {
      const data = await fetchJson(API_BASE + '/evaluaciones?limit=20');
      if (!data.items || data.items.length === 0) {
        mountMessage(listEl, 'No hay procesos en evaluación o recepción en este momento.');
        return;
      }

      listEl.innerHTML = data.items.map(function (item) {
        const progreso = item.estado_proceso === 'EN_EVALUACION' ? 70 : 30;
        const color = item.estado_proceso === 'EN_EVALUACION' ? 'amber' : 'blue';

        return '<article class="bg-white border border-slate-200 rounded-2xl p-6 hover:shadow-md transition-all">'
          + '<div class="flex items-center justify-between gap-3">'
          + '<span class="text-xs font-bold text-slate-400 font-mono">' + item.numero_licitacion + '</span>'
          + '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border ' + estadoBadgeClass(item.estado_proceso) + '">' + item.estado_proceso + '</span>'
          + '</div>'
          + '<h3 class="font-bold text-slate-800 text-lg mt-2">' + (item.descripcion_proyecto || 'Sin descripción') + '</h3>'
          + '<p class="text-slate-500 text-sm mt-1">' + (item.dependencia_nombre || 'Dependencia no disponible') + '</p>'
          + '<div class="mt-5 pt-5 border-t border-slate-100 grid grid-cols-2 md:grid-cols-4 gap-4">'
          + '<div><p class="text-xs text-slate-400 mb-1">Propuestas Recibidas</p><p class="font-bold text-slate-800 text-lg">' + (item.propuestas_recibidas || 0) + '</p></div>'
          + '<div><p class="text-xs text-slate-400 mb-1">Presupuesto</p><p class="font-bold text-slate-800 text-sm">' + formatCurrency(item.presupuesto_estimado) + '</p></div>'
          + '<div><p class="text-xs text-slate-400 mb-1">Cierre Recepción</p><p class="font-semibold text-slate-700 text-sm">' + formatDate(item.fecha_cierre_recepcion) + '</p></div>'
          + '<div><p class="text-xs text-slate-400 mb-1">Fallo Estimado</p><p class="font-semibold text-slate-700 text-sm">' + formatDate(item.fecha_fallo_adjudicacion) + '</p></div>'
          + '</div>'
          + '<div class="mt-4">'
          + '<p class="text-xs text-slate-500 mb-2">Avance del proceso</p>'
          + '<div class="w-full bg-slate-100 rounded-full h-2"><div class="bg-' + color + '-500 h-2 rounded-full" style="width: ' + progreso + '%"></div></div>'
          + '<p class="text-xs text-' + color + '-600 font-medium mt-1">' + progreso + '%</p>'
          + '</div>'
          + '</article>';
      }).join('');
    } catch (_) {
      mountMessage(listEl, 'No se pudieron cargar los procesos en evaluación.', 'error');
    }
  }

  function initHistorial() {
    const listEl = qs('#historial-list');
    if (!listEl) return;

    const searchInput = qs('#historial-search');
    const yearSelect = qs('#historial-year');
    const tipoSelect = qs('#historial-tipo');
    const pageInfo = qs('#historial-page-info');
    const prevBtn = qs('#historial-prev');
    const nextBtn = qs('#historial-next');

    var page = 1;
    var limit = 10;
    var total = 0;

    async function load() {
      const params = new URLSearchParams();
      params.set('page', String(page));
      params.set('limit', String(limit));
      if (searchInput && searchInput.value.trim()) params.set('q', searchInput.value.trim());
      if (yearSelect && yearSelect.value) params.set('year', yearSelect.value);
      if (tipoSelect && tipoSelect.value) params.set('tipo', tipoSelect.value);

      try {
        const data = await fetchJson(API_BASE + '/historial?' + params.toString());
        total = Number(data.total || 0);

        if (!data.items || data.items.length === 0) {
          mountMessage(listEl, 'No se encontraron registros con los filtros aplicados.');
          if (pageInfo) pageInfo.textContent = '0 resultados';
          return;
        }

        listEl.innerHTML = data.items.map(function (item) {
          return '<div class="bg-white border border-slate-200 rounded-xl p-5 hover:shadow-md transition-all">'
            + '<div class="flex flex-col md:flex-row md:items-center justify-between gap-3">'
            + '<div>'
            + '<div class="flex items-center gap-2 mb-1">'
            + '<span class="font-mono text-xs text-slate-400">' + item.numero_licitacion + '</span>'
            + '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold border ' + estadoBadgeClass(item.estado_proceso) + '">' + item.estado_proceso + '</span>'
            + '</div>'
            + '<h3 class="font-semibold text-slate-800">' + (item.descripcion_proyecto || 'Sin descripción') + '</h3>'
            + '<p class="text-slate-500 text-sm">' + (item.dependencia_nombre || '') + ' · ' + tipoLegible(item.tipo_procedimiento) + '</p>'
            + '</div>'
            + '<div class="text-right shrink-0">'
            + '<p class="font-bold text-slate-800">' + formatCurrency(item.monto_contrato || item.presupuesto_estimado || 0) + '</p>'
            + '<p class="text-xs text-slate-400">Actualizada: ' + formatDate(item.fecha_actualizacion) + '</p>'
            + '</div>'
            + '</div>'
            + '</div>';
        }).join('');

        const start = ((page - 1) * limit) + 1;
        const end = Math.min(page * limit, total);
        if (pageInfo) pageInfo.textContent = 'Mostrando ' + start + '-' + end + ' de ' + total;
        if (prevBtn) prevBtn.disabled = page <= 1;
        if (nextBtn) nextBtn.disabled = end >= total;
      } catch (_) {
        mountMessage(listEl, 'No se pudo cargar el historial de licitaciones.', 'error');
      }
    }

    if (searchInput) searchInput.addEventListener('input', function () { page = 1; load(); });
    if (yearSelect) yearSelect.addEventListener('change', function () { page = 1; load(); });
    if (tipoSelect) tipoSelect.addEventListener('change', function () { page = 1; load(); });
    if (prevBtn) prevBtn.addEventListener('click', function () { if (page > 1) { page -= 1; load(); } });
    if (nextBtn) nextBtn.addEventListener('click', function () { page += 1; load(); });

    load();
  }

  function initContratos() {
    const listEl = qs('#contratos-list');
    if (!listEl) return;

    const estatusSelect = qs('#contratos-estatus');
    const summaryTotal = qs('#contratos-summary-total');
    const summaryMonto = qs('#contratos-summary-monto');
    const summaryEjecucion = qs('#contratos-summary-ejecucion');
    const summaryConcluidos = qs('#contratos-summary-concluidos');
    const pageInfo = qs('#contratos-page-info');
    const prevBtn = qs('#contratos-prev');
    const nextBtn = qs('#contratos-next');

    var page = 1;
    var limit = 10;
    var total = 0;

    async function load() {
      const params = new URLSearchParams();
      params.set('page', String(page));
      params.set('limit', String(limit));
      if (estatusSelect && estatusSelect.value) params.set('estatus', estatusSelect.value);

      try {
        const [stats, data] = await Promise.all([
          fetchJson(API_BASE + '/estadisticas'),
          fetchJson(API_BASE + '/contratos?' + params.toString()),
        ]);

        total = Number(data.total || 0);
        if (summaryTotal) summaryTotal.textContent = String(stats.contratos_adjudicados || 0);
        if (summaryMonto) summaryMonto.textContent = formatCurrency(stats.monto_total_contratado || 0);

        const ejec = (data.items || []).filter(function (x) { return x.estatus === 'EN_EJECUCION'; }).length;
        const conc = (data.items || []).filter(function (x) { return x.estatus === 'CONCLUIDO'; }).length;
        if (summaryEjecucion) summaryEjecucion.textContent = String(ejec);
        if (summaryConcluidos) summaryConcluidos.textContent = String(conc);

        if (!data.items || data.items.length === 0) {
          listEl.innerHTML = '<tr><td colspan="5" class="px-6 py-6 text-center text-slate-500">No hay contratos para los filtros seleccionados.</td></tr>';
          if (pageInfo) pageInfo.textContent = '0 resultados';
          return;
        }

        listEl.innerHTML = data.items.map(function (item) {
          return '<tr class="hover:bg-slate-50 transition-colors">'
            + '<td class="px-6 py-4"><p class="font-mono text-xs text-slate-400">' + item.numero_contrato + '</p><p class="font-semibold text-slate-800">' + item.numero_licitacion + '</p></td>'
            + '<td class="px-6 py-4"><p class="text-slate-700">' + (item.adjudicatario_nombre_empresa || 'N/A') + '</p></td>'
            + '<td class="px-6 py-4 hidden md:table-cell"><p class="font-semibold text-slate-800">' + formatCurrency(item.monto_contrato) + '</p></td>'
            + '<td class="px-6 py-4 hidden lg:table-cell"><p class="text-slate-600">' + formatDate(item.fecha_inicio) + '</p></td>'
            + '<td class="px-6 py-4"><span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold border ' + estadoBadgeClass(item.estatus) + '">' + item.estatus + '</span></td>'
            + '</tr>';
        }).join('');

        const start = ((page - 1) * limit) + 1;
        const end = Math.min(page * limit, total);
        if (pageInfo) pageInfo.textContent = 'Mostrando ' + start + '-' + end + ' de ' + total;
        if (prevBtn) prevBtn.disabled = page <= 1;
        if (nextBtn) nextBtn.disabled = end >= total;
      } catch (_) {
        listEl.innerHTML = '<tr><td colspan="5" class="px-6 py-6 text-center text-red-600">No se pudieron cargar los contratos.</td></tr>';
      }
    }

    if (estatusSelect) estatusSelect.addEventListener('change', function () { page = 1; load(); });
    if (prevBtn) prevBtn.addEventListener('click', function () { if (page > 1) { page -= 1; load(); } });
    if (nextBtn) nextBtn.addEventListener('click', function () { page += 1; load(); });

    load();
  }

  function initResultados() {
    const listEl = qs('#resultados-list');
    if (!listEl) return;

    const searchInput = qs('#resultados-search');
    const pageInfo = qs('#resultados-page-info');
    const prevBtn = qs('#resultados-prev');
    const nextBtn = qs('#resultados-next');

    var page = 1;
    var limit = 10;
    var total = 0;

    async function load() {
      const params = new URLSearchParams();
      params.set('page', String(page));
      params.set('limit', String(limit));
      if (searchInput && searchInput.value.trim()) params.set('q', searchInput.value.trim());

      try {
        const data = await fetchJson(API_BASE + '/resultados?' + params.toString());
        total = Number(data.total || 0);

        if (!data.items || data.items.length === 0) {
          listEl.innerHTML = '<tr><td colspan="6" class="px-6 py-6 text-center text-slate-500">No hay resultados para los criterios seleccionados.</td></tr>';
          if (pageInfo) pageInfo.textContent = '0 resultados';
          return;
        }

        listEl.innerHTML = data.items.map(function (item) {
          return '<tr class="hover:bg-slate-50 transition-colors">'
            + '<td class="px-6 py-4"><p class="font-mono text-xs text-slate-400">' + escapeHtml(item.numero_licitacion || 'N/A') + '</p><p class="font-semibold text-slate-800">' + escapeHtml(item.numero_contrato || 'Sin contrato') + '</p></td>'
            + '<td class="px-6 py-4"><p class="text-slate-700">' + escapeHtml(item.descripcion_proyecto || 'Sin descripción') + '</p><p class="text-xs text-slate-400 mt-1">' + escapeHtml(item.dependencia_nombre || 'Dependencia no disponible') + '</p></td>'
            + '<td class="px-6 py-4"><p class="text-slate-700">' + escapeHtml(item.adjudicatario_nombre_empresa || 'N/A') + '</p></td>'
            + '<td class="px-6 py-4 hidden lg:table-cell"><p class="font-semibold text-slate-800">' + formatCurrency(item.monto_contrato || item.presupuesto_estimado || 0) + '</p></td>'
            + '<td class="px-6 py-4 hidden md:table-cell"><p class="text-slate-600">' + formatDate(item.fecha_adjudicacion) + '</p></td>'
            + '<td class="px-6 py-4"><span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold border ' + estadoBadgeClass(item.estado_proceso) + '">' + escapeHtml(item.estado_proceso || 'N/A') + '</span></td>'
            + '</tr>';
        }).join('');

        const start = ((page - 1) * limit) + 1;
        const end = Math.min(page * limit, total);
        if (pageInfo) pageInfo.textContent = 'Mostrando ' + start + '-' + end + ' de ' + total;
        if (prevBtn) prevBtn.disabled = page <= 1;
        if (nextBtn) nextBtn.disabled = end >= total;
      } catch (_) {
        listEl.innerHTML = '<tr><td colspan="6" class="px-6 py-6 text-center text-red-600">No se pudieron cargar los resultados.</td></tr>';
      }
    }

    if (searchInput) searchInput.addEventListener('input', function () { page = 1; load(); });
    if (prevBtn) prevBtn.addEventListener('click', function () { if (page > 1) { page -= 1; load(); } });
    if (nextBtn) nextBtn.addEventListener('click', function () { page += 1; load(); });

    load();
  }

  function initConvocatoriaDetalle() {
    const detalleEl = qs('#convocatoria-detalle');
    if (!detalleEl) return;

    const docsEl = qs('#convocatoria-documentos');
    const params = new URLSearchParams(window.location.search);
    const id = Number(params.get('id') || '');

    if (!id) {
      mountMessage(detalleEl, 'La convocatoria solicitada no es válida.', 'error');
      if (docsEl) {
        docsEl.innerHTML = '<div class="px-6 py-5 text-sm text-slate-500">No hay documentos por mostrar.</div>';
      }
      return;
    }

    Promise.all([
      fetchJson(API_BASE + '/convocatorias/' + String(id)),
      fetchJson(API_BASE + '/convocatorias/' + String(id) + '/documentos'),
    ]).then(function (results) {
      const convocatoria = results[0] || {};
      const documentos = ((results[1] || {}).items) || [];
      const encabezado = qs('#convocatoria-titulo');
      const subtitulo = qs('#convocatoria-subtitulo');

      if (encabezado) encabezado.textContent = convocatoria.numero_licitacion || 'Detalle de convocatoria';
      if (subtitulo) subtitulo.textContent = convocatoria.descripcion_proyecto || 'Información detallada del proceso';

      detalleEl.innerHTML = '<div class="grid md:grid-cols-2 gap-4">'
        + '<div class="bg-slate-50 border border-slate-200 rounded-xl p-4"><p class="text-xs text-slate-400 mb-1">Dependencia</p><p class="font-semibold text-slate-800">' + escapeHtml(convocatoria.dependencia_nombre || 'N/A') + '</p></div>'
        + '<div class="bg-slate-50 border border-slate-200 rounded-xl p-4"><p class="text-xs text-slate-400 mb-1">Tipo de procedimiento</p><p class="font-semibold text-slate-800">' + escapeHtml(tipoLegible(convocatoria.tipo_procedimiento)) + '</p></div>'
        + '<div class="bg-slate-50 border border-slate-200 rounded-xl p-4"><p class="text-xs text-slate-400 mb-1">Estado</p><p class="font-semibold text-slate-800">' + escapeHtml(convocatoria.estado_proceso || 'N/A') + '</p></div>'
        + '<div class="bg-slate-50 border border-slate-200 rounded-xl p-4"><p class="text-xs text-slate-400 mb-1">Presupuesto estimado</p><p class="font-semibold text-slate-800">' + formatCurrency(convocatoria.presupuesto_estimado || 0) + '</p></div>'
        + '<div class="bg-slate-50 border border-slate-200 rounded-xl p-4"><p class="text-xs text-slate-400 mb-1">Cierre de recepción</p><p class="font-semibold text-slate-800">' + formatDate(convocatoria.fecha_cierre_recepcion) + '</p></div>'
        + '<div class="bg-slate-50 border border-slate-200 rounded-xl p-4"><p class="text-xs text-slate-400 mb-1">Fallo estimado</p><p class="font-semibold text-slate-800">' + formatDate(convocatoria.fecha_fallo_adjudicacion) + '</p></div>'
        + '</div>'
        + '<div class="mt-4 bg-white border border-slate-200 rounded-xl p-4">'
        + '<p class="text-xs text-slate-400 mb-1">Descripción del proyecto</p>'
        + '<p class="text-slate-700">' + escapeHtml(convocatoria.descripcion_proyecto || 'Sin descripción') + '</p>'
        + '</div>';

      if (!docsEl) return;
      if (!documentos.length) {
        docsEl.innerHTML = '<div class="px-6 py-5 text-sm text-slate-500">No hay documentos públicos disponibles para esta convocatoria.</div>';
        return;
      }

      docsEl.innerHTML = documentos.map(function (doc) {
        return '<a href="/api/v1/public/documentos/' + encodeURIComponent(String(doc.id_documento)) + '/download" class="flex items-center justify-between gap-3 px-6 py-4 hover:bg-slate-50 transition-colors">'
          + '<div><p class="font-semibold text-slate-800 text-sm">' + escapeHtml(doc.nombre_archivo || 'Documento') + '</p><p class="text-xs text-slate-500 mt-1">' + escapeHtml(doc.tipo_documento || 'N/A') + ' · ' + formatDate(doc.fecha_subida) + '</p></div>'
          + '<span class="inline-flex items-center gap-1.5 text-primary-700 text-sm font-semibold">Descargar <i class="ph ph-download-simple"></i></span>'
          + '</a>';
      }).join('');
    }).catch(function () {
      mountMessage(detalleEl, 'No se pudo cargar el detalle de la convocatoria.', 'error');
      if (docsEl) {
        docsEl.innerHTML = '<div class="px-6 py-5 text-sm text-red-600">No se pudieron cargar los documentos.</div>';
      }
    });
  }

  function initRegistro() {
    const form = qs('#registro-form');
    if (!form) return;

    const status = qs('#registro-status');
    const submitBtn = qs('#registro-submit');

    function setStatus(message, tone) {
      if (!status) return;
      status.className = 'text-sm rounded-lg px-4 py-3 ' + (tone === 'error'
        ? 'bg-red-50 border border-red-200 text-red-700'
        : tone === 'success'
          ? 'bg-emerald-50 border border-emerald-200 text-emerald-700'
          : tone === 'warning'
            ? 'bg-amber-50 border border-amber-200 text-amber-800'
          : 'bg-slate-50 border border-slate-200 text-slate-700');
      status.textContent = message;
      status.classList.remove('hidden');
    }

    form.addEventListener('submit', async function (e) {
      e.preventDefault();
      if (submitBtn) submitBtn.disabled = true;
      if (status) status.classList.add('hidden');

      const payload = {
        nombre_empresa: (qs('#razon-social') || {}).value || '',
        representante_legal: (qs('#nombre-contacto') || {}).value || '',
        registro_fiscal: ((qs('#rfc') || {}).value || '').toUpperCase(),
        regimen_fiscal: (qs('#regimen') || {}).value || '',
        domicilio: (qs('#domicilio-fiscal') || {}).value || '',
        nombre_contacto: (qs('#nombre-contacto') || {}).value || '',
        cargo: (qs('#cargo') || {}).value || '',
        email: ((qs('#email') || {}).value || '').trim().toLowerCase(),
        telefono: (qs('#telefono') || {}).value || '',
        password: (qs('#password') || {}).value || '',
        accepted_terms: !!((qs('#terms') || {}).checked),
        especialidades: qsa('input[name="especialidad[]"]:checked').map(function (x) { return x.value; }),
      };

      try {
        const response = await fetch('/api/v1/public/proveedores/registro', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload),
        });
        const data = await response.json();

        if (!response.ok || !data.success || !data.data || !data.data.token) {
          const errorMessage = (data.errors && data.errors[0]) || data.message || 'No se pudo completar el registro.';
          setStatus(errorMessage, 'error');
          if (submitBtn) submitBtn.disabled = false;
          return;
        }

        localStorage.setItem('sgplopypc_token', data.data.token);
        localStorage.setItem('sgplopypc_user', JSON.stringify(data.data.usuario));

        const docInputs = [
          { id: 'doc-acta', name: 'Acta constitutiva' },
          { id: 'doc-constancia', name: 'Constancia fiscal' },
          { id: 'doc-identificacion', name: 'Identificación' },
        ];
        const failedUploads = [];

        for (var i = 0; i < docInputs.length; i += 1) {
          var doc = docInputs[i];
          var input = qs('#' + doc.id);
          if (!input || !input.files || input.files.length === 0) continue;

          var fd = new FormData();
          fd.append('archivo', input.files[0]);
          fd.append('tipo_documento', 'DOC_LEGAL_PROVEEDOR');
          fd.append('id_proveedor', String(data.data.proveedor.id_proveedor));

          try {
            const uploadResponse = await fetch('/api/v1/documentos/upload', {
              method: 'POST',
              headers: { Authorization: 'Bearer ' + data.data.token },
              body: fd,
            });
            if (!uploadResponse.ok) {
              failedUploads.push(doc.name);
            }
          } catch (_) {
            failedUploads.push(doc.name);
          }
        }

        if (failedUploads.length > 0) {
          setStatus('Registro completado, pero falló la subida de: ' + failedUploads.join(', ') + '. Podrás subirlos después de iniciar sesión.', 'warning');
        } else {
          setStatus('Registro completado. Te redirigiremos al inicio para iniciar sesión con tu nueva cuenta.', 'success');
        }
        setTimeout(function () {
          window.location.href = '/frontend/auth/login.html';
        }, 1400);
      } catch (_) {
        setStatus('No se pudo enviar el registro por un error de red.', 'error');
        if (submitBtn) submitBtn.disabled = false;
      }
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    initLanding();
    initEvaluacion();
    initHistorial();
    initContratos();
    initResultados();
    initConvocatoriaDetalle();
    initRegistro();
  });

  window.SGPLPublic = {
    fetchJson: fetchJson,
    formatCurrency: formatCurrency,
    formatDate: formatDate,
    tipoLegible: tipoLegible,
    escapeHtml: escapeHtml,
  };
})();
