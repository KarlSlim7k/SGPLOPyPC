(function() {
  'use strict';

  function renderPagination(container, options) {
    if (!container) return;

    const { page, total_pages, onPageChange } = options;

    if (total_pages <= 1) {
      container.innerHTML = '';
      return;
    }

    const wrapper = document.createElement('div');
    wrapper.className = 'flex items-center justify-center gap-2 mt-6';

    // Botón Anterior
    const prevBtn = document.createElement('button');
    prevBtn.type = 'button';
    prevBtn.className = 'px-3 py-2 text-sm font-medium border border-slate-300 rounded-lg ' +
      (page <= 1 ? 'opacity-50 cursor-not-allowed bg-slate-100 text-slate-400' : 'bg-white text-slate-700 hover:bg-slate-50');
    prevBtn.textContent = '« Anterior';
    prevBtn.disabled = page <= 1;
    if (page > 1) {
      prevBtn.addEventListener('click', () => onPageChange(page - 1));
    }
    wrapper.appendChild(prevBtn);

    // Números de página
    const maxVisible = 5;
    let startPage = Math.max(1, page - Math.floor(maxVisible / 2));
    let endPage = Math.min(total_pages, startPage + maxVisible - 1);

    if (endPage - startPage + 1 < maxVisible) {
      startPage = Math.max(1, endPage - maxVisible + 1);
    }

    for (let i = startPage; i <= endPage; i++) {
      const pageBtn = document.createElement('button');
      pageBtn.type = 'button';
      pageBtn.className = 'px-3 py-2 text-sm font-medium border rounded-lg ' +
        (i === page ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-slate-700 border-slate-300 hover:bg-slate-50');
      pageBtn.textContent = String(i);
      if (i !== page) {
        pageBtn.addEventListener('click', () => onPageChange(i));
      }
      wrapper.appendChild(pageBtn);
    }

    // Botón Siguiente
    const nextBtn = document.createElement('button');
    nextBtn.type = 'button';
    nextBtn.className = 'px-3 py-2 text-sm font-medium border border-slate-300 rounded-lg ' +
      (page >= total_pages ? 'opacity-50 cursor-not-allowed bg-slate-100 text-slate-400' : 'bg-white text-slate-700 hover:bg-slate-50');
    nextBtn.textContent = 'Siguiente »';
    nextBtn.disabled = page >= total_pages;
    if (page < total_pages) {
      nextBtn.addEventListener('click', () => onPageChange(page + 1));
    }
    wrapper.appendChild(nextBtn);

    container.innerHTML = '';
    container.appendChild(wrapper);
  }

  window.SGPLPagination = { render: renderPagination };
})();
