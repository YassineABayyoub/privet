document.addEventListener('DOMContentLoaded', () => {
  const tableBody = document.querySelector('[data-contract-table-body]');
  if (!tableBody) return;

  const rows = Array.from(tableBody.querySelectorAll('tr[data-contract-row]'));
  const pageNumbers = document.querySelector('.page-numbers');
  const searchInput = document.querySelector('[data-search-input]');
  const categoryFilter = document.querySelector('[data-category-filter]');
  const resetBtn = document.querySelector('[data-reset-filter]');

  const applyFilters = () => {
    const searchTerm = (searchInput ? searchInput.value.trim().toLowerCase() : '');
    const category = categoryFilter ? categoryFilter.value : 'الكل';

    rows.forEach((row) => {
      const rowText = row.textContent.toLowerCase();
      const matchesText = !searchTerm || rowText.includes(searchTerm);
      const matchesCategory = category === 'الكل' || row.dataset.category === category;
      row.hidden = !(matchesText && matchesCategory);
    });
  };

  if (searchInput) searchInput.addEventListener('input', applyFilters);
  if (categoryFilter) categoryFilter.addEventListener('change', applyFilters);
  if (resetBtn) {
    resetBtn.addEventListener('click', () => {
      searchInput.value = '';
      categoryFilter.value = 'الكل';
      applyFilters();
    });
  }

  if (pageNumbers) {
    pageNumbers.querySelectorAll('button').forEach((button) => {
      button.addEventListener('click', () => {
        pageNumbers.querySelectorAll('button').forEach((item) => item.classList.remove('active'));
        button.classList.add('active');
      });
    });
  }
});
