(function () {
  const searchInput = document.getElementById('staffNotificationsSearch');
  const statusFilter = document.getElementById('staffNotificationsStatusFilter');
  const rows = Array.from(document.querySelectorAll('[data-staff-notif-search]'));
  const emptyRow = document.getElementById('staffNotificationsFilterEmpty');

  const applyFilters = () => {
    if (!rows.length) {
      if (emptyRow) {
        emptyRow.classList.add('hidden');
      }
      return;
    }

    const needle = (searchInput?.value || '').trim().toLowerCase();
    const status = (statusFilter?.value || '').trim().toLowerCase();

    let visibleCount = 0;
    rows.forEach((row) => {
      const rowSearch = (row.getAttribute('data-staff-notif-search') || '').toLowerCase();
      const rowStatus = (row.getAttribute('data-staff-notif-status') || '').toLowerCase();

      const matchesSearch = needle === '' || rowSearch.includes(needle);
      const matchesStatus = status === '' || rowStatus === status;
      const visible = matchesSearch && matchesStatus;

      row.classList.toggle('hidden', !visible);
      if (visible) {
        visibleCount += 1;
      }
    });

    if (emptyRow) {
      emptyRow.classList.toggle('hidden', visibleCount > 0);
    }
  };

  searchInput?.addEventListener('input', applyFilters);
  statusFilter?.addEventListener('change', applyFilters);
  applyFilters();

  const markReadForms = Array.from(document.querySelectorAll('.staff-mark-read-form'));
  markReadForms.forEach((form) => {
    form.addEventListener('submit', (event) => {
      const ok = window.confirm('Mark this notification as read?');
      if (!ok) {
        event.preventDefault();
      }
    });
  });
})();
