document.addEventListener('DOMContentLoaded', () => {
  const params = new URLSearchParams(window.location.search);
  const roleFromSearch = params.get('role');
  if (roleFromSearch === 'judge' || roleFromSearch === 'clerk') {
    document.body.dataset.role = roleFromSearch;
  }

  const navLinks = document.querySelectorAll('.nav-item');
  navLinks.forEach((link) => {
    if (link.href && window.location.href.includes(link.getAttribute('href'))) {
      link.classList.add('active');
    }
  });

  const currentRole = document.body.dataset.role || 'judge';
  const roleLabel = document.querySelector('[data-role-label]');
  if (roleLabel) roleLabel.textContent = currentRole === 'clerk' ? 'كاتبة' : 'عدل';

  const summaryLinks = document.querySelectorAll('[data-role-link]');
  summaryLinks.forEach((link) => {
    const hiddenForRole = link.dataset.roleLink;
    if (hiddenForRole && hiddenForRole !== currentRole) {
      link.style.display = 'none';
    } else if (hiddenForRole) {
      link.style.display = '';
    }
  });

  const roleToggle = document.querySelector('[data-role-toggle]');
  if (roleToggle) {
    roleToggle.textContent = currentRole === 'clerk' ? 'واجهة العدل' : 'واجهة الكاتبة';
    roleToggle.onclick = () => {
      const nextRole = currentRole === 'clerk' ? 'judge' : 'clerk';
      const url = new URL(window.location.href);
      url.searchParams.set('role', nextRole);
      window.history.replaceState({}, '', url);
      document.body.dataset.role = nextRole;
      const currentLabels = document.querySelectorAll('[data-role-label]');
      currentLabels.forEach((el) => {
        el.textContent = nextRole === 'clerk' ? 'كاتبة' : 'عدل';
      });
      roleToggle.textContent = nextRole === 'clerk' ? 'واجهة العدل' : 'واجهة الكاتبة';
      summaryLinks.forEach((link) => {
        const hiddenForRole = link.dataset.roleLink;
        if (hiddenForRole && hiddenForRole !== nextRole) {
          link.style.display = 'none';
        } else if (hiddenForRole) {
          link.style.display = '';
        }
      });
    };
  }
});
