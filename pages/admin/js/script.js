(() => {
  document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('sidebar');
    const toggle = document.getElementById('sidebarToggle');
    const closeButton = document.getElementById('sidebarClose');
    const overlay = document.getElementById('sidebarOverlay');
    const profileToggle = document.getElementById('profileToggle');
    const profileMenu = document.getElementById('profileMenu');
    const topnav = document.getElementById('topnav');
    const categoryToggles = document.querySelectorAll('[data-cat-toggle]');

    let isOpen = false;

    function applySidebarState() {
      if (!sidebar || !overlay) return;

      if (isOpen) {
        sidebar.classList.remove('-translate-x-full');
        sidebar.classList.add('translate-x-0');
        overlay.classList.remove('opacity-0', 'pointer-events-none');
        overlay.classList.add('opacity-100');
        sidebar.setAttribute('aria-hidden', 'false');
      } else {
        sidebar.classList.add('-translate-x-full');
        sidebar.classList.remove('translate-x-0');
        overlay.classList.remove('opacity-100');
        overlay.classList.add('opacity-0', 'pointer-events-none');
        sidebar.setAttribute('aria-hidden', 'true');
      }
    }

    if (toggle) {
      toggle.addEventListener('click', () => {
        isOpen = !isOpen;
        applySidebarState();
      });
    }

    if (closeButton) {
      closeButton.addEventListener('click', () => {
        isOpen = false;
        applySidebarState();
      });
    }

    if (overlay) {
      overlay.addEventListener('click', () => {
        isOpen = false;
        applySidebarState();
      });
    }

    document.addEventListener('click', (event) => {
      if (!isOpen || !sidebar || !toggle || !overlay) return;
      const clickedInsideSidebar = sidebar.contains(event.target);
      const clickedToggle = toggle.contains(event.target);
      const clickedOverlay = overlay.contains(event.target);

      if (!clickedInsideSidebar && !clickedToggle && !clickedOverlay) {
        isOpen = false;
        applySidebarState();
      }
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && isOpen) {
        isOpen = false;
        applySidebarState();
      }
    });

    if (profileToggle && profileMenu) {
      profileToggle.addEventListener('click', (event) => {
        event.stopPropagation();
        profileMenu.classList.toggle('hidden');
      });

      document.addEventListener('click', (event) => {
        if (!profileMenu.classList.contains('hidden') && !profileMenu.contains(event.target) && !profileToggle.contains(event.target)) {
          profileMenu.classList.add('hidden');
        }
      });
    }

    if (topnav) {
      let lastScrollY = window.scrollY;
      const navHeight = topnav.offsetHeight;

      window.addEventListener('scroll', () => {
        const currentScrollY = window.scrollY;
        const delta = currentScrollY - lastScrollY;

        if (Math.abs(delta) < 8) return;

        if (delta > 0 && currentScrollY > navHeight) {
          topnav.classList.add('-translate-y-full');
        } else {
          topnav.classList.remove('-translate-y-full');
        }

        lastScrollY = currentScrollY;
      });
    }

    if (categoryToggles.length) {
      categoryToggles.forEach((toggleButton) => {
        const key = toggleButton.getAttribute('data-cat-toggle');
        const content = document.querySelector(`[data-cat-content="${key}"]`);
        if (!content) return;

        toggleButton.addEventListener('click', () => {
          const expanded = toggleButton.getAttribute('aria-expanded') === 'true';
          toggleButton.setAttribute('aria-expanded', expanded ? 'false' : 'true');
          content.classList.toggle('hidden', expanded);
        });
      });
    }

    if (window.DataTable) {
      const adminTables = document.querySelectorAll('main table:not([data-simple-table="true"])');
      adminTables.forEach((table) => {
        const hasHead = table.querySelector('thead');
        const hasBody = table.querySelector('tbody');
        if (!hasHead || !hasBody || table.dataset.datatableInitialized === 'true') return;

        const dataTableOptions = {
          pageLength: 5,
          lengthMenu: [5, 10, 25, 50],
          responsive: true,
          autoWidth: false
        };

        if (table.id === 'usersTable') {
          dataTableOptions.pageLength = 10;
          dataTableOptions.lengthMenu = [10, 25, 50, 100];
          dataTableOptions.stateSave = true;
          dataTableOptions.order = [[5, 'desc']];
          dataTableOptions.columnDefs = [
            { targets: [6], orderable: false, searchable: false }
          ];

          if (window.DataTable && window.DataTable.Buttons) {
            dataTableOptions.layout = {
              topStart: {
                buttons: ['copyHtml5', 'csvHtml5', 'excelHtml5']
              }
            };
          }
        }

        const dataTable = new DataTable(table, dataTableOptions);

        if (table.id === 'usersTable') {
          const statusFilter = document.getElementById('usersStatusFilter');
          const usersSearchInput = document.getElementById('usersSearchInput');

          usersSearchInput?.addEventListener('input', () => {
            dataTable.search((usersSearchInput.value || '').trim()).draw();
          });

          statusFilter?.addEventListener('change', () => {
            const selected = (statusFilter.value || '').trim();
            if (!selected) {
              dataTable.column(4).search('').draw();
              return;
            }

            const escaped = selected.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            const exactSearch = `^${escaped}$`;
            try {
              dataTable.column(4).search(exactSearch, { regex: true, smart: false }).draw();
            } catch (_error) {
              dataTable.column(4).search(exactSearch, true, false).draw();
            }
          });
        }

        if (table.id === 'recruitmentApplicantsTable') {
          const recruitmentSearchInput = document.getElementById('recruitmentApplicantsSearch');
          const recruitmentStatusFilter = document.getElementById('recruitmentApplicantsStatusFilter');

          recruitmentSearchInput?.addEventListener('input', () => {
            dataTable.search((recruitmentSearchInput.value || '').trim()).draw();
          });

          recruitmentStatusFilter?.addEventListener('change', () => {
            const selected = (recruitmentStatusFilter.value || '').trim();
            if (!selected) {
              dataTable.column(3).search('').draw();
              return;
            }

            const escaped = selected.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            const exactSearch = `^${escaped}$`;
            try {
              dataTable.column(3).search(exactSearch, { regex: true, smart: false }).draw();
            } catch (_error) {
              dataTable.column(3).search(exactSearch, true, false).draw();
            }
          });
        }

        if (table.id === 'applicantTrackingTable') {
          const trackingSearchInput = document.getElementById('applicantTrackingSearch');
          const trackingStatusFilter = document.getElementById('applicantTrackingStatusFilter');

          trackingSearchInput?.addEventListener('input', () => {
            dataTable.search((trackingSearchInput.value || '').trim()).draw();
          });

          trackingStatusFilter?.addEventListener('change', () => {
            const selected = (trackingStatusFilter.value || '').trim();
            if (!selected) {
              dataTable.column(2).search('').draw();
              return;
            }

            const escaped = selected.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            const exactSearch = `^${escaped}$`;
            try {
              dataTable.column(2).search(exactSearch, { regex: true, smart: false }).draw();
            } catch (_error) {
              dataTable.column(2).search(exactSearch, true, false).draw();
            }
          });
        }

        if (table.id === 'documentManagementTable') {
          const documentSearchInput = document.getElementById('documentManagementSearch');
          const documentStatusFilter = document.getElementById('documentManagementStatusFilter');

          documentSearchInput?.addEventListener('input', () => {
            dataTable.search((documentSearchInput.value || '').trim()).draw();
          });

          documentStatusFilter?.addEventListener('change', () => {
            const selected = (documentStatusFilter.value || '').trim();
            if (!selected) {
              dataTable.column(3).search('').draw();
              return;
            }

            const escaped = selected.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            const exactSearch = `^${escaped}$`;
            try {
              dataTable.column(3).search(exactSearch, { regex: true, smart: false }).draw();
            } catch (_error) {
              dataTable.column(3).search(exactSearch, true, false).draw();
            }
          });
        }

        if (table.id === 'timeAdjustmentsTable') {
          const adjustmentsSearchInput = document.getElementById('adjustmentsSearch');
          const adjustmentsStatusFilter = document.getElementById('adjustmentsStatusFilter');

          adjustmentsSearchInput?.addEventListener('input', () => {
            dataTable.search((adjustmentsSearchInput.value || '').trim()).draw();
          });

          adjustmentsStatusFilter?.addEventListener('change', () => {
            const selected = (adjustmentsStatusFilter.value || '').trim();
            if (!selected) {
              dataTable.column(4).search('').draw();
              return;
            }

            const escaped = selected.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            const exactSearch = `^${escaped}$`;
            try {
              dataTable.column(4).search(exactSearch, { regex: true, smart: false }).draw();
            } catch (_error) {
              dataTable.column(4).search(exactSearch, true, false).draw();
            }
          });
        }

        if (table.id === 'leaveRequestsTable') {
          const leaveSearchInput = document.getElementById('leaveRequestsSearch');
          const leaveStatusFilter = document.getElementById('leaveRequestsStatusFilter');

          leaveSearchInput?.addEventListener('input', () => {
            dataTable.search((leaveSearchInput.value || '').trim()).draw();
          });

          leaveStatusFilter?.addEventListener('change', () => {
            const selected = (leaveStatusFilter.value || '').trim();
            if (!selected) {
              dataTable.column(4).search('').draw();
              return;
            }

            const escaped = selected.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            const exactSearch = `^${escaped}$`;
            try {
              dataTable.column(4).search(exactSearch, { regex: true, smart: false }).draw();
            } catch (_error) {
              dataTable.column(4).search(exactSearch, true, false).draw();
            }
          });
        }

        if (table.id === 'payrollBatchesTable') {
          const payrollBatchesSearchInput = document.getElementById('payrollBatchesSearch');
          const payrollBatchesStatusFilter = document.getElementById('payrollBatchesStatusFilter');

          payrollBatchesSearchInput?.addEventListener('input', () => {
            dataTable.search((payrollBatchesSearchInput.value || '').trim()).draw();
          });

          payrollBatchesStatusFilter?.addEventListener('change', () => {
            const selected = (payrollBatchesStatusFilter.value || '').trim();
            if (!selected) {
              dataTable.column(4).search('').draw();
              return;
            }

            const escaped = selected.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            const exactSearch = `^${escaped}$`;
            try {
              dataTable.column(4).search(exactSearch, { regex: true, smart: false }).draw();
            } catch (_error) {
              dataTable.column(4).search(exactSearch, true, false).draw();
            }
          });
        }

        if (table.id === 'payrollPayslipsTable') {
          const payrollPayslipsSearchInput = document.getElementById('payrollPayslipsSearch');
          const payrollPayslipsStatusFilter = document.getElementById('payrollPayslipsStatusFilter');

          payrollPayslipsSearchInput?.addEventListener('input', () => {
            dataTable.search((payrollPayslipsSearchInput.value || '').trim()).draw();
          });

          payrollPayslipsStatusFilter?.addEventListener('change', () => {
            const selected = (payrollPayslipsStatusFilter.value || '').trim();
            if (!selected) {
              dataTable.column(5).search('').draw();
              return;
            }

            const escaped = selected.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            const exactSearch = `^${escaped}$`;
            try {
              dataTable.column(5).search(exactSearch, { regex: true, smart: false }).draw();
            } catch (_error) {
              dataTable.column(5).search(exactSearch, true, false).draw();
            }
          });
        }

        if (table.id === 'adminNotificationsTable') {
          const notificationsSearchInput = document.getElementById('adminNotificationsSearch');
          const notificationsStatusFilter = document.getElementById('adminNotificationsStatusFilter');

          notificationsSearchInput?.addEventListener('input', () => {
            dataTable.search((notificationsSearchInput.value || '').trim()).draw();
          });

          notificationsStatusFilter?.addEventListener('change', () => {
            const selected = (notificationsStatusFilter.value || '').trim();
            if (!selected) {
              dataTable.column(4).search('').draw();
              return;
            }

            const escaped = selected.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            const exactSearch = `^${escaped}$`;
            try {
              dataTable.column(4).search(exactSearch, { regex: true, smart: false }).draw();
            } catch (_error) {
              dataTable.column(4).search(exactSearch, true, false).draw();
            }
          });
        }

        if (table.id === 'personalInfoEmployeesTable') {
          const personalInfoSearchInput = document.getElementById('personalInfoRecordsSearchInput');
          const personalInfoDepartmentFilter = document.getElementById('personalInfoRecordsDepartmentFilter');
          const personalInfoStatusFilter = document.getElementById('personalInfoRecordsStatusFilter');

          personalInfoSearchInput?.addEventListener('input', () => {
            dataTable.search((personalInfoSearchInput.value || '').trim()).draw();
          });

          personalInfoDepartmentFilter?.addEventListener('change', () => {
            const selected = (personalInfoDepartmentFilter.value || '').trim();
            if (!selected) {
              dataTable.column(2).search('').draw();
              return;
            }

            const escaped = selected.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            const exactSearch = `^${escaped}$`;
            try {
              dataTable.column(2).search(exactSearch, { regex: true, smart: false }).draw();
            } catch (_error) {
              dataTable.column(2).search(exactSearch, true, false).draw();
            }
          });

          personalInfoStatusFilter?.addEventListener('change', () => {
            const selected = (personalInfoStatusFilter.value || '').trim();
            if (!selected) {
              dataTable.column(4).search('').draw();
              return;
            }

            const escaped = selected.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            const exactSearch = `^${escaped}$`;
            try {
              dataTable.column(4).search(exactSearch, { regex: true, smart: false }).draw();
            } catch (_error) {
              dataTable.column(4).search(exactSearch, true, false).draw();
            }
          });
        }

        table.dataset.datatableInitialized = 'true';
      });
    }

    const usersTable = document.getElementById('usersTable');
    const usersSearchInput = document.getElementById('usersSearchInput');
    const usersStatusFilter = document.getElementById('usersStatusFilter');

    if (usersTable && usersSearchInput && usersStatusFilter && usersTable.dataset.datatableInitialized !== 'true') {
      const userRows = Array.from(usersTable.querySelectorAll('tbody tr')).filter((row) => !row.querySelector('td[colspan]'));

      const applyPlainUsersFilter = () => {
        const searchText = (usersSearchInput.value || '').trim().toLowerCase();
        const selectedStatus = (usersStatusFilter.value || '').trim().toLowerCase();

        userRows.forEach((row) => {
          const rowSearch = (row.getAttribute('data-user-search') || '').toLowerCase();
          const rowStatus = (row.getAttribute('data-user-status') || '').toLowerCase();
          const matchesSearch = !searchText || rowSearch.includes(searchText);
          const matchesStatus = !selectedStatus || rowStatus === selectedStatus;

          row.classList.toggle('hidden', !(matchesSearch && matchesStatus));
        });
      };

      usersSearchInput.addEventListener('input', applyPlainUsersFilter);
      usersStatusFilter.addEventListener('change', applyPlainUsersFilter);
    }

    const recruitmentApplicantsTable = document.getElementById('recruitmentApplicantsTable');
    const recruitmentApplicantsSearch = document.getElementById('recruitmentApplicantsSearch');
    const recruitmentApplicantsStatus = document.getElementById('recruitmentApplicantsStatusFilter');
    const recruitmentStatusChips = document.querySelectorAll('[data-app-status-chip]');

    const setActiveRecruitmentChip = () => {
      if (!recruitmentApplicantsStatus || !recruitmentStatusChips.length) return;
      const selected = (recruitmentApplicantsStatus.value || '').trim();

      recruitmentStatusChips.forEach((chip) => {
        const chipValue = (chip.getAttribute('data-app-status-chip') || '').trim();
        const isActive = chipValue === selected;

        chip.classList.toggle('bg-slate-900', isActive && chipValue === '');
        chip.classList.toggle('text-white', isActive && chipValue === '');

        chip.classList.toggle('bg-emerald-100', isActive && chipValue === 'Approved');
        chip.classList.toggle('bg-amber-100', isActive && chipValue === 'Pending');
        chip.classList.toggle('bg-blue-100', isActive && chipValue === 'For Interview');
        chip.classList.toggle('ring-2', isActive);
        chip.classList.toggle('ring-offset-1', isActive);
      });
    };

    recruitmentStatusChips.forEach((chip) => {
      chip.addEventListener('click', () => {
        if (!recruitmentApplicantsStatus) return;
        recruitmentApplicantsStatus.value = chip.getAttribute('data-app-status-chip') || '';
        recruitmentApplicantsStatus.dispatchEvent(new Event('change'));
        setActiveRecruitmentChip();
      });
    });

    recruitmentApplicantsStatus?.addEventListener('change', setActiveRecruitmentChip);
    setActiveRecruitmentChip();

    if (
      recruitmentApplicantsTable
      && recruitmentApplicantsSearch
      && recruitmentApplicantsStatus
      && recruitmentApplicantsTable.dataset.datatableInitialized !== 'true'
    ) {
      const applicantRows = Array.from(recruitmentApplicantsTable.querySelectorAll('tbody tr')).filter((row) => !row.querySelector('td[colspan]'));

      const applyPlainRecruitmentFilter = () => {
        const searchText = (recruitmentApplicantsSearch.value || '').trim().toLowerCase();
        const selectedStatus = (recruitmentApplicantsStatus.value || '').trim().toLowerCase();

        applicantRows.forEach((row) => {
          const rowSearch = (row.getAttribute('data-app-search') || '').toLowerCase();
          const rowStatus = (row.getAttribute('data-app-status') || '').toLowerCase();
          const matchesSearch = !searchText || rowSearch.includes(searchText);
          const matchesStatus = !selectedStatus || rowStatus === selectedStatus;
          row.classList.toggle('hidden', !(matchesSearch && matchesStatus));
        });
      };

      recruitmentApplicantsSearch.addEventListener('input', applyPlainRecruitmentFilter);
      recruitmentApplicantsStatus.addEventListener('change', applyPlainRecruitmentFilter);
    }

    const trackingTable = document.getElementById('applicantTrackingTable');
    const trackingSearchInput = document.getElementById('applicantTrackingSearch');
    const trackingStatusFilter = document.getElementById('applicantTrackingStatusFilter');

    const adminApplicantsTable = document.getElementById('adminApplicantsTable');
    const adminApplicantsSearch = document.getElementById('adminApplicantsSearch');
    const adminApplicantsStatus = document.getElementById('adminApplicantsStatusFilter');

    if (
      adminApplicantsTable
      && adminApplicantsSearch
      && adminApplicantsStatus
      && adminApplicantsTable.dataset.datatableInitialized !== 'true'
    ) {
      const adminApplicantRows = Array.from(adminApplicantsTable.querySelectorAll('tbody tr')).filter((row) => !row.querySelector('td[colspan]'));

      const applyPlainApplicantsFilter = () => {
        const searchText = (adminApplicantsSearch.value || '').trim().toLowerCase();
        const selectedStatus = (adminApplicantsStatus.value || '').trim().toLowerCase();

        adminApplicantRows.forEach((row) => {
          const rowSearch = (row.getAttribute('data-applicants-search') || '').toLowerCase();
          const rowStatus = (row.getAttribute('data-applicants-status') || '').toLowerCase();
          const matchesSearch = !searchText || rowSearch.includes(searchText);
          const matchesStatus = !selectedStatus || rowStatus === selectedStatus;
          row.classList.toggle('hidden', !(matchesSearch && matchesStatus));
        });
      };

      adminApplicantsSearch.addEventListener('input', applyPlainApplicantsFilter);
      adminApplicantsStatus.addEventListener('change', applyPlainApplicantsFilter);
    }

    if (
      trackingTable
      && trackingSearchInput
      && trackingStatusFilter
      && trackingTable.dataset.datatableInitialized !== 'true'
    ) {
      const trackingRows = Array.from(trackingTable.querySelectorAll('tbody tr')).filter((row) => !row.querySelector('td[colspan]'));

      const applyPlainTrackingFilter = () => {
        const searchText = (trackingSearchInput.value || '').trim().toLowerCase();
        const selectedStatus = (trackingStatusFilter.value || '').trim().toLowerCase();

        trackingRows.forEach((row) => {
          const rowSearch = (row.getAttribute('data-track-search') || '').toLowerCase();
          const rowStatus = (row.getAttribute('data-track-status') || '').toLowerCase();
          const matchesSearch = !searchText || rowSearch.includes(searchText);
          const matchesStatus = !selectedStatus || rowStatus === selectedStatus;
          row.classList.toggle('hidden', !(matchesSearch && matchesStatus));
        });
      };

      trackingSearchInput.addEventListener('input', applyPlainTrackingFilter);
      trackingStatusFilter.addEventListener('change', applyPlainTrackingFilter);
    }

    const documentTable = document.getElementById('documentManagementTable');
    const documentSearchInput = document.getElementById('documentManagementSearch');
    const documentStatusFilter = document.getElementById('documentManagementStatusFilter');

    if (
      documentTable
      && documentSearchInput
      && documentStatusFilter
      && documentTable.dataset.datatableInitialized !== 'true'
    ) {
      const documentRows = Array.from(documentTable.querySelectorAll('tbody tr')).filter((row) => !row.querySelector('td[colspan]'));

      const applyPlainDocumentFilter = () => {
        const searchText = (documentSearchInput.value || '').trim().toLowerCase();
        const selectedStatus = (documentStatusFilter.value || '').trim().toLowerCase();

        documentRows.forEach((row) => {
          const rowSearch = (row.getAttribute('data-doc-search') || '').toLowerCase();
          const rowStatus = (row.getAttribute('data-doc-status') || '').toLowerCase();
          const matchesSearch = !searchText || rowSearch.includes(searchText);
          const matchesStatus = !selectedStatus || rowStatus === selectedStatus;
          row.classList.toggle('hidden', !(matchesSearch && matchesStatus));
        });
      };

      documentSearchInput.addEventListener('input', applyPlainDocumentFilter);
      documentStatusFilter.addEventListener('change', applyPlainDocumentFilter);
    }

    const adjustmentsTable = document.getElementById('timeAdjustmentsTable');
    const adjustmentsSearch = document.getElementById('adjustmentsSearch');
    const adjustmentsStatus = document.getElementById('adjustmentsStatusFilter');

    if (
      adjustmentsTable
      && adjustmentsSearch
      && adjustmentsStatus
      && adjustmentsTable.dataset.datatableInitialized !== 'true'
    ) {
      const adjustmentRows = Array.from(adjustmentsTable.querySelectorAll('tbody tr')).filter((row) => !row.querySelector('td[colspan]'));

      const applyPlainAdjustmentFilter = () => {
        const searchText = (adjustmentsSearch.value || '').trim().toLowerCase();
        const selectedStatus = (adjustmentsStatus.value || '').trim().toLowerCase();

        adjustmentRows.forEach((row) => {
          const rowSearch = (row.getAttribute('data-adjust-search') || '').toLowerCase();
          const rowStatus = (row.getAttribute('data-adjust-status') || '').toLowerCase();
          const matchesSearch = !searchText || rowSearch.includes(searchText);
          const matchesStatus = !selectedStatus || rowStatus === selectedStatus;
          row.classList.toggle('hidden', !(matchesSearch && matchesStatus));
        });
      };

      adjustmentsSearch.addEventListener('input', applyPlainAdjustmentFilter);
      adjustmentsStatus.addEventListener('change', applyPlainAdjustmentFilter);
    }

    const leaveRequestsTable = document.getElementById('leaveRequestsTable');
    const leaveRequestsSearch = document.getElementById('leaveRequestsSearch');
    const leaveRequestsStatus = document.getElementById('leaveRequestsStatusFilter');

    if (
      leaveRequestsTable
      && leaveRequestsSearch
      && leaveRequestsStatus
      && leaveRequestsTable.dataset.datatableInitialized !== 'true'
    ) {
      const leaveRows = Array.from(leaveRequestsTable.querySelectorAll('tbody tr')).filter((row) => !row.querySelector('td[colspan]'));

      const applyPlainLeaveFilter = () => {
        const searchText = (leaveRequestsSearch.value || '').trim().toLowerCase();
        const selectedStatus = (leaveRequestsStatus.value || '').trim().toLowerCase();

        leaveRows.forEach((row) => {
          const rowSearch = (row.getAttribute('data-leave-search') || '').toLowerCase();
          const rowStatus = (row.getAttribute('data-leave-status') || '').toLowerCase();
          const matchesSearch = !searchText || rowSearch.includes(searchText);
          const matchesStatus = !selectedStatus || rowStatus === selectedStatus;
          row.classList.toggle('hidden', !(matchesSearch && matchesStatus));
        });
      };

      leaveRequestsSearch.addEventListener('input', applyPlainLeaveFilter);
      leaveRequestsStatus.addEventListener('change', applyPlainLeaveFilter);
    }

    const payrollBatchesTable = document.getElementById('payrollBatchesTable');
    const payrollBatchesSearch = document.getElementById('payrollBatchesSearch');
    const payrollBatchesStatus = document.getElementById('payrollBatchesStatusFilter');

    if (
      payrollBatchesTable
      && payrollBatchesSearch
      && payrollBatchesStatus
      && payrollBatchesTable.dataset.datatableInitialized !== 'true'
    ) {
      const payrollBatchRows = Array.from(payrollBatchesTable.querySelectorAll('tbody tr')).filter((row) => !row.querySelector('td[colspan]'));

      const applyPlainPayrollBatchFilter = () => {
        const searchText = (payrollBatchesSearch.value || '').trim().toLowerCase();
        const selectedStatus = (payrollBatchesStatus.value || '').trim().toLowerCase();

        payrollBatchRows.forEach((row) => {
          const rowSearch = (row.getAttribute('data-payroll-batch-search') || '').toLowerCase();
          const rowStatus = (row.getAttribute('data-payroll-batch-status') || '').toLowerCase();
          const matchesSearch = !searchText || rowSearch.includes(searchText);
          const matchesStatus = !selectedStatus || rowStatus === selectedStatus;
          row.classList.toggle('hidden', !(matchesSearch && matchesStatus));
        });
      };

      payrollBatchesSearch.addEventListener('input', applyPlainPayrollBatchFilter);
      payrollBatchesStatus.addEventListener('change', applyPlainPayrollBatchFilter);
    }

    const payrollPayslipsTable = document.getElementById('payrollPayslipsTable');
    const payrollPayslipsSearch = document.getElementById('payrollPayslipsSearch');
    const payrollPayslipsStatus = document.getElementById('payrollPayslipsStatusFilter');

    if (
      payrollPayslipsTable
      && payrollPayslipsSearch
      && payrollPayslipsStatus
      && payrollPayslipsTable.dataset.datatableInitialized !== 'true'
    ) {
      const payrollPayslipRows = Array.from(payrollPayslipsTable.querySelectorAll('tbody tr')).filter((row) => !row.querySelector('td[colspan]'));

      const applyPlainPayrollPayslipFilter = () => {
        const searchText = (payrollPayslipsSearch.value || '').trim().toLowerCase();
        const selectedStatus = (payrollPayslipsStatus.value || '').trim().toLowerCase();

        payrollPayslipRows.forEach((row) => {
          const rowSearch = (row.getAttribute('data-payroll-payslip-search') || '').toLowerCase();
          const rowStatus = (row.getAttribute('data-payroll-payslip-status') || '').toLowerCase();
          const matchesSearch = !searchText || rowSearch.includes(searchText);
          const matchesStatus = !selectedStatus || rowStatus === selectedStatus;
          row.classList.toggle('hidden', !(matchesSearch && matchesStatus));
        });
      };

      payrollPayslipsSearch.addEventListener('input', applyPlainPayrollPayslipFilter);
      payrollPayslipsStatus.addEventListener('change', applyPlainPayrollPayslipFilter);
    }

    const adminNotificationsTable = document.getElementById('adminNotificationsTable');
    const adminNotificationsSearch = document.getElementById('adminNotificationsSearch');
    const adminNotificationsStatus = document.getElementById('adminNotificationsStatusFilter');

    if (
      adminNotificationsTable
      && adminNotificationsSearch
      && adminNotificationsStatus
      && adminNotificationsTable.dataset.datatableInitialized !== 'true'
    ) {
      const notificationRows = Array.from(adminNotificationsTable.querySelectorAll('tbody tr')).filter((row) => !row.querySelector('td[colspan]'));

      const applyPlainNotificationsFilter = () => {
        const searchText = (adminNotificationsSearch.value || '').trim().toLowerCase();
        const selectedStatus = (adminNotificationsStatus.value || '').trim().toLowerCase();

        notificationRows.forEach((row) => {
          const rowSearch = (row.getAttribute('data-notif-search') || '').toLowerCase();
          const rowStatus = (row.getAttribute('data-notif-status') || '').toLowerCase();
          const matchesSearch = !searchText || rowSearch.includes(searchText);
          const matchesStatus = !selectedStatus || rowStatus === selectedStatus;
          row.classList.toggle('hidden', !(matchesSearch && matchesStatus));
        });
      };

      adminNotificationsSearch.addEventListener('input', applyPlainNotificationsFilter);
      adminNotificationsStatus.addEventListener('change', applyPlainNotificationsFilter);
    }

    const personalInfoTable = document.getElementById('personalInfoEmployeesTable');
    const personalInfoSearch = document.getElementById('personalInfoRecordsSearchInput');
    const personalInfoDepartment = document.getElementById('personalInfoRecordsDepartmentFilter');
    const personalInfoStatus = document.getElementById('personalInfoRecordsStatusFilter');

    if (
      personalInfoTable
      && personalInfoSearch
      && personalInfoDepartment
      && personalInfoStatus
      && personalInfoTable.dataset.datatableInitialized !== 'true'
    ) {
      const profileRows = Array.from(personalInfoTable.querySelectorAll('tbody tr')).filter((row) => !row.querySelector('td[colspan]'));

      const applyPlainPersonalInfoFilter = () => {
        const searchText = (personalInfoSearch.value || '').trim().toLowerCase();
        const selectedDepartment = (personalInfoDepartment.value || '').trim().toLowerCase();
        const selectedStatus = (personalInfoStatus.value || '').trim().toLowerCase();

        profileRows.forEach((row) => {
          const rowSearch = (row.getAttribute('data-profile-search') || '').toLowerCase();
          const rowDepartment = (row.getAttribute('data-profile-department') || '').toLowerCase();
          const rowStatus = (row.getAttribute('data-profile-status') || '').toLowerCase();

          const matchesSearch = !searchText || rowSearch.includes(searchText);
          const matchesDepartment = !selectedDepartment || rowDepartment === selectedDepartment;
          const matchesStatus = !selectedStatus || rowStatus === selectedStatus;

          row.classList.toggle('hidden', !(matchesSearch && matchesDepartment && matchesStatus));
        });
      };

      personalInfoSearch.addEventListener('input', applyPlainPersonalInfoFilter);
      personalInfoDepartment.addEventListener('change', applyPlainPersonalInfoFilter);
      personalInfoStatus.addEventListener('change', applyPlainPersonalInfoFilter);
    }

    const scheduleApplicationId = document.getElementById('scheduleApplicationId');
    const scheduleApplicantName = document.getElementById('scheduleApplicantName');
    const statusApplicationId = document.getElementById('statusApplicationId');
    const statusApplicantName = document.getElementById('statusApplicantName');
    const statusCurrentStatus = document.getElementById('statusCurrentStatus');
    const reviewDocumentId = document.getElementById('reviewDocumentId');
    const reviewDocumentTitle = document.getElementById('reviewDocumentTitle');
    const reviewCurrentStatus = document.getElementById('reviewCurrentStatus');
    const archiveDocumentId = document.getElementById('archiveDocumentId');
    const archiveDocumentTitle = document.getElementById('archiveDocumentTitle');
    const archiveCurrentStatus = document.getElementById('archiveCurrentStatus');
    const adjustmentRequestId = document.getElementById('adjustmentRequestId');
    const adjustmentEmployeeName = document.getElementById('adjustmentEmployeeName');
    const adjustmentCurrentStatus = document.getElementById('adjustmentCurrentStatus');
    const adjustmentRequestedWindow = document.getElementById('adjustmentRequestedWindow');
    const leaveRequestId = document.getElementById('leaveRequestId');
    const leaveEmployeeName = document.getElementById('leaveEmployeeName');
    const leaveCurrentStatus = document.getElementById('leaveCurrentStatus');
    const leaveDateRange = document.getElementById('leaveDateRange');
    const payrollRunId = document.getElementById('payrollRunId');
    const payrollBatchPeriod = document.getElementById('payrollBatchPeriod');
    const payrollBatchStatus = document.getElementById('payrollBatchStatus');
    const payrollBatchEmployees = document.getElementById('payrollBatchEmployees');
    const payrollBatchNet = document.getElementById('payrollBatchNet');
    const payrollSalarySetupForm = document.getElementById('payrollSalarySetupForm');
    const payrollEmployeeSearch = document.getElementById('payrollEmployeeSearch');
    const payrollEmployeeOptions = document.getElementById('payrollEmployeeOptions');
    const payrollSelectedPersonId = document.getElementById('payrollSelectedPersonId');
    const payrollPayFrequency = document.getElementById('payrollPayFrequency');
    const payrollBasePay = document.getElementById('payrollBasePay');
    const profileEmployeeName = document.getElementById('profileEmployeeName');
    const profileEmployeeList = document.getElementById('profileEmployeeList');
    const profilePersonId = document.getElementById('profilePersonId');
    const profileAction = document.getElementById('profileAction');
    const decisionApplicationId = document.getElementById('decisionApplicationId');

    document.querySelectorAll('[data-applicant-open]').forEach((button) => {
      button.addEventListener('click', () => {
        const applicationId = button.getAttribute('data-application-id') || '';
        if (decisionApplicationId) {
          decisionApplicationId.value = applicationId;
          decisionApplicationId.dispatchEvent(new Event('change'));
        }

        const decisionForm = document.getElementById('applicantDecisionForm');
        decisionForm?.scrollIntoView({ behavior: 'smooth', block: 'start' });
      });
    });

    document.querySelectorAll('[data-track-schedule]').forEach((button) => {
      button.addEventListener('click', () => {
        const applicationId = button.getAttribute('data-application-id') || '';
        const applicantName = button.getAttribute('data-applicant-name') || '';

        if (scheduleApplicationId) scheduleApplicationId.value = applicationId;
        if (scheduleApplicantName) scheduleApplicantName.value = applicantName;
        openModal('scheduleInterviewModal');
      });
    });

    document.querySelectorAll('[data-track-status-update]').forEach((button) => {
      button.addEventListener('click', () => {
        const applicationId = button.getAttribute('data-application-id') || '';
        const applicantName = button.getAttribute('data-applicant-name') || '';
        const currentStatus = button.getAttribute('data-current-status') || '';

        if (statusApplicationId) statusApplicationId.value = applicationId;
        if (statusApplicantName) statusApplicantName.value = applicantName;
        if (statusCurrentStatus) {
          const normalized = currentStatus ? currentStatus.charAt(0).toUpperCase() + currentStatus.slice(1) : '';
          statusCurrentStatus.value = normalized;
        }
        openModal('updateStatusModal');
      });
    });

    document.querySelectorAll('[data-doc-review]').forEach((button) => {
      button.addEventListener('click', () => {
        const documentId = button.getAttribute('data-document-id') || '';
        const documentTitle = button.getAttribute('data-document-title') || '';
        const currentStatus = button.getAttribute('data-current-status') || '';

        if (reviewDocumentId) reviewDocumentId.value = documentId;
        if (reviewDocumentTitle) reviewDocumentTitle.value = documentTitle;
        if (reviewCurrentStatus) reviewCurrentStatus.value = currentStatus;
        openModal('reviewDocumentModal');
      });
    });

    document.querySelectorAll('[data-doc-archive]').forEach((button) => {
      button.addEventListener('click', () => {
        const documentId = button.getAttribute('data-document-id') || '';
        const documentTitle = button.getAttribute('data-document-title') || '';
        const currentStatus = button.getAttribute('data-current-status') || '';

        if (archiveDocumentId) archiveDocumentId.value = documentId;
        if (archiveDocumentTitle) archiveDocumentTitle.value = documentTitle;
        if (archiveCurrentStatus) archiveCurrentStatus.value = currentStatus;
        openModal('archiveDocumentModal');
      });
    });

    document.querySelectorAll('[data-adjust-review]').forEach((button) => {
      button.addEventListener('click', () => {
        const requestId = button.getAttribute('data-request-id') || '';
        const employeeName = button.getAttribute('data-employee-name') || '';
        const currentStatus = button.getAttribute('data-current-status') || '';
        const requestedWindow = button.getAttribute('data-requested-window') || '';

        if (adjustmentRequestId) adjustmentRequestId.value = requestId;
        if (adjustmentEmployeeName) adjustmentEmployeeName.value = employeeName;
        if (adjustmentCurrentStatus) adjustmentCurrentStatus.value = currentStatus;
        if (adjustmentRequestedWindow) adjustmentRequestedWindow.value = requestedWindow;
        openModal('reviewAdjustmentModal');
      });
    });

    document.querySelectorAll('[data-leave-review]').forEach((button) => {
      button.addEventListener('click', () => {
        const requestId = button.getAttribute('data-leave-request-id') || '';
        const employeeName = button.getAttribute('data-employee-name') || '';
        const currentStatus = button.getAttribute('data-current-status') || '';
        const dateRange = button.getAttribute('data-date-range') || '';

        if (leaveRequestId) leaveRequestId.value = requestId;
        if (leaveEmployeeName) leaveEmployeeName.value = employeeName;
        if (leaveCurrentStatus) leaveCurrentStatus.value = currentStatus;
        if (leaveDateRange) leaveDateRange.value = dateRange;
        openModal('reviewLeaveModal');
      });
    });

    document.querySelectorAll('[data-payroll-review]').forEach((button) => {
      button.addEventListener('click', () => {
        const runId = button.getAttribute('data-run-id') || '';
        const periodLabel = button.getAttribute('data-period-label') || '';
        const currentStatus = button.getAttribute('data-current-status') || '';
        const employeeCount = button.getAttribute('data-employee-count') || '';
        const totalNet = button.getAttribute('data-total-net') || '';

        if (payrollRunId) payrollRunId.value = runId;
        if (payrollBatchPeriod) payrollBatchPeriod.value = periodLabel;
        if (payrollBatchStatus) payrollBatchStatus.value = currentStatus;
        if (payrollBatchEmployees) payrollBatchEmployees.value = employeeCount;
        if (payrollBatchNet) payrollBatchNet.value = totalNet;
        openModal('reviewPayrollBatchModal');
      });
    });

    document.querySelectorAll('[data-person-profile-open]').forEach((button) => {
      button.addEventListener('click', () => {
        const personId = button.getAttribute('data-person-id') || '';
        const employeeName = button.getAttribute('data-employee-name') || '';

        if (profileEmployeeName) profileEmployeeName.value = employeeName;
        if (profilePersonId) profilePersonId.value = personId;
        if (profileAction) profileAction.value = 'edit';
        openModal('personalInfoProfileModal');
      });
    });

    if (payrollEmployeeSearch && payrollEmployeeOptions && payrollSelectedPersonId) {
      const payrollOptionLookup = {};
      const payrollOptionLabels = [];
      Array.from(payrollEmployeeOptions.querySelectorAll('option')).forEach((option) => {
        const label = (option.getAttribute('value') || '').trim();
        if (!label) return;
        payrollOptionLabels.push(label);
        payrollOptionLookup[label] = {
          personId: option.getAttribute('data-person-id') || '',
          monthlyRate: option.getAttribute('data-monthly-rate') || '',
          payFrequency: option.getAttribute('data-pay-frequency') || ''
        };
      });

      const applyPayrollEmployeeSelection = () => {
        const selectedLabel = (payrollEmployeeSearch.value || '').trim();
        const payload = payrollOptionLookup[selectedLabel] || null;

        if (!payload || !payload.personId) {
          payrollSelectedPersonId.value = '';
          return;
        }

        payrollSelectedPersonId.value = payload.personId;

        if (payrollPayFrequency && payload.payFrequency) {
          payrollPayFrequency.value = payload.payFrequency;
        }

        if (payrollBasePay && payload.monthlyRate && Number(payload.monthlyRate) > 0) {
          payrollBasePay.value = Number(payload.monthlyRate).toFixed(2);
        }
      };

      payrollEmployeeSearch.addEventListener('change', applyPayrollEmployeeSelection);
      payrollEmployeeSearch.addEventListener('blur', applyPayrollEmployeeSelection);
      payrollEmployeeSearch.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter') return;

        const typed = (payrollEmployeeSearch.value || '').trim().toLowerCase();
        if (!typed) return;

        const hasExact = !!payrollOptionLookup[payrollEmployeeSearch.value.trim()];
        if (hasExact) return;

        const firstMatch = payrollOptionLabels.find((label) => label.toLowerCase().includes(typed));
        if (!firstMatch) return;

        event.preventDefault();
        payrollEmployeeSearch.value = firstMatch;
        applyPayrollEmployeeSelection();

        if (payrollSalarySetupForm && payrollSelectedPersonId.value) {
          payrollSalarySetupForm.requestSubmit();
        }
      });

      payrollSalarySetupForm?.addEventListener('submit', (event) => {
        applyPayrollEmployeeSelection();
        if (payrollSelectedPersonId.value) {
          payrollEmployeeSearch.setCustomValidity('');
          return;
        }

        event.preventDefault();
        payrollEmployeeSearch.setCustomValidity('Please select an employee from the suggested list.');
        payrollEmployeeSearch.reportValidity();
      });

      payrollEmployeeSearch.addEventListener('input', () => {
        if (payrollSelectedPersonId.value) {
          payrollSelectedPersonId.value = '';
        }
        payrollEmployeeSearch.setCustomValidity('');
      });
    }

    if (profileEmployeeName && profileEmployeeList && profilePersonId && profileAction) {
      const profileOptionLookup = {};
      Array.from(profileEmployeeList.querySelectorAll('option')).forEach((option) => {
        const label = (option.getAttribute('value') || '').trim();
        if (!label) return;
        profileOptionLookup[label] = option.getAttribute('data-person-id') || '';
      });

      const applyProfilePersonSelection = () => {
        const selectedLabel = (profileEmployeeName.value || '').trim();
        profilePersonId.value = profileOptionLookup[selectedLabel] || '';
      };

      profileEmployeeName.addEventListener('change', applyProfilePersonSelection);
      profileEmployeeName.addEventListener('blur', applyProfilePersonSelection);
      profileEmployeeName.addEventListener('input', () => {
        profilePersonId.value = '';
        profileEmployeeName.setCustomValidity('');
      });

      profileEmployeeName.form?.addEventListener('submit', (event) => {
        applyProfilePersonSelection();
        const mode = (profileAction.value || '').trim().toLowerCase();
        if (mode === 'add') {
          profileEmployeeName.setCustomValidity('');
          return;
        }

        if (profilePersonId.value) {
          profileEmployeeName.setCustomValidity('');
          return;
        }

        event.preventDefault();
        profileEmployeeName.setCustomValidity('Select an existing employee from the suggestions for edit/archive actions.');
        profileEmployeeName.reportValidity();
      });
    }

    const accountForm = document.getElementById('accountForm');
    const accountActionSelect = document.getElementById('accountActionSelect');
    const accountEmailInput = document.getElementById('accountEmailInput');
    const accountFullNameInput = document.getElementById('accountFullNameInput');
    const roleUserSelect = document.getElementById('roleUserSelect');
    const credentialUserSelect = document.getElementById('credentialUserSelect');
    const modalElements = document.querySelectorAll('[data-modal]');
    const modalOpenButtons = document.querySelectorAll('[data-modal-open]');
    const modalCloseButtons = document.querySelectorAll('[data-modal-close]');

    function setBodyScrollLock(isLocked) {
      document.body.classList.toggle('overflow-hidden', isLocked);
    }

    function closeAllModals() {
      modalElements.forEach((modal) => {
        modal.classList.add('hidden');
        modal.setAttribute('aria-hidden', 'true');
      });
      setBodyScrollLock(false);
    }

    function openModal(modalId) {
      if (!modalId) return;
      const modal = document.getElementById(modalId);
      if (!modal) return;

      closeAllModals();
      modal.classList.remove('hidden');
      modal.setAttribute('aria-hidden', 'false');
      setBodyScrollLock(true);

      const focusTarget = modal.querySelector('input, select, textarea, button');
      focusTarget?.focus();
    }

    modalOpenButtons.forEach((button) => {
      button.addEventListener('click', () => {
        const modalId = button.getAttribute('data-modal-open');
        openModal(modalId);
      });
    });

    modalCloseButtons.forEach((button) => {
      button.addEventListener('click', () => {
        closeAllModals();
      });
    });

    modalElements.forEach((modal) => {
      const modalContent = modal.querySelector('.relative');
      modal.addEventListener('click', (event) => {
        if (modalContent && modalContent.contains(event.target)) return;
        closeAllModals();
      });
    });

    document.querySelectorAll('[data-fill-role]').forEach((button) => {
      button.addEventListener('click', () => {
        const userId = button.getAttribute('data-user-id') || '';
        if (!userId || !roleUserSelect) return;

        roleUserSelect.value = userId;
        roleUserSelect.dispatchEvent(new Event('change'));
        openModal('roleModal');
      });
    });

    document.querySelectorAll('[data-fill-credential]').forEach((button) => {
      button.addEventListener('click', () => {
        const userId = button.getAttribute('data-user-id') || '';
        if (!userId || !credentialUserSelect) return;

        credentialUserSelect.value = userId;
        credentialUserSelect.dispatchEvent(new Event('change'));
        openModal('credentialModal');
      });
    });

    document.querySelectorAll('[data-prepare-archive]').forEach((button) => {
      button.addEventListener('click', () => {
        const email = button.getAttribute('data-email') || '';
        const displayName = button.getAttribute('data-display-name') || '';

        if (!accountForm || !accountActionSelect || !accountEmailInput) return;

        accountActionSelect.value = 'archive';
        accountEmailInput.value = email;
        if (accountFullNameInput) {
          accountFullNameInput.value = displayName;
        }

        openModal('accountModal');
        accountEmailInput.focus();
      });
    });

    if (window.flatpickr) {
      const dateInputs = document.querySelectorAll('main input[type="date"]:not([data-flatpickr="off"])');
      dateInputs.forEach((input) => {
        if (input.dataset.flatpickrInitialized === 'true') return;
        flatpickr(input, {
          dateFormat: 'Y-m-d',
          allowInput: true
        });
        input.dataset.flatpickrInitialized = 'true';
      });

      const timeInputs = document.querySelectorAll('main input[type="time"]:not([data-flatpickr="off"])');
      timeInputs.forEach((input) => {
        if (input.dataset.flatpickrInitialized === 'true') return;
        flatpickr(input, {
          enableTime: true,
          noCalendar: true,
          dateFormat: 'H:i',
          time_24hr: true,
          allowInput: true
        });
        input.dataset.flatpickrInitialized = 'true';
      });
    }

    if (window.Swal) {
      const adminForms = document.querySelectorAll('main form');
      adminForms.forEach((form) => {
        if (form.dataset.swalBound === 'true') return;

        form.addEventListener('submit', (event) => {
          const action = (form.getAttribute('action') || '').trim();
          if (action !== '') return;

          event.preventDefault();
          Swal.fire({
            icon: 'success',
            title: 'Saved',
            text: 'Demo update applied successfully.',
            confirmButtonColor: '#0f172a'
          });
        });

        form.dataset.swalBound = 'true';
      });
    }

    if (window.Chart) {
      const chartCanvases = document.querySelectorAll('canvas[data-chart-type]');
      chartCanvases.forEach((canvas) => {
        if (canvas.dataset.chartInitialized === 'true') return;

        const chartType = canvas.dataset.chartType;
        const labelText = canvas.dataset.chartLabel || 'Dataset';
        const labels = JSON.parse(canvas.dataset.chartLabels || '[]');
        const values = JSON.parse(canvas.dataset.chartValues || '[]');
        const colors = JSON.parse(canvas.dataset.chartColors || '["#0f172a", "#10b981", "#f59e0b", "#3b82f6", "#e11d48"]');

        if (!chartType || !labels.length || !values.length) return;

        new Chart(canvas, {
          type: chartType,
          data: {
            labels,
            datasets: [{
              label: labelText,
              data: values,
              backgroundColor: colors,
              borderColor: colors,
              borderWidth: 1,
              tension: 0.35
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                position: 'bottom'
              }
            }
          }
        });

        canvas.dataset.chartInitialized = 'true';
      });
    }

    applySidebarState();

    document.addEventListener('keydown', (event) => {
      if (event.key !== 'Escape') return;

      const hasOpenModal = Array.from(modalElements).some((modal) => !modal.classList.contains('hidden'));
      if (hasOpenModal) {
        closeAllModals();
      }
    });
  });
})();
