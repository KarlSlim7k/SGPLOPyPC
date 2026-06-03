/**
 * Componente reutilizable de empty state.
 *
 * Uso:
 *   SGPLEmptyState.render(container, { icon: 'ph-archive', title: 'Sin datos', description: 'Aún no hay registros.', cta: { text: 'Crear', href: '/crear' } });
 */
(function () {
  function render(container, options) {
    if (!container) return;
    var opts = options || {};
    var icon = opts.icon || 'ph-archive';
    var title = opts.title || 'Sin datos';
    var description = opts.description || '';
    var cta = opts.cta || null;

    var html = '<div class="px-6 py-10 text-center">'
      + '<i class="' + icon + ' text-4xl text-slate-300"></i>'
      + '<p class="text-slate-500 text-sm mt-3 font-medium">' + (title) + '</p>';

    if (description) {
      html += '<p class="text-slate-400 text-sm mt-1">' + description + '</p>';
    }

    if (cta) {
      html += '<a href="' + cta.href + '" class="inline-flex items-center gap-2 mt-4 bg-primary-600 hover:bg-primary-700 text-white font-semibold px-4 py-2 rounded-lg text-sm">'
        + (cta.icon ? '<i class="' + cta.icon + '"></i> ' : '')
        + cta.text
        + '</a>';
    }

    html += '</div>';
    container.innerHTML = html;
  }

  window.SGPLEmptyState = { render: render };
})();
