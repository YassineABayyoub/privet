document.addEventListener('DOMContentLoaded', () => {
  const searchInput = document.querySelector('[data-user-search]');
  const tableBody = document.querySelector('[data-user-table-body]');
  if (!searchInput || !tableBody) return;

  const rows = Array.from(tableBody.querySelectorAll('tr[data-user-row]'));
  searchInput.addEventListener('input', () => {
    const term = searchInput.value.trim().toLowerCase();
    rows.forEach((row) => {
      const text = row.textContent.toLowerCase();
      row.hidden = term && !text.includes(term);
    });
  });
});
