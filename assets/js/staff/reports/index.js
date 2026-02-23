(function () {
  const PAGE_SIZE = 10;

  const debounce = (fn, wait = 250) => {
    let timer = null;

    return (...args) => {
      if (timer) {
        window.clearTimeout(timer);
      }

      timer = window.setTimeout(() => {
        fn(...args);
      }, wait);
    };
  };

  const initReportTable = (config) => {
    const rows = Array.from(document.querySelectorAll(`[data-report-row="${config.key}"]`));
    if (!rows.length) {
      return;
    }

    const searchInput = config.searchInputId ? document.getElementById(config.searchInputId) : null;
    const statusFilter = config.statusFilterId ? document.getElementById(config.statusFilterId) : null;
    const departmentFilter = config.departmentFilterId ? document.getElementById(config.departmentFilterId) : null;
    const startDateInput = config.startDateInputId ? document.getElementById(config.startDateInputId) : null;
    const endDateInput = config.endDateInputId ? document.getElementById(config.endDateInputId) : null;
    const emptyRow = document.getElementById(config.emptyRowId);
    const prevButton = document.getElementById(config.prevButtonId);
    const nextButton = document.getElementById(config.nextButtonId);
    const pageLabel = document.getElementById(config.pageLabelId);
    const paginationInfo = document.getElementById(config.paginationInfoId);

    let currentPage = 1;

    const apply = () => {
      const searchNeedle = (searchInput?.value || '').trim().toLowerCase();
      const statusNeedle = (statusFilter?.value || '').trim().toLowerCase();
      const departmentNeedle = (departmentFilter?.value || '').trim().toLowerCase();
      const startDate = (startDateInput?.value || '').trim();
      const endDate = (endDateInput?.value || '').trim();

      const filteredRows = rows.filter((row) => {
        const rowSearch = (row.getAttribute('data-report-search') || '').toLowerCase();
        const rowStatus = (row.getAttribute('data-report-status') || '').toLowerCase();
        const rowDepartment = (row.getAttribute('data-report-department') || '').toLowerCase();
        const rowDate = (row.getAttribute('data-report-date') || '').trim();

        if (searchNeedle !== '' && !rowSearch.includes(searchNeedle)) {
          return false;
        }
        if (statusNeedle !== '' && rowStatus !== statusNeedle) {
          return false;
        }
        if (departmentNeedle !== '' && rowDepartment !== departmentNeedle) {
          return false;
        }
        if (startDate !== '' && (rowDate === '' || rowDate < startDate)) {
          return false;
        }
        if (endDate !== '' && (rowDate === '' || rowDate > endDate)) {
          return false;
        }

        return true;
      });

      const totalRows = filteredRows.length;
      const totalPages = Math.max(1, Math.ceil(totalRows / PAGE_SIZE));
      if (currentPage > totalPages) {
        currentPage = totalPages;
      }

      const startIndex = totalRows === 0 ? 0 : (currentPage - 1) * PAGE_SIZE;
      const endIndex = totalRows === 0 ? 0 : Math.min(startIndex + PAGE_SIZE, totalRows);
      const visibleSet = new Set(filteredRows.slice(startIndex, endIndex));

      rows.forEach((row) => {
        row.classList.toggle('hidden', !visibleSet.has(row));
      });

      if (emptyRow) {
        emptyRow.classList.toggle('hidden', totalRows !== 0);
      }

      if (paginationInfo) {
        if (totalRows === 0) {
          paginationInfo.textContent = 'Showing 0 to 0 of 0 entries';
        } else {
          paginationInfo.textContent = `Showing ${startIndex + 1} to ${endIndex} of ${totalRows} entries`;
        }
      }

      if (pageLabel) {
        pageLabel.textContent = `Page ${totalRows === 0 ? 1 : currentPage} of ${totalRows === 0 ? 1 : totalPages}`;
      }

      if (prevButton) {
        prevButton.disabled = totalRows === 0 || currentPage <= 1;
      }
      if (nextButton) {
        nextButton.disabled = totalRows === 0 || currentPage >= totalPages;
      }
    };

    const debouncedApply = debounce(() => {
      currentPage = 1;
      apply();
    }, 250);

    searchInput?.addEventListener('input', debouncedApply);
    statusFilter?.addEventListener('change', () => {
      currentPage = 1;
      apply();
    });
    departmentFilter?.addEventListener('change', () => {
      currentPage = 1;
      apply();
    });
    startDateInput?.addEventListener('change', () => {
      currentPage = 1;
      apply();
    });
    endDateInput?.addEventListener('change', () => {
      currentPage = 1;
      apply();
    });

    prevButton?.addEventListener('click', () => {
      if (currentPage > 1) {
        currentPage -= 1;
        apply();
      }
    });

    nextButton?.addEventListener('click', () => {
      currentPage += 1;
      apply();
    });

    apply();
  };

  initReportTable({
    key: 'employees',
    searchInputId: 'staffReportEmployeesSearch',
    statusFilterId: 'staffReportEmployeesStatusFilter',
    departmentFilterId: 'staffReportEmployeesDepartmentFilter',
    startDateInputId: null,
    endDateInputId: null,
    emptyRowId: 'staffReportEmployeesFilterEmpty',
    prevButtonId: 'staffReportEmployeesPrev',
    nextButtonId: 'staffReportEmployeesNext',
    pageLabelId: 'staffReportEmployeesPageLabel',
    paginationInfoId: 'staffReportEmployeesPaginationInfo',
  });

  initReportTable({
    key: 'timekeeping',
    searchInputId: 'staffReportTimekeepingSearch',
    statusFilterId: 'staffReportTimekeepingStatusFilter',
    departmentFilterId: 'staffReportTimekeepingDepartmentFilter',
    startDateInputId: 'staffReportTimekeepingStartDate',
    endDateInputId: 'staffReportTimekeepingEndDate',
    emptyRowId: 'staffReportTimekeepingFilterEmpty',
    prevButtonId: 'staffReportTimekeepingPrev',
    nextButtonId: 'staffReportTimekeepingNext',
    pageLabelId: 'staffReportTimekeepingPageLabel',
    paginationInfoId: 'staffReportTimekeepingPaginationInfo',
  });

  initReportTable({
    key: 'payroll',
    searchInputId: 'staffReportPayrollSearch',
    statusFilterId: 'staffReportPayrollStatusFilter',
    departmentFilterId: 'staffReportPayrollDepartmentFilter',
    startDateInputId: 'staffReportPayrollStartDate',
    endDateInputId: 'staffReportPayrollEndDate',
    emptyRowId: 'staffReportPayrollFilterEmpty',
    prevButtonId: 'staffReportPayrollPrev',
    nextButtonId: 'staffReportPayrollNext',
    pageLabelId: 'staffReportPayrollPageLabel',
    paginationInfoId: 'staffReportPayrollPaginationInfo',
  });

  initReportTable({
    key: 'recruitment',
    searchInputId: 'staffReportRecruitmentSearch',
    statusFilterId: 'staffReportRecruitmentStatusFilter',
    departmentFilterId: 'staffReportRecruitmentDepartmentFilter',
    startDateInputId: 'staffReportRecruitmentStartDate',
    endDateInputId: 'staffReportRecruitmentEndDate',
    emptyRowId: 'staffReportRecruitmentFilterEmpty',
    prevButtonId: 'staffReportRecruitmentPrev',
    nextButtonId: 'staffReportRecruitmentNext',
    pageLabelId: 'staffReportRecruitmentPageLabel',
    paginationInfoId: 'staffReportRecruitmentPaginationInfo',
  });

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
