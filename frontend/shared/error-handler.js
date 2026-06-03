/**
 * Error boundary global para capturar errores no manejados
 * y mostrar un toast genérico sin romper la UX.
 */
(function () {
  function showErrorToast(message) {
    var existing = document.getElementById('sgpl-global-error-toast');
    if (existing) existing.remove();

    var toast = document.createElement('div');
    toast.id = 'sgpl-global-error-toast';
    toast.className = 'fixed bottom-4 right-4 z-50 bg-red-600 text-white text-sm font-medium px-4 py-3 rounded-xl shadow-lg border border-red-700 flex items-center gap-2';
    toast.innerHTML = '<i class="ph ph-warning-circle text-lg"></i> '
      + '<span>' + (message || 'Ocurrió un error inesperado.') + '</span>';

    document.body.appendChild(toast);

    setTimeout(function () {
      if (toast.parentNode) toast.parentNode.removeChild(toast);
    }, 5000);
  }

  window.addEventListener('unhandledrejection', function (event) {
    console.error('Unhandled rejection:', event.reason);
    showErrorToast('Ocurrió un error inesperado. Intenta de nuevo.');
  });

  window.addEventListener('error', function (event) {
    console.error('Global error:', event.error);
    showErrorToast('Ocurrió un error inesperado. Intenta de nuevo.');
  });
})();
