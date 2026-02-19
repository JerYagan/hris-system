(function () {
  const searchInput = document.getElementById('staffReportEmployeesSearch');
  const statusFilter = document.getElementById('staffReportEmployeesStatusFilter');
  const departmentFilter = document.getElementById('staffReportEmployeesDepartmentFilter');
  const rows = Array.from(document.querySelectorAll('[data-staff-report-row]'));
  const emptyRow = document.getElementById('staffReportEmployeesFilterEmpty');

  const applyEmployeeFilters = () => {
    if (!rows.length) {
      if (emptyRow) {
        emptyRow.classList.add('hidden');
      }
      return;
    }

    const needle = (searchInput?.value || '').trim().toLowerCase();
    const status = (statusFilter?.value || '').trim().toLowerCase();
    const department = (departmentFilter?.value || '').trim().toLowerCase();

    let visibleCount = 0;

    rows.forEach((row) => {
      const rowSearch = (row.getAttribute('data-staff-report-search') || '').toLowerCase();
      const rowStatus = (row.getAttribute('data-staff-report-status') || '').toLowerCase();
      const rowDepartment = (row.getAttribute('data-staff-report-department') || '').toLowerCase();

      const matchesSearch = needle === '' || rowSearch.includes(needle);
      const matchesStatus = status === '' || rowStatus === status;
      const matchesDepartment = department === '' || rowDepartment === department;

      const visible = matchesSearch && matchesStatus && matchesDepartment;
      row.classList.toggle('hidden', !visible);
      if (visible) {
        visibleCount += 1;
      }
    });

    if (emptyRow) {
      emptyRow.classList.toggle('hidden', visibleCount > 0);
    }
  };

  searchInput?.addEventListener('input', applyEmployeeFilters);
  statusFilter?.addEventListener('change', applyEmployeeFilters);
  departmentFilter?.addEventListener('change', applyEmployeeFilters);
  applyEmployeeFilters();

  const coverageSelect = document.getElementById('staffReportCoverageSelect');
  const customRangeWrap = document.getElementById('staffReportCustomDateRange');
  const startInput = document.getElementById('staffReportStartDate');
  const endInput = document.getElementById('staffReportEndDate');

  const syncCoverageUi = () => {
    const custom = (coverageSelect?.value || '').toLowerCase() === 'custom_range';
    customRangeWrap?.classList.toggle('hidden', !custom);
    if (startInput) {
      startInput.required = custom;
    }
    if (endInput) {
      endInput.required = custom;
    }
  };

  coverageSelect?.addEventListener('change', syncCoverageUi);
  syncCoverageUi();

  const exportForm = document.getElementById('staffReportExportForm');
  const exportSubmit = document.getElementById('staffReportExportSubmit');

  exportForm?.addEventListener('submit', (event) => {
    const reportType = (exportForm.querySelector('[name="report_type"]')?.value || 'report').replace(/_/g, ' ');
    const fileFormat = (exportForm.querySelector('[name="file_format"]')?.value || '').toUpperCase();

    const confirmed = window.confirm(`Generate ${reportType} export as ${fileFormat}?`);
    if (!confirmed) {
      event.preventDefault();
      return;
    }

    if (exportSubmit) {
      exportSubmit.disabled = true;
      exportSubmit.textContent = 'Generating...';
    }
  });
})();
