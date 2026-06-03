/**
 * Breadcrumbs reutilizable.
 *
 * Uso:
 *   SGPLBreadcrumbs.render(container, [
 *     { label: 'Inicio', href: '/frontend/proveedor/centro.html' },
 *     { label: 'Mis contratos', href: '/frontend/proveedor/contratos.html' },
 *     { label: 'Detalle', href: null }
 *   ]);
 */
(function () {
  function render(container, items) {
    if (!container || !Array.isArray(items)) return;
    var html = '<nav aria-label="Breadcrumb" class="text-sm text-slate-500">';
    items.forEach(function (item, idx) {
      var isLast = idx === items.length - 1;
      if (idx > 0) {
        html += '<span class="mx-2 text-slate-300">/</span>';
      }
      if (isLast || !item.href) {
        html += '<span class="text-slate-700 font-medium">' + (item.label || '') + '</span>';
      } else {
        html += '<a href="' + item.href + '" class="hover:text-primary-600 transition-colors">' + (item.label || '') + '</a>';
      }
    });
    html += '</nav>';
    container.innerHTML = html;
  }

  window.SGPLBreadcrumbs = { render: render };
})();
