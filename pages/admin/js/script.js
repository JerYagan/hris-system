(() => {
  document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('sidebar');
    const toggle = document.getElementById('sidebarToggle');
    const closeButton = document.getElementById('sidebarClose');
    const overlay = document.getElementById('sidebarOverlay');
    const profileToggle = document.getElementById('profileToggle');
    const profileMenu = document.getElementById('profileMenu');
    const notificationToggle = document.getElementById('notificationToggle');
    const notificationMenu = document.getElementById('notificationMenu');
    const topnavSearchInput = document.getElementById('topnavGlobalSearch');
    const topnavSearchResults = document.getElementById('topnavSearchResults');
    const topnavSearchResultsBody = document.getElementById('topnavSearchResultsBody');
    const topnavSearchMeta = document.getElementById('topnavSearchMeta');
    const topnavSearchWrapper = document.getElementById('topnavSearchWrapper');
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
        if (notificationMenu && !notificationMenu.classList.contains('hidden')) {
          notificationMenu.classList.add('hidden');
        }
        profileMenu.classList.toggle('hidden');
      });

      document.addEventListener('click', (event) => {
        if (!profileMenu.classList.contains('hidden') && !profileMenu.contains(event.target) && !profileToggle.contains(event.target)) {
          profileMenu.classList.add('hidden');
        }
      });
    }

    if (notificationToggle && notificationMenu) {
      notificationToggle.addEventListener('click', (event) => {
        event.stopPropagation();
        if (profileMenu && !profileMenu.classList.contains('hidden')) {
          profileMenu.classList.add('hidden');
        }
        notificationMenu.classList.toggle('hidden');
      });

      document.addEventListener('click', (event) => {
        if (!notificationMenu.classList.contains('hidden') && !notificationMenu.contains(event.target) && !notificationToggle.contains(event.target)) {
          notificationMenu.classList.add('hidden');
        }
      });

      document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !notificationMenu.classList.contains('hidden')) {
          notificationMenu.classList.add('hidden');
        }
      });
    }

    if (topnavSearchInput && topnavSearchResults && topnavSearchResultsBody && topnavSearchMeta) {
      const normalize = (value) => String(value || '').toLowerCase().trim();
      const compactText = (value) => String(value || '').replace(/\s+/g, ' ').trim();
      const escapeHtml = (value) => String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');

      const openSearchResults = () => {
        topnavSearchResults.classList.remove('hidden');
      };

      const closeSearchResults = () => {
        topnavSearchResults.classList.add('hidden');
      };

      const buildSearchableItems = () => {
        const items = [];
        const seen = new Set();
        let autoIdCounter = 0;

        const registerItem = (rawTitle, rawSubtitle, element, fallbackSearchText) => {
          const title = compactText(rawTitle);
          const subtitle = compactText(rawSubtitle);
          const searchText = compactText(fallbackSearchText || `${title} ${subtitle}`);
          if (!title || !element || !searchText) {
            return;
          }

          if (!element.id) {
            autoIdCounter += 1;
            element.id = `topnav-search-record-${autoIdCounter}`;
          }

          const key = `${title}|${subtitle}|${element.id}`;
          if (seen.has(key)) {
            return;
          }
          seen.add(key);

          const preview = searchText.length > 140 ? `${searchText.slice(0, 140)}…` : searchText;
          items.push({
            title,
            subtitle: subtitle || 'Record',
            preview,
            targetId: element.id,
            searchKey: normalize(`${title} ${subtitle} ${searchText}`),
          });
        };

        document.querySelectorAll('main table').forEach((table) => {
          const section = table.closest('section, article, .bg-white, .rounded-xl, .rounded-2xl');
          const sectionHeading = section ? section.querySelector('h1, h2, h3, .text-lg, .text-xl') : null;
          const caption = table.querySelector('caption');
          const contextLabel = compactText((caption && caption.textContent) || (sectionHeading && sectionHeading.textContent) || 'Table Record');

          table.querySelectorAll('tbody tr').forEach((row, rowIndex) => {
            const cellTexts = Array.from(row.querySelectorAll('td, th'))
              .map((cell) => compactText(cell.textContent))
              .filter(Boolean);

            if (!cellTexts.length) {
              return;
            }

            const rowTitle = cellTexts[0] || `${contextLabel} #${rowIndex + 1}`;
            registerItem(rowTitle, contextLabel, row, cellTexts.join(' '));
          });
        });

        document.querySelectorAll('main [data-search-item], main .searchable-item').forEach((element) => {
          const title = element.getAttribute('data-search-title') || compactText(element.textContent);
          const subtitle = element.getAttribute('data-search-type') || 'Record';
          const blob = element.getAttribute('data-search-body') || compactText(element.textContent);
          registerItem(title, subtitle, element, blob);
        });

        return items;
      };

      const focusRecord = (targetId) => {
        if (!targetId) return;
        const targetElement = document.getElementById(targetId);
        if (!targetElement) return;

        targetElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
        targetElement.classList.add('ring-2', 'ring-emerald-300', 'ring-offset-2', 'ring-offset-white');
        setTimeout(() => {
          targetElement.classList.remove('ring-2', 'ring-emerald-300', 'ring-offset-2', 'ring-offset-white');
        }, 1600);
      };

      const renderSearchResults = (query) => {
        const normalizedQuery = normalize(query);
        const searchableItems = buildSearchableItems();

        if (!normalizedQuery) {
          topnavSearchMeta.textContent = `${searchableItems.length} records indexed`;
          topnavSearchResultsBody.innerHTML = '<div class="px-4 py-6 text-sm text-slate-500 text-center">Start typing to find accounts, documents, and records.</div>';
          openSearchResults();
          return;
        }

        const matches = searchableItems
          .filter((item) => item.searchKey.includes(normalizedQuery))
          .slice(0, 10);

        topnavSearchMeta.textContent = `${matches.length} record(s)`;
        if (matches.length === 0) {
          topnavSearchResultsBody.innerHTML = '<div class="px-4 py-6 text-sm text-slate-500 text-center">No matching records found in this module.</div>';
          openSearchResults();
          return;
        }

        topnavSearchResultsBody.innerHTML = matches.map((item) => `
          <button type="button" data-search-target="${escapeHtml(item.targetId)}" class="w-full text-left px-4 py-3 border-b border-slate-100 hover:bg-slate-50 transition-colors">
            <p class="text-sm font-medium text-slate-800">${escapeHtml(item.title)}</p>
            <p class="text-xs text-slate-500 mt-1">${escapeHtml(item.subtitle)}</p>
            <p class="text-[11px] text-slate-400 mt-1 line-clamp-1">${escapeHtml(item.preview)}</p>
          </button>
        `).join('');

        openSearchResults();
      };

      topnavSearchResultsBody.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-search-target]');
        if (!trigger) {
          return;
        }

        const targetId = trigger.getAttribute('data-search-target') || '';
        focusRecord(targetId);
        closeSearchResults();
      });

      let debounceTimer = null;
      topnavSearchInput.addEventListener('input', () => {
        if (debounceTimer) {
          clearTimeout(debounceTimer);
        }

        debounceTimer = setTimeout(() => {
          renderSearchResults(topnavSearchInput.value || '');
        }, 250);
      });

      topnavSearchInput.addEventListener('focus', () => {
        renderSearchResults(topnavSearchInput.value || '');
      });

      document.addEventListener('click', (event) => {
        if (!topnavSearchWrapper || !topnavSearchResults) {
          return;
        }

        if (!topnavSearchResults.classList.contains('hidden') && !topnavSearchWrapper.contains(event.target)) {
          closeSearchResults();
        }
      });

      document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !topnavSearchResults.classList.contains('hidden')) {
          closeSearchResults();
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
          autoWidth: false,
          ordering: true
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

        if (table.id === 'adminApplicantsTable') {
          dataTableOptions.order = [[2, 'desc']];
          dataTableOptions.columnDefs = [
            { targets: [5], orderable: false, searchable: false }
          ];
        }

        if (table.id === 'adminRecommendationTable') {
          dataTableOptions.order = [[0, 'asc']];
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

        if (table.id === 'adminApplicantsTable') {
          const adminApplicantsSearch = document.getElementById('adminApplicantsSearch');
          const adminApplicantsStatusFilter = document.getElementById('adminApplicantsStatusFilter');

          adminApplicantsSearch?.addEventListener('input', () => {
            dataTable.search((adminApplicantsSearch.value || '').trim()).draw();
          });

          adminApplicantsStatusFilter?.addEventListener('change', () => {
            const selected = (adminApplicantsStatusFilter.value || '').trim();
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

        if (table.id === 'adminRecommendationTable') {
          const adminRecommendationSearch = document.getElementById('adminRecommendationSearch');
          const adminRecommendationAlignmentFilter = document.getElementById('adminRecommendationAlignmentFilter');

          adminRecommendationSearch?.addEventListener('input', () => {
            dataTable.search((adminRecommendationSearch.value || '').trim()).draw();
          });

          adminRecommendationAlignmentFilter?.addEventListener('change', () => {
            const selected = (adminRecommendationAlignmentFilter.value || '').trim();
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

        if (table.id === 'recruitmentPostingsTable') {
          const recruitmentPostingsSearch = document.getElementById('recruitmentPostingsSearch');
          const recruitmentPostingsStatusFilter = document.getElementById('recruitmentPostingsStatusFilter');

          recruitmentPostingsSearch?.addEventListener('input', () => {
            dataTable.search((recruitmentPostingsSearch.value || '').trim()).draw();
          });

          recruitmentPostingsStatusFilter?.addEventListener('change', () => {
            const selected = (recruitmentPostingsStatusFilter.value || '').trim();
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

        if (table.id === 'learningTrainingRecordsTable') {
          const learningRecordsSearch = document.getElementById('learningRecordsSearch');
          const learningRecordsStatusFilter = document.getElementById('learningRecordsStatusFilter');

          learningRecordsSearch?.addEventListener('input', () => {
            dataTable.search((learningRecordsSearch.value || '').trim()).draw();
          });

          learningRecordsStatusFilter?.addEventListener('change', () => {
            const selected = (learningRecordsStatusFilter.value || '').trim();
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

        if (table.id === 'learningScheduleTable') {
          const learningScheduleSearch = document.getElementById('learningScheduleSearch');
          const learningScheduleStatusFilter = document.getElementById('learningScheduleStatusFilter');

          learningScheduleSearch?.addEventListener('input', () => {
            dataTable.search((learningScheduleSearch.value || '').trim()).draw();
          });

          learningScheduleStatusFilter?.addEventListener('change', () => {
            const selected = (learningScheduleStatusFilter.value || '').trim();
            if (!selected) {
              dataTable.column(7).search('').draw();
              return;
            }

            const escaped = selected.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            const exactSearch = `^${escaped}$`;
            try {
              dataTable.column(7).search(exactSearch, { regex: true, smart: false }).draw();
            } catch (_error) {
              dataTable.column(7).search(exactSearch, true, false).draw();
            }
          });
        }

        if (table.id === 'learningAttendanceTable') {
          const learningAttendanceSearch = document.getElementById('learningAttendanceSearch');
          const learningAttendanceStatusFilter = document.getElementById('learningAttendanceStatusFilter');

          learningAttendanceSearch?.addEventListener('input', () => {
            dataTable.search((learningAttendanceSearch.value || '').trim()).draw();
          });

          learningAttendanceStatusFilter?.addEventListener('change', () => {
            const selected = (learningAttendanceStatusFilter.value || '').trim();
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

        if (table.id === 'praiseRatingsTable') {
          const praiseRatingsSearch = document.getElementById('praiseRatingsSearch');
          const praiseRatingsStatusFilter = document.getElementById('praiseRatingsStatusFilter');

          praiseRatingsSearch?.addEventListener('input', () => {
            dataTable.search((praiseRatingsSearch.value || '').trim()).draw();
          });

          praiseRatingsStatusFilter?.addEventListener('change', () => {
            const selected = (praiseRatingsStatusFilter.value || '').trim();
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

        if (table.id === 'praiseNominationsTable') {
          const praiseNominationsSearch = document.getElementById('praiseNominationsSearch');

          praiseNominationsSearch?.addEventListener('input', () => {
            dataTable.search((praiseNominationsSearch.value || '').trim()).draw();
          });
        }

        if (table.id === 'praisePerformanceReportsTable') {
          const praisePerformanceSearch = document.getElementById('praisePerformanceSearch');
          const praisePerformancePeriodFilter = document.getElementById('praisePerformancePeriodFilter');

          praisePerformanceSearch?.addEventListener('input', () => {
            dataTable.search((praisePerformanceSearch.value || '').trim()).draw();
          });

          praisePerformancePeriodFilter?.addEventListener('change', () => {
            const selected = (praisePerformancePeriodFilter.value || '').trim();
            if (!selected) {
              dataTable.column(0).search('').draw();
              return;
            }

            const escaped = selected.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            const exactSearch = `^${escaped}$`;
            try {
              dataTable.column(0).search(exactSearch, { regex: true, smart: false }).draw();
            } catch (_error) {
              dataTable.column(0).search(exactSearch, true, false).draw();
            }
          });
        }

        if (table.id === 'praiseRecognitionHistoryTable') {
          const praiseRecognitionSearch = document.getElementById('praiseRecognitionSearch');
          const praiseRecognitionCycleFilter = document.getElementById('praiseRecognitionCycleFilter');

          praiseRecognitionSearch?.addEventListener('input', () => {
            dataTable.search((praiseRecognitionSearch.value || '').trim()).draw();
          });

          praiseRecognitionCycleFilter?.addEventListener('change', () => {
            const selected = (praiseRecognitionCycleFilter.value || '').trim();
            if (!selected) {
              dataTable.column(0).search('').draw();
              return;
            }

            const escaped = selected.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            const exactSearch = `^${escaped}$`;
            try {
              dataTable.column(0).search(exactSearch, { regex: true, smart: false }).draw();
            } catch (_error) {
              dataTable.column(0).search(exactSearch, true, false).draw();
            }
          });
        }

        if (table.id === 'profileLoginHistoryTable') {
          const profileLoginSearch = document.getElementById('profileLoginSearch');
          const profileLoginEventFilter = document.getElementById('profileLoginEventFilter');

          profileLoginSearch?.addEventListener('input', () => {
            dataTable.search((profileLoginSearch.value || '').trim()).draw();
          });

          profileLoginEventFilter?.addEventListener('change', () => {
            const selected = (profileLoginEventFilter.value || '').trim();
            if (!selected) {
              dataTable.column(0).search('').draw();
              return;
            }

            const escaped = selected.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            const exactSearch = `^${escaped}$`;
            try {
              dataTable.column(0).search(exactSearch, { regex: true, smart: false }).draw();
            } catch (_error) {
              dataTable.column(0).search(exactSearch, true, false).draw();
            }
          });
        }

        if (table.id === 'dashboardPendingLeaveTable') {
          const dashboardLeaveSearch = document.getElementById('dashboardLeaveSearch');
          const dashboardLeaveStatusFilter = document.getElementById('dashboardLeaveStatusFilter');

          dashboardLeaveSearch?.addEventListener('input', () => {
            dataTable.search((dashboardLeaveSearch.value || '').trim()).draw();
          });

          dashboardLeaveStatusFilter?.addEventListener('change', () => {
            const selected = (dashboardLeaveStatusFilter.value || '').trim();
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

        if (table.id === 'dashboardNotificationsTable') {
          const dashboardNotificationsSearch = document.getElementById('dashboardNotificationsSearch');
          const dashboardNotificationsStatusFilter = document.getElementById('dashboardNotificationsStatusFilter');

          dashboardNotificationsSearch?.addEventListener('input', () => {
            dataTable.search((dashboardNotificationsSearch.value || '').trim()).draw();
          });

          dashboardNotificationsStatusFilter?.addEventListener('change', () => {
            const selected = (dashboardNotificationsStatusFilter.value || '').trim();
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

        if (table.id === 'dashboardDepartmentTable') {
          const dashboardDepartmentSearch = document.getElementById('dashboardDepartmentSearch');

          dashboardDepartmentSearch?.addEventListener('input', () => {
            dataTable.search((dashboardDepartmentSearch.value || '').trim()).draw();
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

          personalInfoStatusFilter?.addEventListener('change', () => {
            const selected = (personalInfoStatusFilter.value || '').trim();
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

        if (table.id === 'personalInfoFamilyTable') {
          const familySearchInput = document.getElementById('personalInfoFamilySearchInput');
          const familyDepartmentFilter = document.getElementById('personalInfoFamilyDepartmentFilter');
          const familyStatusFilter = document.getElementById('personalInfoFamilyStatusFilter');

          familySearchInput?.addEventListener('input', () => {
            dataTable.search((familySearchInput.value || '').trim()).draw();
          });

          familyDepartmentFilter?.addEventListener('change', () => {
            const selected = (familyDepartmentFilter.value || '').trim();
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

          familyStatusFilter?.addEventListener('change', () => {
            const selected = (familyStatusFilter.value || '').trim();
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

        if (table.id === 'personalInfoEducationTable') {
          const educationSearchInput = document.getElementById('personalInfoEducationSearchInput');
          const educationDepartmentFilter = document.getElementById('personalInfoEducationDepartmentFilter');
          const educationStatusFilter = document.getElementById('personalInfoEducationStatusFilter');

          educationSearchInput?.addEventListener('input', () => {
            dataTable.search((educationSearchInput.value || '').trim()).draw();
          });

          educationDepartmentFilter?.addEventListener('change', () => {
            const selected = (educationDepartmentFilter.value || '').trim();
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

          educationStatusFilter?.addEventListener('change', () => {
            const selected = (educationStatusFilter.value || '').trim();
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
    const recruitmentPostingsTable = document.getElementById('recruitmentPostingsTable');
    const recruitmentPostingsSearch = document.getElementById('recruitmentPostingsSearch');
    const recruitmentPostingsStatus = document.getElementById('recruitmentPostingsStatusFilter');

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

    if (
      recruitmentPostingsTable
      && recruitmentPostingsSearch
      && recruitmentPostingsStatus
      && recruitmentPostingsTable.dataset.datatableInitialized !== 'true'
    ) {
      const postingRows = Array.from(recruitmentPostingsTable.querySelectorAll('tbody tr')).filter((row) => !row.querySelector('td[colspan]'));

      const applyPlainRecruitmentPostingsFilter = () => {
        const searchText = (recruitmentPostingsSearch.value || '').trim().toLowerCase();
        const selectedStatus = (recruitmentPostingsStatus.value || '').trim().toLowerCase();

        postingRows.forEach((row) => {
          const rowSearch = (row.getAttribute('data-recruitment-postings-search') || '').toLowerCase();
          const rowStatus = (row.getAttribute('data-recruitment-postings-status') || '').toLowerCase();
          const matchesSearch = !searchText || rowSearch.includes(searchText);
          const matchesStatus = !selectedStatus || rowStatus === selectedStatus;
          row.classList.toggle('hidden', !(matchesSearch && matchesStatus));
        });
      };

      recruitmentPostingsSearch.addEventListener('input', applyPlainRecruitmentPostingsFilter);
      recruitmentPostingsStatus.addEventListener('change', applyPlainRecruitmentPostingsFilter);
    }

    const trackingTable = document.getElementById('applicantTrackingTable');
    const trackingSearchInput = document.getElementById('applicantTrackingSearch');
    const trackingStatusFilter = document.getElementById('applicantTrackingStatusFilter');
    const learningRecordsTable = document.getElementById('learningTrainingRecordsTable');
    const learningRecordsSearch = document.getElementById('learningRecordsSearch');
    const learningRecordsStatusFilter = document.getElementById('learningRecordsStatusFilter');
    const learningRecordsPageSize = document.getElementById('learningRecordsPageSize');
    const learningRecordsPageInfo = document.getElementById('learningRecordsPageInfo');
    const learningRecordsPrevPage = document.getElementById('learningRecordsPrevPage');
    const learningRecordsNextPage = document.getElementById('learningRecordsNextPage');
    const learningScheduleTable = document.getElementById('learningScheduleTable');
    const learningScheduleSearch = document.getElementById('learningScheduleSearch');
    const learningScheduleStatusFilter = document.getElementById('learningScheduleStatusFilter');
    const learningSchedulePageSize = document.getElementById('learningSchedulePageSize');
    const learningSchedulePageInfo = document.getElementById('learningSchedulePageInfo');
    const learningSchedulePrevPage = document.getElementById('learningSchedulePrevPage');
    const learningScheduleNextPage = document.getElementById('learningScheduleNextPage');
    const learningAttendanceTable = document.getElementById('learningAttendanceTable');
    const learningAttendanceSearch = document.getElementById('learningAttendanceSearch');
    const learningAttendanceStatusFilter = document.getElementById('learningAttendanceStatusFilter');
    const learningAttendancePageSize = document.getElementById('learningAttendancePageSize');
    const learningAttendancePageInfo = document.getElementById('learningAttendancePageInfo');
    const learningAttendancePrevPage = document.getElementById('learningAttendancePrevPage');
    const learningAttendanceNextPage = document.getElementById('learningAttendanceNextPage');
    const praiseRatingsTable = document.getElementById('praiseRatingsTable');
    const praiseRatingsSearch = document.getElementById('praiseRatingsSearch');
    const praiseRatingsStatusFilter = document.getElementById('praiseRatingsStatusFilter');
    const praiseNominationsTable = document.getElementById('praiseNominationsTable');
    const praiseNominationsSearch = document.getElementById('praiseNominationsSearch');
    const praisePerformanceReportsTable = document.getElementById('praisePerformanceReportsTable');
    const praisePerformanceSearch = document.getElementById('praisePerformanceSearch');
    const praisePerformancePeriodFilter = document.getElementById('praisePerformancePeriodFilter');
    const praisePerformanceExportBtn = document.getElementById('praisePerformanceExportBtn');
    const praisePerformanceExportPdfBtn = document.getElementById('praisePerformanceExportPdfBtn');
    const praiseRecognitionHistoryTable = document.getElementById('praiseRecognitionHistoryTable');
    const praiseRecognitionSearch = document.getElementById('praiseRecognitionSearch');
    const praiseRecognitionCycleFilter = document.getElementById('praiseRecognitionCycleFilter');
    const praiseRecognitionExportBtn = document.getElementById('praiseRecognitionExportBtn');
    const praiseRecognitionExportPdfBtn = document.getElementById('praiseRecognitionExportPdfBtn');
    const profileLoginHistoryTable = document.getElementById('profileLoginHistoryTable');
    const profileLoginSearch = document.getElementById('profileLoginSearch');
    const profileLoginEventFilter = document.getElementById('profileLoginEventFilter');
    const dashboardPendingLeaveTable = document.getElementById('dashboardPendingLeaveTable');
    const dashboardLeaveSearch = document.getElementById('dashboardLeaveSearch');
    const dashboardLeaveStatusFilter = document.getElementById('dashboardLeaveStatusFilter');
    const dashboardNotificationsTable = document.getElementById('dashboardNotificationsTable');
    const dashboardNotificationsSearch = document.getElementById('dashboardNotificationsSearch');
    const dashboardNotificationsStatusFilter = document.getElementById('dashboardNotificationsStatusFilter');
    const dashboardDepartmentTable = document.getElementById('dashboardDepartmentTable');
    const dashboardDepartmentSearch = document.getElementById('dashboardDepartmentSearch');
    const reportEmployeesTable = document.getElementById('reportEmployeesTable');
    const reportEmployeesSearch = document.getElementById('reportEmployeesSearch');
    const reportEmployeesDepartmentFilter = document.getElementById('reportEmployeesDepartmentFilter');
    const reportEmployeesStatusFilter = document.getElementById('reportEmployeesStatusFilter');
    const reportCoverageSelect = document.getElementById('reportCoverageSelect');
    const reportCustomDateRange = document.getElementById('reportCustomDateRange');
    const reportCustomStartDate = document.getElementById('reportCustomStartDate');
    const reportCustomEndDate = document.getElementById('reportCustomEndDate');

    const adminApplicantsTable = document.getElementById('adminApplicantsTable');
    const adminApplicantsSearch = document.getElementById('adminApplicantsSearch');
    const adminApplicantsStatus = document.getElementById('adminApplicantsStatusFilter');
    const adminRecommendationTable = document.getElementById('adminRecommendationTable');
    const adminRecommendationSearch = document.getElementById('adminRecommendationSearch');
    const adminRecommendationAlignmentFilter = document.getElementById('adminRecommendationAlignmentFilter');

    if (
      reportEmployeesTable
      && reportEmployeesSearch
      && reportEmployeesDepartmentFilter
      && reportEmployeesStatusFilter
      && reportEmployeesTable.dataset.datatableInitialized !== 'true'
    ) {
      const reportEmployeeRows = Array.from(reportEmployeesTable.querySelectorAll('tbody tr')).filter((row) => !row.querySelector('td[colspan]'));

      const applyPlainReportEmployeesFilter = () => {
        const searchText = (reportEmployeesSearch.value || '').trim().toLowerCase();
        const selectedDepartment = (reportEmployeesDepartmentFilter.value || '').trim().toLowerCase();
        const selectedStatus = (reportEmployeesStatusFilter.value || '').trim().toLowerCase();

        reportEmployeeRows.forEach((row) => {
          const rowSearch = (row.getAttribute('data-report-employee-search') || '').toLowerCase();
          const rowDepartment = (row.getAttribute('data-report-employee-department') || '').toLowerCase();
          const rowStatus = (row.getAttribute('data-report-employee-status') || '').toLowerCase();

          const matchesSearch = !searchText || rowSearch.includes(searchText);
          const matchesDepartment = !selectedDepartment || rowDepartment === selectedDepartment;
          const matchesStatus = !selectedStatus || rowStatus === selectedStatus;

          row.classList.toggle('hidden', !(matchesSearch && matchesDepartment && matchesStatus));
        });
      };

      reportEmployeesSearch.addEventListener('input', applyPlainReportEmployeesFilter);
      reportEmployeesDepartmentFilter.addEventListener('change', applyPlainReportEmployeesFilter);
      reportEmployeesStatusFilter.addEventListener('change', applyPlainReportEmployeesFilter);
    }

    if (reportCoverageSelect && reportCustomDateRange && reportCustomStartDate && reportCustomEndDate) {
      const toggleReportCustomRange = () => {
        const isCustom = (reportCoverageSelect.value || '').trim().toLowerCase() === 'custom_range';
        reportCustomDateRange.classList.toggle('hidden', !isCustom);
        reportCustomStartDate.required = isCustom;
        reportCustomEndDate.required = isCustom;
      };

      reportCoverageSelect.addEventListener('change', toggleReportCustomRange);
      toggleReportCustomRange();
    }

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
      adminRecommendationTable
      && adminRecommendationSearch
      && adminRecommendationAlignmentFilter
      && adminRecommendationTable.dataset.datatableInitialized !== 'true'
    ) {
      const recommendationRows = Array.from(adminRecommendationTable.querySelectorAll('tbody tr')).filter((row) => !row.querySelector('td[colspan]'));

      const applyPlainRecommendationFilter = () => {
        const searchText = (adminRecommendationSearch.value || '').trim().toLowerCase();
        const selectedAlignment = (adminRecommendationAlignmentFilter.value || '').trim().toLowerCase();

        recommendationRows.forEach((row) => {
          const rowSearch = (row.getAttribute('data-recommendation-search') || '').toLowerCase();
          const rowAlignment = (row.getAttribute('data-recommendation-alignment') || '').toLowerCase();
          const matchesSearch = !searchText || rowSearch.includes(searchText);
          const matchesAlignment = !selectedAlignment || rowAlignment === selectedAlignment;
          row.classList.toggle('hidden', !(matchesSearch && matchesAlignment));
        });
      };

      adminRecommendationSearch.addEventListener('input', applyPlainRecommendationFilter);
      adminRecommendationAlignmentFilter.addEventListener('change', applyPlainRecommendationFilter);
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

    if (
      learningRecordsTable
      && learningRecordsSearch
      && learningRecordsStatusFilter
      && learningRecordsTable.dataset.datatableInitialized !== 'true'
    ) {
      const learningRecordRows = Array.from(learningRecordsTable.querySelectorAll('tbody tr')).filter((row) => !row.querySelector('td[colspan]'));
      let learningRecordsCurrentPage = 1;

      const getLearningRecordsPageSize = () => {
        const value = Number(learningRecordsPageSize?.value || 10);
        return [10, 25, 50].includes(value) ? value : 10;
      };

      const applyPlainLearningRecordsFilter = () => {
        const searchText = (learningRecordsSearch.value || '').trim().toLowerCase();
        const selectedStatus = (learningRecordsStatusFilter.value || '').trim().toLowerCase();
        const matchedRows = [];

        learningRecordRows.forEach((row) => {
          const rowSearch = (row.getAttribute('data-learning-record-search') || '').toLowerCase();
          const rowStatus = (row.getAttribute('data-learning-record-status') || '').toLowerCase();
          const matchesSearch = !searchText || rowSearch.includes(searchText);
          const matchesStatus = !selectedStatus || rowStatus === selectedStatus;
          if (matchesSearch && matchesStatus) {
            matchedRows.push(row);
          }
          row.classList.add('hidden');
        });

        const pageSize = getLearningRecordsPageSize();
        const totalPages = Math.max(1, Math.ceil(matchedRows.length / pageSize));
        if (learningRecordsCurrentPage > totalPages) {
          learningRecordsCurrentPage = totalPages;
        }

        const start = (learningRecordsCurrentPage - 1) * pageSize;
        const pagedRows = matchedRows.slice(start, start + pageSize);
        pagedRows.forEach((row) => row.classList.remove('hidden'));

        if (learningRecordsPageInfo) {
          learningRecordsPageInfo.textContent = `Page ${learningRecordsCurrentPage} of ${totalPages}`;
        }
        if (learningRecordsPrevPage) {
          learningRecordsPrevPage.disabled = learningRecordsCurrentPage <= 1;
        }
        if (learningRecordsNextPage) {
          learningRecordsNextPage.disabled = learningRecordsCurrentPage >= totalPages;
        }
      };

      learningRecordsSearch.addEventListener('input', () => {
        learningRecordsCurrentPage = 1;
        applyPlainLearningRecordsFilter();
      });
      learningRecordsStatusFilter.addEventListener('change', () => {
        learningRecordsCurrentPage = 1;
        applyPlainLearningRecordsFilter();
      });
      learningRecordsPageSize?.addEventListener('change', () => {
        learningRecordsCurrentPage = 1;
        applyPlainLearningRecordsFilter();
      });
      learningRecordsPrevPage?.addEventListener('click', () => {
        if (learningRecordsCurrentPage <= 1) return;
        learningRecordsCurrentPage -= 1;
        applyPlainLearningRecordsFilter();
      });
      learningRecordsNextPage?.addEventListener('click', () => {
        learningRecordsCurrentPage += 1;
        applyPlainLearningRecordsFilter();
      });

      applyPlainLearningRecordsFilter();
    }

    if (
      learningScheduleTable
      && learningScheduleSearch
      && learningScheduleStatusFilter
      && learningScheduleTable.dataset.datatableInitialized !== 'true'
    ) {
      const learningScheduleRows = Array.from(learningScheduleTable.querySelectorAll('tbody tr')).filter((row) => !row.querySelector('td[colspan]'));
      let learningScheduleCurrentPage = 1;

      const getLearningSchedulePageSize = () => {
        const value = Number(learningSchedulePageSize?.value || 10);
        return [10, 25, 50].includes(value) ? value : 10;
      };

      const applyPlainLearningScheduleFilter = () => {
        const searchText = (learningScheduleSearch.value || '').trim().toLowerCase();
        const selectedStatus = (learningScheduleStatusFilter.value || '').trim().toLowerCase();
        const matchedRows = [];

        learningScheduleRows.forEach((row) => {
          const rowSearch = (row.getAttribute('data-learning-schedule-search') || '').toLowerCase();
          const rowStatus = (row.getAttribute('data-learning-schedule-status') || '').toLowerCase();
          const matchesSearch = !searchText || rowSearch.includes(searchText);
          const matchesStatus = !selectedStatus || rowStatus === selectedStatus;
          if (matchesSearch && matchesStatus) {
            matchedRows.push(row);
          }
          row.classList.add('hidden');
        });

        const pageSize = getLearningSchedulePageSize();
        const totalPages = Math.max(1, Math.ceil(matchedRows.length / pageSize));
        if (learningScheduleCurrentPage > totalPages) {
          learningScheduleCurrentPage = totalPages;
        }

        const start = (learningScheduleCurrentPage - 1) * pageSize;
        const pagedRows = matchedRows.slice(start, start + pageSize);
        pagedRows.forEach((row) => row.classList.remove('hidden'));

        if (learningSchedulePageInfo) {
          learningSchedulePageInfo.textContent = `Page ${learningScheduleCurrentPage} of ${totalPages}`;
        }
        if (learningSchedulePrevPage) {
          learningSchedulePrevPage.disabled = learningScheduleCurrentPage <= 1;
        }
        if (learningScheduleNextPage) {
          learningScheduleNextPage.disabled = learningScheduleCurrentPage >= totalPages;
        }
      };

      learningScheduleSearch.addEventListener('input', () => {
        learningScheduleCurrentPage = 1;
        applyPlainLearningScheduleFilter();
      });
      learningScheduleStatusFilter.addEventListener('change', () => {
        learningScheduleCurrentPage = 1;
        applyPlainLearningScheduleFilter();
      });
      learningSchedulePageSize?.addEventListener('change', () => {
        learningScheduleCurrentPage = 1;
        applyPlainLearningScheduleFilter();
      });
      learningSchedulePrevPage?.addEventListener('click', () => {
        if (learningScheduleCurrentPage <= 1) return;
        learningScheduleCurrentPage -= 1;
        applyPlainLearningScheduleFilter();
      });
      learningScheduleNextPage?.addEventListener('click', () => {
        learningScheduleCurrentPage += 1;
        applyPlainLearningScheduleFilter();
      });

      applyPlainLearningScheduleFilter();
    }

    if (
      learningAttendanceTable
      && learningAttendanceSearch
      && learningAttendanceStatusFilter
      && learningAttendanceTable.dataset.datatableInitialized !== 'true'
    ) {
      const learningAttendanceRows = Array.from(learningAttendanceTable.querySelectorAll('tbody tr')).filter((row) => !row.querySelector('td[colspan]'));
      let learningAttendanceCurrentPage = 1;

      const getLearningAttendancePageSize = () => {
        const value = Number(learningAttendancePageSize?.value || 10);
        return [10, 25, 50].includes(value) ? value : 10;
      };

      const applyPlainLearningAttendanceFilter = () => {
        const searchText = (learningAttendanceSearch.value || '').trim().toLowerCase();
        const selectedStatus = (learningAttendanceStatusFilter.value || '').trim().toLowerCase();
        const matchedRows = [];

        learningAttendanceRows.forEach((row) => {
          const rowSearch = (row.getAttribute('data-learning-attendance-search') || '').toLowerCase();
          const rowStatus = (row.getAttribute('data-learning-attendance-status') || '').toLowerCase();
          const matchesSearch = !searchText || rowSearch.includes(searchText);
          const matchesStatus = !selectedStatus || rowStatus === selectedStatus;
          if (matchesSearch && matchesStatus) {
            matchedRows.push(row);
          }
          row.classList.add('hidden');
        });

        const pageSize = getLearningAttendancePageSize();
        const totalPages = Math.max(1, Math.ceil(matchedRows.length / pageSize));
        if (learningAttendanceCurrentPage > totalPages) {
          learningAttendanceCurrentPage = totalPages;
        }

        const start = (learningAttendanceCurrentPage - 1) * pageSize;
        const pagedRows = matchedRows.slice(start, start + pageSize);
        pagedRows.forEach((row) => row.classList.remove('hidden'));

        if (learningAttendancePageInfo) {
          learningAttendancePageInfo.textContent = `Page ${learningAttendanceCurrentPage} of ${totalPages}`;
        }
        if (learningAttendancePrevPage) {
          learningAttendancePrevPage.disabled = learningAttendanceCurrentPage <= 1;
        }
        if (learningAttendanceNextPage) {
          learningAttendanceNextPage.disabled = learningAttendanceCurrentPage >= totalPages;
        }
      };

      learningAttendanceSearch.addEventListener('input', () => {
        learningAttendanceCurrentPage = 1;
        applyPlainLearningAttendanceFilter();
      });
      learningAttendanceStatusFilter.addEventListener('change', () => {
        learningAttendanceCurrentPage = 1;
        applyPlainLearningAttendanceFilter();
      });
      learningAttendancePageSize?.addEventListener('change', () => {
        learningAttendanceCurrentPage = 1;
        applyPlainLearningAttendanceFilter();
      });
      learningAttendancePrevPage?.addEventListener('click', () => {
        if (learningAttendanceCurrentPage <= 1) return;
        learningAttendanceCurrentPage -= 1;
        applyPlainLearningAttendanceFilter();
      });
      learningAttendanceNextPage?.addEventListener('click', () => {
        learningAttendanceCurrentPage += 1;
        applyPlainLearningAttendanceFilter();
      });

      applyPlainLearningAttendanceFilter();
    }

    if (
      praiseRatingsTable
      && praiseRatingsSearch
      && praiseRatingsStatusFilter
      && praiseRatingsTable.dataset.datatableInitialized !== 'true'
    ) {
      const praiseRatingRows = Array.from(praiseRatingsTable.querySelectorAll('tbody tr')).filter((row) => !row.querySelector('td[colspan]'));

      const applyPlainPraiseRatingsFilter = () => {
        const searchText = (praiseRatingsSearch.value || '').trim().toLowerCase();
        const selectedStatus = (praiseRatingsStatusFilter.value || '').trim().toLowerCase();

        praiseRatingRows.forEach((row) => {
          const rowSearch = (row.getAttribute('data-praise-rating-search') || '').toLowerCase();
          const rowStatus = (row.getAttribute('data-praise-rating-status') || '').toLowerCase();
          const matchesSearch = !searchText || rowSearch.includes(searchText);
          const matchesStatus = !selectedStatus || rowStatus === selectedStatus;
          row.classList.toggle('hidden', !(matchesSearch && matchesStatus));
        });
      };

      praiseRatingsSearch.addEventListener('input', applyPlainPraiseRatingsFilter);
      praiseRatingsStatusFilter.addEventListener('change', applyPlainPraiseRatingsFilter);
    }

    if (
      praiseNominationsTable
      && praiseNominationsSearch
      && praiseNominationsTable.dataset.datatableInitialized !== 'true'
    ) {
      const praiseNominationRows = Array.from(praiseNominationsTable.querySelectorAll('tbody tr')).filter((row) => !row.querySelector('td[colspan]'));

      const applyPlainPraiseNominationsFilter = () => {
        const searchText = (praiseNominationsSearch.value || '').trim().toLowerCase();

        praiseNominationRows.forEach((row) => {
          const rowSearch = (row.getAttribute('data-praise-nomination-search') || '').toLowerCase();
          const matchesSearch = !searchText || rowSearch.includes(searchText);
          row.classList.toggle('hidden', !matchesSearch);
        });
      };

      praiseNominationsSearch.addEventListener('input', applyPlainPraiseNominationsFilter);
    }

    if (
      praisePerformanceReportsTable
      && praisePerformanceSearch
      && praisePerformancePeriodFilter
      && praisePerformanceReportsTable.dataset.datatableInitialized !== 'true'
    ) {
      const performanceRows = Array.from(praisePerformanceReportsTable.querySelectorAll('tbody tr')).filter((row) => !row.querySelector('td[colspan]'));

      const applyPlainPraisePerformanceFilter = () => {
        const searchText = (praisePerformanceSearch.value || '').trim().toLowerCase();
        const selectedPeriod = (praisePerformancePeriodFilter.value || '').trim().toLowerCase();

        performanceRows.forEach((row) => {
          const rowSearch = (row.getAttribute('data-praise-performance-search') || '').toLowerCase();
          const rowPeriod = (row.getAttribute('data-praise-performance-period') || '').toLowerCase();
          const matchesSearch = !searchText || rowSearch.includes(searchText);
          const matchesPeriod = !selectedPeriod || rowPeriod === selectedPeriod;
          row.classList.toggle('hidden', !(matchesSearch && matchesPeriod));
        });
      };

      praisePerformanceSearch.addEventListener('input', applyPlainPraisePerformanceFilter);
      praisePerformancePeriodFilter.addEventListener('change', applyPlainPraisePerformanceFilter);
    }

    if (
      praiseRecognitionHistoryTable
      && praiseRecognitionSearch
      && praiseRecognitionCycleFilter
      && praiseRecognitionHistoryTable.dataset.datatableInitialized !== 'true'
    ) {
      const recognitionRows = Array.from(praiseRecognitionHistoryTable.querySelectorAll('tbody tr')).filter((row) => !row.querySelector('td[colspan]'));

      const applyPlainPraiseRecognitionFilter = () => {
        const searchText = (praiseRecognitionSearch.value || '').trim().toLowerCase();
        const selectedCycle = (praiseRecognitionCycleFilter.value || '').trim().toLowerCase();

        recognitionRows.forEach((row) => {
          const rowSearch = (row.getAttribute('data-praise-recognition-search') || '').toLowerCase();
          const rowCycle = (row.getAttribute('data-praise-recognition-cycle') || '').toLowerCase();
          const matchesSearch = !searchText || rowSearch.includes(searchText);
          const matchesCycle = !selectedCycle || rowCycle === selectedCycle;
          row.classList.toggle('hidden', !(matchesSearch && matchesCycle));
        });
      };

      praiseRecognitionSearch.addEventListener('input', applyPlainPraiseRecognitionFilter);
      praiseRecognitionCycleFilter.addEventListener('change', applyPlainPraiseRecognitionFilter);
    }

    const exportVisibleTableToCsv = (tableElement, filename) => {
      if (!tableElement) {
        return;
      }

      const headers = Array.from(tableElement.querySelectorAll('thead th')).map((header) => (`"${(header.textContent || '').trim().replace(/"/g, '""')}"`));
      const rows = Array.from(tableElement.querySelectorAll('tbody tr')).filter((row) => {
        if (row.classList.contains('hidden')) {
          return false;
        }
        return !row.querySelector('td[colspan]');
      });

      const lines = [];
      if (headers.length > 0) {
        lines.push(headers.join(','));
      }

      rows.forEach((row) => {
        const cells = Array.from(row.querySelectorAll('td')).map((cell) => (`"${(cell.textContent || '').trim().replace(/"/g, '""')}"`));
        lines.push(cells.join(','));
      });

      const csvContent = lines.join('\n');
      const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
      const url = URL.createObjectURL(blob);
      const anchor = document.createElement('a');
      anchor.href = url;
      anchor.download = filename;
      document.body.appendChild(anchor);
      anchor.click();
      document.body.removeChild(anchor);
      URL.revokeObjectURL(url);
    };

    const exportVisibleTableToPdf = (tableElement, title) => {
      if (!tableElement) {
        return;
      }

      const headers = Array.from(tableElement.querySelectorAll('thead th')).map((header) => (header.textContent || '').trim());
      const rows = Array.from(tableElement.querySelectorAll('tbody tr')).filter((row) => {
        if (row.classList.contains('hidden')) {
          return false;
        }
        return !row.querySelector('td[colspan]');
      }).map((row) => Array.from(row.querySelectorAll('td')).map((cell) => (cell.textContent || '').trim()));

      const headerHtml = headers.map((header) => `<th>${header}</th>`).join('');
      const bodyHtml = rows.map((cells) => `<tr>${cells.map((cell) => `<td>${cell.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')}</td>`).join('')}</tr>`).join('');

      const printWindow = window.open('', '_blank', 'width=1100,height=800');
      if (!printWindow) {
        return;
      }

      printWindow.document.write(`
        <html>
          <head>
            <title>${title}</title>
            <style>
              body { font-family: Arial, sans-serif; margin: 24px; color: #0f172a; }
              h1 { font-size: 20px; margin: 0 0 16px; }
              table { width: 100%; border-collapse: collapse; font-size: 12px; }
              th, td { border: 1px solid #cbd5e1; padding: 8px; text-align: left; vertical-align: top; }
              th { background: #f1f5f9; }
              .meta { font-size: 11px; color: #475569; margin-bottom: 12px; }
            </style>
          </head>
          <body>
            <h1>${title}</h1>
            <div class="meta">Generated on ${new Date().toLocaleString()}</div>
            <table>
              <thead><tr>${headerHtml}</tr></thead>
              <tbody>${bodyHtml}</tbody>
            </table>
          </body>
        </html>
      `);
      printWindow.document.close();
      printWindow.focus();
      printWindow.print();
      printWindow.close();
    };

    praisePerformanceExportBtn?.addEventListener('click', () => {
      exportVisibleTableToCsv(praisePerformanceReportsTable, 'praise-performance-summary.csv');
    });

    praiseRecognitionExportBtn?.addEventListener('click', () => {
      exportVisibleTableToCsv(praiseRecognitionHistoryTable, 'praise-recognition-history.csv');
    });

    praisePerformanceExportPdfBtn?.addEventListener('click', () => {
      exportVisibleTableToPdf(praisePerformanceReportsTable, 'PRAISE Performance Summary');
    });

    praiseRecognitionExportPdfBtn?.addEventListener('click', () => {
      exportVisibleTableToPdf(praiseRecognitionHistoryTable, 'PRAISE Recognition History');
    });

    if (
      profileLoginHistoryTable
      && profileLoginSearch
      && profileLoginEventFilter
      && profileLoginHistoryTable.dataset.datatableInitialized !== 'true'
    ) {
      const profileLoginRows = Array.from(profileLoginHistoryTable.querySelectorAll('tbody tr')).filter((row) => !row.querySelector('td[colspan]'));

      const applyPlainProfileLoginFilter = () => {
        const searchText = (profileLoginSearch.value || '').trim().toLowerCase();
        const selectedEvent = (profileLoginEventFilter.value || '').trim().toLowerCase();

        profileLoginRows.forEach((row) => {
          const rowSearch = (row.getAttribute('data-profile-login-search') || '').toLowerCase();
          const rowEvent = (row.getAttribute('data-profile-login-event') || '').toLowerCase();
          const matchesSearch = !searchText || rowSearch.includes(searchText);
          const matchesEvent = !selectedEvent || rowEvent === selectedEvent;
          row.classList.toggle('hidden', !(matchesSearch && matchesEvent));
        });
      };

      profileLoginSearch.addEventListener('input', applyPlainProfileLoginFilter);
      profileLoginEventFilter.addEventListener('change', applyPlainProfileLoginFilter);
    }

    if (
      dashboardPendingLeaveTable
      && dashboardLeaveSearch
      && dashboardLeaveStatusFilter
      && dashboardPendingLeaveTable.dataset.datatableInitialized !== 'true'
    ) {
      const leaveRows = Array.from(dashboardPendingLeaveTable.querySelectorAll('tbody tr')).filter((row) => !row.querySelector('td[colspan]'));

      const applyPlainDashboardLeaveFilter = () => {
        const searchText = (dashboardLeaveSearch.value || '').trim().toLowerCase();
        const selectedStatus = (dashboardLeaveStatusFilter.value || '').trim().toLowerCase();

        leaveRows.forEach((row) => {
          const rowSearch = (row.getAttribute('data-dashboard-leave-search') || '').toLowerCase();
          const rowStatus = (row.getAttribute('data-dashboard-leave-status') || '').toLowerCase();
          const matchesSearch = !searchText || rowSearch.includes(searchText);
          const matchesStatus = !selectedStatus || rowStatus === selectedStatus;
          row.classList.toggle('hidden', !(matchesSearch && matchesStatus));
        });
      };

      dashboardLeaveSearch.addEventListener('input', applyPlainDashboardLeaveFilter);
      dashboardLeaveStatusFilter.addEventListener('change', applyPlainDashboardLeaveFilter);
    }

    if (
      dashboardNotificationsTable
      && dashboardNotificationsSearch
      && dashboardNotificationsStatusFilter
      && dashboardNotificationsTable.dataset.datatableInitialized !== 'true'
    ) {
      const dashboardNotificationRows = Array.from(dashboardNotificationsTable.querySelectorAll('tbody tr')).filter((row) => !row.querySelector('td[colspan]'));

      const applyPlainDashboardNotificationsFilter = () => {
        const searchText = (dashboardNotificationsSearch.value || '').trim().toLowerCase();
        const selectedStatus = (dashboardNotificationsStatusFilter.value || '').trim().toLowerCase();

        dashboardNotificationRows.forEach((row) => {
          const rowSearch = (row.getAttribute('data-dashboard-notification-search') || '').toLowerCase();
          const rowStatus = (row.getAttribute('data-dashboard-notification-status') || '').toLowerCase();
          const matchesSearch = !searchText || rowSearch.includes(searchText);
          const matchesStatus = !selectedStatus || rowStatus === selectedStatus;
          row.classList.toggle('hidden', !(matchesSearch && matchesStatus));
        });
      };

      dashboardNotificationsSearch.addEventListener('input', applyPlainDashboardNotificationsFilter);
      dashboardNotificationsStatusFilter.addEventListener('change', applyPlainDashboardNotificationsFilter);
    }

    if (
      dashboardDepartmentTable
      && dashboardDepartmentSearch
      && dashboardDepartmentTable.dataset.datatableInitialized !== 'true'
    ) {
      const dashboardDepartmentRows = Array.from(dashboardDepartmentTable.querySelectorAll('tbody tr')).filter((row) => !row.querySelector('td[colspan]'));

      const applyPlainDashboardDepartmentFilter = () => {
        const searchText = (dashboardDepartmentSearch.value || '').trim().toLowerCase();

        dashboardDepartmentRows.forEach((row) => {
          const rowSearch = (row.getAttribute('data-dashboard-department-search') || '').toLowerCase();
          const matchesSearch = !searchText || rowSearch.includes(searchText);
          row.classList.toggle('hidden', !matchesSearch);
        });
      };

      dashboardDepartmentSearch.addEventListener('input', applyPlainDashboardDepartmentFilter);
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

    const payrollEmployeePickerTable = document.getElementById('payrollEmployeePickerTable');
    const payrollEmployeePickerSearch = document.getElementById('payrollEmployeePickerSearch');
    const payrollEmployeePickerStatusFilter = document.getElementById('payrollEmployeePickerStatusFilter');

    if (
      payrollEmployeePickerTable
      && payrollEmployeePickerSearch
      && payrollEmployeePickerStatusFilter
      && payrollEmployeePickerTable.dataset.datatableInitialized !== 'true'
    ) {
      const payrollEmployeePickerRows = Array.from(payrollEmployeePickerTable.querySelectorAll('tbody tr')).filter((row) => !row.querySelector('td[colspan]'));

      const applyPayrollEmployeePickerFilter = () => {
        const searchText = (payrollEmployeePickerSearch.value || '').trim().toLowerCase();
        const selectedStatus = (payrollEmployeePickerStatusFilter.value || '').trim().toLowerCase();

        payrollEmployeePickerRows.forEach((row) => {
          const rowSearch = (row.getAttribute('data-payroll-employee-picker-search') || '').toLowerCase();
          const rowStatus = (row.getAttribute('data-payroll-employee-picker-status') || '').toLowerCase();
          const matchesSearch = !searchText || rowSearch.includes(searchText);
          const matchesStatus = !selectedStatus || rowStatus === selectedStatus;
          row.classList.toggle('hidden', !(matchesSearch && matchesStatus));
        });
      };

      payrollEmployeePickerSearch.addEventListener('input', applyPayrollEmployeePickerFilter);
      payrollEmployeePickerStatusFilter.addEventListener('change', applyPayrollEmployeePickerFilter);
    }

    const payrollSetupLogsTable = document.getElementById('payrollSetupLogsTable');
    const payrollSetupLogsSearch = document.getElementById('payrollSetupLogsSearch');
    const payrollSetupLogsStatusFilter = document.getElementById('payrollSetupLogsStatusFilter');
    const payrollSetupLogsPageSize = document.getElementById('payrollSetupLogsPageSize');
    const payrollSetupLogsPageInfo = document.getElementById('payrollSetupLogsPageInfo');
    const payrollSetupLogsPrev = document.getElementById('payrollSetupLogsPrev');
    const payrollSetupLogsNext = document.getElementById('payrollSetupLogsNext');
    const payrollSetupLogsSelectAll = document.getElementById('payrollSetupLogsSelectAll');
    const payrollBulkDeleteSetupForm = document.getElementById('payrollBulkDeleteSetupForm');
    const payrollBulkDeleteSetupButton = document.getElementById('payrollBulkDeleteSetupButton');

    if (
      payrollSetupLogsTable
      && payrollSetupLogsSearch
      && payrollSetupLogsStatusFilter
      && payrollSetupLogsPageSize
      && payrollSetupLogsPageInfo
      && payrollSetupLogsPrev
      && payrollSetupLogsNext
      && payrollSetupLogsTable.dataset.datatableInitialized !== 'true'
    ) {
      const payrollSetupLogRows = Array.from(payrollSetupLogsTable.querySelectorAll('tbody tr')).filter((row) => !row.querySelector('td[colspan]'));
      const payrollSetupLogCheckboxes = Array.from(payrollSetupLogsTable.querySelectorAll('[data-payroll-setup-log-select]'));
      let payrollSetupLogsCurrentPage = 1;

      const formatLogsPageInfo = (startIndex, endIndex, total) => {
        if (!total) {
          return 'Showing 0 of 0';
        }

        return `Showing ${startIndex}-${endIndex} of ${total}`;
      };

      const getFilteredPayrollSetupLogs = () => {
        const searchText = (payrollSetupLogsSearch.value || '').trim().toLowerCase();
        const selectedStatus = (payrollSetupLogsStatusFilter.value || '').trim().toLowerCase();

        return payrollSetupLogRows.filter((row) => {
          const rowSearch = (row.getAttribute('data-payroll-setup-log-search') || '').toLowerCase();
          const rowStatus = (row.getAttribute('data-payroll-setup-log-status') || '').toLowerCase();
          const matchesSearch = !searchText || rowSearch.includes(searchText);
          const matchesStatus = !selectedStatus || rowStatus === selectedStatus;
          return matchesSearch && matchesStatus;
        });
      };

      const renderPayrollSetupLogPage = () => {
        const pageSize = Math.max(1, parseInt(payrollSetupLogsPageSize.value, 10) || 10);
        const filteredRows = getFilteredPayrollSetupLogs();
        const totalRows = filteredRows.length;
        const totalPages = Math.max(1, Math.ceil(totalRows / pageSize));

        if (payrollSetupLogsCurrentPage > totalPages) {
          payrollSetupLogsCurrentPage = totalPages;
        }

        const start = (payrollSetupLogsCurrentPage - 1) * pageSize;
        const end = start + pageSize;
        const visibleRows = filteredRows.slice(start, end);

        payrollSetupLogRows.forEach((row) => {
          row.classList.toggle('hidden', !visibleRows.includes(row));
        });

        const startIndex = totalRows ? start + 1 : 0;
        const endIndex = totalRows ? Math.min(end, totalRows) : 0;
        payrollSetupLogsPageInfo.textContent = formatLogsPageInfo(startIndex, endIndex, totalRows);
        payrollSetupLogsPrev.disabled = payrollSetupLogsCurrentPage <= 1 || totalRows === 0;
        payrollSetupLogsNext.disabled = payrollSetupLogsCurrentPage >= totalPages || totalRows === 0;
        payrollSetupLogsPrev.classList.toggle('opacity-50', payrollSetupLogsPrev.disabled);
        payrollSetupLogsPrev.classList.toggle('cursor-not-allowed', payrollSetupLogsPrev.disabled);
        payrollSetupLogsNext.classList.toggle('opacity-50', payrollSetupLogsNext.disabled);
        payrollSetupLogsNext.classList.toggle('cursor-not-allowed', payrollSetupLogsNext.disabled);

        if (payrollSetupLogsSelectAll) {
          const visibleCheckboxes = visibleRows
            .map((row) => row.querySelector('[data-payroll-setup-log-select]'))
            .filter((checkbox) => checkbox instanceof HTMLInputElement);

          if (visibleCheckboxes.length === 0) {
            payrollSetupLogsSelectAll.checked = false;
            payrollSetupLogsSelectAll.indeterminate = false;
          } else {
            const visibleCheckedCount = visibleCheckboxes.filter((checkbox) => checkbox.checked).length;
            payrollSetupLogsSelectAll.checked = visibleCheckedCount === visibleCheckboxes.length;
            payrollSetupLogsSelectAll.indeterminate = visibleCheckedCount > 0 && visibleCheckedCount < visibleCheckboxes.length;
          }
        }

        if (payrollBulkDeleteSetupButton) {
          const selectedCount = payrollSetupLogCheckboxes.filter((checkbox) => checkbox.checked).length;
          payrollBulkDeleteSetupButton.disabled = selectedCount === 0;
          payrollBulkDeleteSetupButton.setAttribute('aria-disabled', selectedCount === 0 ? 'true' : 'false');
          payrollBulkDeleteSetupButton.innerHTML = `<span class="material-symbols-outlined text-[15px]">delete</span>${selectedCount > 0 ? `Delete Selected (${selectedCount})` : 'Delete Selected'}`;
        }
      };

      const resetPayrollSetupLogsPage = () => {
        payrollSetupLogsCurrentPage = 1;
        renderPayrollSetupLogPage();
      };

      payrollSetupLogsSearch.addEventListener('input', resetPayrollSetupLogsPage);
      payrollSetupLogsStatusFilter.addEventListener('change', resetPayrollSetupLogsPage);
      payrollSetupLogsPageSize.addEventListener('change', resetPayrollSetupLogsPage);
      payrollSetupLogsPrev.addEventListener('click', () => {
        if (payrollSetupLogsCurrentPage > 1) {
          payrollSetupLogsCurrentPage -= 1;
          renderPayrollSetupLogPage();
        }
      });
      payrollSetupLogsNext.addEventListener('click', () => {
        payrollSetupLogsCurrentPage += 1;
        renderPayrollSetupLogPage();
      });

      payrollSetupLogCheckboxes.forEach((checkbox) => {
        checkbox.addEventListener('change', () => {
          renderPayrollSetupLogPage();
        });
      });

      if (payrollSetupLogsSelectAll) {
        payrollSetupLogsSelectAll.addEventListener('change', () => {
          const visibleRows = payrollSetupLogRows.filter((row) => !row.classList.contains('hidden'));
          visibleRows.forEach((row) => {
            const checkbox = row.querySelector('[data-payroll-setup-log-select]');
            if (checkbox instanceof HTMLInputElement) {
              checkbox.checked = payrollSetupLogsSelectAll.checked;
            }
          });
          renderPayrollSetupLogPage();
        });
      }

      if (payrollBulkDeleteSetupForm) {
        payrollBulkDeleteSetupForm.addEventListener('submit', (event) => {
          const selectedCount = payrollSetupLogCheckboxes.filter((checkbox) => checkbox.checked).length;
          if (selectedCount === 0) {
            event.preventDefault();
            return;
          }

          event.preventDefault();

          const submitBulkDelete = () => payrollBulkDeleteSetupForm.submit();

          if (typeof window.Swal !== 'undefined') {
            window.Swal.fire({
              icon: 'warning',
              title: 'Delete selected salary setups?',
              text: `This will delete ${selectedCount} selected setup${selectedCount === 1 ? '' : 's'} and recalculate compensation timelines.`,
              showCancelButton: true,
              confirmButtonText: 'Delete selected',
              cancelButtonText: 'Cancel',
              confirmButtonColor: '#be123c'
            }).then((result) => {
              if (result.isConfirmed) {
                submitBulkDelete();
              }
            });
            return;
          }

          if (window.confirm(`Delete ${selectedCount} selected salary setup${selectedCount === 1 ? '' : 's'}?`)) {
            submitBulkDelete();
          }
        });
      }

      renderPayrollSetupLogPage();
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

    const personalInfoFamilyTable = document.getElementById('personalInfoFamilyTable');
    const personalInfoFamilySearch = document.getElementById('personalInfoFamilySearchInput');
    const personalInfoFamilyDepartment = document.getElementById('personalInfoFamilyDepartmentFilter');
    const personalInfoFamilyStatus = document.getElementById('personalInfoFamilyStatusFilter');

    if (
      personalInfoFamilyTable
      && personalInfoFamilySearch
      && personalInfoFamilyDepartment
      && personalInfoFamilyStatus
      && personalInfoFamilyTable.dataset.datatableInitialized !== 'true'
    ) {
      const familyRows = Array.from(personalInfoFamilyTable.querySelectorAll('tbody tr')).filter((row) => !row.querySelector('td[colspan]'));

      const applyPlainFamilyFilter = () => {
        const searchText = (personalInfoFamilySearch.value || '').trim().toLowerCase();
        const selectedDepartment = (personalInfoFamilyDepartment.value || '').trim().toLowerCase();
        const selectedStatus = (personalInfoFamilyStatus.value || '').trim().toLowerCase();

        familyRows.forEach((row) => {
          const rowSearch = (row.getAttribute('data-family-search') || '').toLowerCase();
          const rowDepartment = (row.getAttribute('data-family-department') || '').toLowerCase();
          const rowStatus = (row.getAttribute('data-family-status') || '').toLowerCase();

          const matchesSearch = !searchText || rowSearch.includes(searchText);
          const matchesDepartment = !selectedDepartment || rowDepartment === selectedDepartment;
          const matchesStatus = !selectedStatus || rowStatus === selectedStatus;

          row.classList.toggle('hidden', !(matchesSearch && matchesDepartment && matchesStatus));
        });
      };

      personalInfoFamilySearch.addEventListener('input', applyPlainFamilyFilter);
      personalInfoFamilyDepartment.addEventListener('change', applyPlainFamilyFilter);
      personalInfoFamilyStatus.addEventListener('change', applyPlainFamilyFilter);
    }

    const personalInfoEducationTable = document.getElementById('personalInfoEducationTable');
    const personalInfoEducationSearch = document.getElementById('personalInfoEducationSearchInput');
    const personalInfoEducationDepartment = document.getElementById('personalInfoEducationDepartmentFilter');
    const personalInfoEducationStatus = document.getElementById('personalInfoEducationStatusFilter');

    if (
      personalInfoEducationTable
      && personalInfoEducationSearch
      && personalInfoEducationDepartment
      && personalInfoEducationStatus
      && personalInfoEducationTable.dataset.datatableInitialized !== 'true'
    ) {
      const educationRows = Array.from(personalInfoEducationTable.querySelectorAll('tbody tr')).filter((row) => !row.querySelector('td[colspan]'));

      const applyPlainEducationFilter = () => {
        const searchText = (personalInfoEducationSearch.value || '').trim().toLowerCase();
        const selectedDepartment = (personalInfoEducationDepartment.value || '').trim().toLowerCase();
        const selectedStatus = (personalInfoEducationStatus.value || '').trim().toLowerCase();

        educationRows.forEach((row) => {
          const rowSearch = (row.getAttribute('data-education-search') || '').toLowerCase();
          const rowDepartment = (row.getAttribute('data-education-department') || '').toLowerCase();
          const rowStatus = (row.getAttribute('data-education-status') || '').toLowerCase();

          const matchesSearch = !searchText || rowSearch.includes(searchText);
          const matchesDepartment = !selectedDepartment || rowDepartment === selectedDepartment;
          const matchesStatus = !selectedStatus || rowStatus === selectedStatus;

          row.classList.toggle('hidden', !(matchesSearch && matchesDepartment && matchesStatus));
        });
      };

      personalInfoEducationSearch.addEventListener('input', applyPlainEducationFilter);
      personalInfoEducationDepartment.addEventListener('change', applyPlainEducationFilter);
      personalInfoEducationStatus.addEventListener('change', applyPlainEducationFilter);
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
    const documentViewerTitle = document.getElementById('documentViewerTitle');
    const documentViewerMeta = document.getElementById('documentViewerMeta');
    const documentViewerContent = document.getElementById('documentViewerContent');
    const documentViewerOpenLink = document.getElementById('documentViewerOpenLink');
    const uploaderDocumentsTitle = document.getElementById('uploaderDocumentsTitle');
    const uploaderDocumentsMeta = document.getElementById('uploaderDocumentsMeta');
    const uploaderDocumentsBody = document.getElementById('uploaderDocumentsBody');
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
    const openGeneratePayrollSummary = document.getElementById('openGeneratePayrollSummary');
    const generatePayrollPeriod = document.getElementById('generatePayrollPeriod');
    const generatePayrollPeriodIdConfirm = document.getElementById('generatePayrollPeriodIdConfirm');
    const generatePayrollPeriodLabelConfirm = document.getElementById('generatePayrollPeriodLabelConfirm');
    const generatePayrollPreviewEmpty = document.getElementById('generatePayrollPreviewEmpty');
    const generatePayrollPreviewContent = document.getElementById('generatePayrollPreviewContent');
    const generatePayrollPreviewEmployeeCount = document.getElementById('generatePayrollPreviewEmployeeCount');
    const generatePayrollPreviewGross = document.getElementById('generatePayrollPreviewGross');
    const generatePayrollPreviewNet = document.getElementById('generatePayrollPreviewNet');
    const generatePayrollPreviewBody = document.getElementById('generatePayrollPreviewBody');
    const generatePayrollConfirmButton = document.getElementById('generatePayrollConfirmButton');
    const generatePayrollPeriodHint = document.getElementById('generatePayrollPeriodHint');
    const generatePayrollPreviewQuickEmployees = document.getElementById('generatePayrollPreviewQuickEmployees');
    const generatePayrollPreviewQuickNet = document.getElementById('generatePayrollPreviewQuickNet');
    const generationPreviewByPeriodDataNode = document.getElementById('generationPreviewByPeriodData');
    const releasePayrollRunSelect = document.getElementById('releasePayrollRunSelect');
    const releasePayslipsSubmit = document.getElementById('releasePayslipsSubmit');
    const releasePayslipRunHint = document.getElementById('releasePayslipRunHint');
    const releasePayslipRunMeta = document.getElementById('releasePayslipRunMeta');
    const payrollSalarySetupForm = document.getElementById('payrollSalarySetupForm');
    const payrollEmployeeSearch = document.getElementById('payrollEmployeeSearch');
    const payrollEmployeeOptions = document.getElementById('payrollEmployeeOptions');
    const payrollSelectedPersonId = document.getElementById('payrollSelectedPersonId');
    const payrollPayFrequency = document.getElementById('payrollPayFrequency');
    const payrollBasePay = document.getElementById('payrollBasePay');
    const payrollOtherDeduction = document.getElementById('payrollOtherDeduction');
    const payrollComputedMonthlyRate = document.getElementById('payrollComputedMonthlyRate');
    const payrollEstimatedNetCycle = document.getElementById('payrollEstimatedNetCycle');
    const payrollEffectivityHint = document.getElementById('payrollEffectivityHint');
    const payrollEffectiveFromInput = document.querySelector('#payrollSalarySetupForm input[name="effective_from"]');
    const payrollAllowanceInput = document.querySelector('#payrollSalarySetupForm input[name="allowance_total"]');
    const payrollTaxInput = document.querySelector('#payrollSalarySetupForm input[name="tax_deduction"]');
    const payrollGovernmentInput = document.querySelector('#payrollSalarySetupForm input[name="government_deductions"]');
    const personalInfoProfileForm = document.getElementById('personalInfoProfileForm');
    const personalInfoProfileModalTitle = document.getElementById('personalInfoProfileModalTitle');
    const personalInfoProfileEmployeeLabel = document.getElementById('personalInfoProfileEmployeeLabel');
    const personalInfoProfileSubmit = document.getElementById('personalInfoProfileSubmit');
    const profileFirstName = document.getElementById('profileFirstName');
    const profileMiddleName = document.getElementById('profileMiddleName');
    const profileSurname = document.getElementById('profileSurname');
    const profileNameExtension = document.getElementById('profileNameExtension');
    const profileDateOfBirth = document.getElementById('profileDateOfBirth');
    const profilePlaceOfBirth = document.getElementById('profilePlaceOfBirth');
    const profileSexAtBirth = document.getElementById('profileSexAtBirth');
    const profileCivilStatus = document.getElementById('profileCivilStatus');
    const profileHeightM = document.getElementById('profileHeightM');
    const profileWeightKg = document.getElementById('profileWeightKg');
    const profileBloodType = document.getElementById('profileBloodType');
    const profileCitizenship = document.getElementById('profileCitizenship');
    const profileDualCitizenshipCountry = document.getElementById('profileDualCitizenshipCountry');
    const profileTelephoneNo = document.getElementById('profileTelephoneNo');
    const profileResidentialHouseNo = document.getElementById('profileResidentialHouseNo');
    const profileResidentialStreet = document.getElementById('profileResidentialStreet');
    const profileResidentialSubdivision = document.getElementById('profileResidentialSubdivision');
    const profileResidentialBarangay = document.getElementById('profileResidentialBarangay');
    const profileResidentialCity = document.getElementById('profileResidentialCity');
    const profileResidentialProvince = document.getElementById('profileResidentialProvince');
    const profileResidentialZipCode = document.getElementById('profileResidentialZipCode');
    const profilePermanentHouseNo = document.getElementById('profilePermanentHouseNo');
    const profilePermanentStreet = document.getElementById('profilePermanentStreet');
    const profilePermanentSubdivision = document.getElementById('profilePermanentSubdivision');
    const profilePermanentBarangay = document.getElementById('profilePermanentBarangay');
    const profilePermanentCity = document.getElementById('profilePermanentCity');
    const profilePermanentProvince = document.getElementById('profilePermanentProvince');
    const profilePermanentZipCode = document.getElementById('profilePermanentZipCode');
    const profileUmidNo = document.getElementById('profileUmidNo');
    const profilePagibigNo = document.getElementById('profilePagibigNo');
    const profilePhilhealthNo = document.getElementById('profilePhilhealthNo');
    const profilePsnNo = document.getElementById('profilePsnNo');
    const profileTinNo = document.getElementById('profileTinNo');
    const profileEmail = document.getElementById('profileEmail');
    const profileMobile = document.getElementById('profileMobile');
    const profileAgencyEmployeeNo = document.getElementById('profileAgencyEmployeeNo');
    const personalInfoFamilyForm = document.getElementById('personalInfoFamilyForm');
    const familyPersonId = document.getElementById('familyPersonId');
    const personalInfoFamilyEmployeeLabel = document.getElementById('personalInfoFamilyEmployeeLabel');
    const familySpouseSurname = document.getElementById('familySpouseSurname');
    const familySpouseFirstName = document.getElementById('familySpouseFirstName');
    const familySpouseMiddleName = document.getElementById('familySpouseMiddleName');
    const familySpouseExtensionName = document.getElementById('familySpouseExtensionName');
    const familySpouseOccupation = document.getElementById('familySpouseOccupation');
    const familySpouseEmployerBusinessName = document.getElementById('familySpouseEmployerBusinessName');
    const familySpouseBusinessAddress = document.getElementById('familySpouseBusinessAddress');
    const familySpouseTelephoneNo = document.getElementById('familySpouseTelephoneNo');
    const familyFatherSurname = document.getElementById('familyFatherSurname');
    const familyFatherFirstName = document.getElementById('familyFatherFirstName');
    const familyFatherMiddleName = document.getElementById('familyFatherMiddleName');
    const familyFatherExtensionName = document.getElementById('familyFatherExtensionName');
    const familyMotherSurname = document.getElementById('familyMotherSurname');
    const familyMotherFirstName = document.getElementById('familyMotherFirstName');
    const familyMotherMiddleName = document.getElementById('familyMotherMiddleName');
    const familyMotherExtensionName = document.getElementById('familyMotherExtensionName');
    const personalInfoFamilyAddChildButton = document.getElementById('personalInfoFamilyAddChildButton');
    const personalInfoFamilyChildrenTableBody = document.getElementById('personalInfoFamilyChildrenTableBody');
    const personalInfoFamilyChildRowTemplate = document.getElementById('personalInfoFamilyChildRowTemplate');
    const personalInfoEducationForm = document.getElementById('personalInfoEducationForm');
    const educationPersonId = document.getElementById('educationPersonId');
    const personalInfoEducationEmployeeLabel = document.getElementById('personalInfoEducationEmployeeLabel');
    const profilePersonId = document.getElementById('profilePersonId');
    const profileAction = document.getElementById('profileAction');
    const personalInfoAssignmentPersonId = document.getElementById('personalInfoAssignmentPersonId');
    const personalInfoStatusPersonId = document.getElementById('personalInfoStatusPersonId');
    const personalInfoAssignmentEmployeeDisplay = document.getElementById('personalInfoAssignmentEmployeeDisplay');
    const personalInfoStatusEmployeeDisplay = document.getElementById('personalInfoStatusEmployeeDisplay');
    const personalInfoEligibilityForm = document.getElementById('personalInfoEligibilityForm');
    const personalInfoEligibilityAction = document.getElementById('personalInfoEligibilityAction');
    const personalInfoEligibilityId = document.getElementById('personalInfoEligibilityId');
    const personalInfoEligibilityPersonId = document.getElementById('personalInfoEligibilityPersonId');
    const personalInfoEligibilityEmployeeLabel = document.getElementById('personalInfoEligibilityEmployeeLabel');
    const personalInfoEligibilityName = document.getElementById('personalInfoEligibilityName');
    const personalInfoEligibilityRating = document.getElementById('personalInfoEligibilityRating');
    const personalInfoEligibilityExamDate = document.getElementById('personalInfoEligibilityExamDate');
    const personalInfoEligibilityExamPlace = document.getElementById('personalInfoEligibilityExamPlace');
    const personalInfoEligibilityLicenseNo = document.getElementById('personalInfoEligibilityLicenseNo');
    const personalInfoEligibilityLicenseValidity = document.getElementById('personalInfoEligibilityLicenseValidity');
    const personalInfoEligibilitySequence = document.getElementById('personalInfoEligibilitySequence');
    const personalInfoEligibilitySubmit = document.getElementById('personalInfoEligibilitySubmit');
    const personalInfoEligibilityResetButton = document.getElementById('personalInfoEligibilityResetButton');
    const personalInfoEligibilityTableBody = document.getElementById('personalInfoEligibilityTableBody');
    const personalInfoEligibilityDeleteForm = document.getElementById('personalInfoEligibilityDeleteForm');
    const personalInfoEligibilityDeletePersonId = document.getElementById('personalInfoEligibilityDeletePersonId');
    const personalInfoEligibilityDeleteId = document.getElementById('personalInfoEligibilityDeleteId');
    const personalInfoWorkExperienceForm = document.getElementById('personalInfoWorkExperienceForm');
    const personalInfoWorkExperienceAction = document.getElementById('personalInfoWorkExperienceAction');
    const personalInfoWorkExperienceId = document.getElementById('personalInfoWorkExperienceId');
    const personalInfoWorkExperiencePersonId = document.getElementById('personalInfoWorkExperiencePersonId');
    const personalInfoWorkExperienceEmployeeLabel = document.getElementById('personalInfoWorkExperienceEmployeeLabel');
    const personalInfoWorkExperienceDateFrom = document.getElementById('personalInfoWorkExperienceDateFrom');
    const personalInfoWorkExperienceDateTo = document.getElementById('personalInfoWorkExperienceDateTo');
    const personalInfoWorkExperiencePosition = document.getElementById('personalInfoWorkExperiencePosition');
    const personalInfoWorkExperienceOffice = document.getElementById('personalInfoWorkExperienceOffice');
    const personalInfoWorkExperienceSalary = document.getElementById('personalInfoWorkExperienceSalary');
    const personalInfoWorkExperienceSalaryGrade = document.getElementById('personalInfoWorkExperienceSalaryGrade');
    const personalInfoWorkExperienceAppointmentStatus = document.getElementById('personalInfoWorkExperienceAppointmentStatus');
    const personalInfoWorkExperienceGovernment = document.getElementById('personalInfoWorkExperienceGovernment');
    const personalInfoWorkExperienceSeparationReason = document.getElementById('personalInfoWorkExperienceSeparationReason');
    const personalInfoWorkExperienceAchievements = document.getElementById('personalInfoWorkExperienceAchievements');
    const personalInfoWorkExperienceSequence = document.getElementById('personalInfoWorkExperienceSequence');
    const personalInfoWorkExperienceSubmit = document.getElementById('personalInfoWorkExperienceSubmit');
    const personalInfoWorkExperienceResetButton = document.getElementById('personalInfoWorkExperienceResetButton');
    const personalInfoWorkExperienceTableBody = document.getElementById('personalInfoWorkExperienceTableBody');
    const personalInfoWorkExperienceDeleteForm = document.getElementById('personalInfoWorkExperienceDeleteForm');
    const personalInfoWorkExperienceDeletePersonId = document.getElementById('personalInfoWorkExperienceDeletePersonId');
    const personalInfoWorkExperienceDeleteId = document.getElementById('personalInfoWorkExperienceDeleteId');
    const decisionApplicationId = document.getElementById('decisionApplicationId');
    const applicantProfileName = document.getElementById('applicantProfileName');
    const applicantProfileMeta = document.getElementById('applicantProfileMeta');
    const learningEnrollmentId = document.getElementById('learningEnrollmentId');
    const learningAttendanceTraining = document.getElementById('learningAttendanceTraining');
    const learningAttendanceEmployee = document.getElementById('learningAttendanceEmployee');
    const learningAttendanceCurrentStatus = document.getElementById('learningAttendanceCurrentStatus');
    const learningAttendanceNewStatus = document.getElementById('learningAttendanceNewStatus');
    const learningDetailsProgramCode = document.getElementById('learningDetailsProgramCode');
    const learningDetailsStatusPill = document.getElementById('learningDetailsStatusPill');
    const learningDetailsTitle = document.getElementById('learningDetailsTitle');
    const learningDetailsType = document.getElementById('learningDetailsType');
    const learningDetailsCategory = document.getElementById('learningDetailsCategory');
    const learningDetailsDate = document.getElementById('learningDetailsDate');
    const learningDetailsTime = document.getElementById('learningDetailsTime');
    const learningDetailsProvider = document.getElementById('learningDetailsProvider');
    const learningDetailsVenue = document.getElementById('learningDetailsVenue');
    const learningDetailsMode = document.getElementById('learningDetailsMode');
    const learningDetailsParticipantsCount = document.getElementById('learningDetailsParticipantsCount');
    const learningDetailsParticipantsList = document.getElementById('learningDetailsParticipantsList');
    const learningParticipantSearch = document.getElementById('learningParticipantSearch');
    const learningParticipantsSelectAll = document.getElementById('learningParticipantsSelectAll');
    const learningParticipantsClearAll = document.getElementById('learningParticipantsClearAll');
    const learningParticipantChecklist = document.getElementById('learningParticipantChecklist');
    const praiseEvaluationId = document.getElementById('praiseEvaluationId');
    const praiseEvaluationEmployee = document.getElementById('praiseEvaluationEmployee');
    const praiseEvaluationCycle = document.getElementById('praiseEvaluationCycle');
    const praiseEvaluationDecision = document.getElementById('praiseEvaluationDecision');
    const praiseEvaluationRemarks = document.getElementById('praiseEvaluationRemarks');
    const praiseRatingModalTitle = document.getElementById('praiseRatingModalTitle');
    const praiseRatingDecisionHint = document.getElementById('praiseRatingDecisionHint');
    const praiseRatingSubmitBtn = document.getElementById('praiseRatingSubmitBtn');
    const praiseAwardCategoryFormAction = document.getElementById('praiseAwardCategoryFormAction');
    const praiseAwardCategoryId = document.getElementById('praiseAwardCategoryId');
    const praiseAwardCategoryModalTitle = document.getElementById('praiseAwardCategoryModalTitle');
    const praiseAwardCategorySubmitBtn = document.getElementById('praiseAwardCategorySubmitBtn');
    const praiseAwardCategoryName = document.getElementById('praiseAwardCategoryName');
    const praiseAwardCategoryCode = document.getElementById('praiseAwardCategoryCode');
    const praiseAwardCategoryDescription = document.getElementById('praiseAwardCategoryDescription');
    const praiseAwardCategoryCriteria = document.getElementById('praiseAwardCategoryCriteria');
    const praiseAwardCategoryStatus = document.getElementById('praiseAwardCategoryStatus');
    const praiseAwardNewCategoryBtn = document.getElementById('praiseAwardNewCategoryBtn');
    const praiseNominationId = document.getElementById('praiseNominationId');
    const praiseNomineeName = document.getElementById('praiseNomineeName');
    const praiseNominationAward = document.getElementById('praiseNominationAward');
    const praisePublishNominationId = document.getElementById('praisePublishNominationId');
    const praisePublishAwardeeName = document.getElementById('praisePublishAwardeeName');
    const praisePublishAwardName = document.getElementById('praisePublishAwardName');
    const dashboardLeaveRequestId = document.getElementById('dashboardLeaveRequestId');
    const dashboardLeaveEmployeeName = document.getElementById('dashboardLeaveEmployeeName');
    const dashboardLeaveCurrentStatus = document.getElementById('dashboardLeaveCurrentStatus');
    const dashboardLeaveDateRange = document.getElementById('dashboardLeaveDateRange');
    const dashboardNotificationId = document.getElementById('dashboardNotificationId');
    const dashboardNotificationTitle = document.getElementById('dashboardNotificationTitle');
    const recruitmentEditPostingId = document.getElementById('recruitmentEditPostingId');
    const recruitmentEditTitle = document.getElementById('recruitmentEditTitle');
    const recruitmentEditOfficeId = document.getElementById('recruitmentEditOfficeId');
    const recruitmentEditPositionId = document.getElementById('recruitmentEditPositionId');
    const recruitmentEditDescription = document.getElementById('recruitmentEditDescription');
    const recruitmentEditQualifications = document.getElementById('recruitmentEditQualifications');
    const recruitmentEditResponsibilities = document.getElementById('recruitmentEditResponsibilities');
    const recruitmentEditOpenDate = document.getElementById('recruitmentEditOpenDate');
    const recruitmentEditCloseDate = document.getElementById('recruitmentEditCloseDate');
    const recruitmentEditStatus = document.getElementById('recruitmentEditStatus');
    const recruitmentArchivePostingId = document.getElementById('recruitmentArchivePostingId');
    const recruitmentArchivePostingTitle = document.getElementById('recruitmentArchivePostingTitle');

    const pdsTabButtons = Array.from(document.querySelectorAll('[data-pds-tab-target]'));
    const pdsButtonBaseClasses = ['flex-1', 'px-4', 'py-2.5', 'text-sm', 'font-medium', 'text-center', 'border-b-2', 'transition-colors', 'duration-150', 'bg-white'];
    const pdsActiveClasses = ['border-slate-900', 'text-slate-900'];
    const pdsInactiveClasses = ['border-transparent', 'text-slate-500', 'hover:text-slate-700', 'hover:border-slate-300'];

    const setPdsTabActive = (section) => {
      pdsTabButtons.forEach((button) => {
        const isActive = (button.getAttribute('data-pds-tab-target') || '') === section;
        button.className = [
          ...pdsButtonBaseClasses,
          ...(isActive ? pdsActiveClasses : pdsInactiveClasses)
        ].join(' ');
      });
    };

    const openPdsSectionForPerson = (section, personId) => {
      const safePersonId = (personId || '').trim();
      if (!safePersonId) {
        return false;
      }

      let selector = '';
      if (section === 'section_i') {
        selector = `[data-person-profile-open][data-person-id="${safePersonId}"]`;
      } else if (section === 'section_ii') {
        selector = `[data-person-family-open][data-person-id="${safePersonId}"]`;
      } else if (section === 'section_iii') {
        selector = `[data-person-education-open][data-person-id="${safePersonId}"]`;
      }

      if (!selector) {
        return false;
      }

      const targetButton = document.querySelector(selector);
      if (!targetButton) {
        return false;
      }

      targetButton.click();
      return true;
    };

    pdsTabButtons.forEach((button) => {
      button.addEventListener('click', () => {
        const targetSection = button.getAttribute('data-pds-tab-target') || '';
        if (!targetSection) {
          return;
        }

        const modal = button.closest('[data-modal]');
        if (!modal) {
          return;
        }

        const modalId = modal.getAttribute('id') || '';
        const currentSection = modalId === 'personalInfoProfileModal'
          ? 'section_i'
          : (modalId === 'personalInfoFamilyModal' ? 'section_ii' : (modalId === 'personalInfoEducationModal' ? 'section_iii' : ''));

        if (targetSection === currentSection) {
          setPdsTabActive(targetSection);
          return;
        }

        let personId = '';
        if (modalId === 'personalInfoProfileModal') {
          personId = profilePersonId?.value || '';
        } else if (modalId === 'personalInfoFamilyModal') {
          personId = familyPersonId?.value || '';
        } else if (modalId === 'personalInfoEducationModal') {
          personId = educationPersonId?.value || '';
        }

        if (!openPdsSectionForPerson(targetSection, personId)) {
          if (window.Swal) {
            Swal.fire({
              icon: 'info',
              title: 'Unable to switch section',
              text: 'Please open this employee from the target section table first.',
              confirmButtonColor: '#0f172a'
            });
          }
        }
      });
    });

    document.querySelectorAll('[data-applicant-open]').forEach((button) => {
      button.addEventListener('click', () => {
        const applicationId = button.getAttribute('data-application-id') || '';
        const applicantName = button.getAttribute('data-applicant-name') || 'Applicant';
        const applicantEmail = button.getAttribute('data-applicant-email') || '-';
        const applicantPosition = button.getAttribute('data-applicant-position') || '-';
        const applicantSubmitted = button.getAttribute('data-applicant-submitted') || '-';
        const applicantScreening = button.getAttribute('data-applicant-screening') || '-';

        if (decisionApplicationId) {
          decisionApplicationId.value = applicationId;
          decisionApplicationId.dispatchEvent(new Event('change'));
        }

        if (applicantProfileName) {
          applicantProfileName.textContent = applicantName;
        }

        if (applicantProfileMeta) {
          applicantProfileMeta.textContent = `${applicantPosition} • ${applicantEmail} • Submitted ${applicantSubmitted} • ${applicantScreening}`;
        }

        openModal('applicantDecisionModal');
      });
    });

    document.querySelectorAll('[data-learning-attendance-update]').forEach((button) => {
      button.addEventListener('click', () => {
        const enrollmentId = button.getAttribute('data-enrollment-id') || '';
        const trainingTitle = button.getAttribute('data-training-title') || '';
        const employeeName = button.getAttribute('data-employee-name') || '';
        const currentStatus = (button.getAttribute('data-current-status') || '').trim().toLowerCase();

        const attendanceStatusLabel = (status) => {
          if (status === 'completed') return 'Present';
          if (status === 'enrolled') return 'Enrolled';
          if (status === 'failed') return 'Absent';
          if (status === 'dropped') return 'Dropped';
          return status ? status.charAt(0).toUpperCase() + status.slice(1) : '';
        };

        if (learningEnrollmentId) learningEnrollmentId.value = enrollmentId;
        if (learningAttendanceTraining) learningAttendanceTraining.value = trainingTitle;
        if (learningAttendanceEmployee) learningAttendanceEmployee.value = employeeName;

        if (learningAttendanceCurrentStatus) {
          learningAttendanceCurrentStatus.value = attendanceStatusLabel(currentStatus);
        }

        if (learningAttendanceNewStatus) {
          learningAttendanceNewStatus.value = currentStatus || 'enrolled';
        }

        openModal('learningAttendanceModal');
      });
    });

    document.querySelectorAll('[data-learning-training-view]').forEach((button) => {
      button.addEventListener('click', () => {
        const statusLabel = button.getAttribute('data-status') || '-';

        if (learningDetailsProgramCode) learningDetailsProgramCode.textContent = button.getAttribute('data-program-code') || '-';
        if (learningDetailsTitle) learningDetailsTitle.textContent = button.getAttribute('data-title') || '-';
        if (learningDetailsType) learningDetailsType.textContent = button.getAttribute('data-training-type') || '-';
        if (learningDetailsCategory) learningDetailsCategory.textContent = button.getAttribute('data-training-category') || '-';
        if (learningDetailsDate) learningDetailsDate.textContent = button.getAttribute('data-schedule-date') || '-';
        if (learningDetailsTime) learningDetailsTime.textContent = button.getAttribute('data-schedule-time') || '-';
        if (learningDetailsProvider) learningDetailsProvider.textContent = button.getAttribute('data-provider') || '-';
        if (learningDetailsVenue) learningDetailsVenue.textContent = button.getAttribute('data-venue') || '-';
        if (learningDetailsMode) learningDetailsMode.textContent = button.getAttribute('data-mode') || '-';
        if (learningDetailsParticipantsCount) learningDetailsParticipantsCount.textContent = button.getAttribute('data-participants-count') || '0';

        if (learningDetailsStatusPill) {
          learningDetailsStatusPill.textContent = statusLabel;
          learningDetailsStatusPill.className = 'px-2.5 py-1 text-xs rounded-full bg-slate-100 text-slate-700';

          const normalizedStatus = statusLabel.trim().toLowerCase();
          if (normalizedStatus === 'completed') {
            learningDetailsStatusPill.className = 'px-2.5 py-1 text-xs rounded-full bg-emerald-100 text-emerald-800';
          } else if (normalizedStatus === 'in progress' || normalizedStatus === 'planned' || normalizedStatus === 'open' || normalizedStatus === 'ongoing') {
            learningDetailsStatusPill.className = 'px-2.5 py-1 text-xs rounded-full bg-blue-100 text-blue-800';
          } else if (normalizedStatus === 'needs review' || normalizedStatus === 'cancelled' || normalizedStatus === 'failed') {
            learningDetailsStatusPill.className = 'px-2.5 py-1 text-xs rounded-full bg-amber-100 text-amber-800';
          } else if (normalizedStatus === 'dropped') {
            learningDetailsStatusPill.className = 'px-2.5 py-1 text-xs rounded-full bg-rose-100 text-rose-800';
          }
        }

        if (learningDetailsParticipantsList) {
          const participantsRaw = button.getAttribute('data-participants-list') || '';
          const participants = participantsRaw
            .split(',')
            .map((name) => name.trim())
            .filter((name) => name !== '' && name !== 'No participants enrolled yet.');

          learningDetailsParticipantsList.innerHTML = '';

          if (participants.length === 0) {
            const emptyChip = document.createElement('span');
            emptyChip.className = 'px-2 py-1 text-xs rounded bg-slate-100 text-slate-700';
            emptyChip.textContent = 'No participants enrolled yet.';
            learningDetailsParticipantsList.appendChild(emptyChip);
          } else {
            participants.forEach((participantName) => {
              const chip = document.createElement('span');
              chip.className = 'px-2 py-1 text-xs rounded bg-slate-100 text-slate-700';
              chip.textContent = participantName;
              learningDetailsParticipantsList.appendChild(chip);
            });
          }
        }

        openModal('learningTrainingDetailsModal');
      });
    });

    if (learningParticipantChecklist) {
      const participantItems = Array.from(learningParticipantChecklist.querySelectorAll('[data-learning-participant-item]'));

      const applyParticipantSearch = () => {
        const searchText = (learningParticipantSearch?.value || '').trim().toLowerCase();

        participantItems.forEach((item) => {
          const haystack = (item.getAttribute('data-learning-participant-search') || '').toLowerCase();
          const matches = !searchText || haystack.includes(searchText);
          item.classList.toggle('hidden', !matches);
        });
      };

      learningParticipantSearch?.addEventListener('input', applyParticipantSearch);

      learningParticipantsSelectAll?.addEventListener('click', () => {
        participantItems.forEach((item) => {
          if (item.classList.contains('hidden')) {
            return;
          }

          const checkbox = item.querySelector('input[type="checkbox"]');
          if (checkbox) {
            checkbox.checked = true;
          }
        });
      });

      learningParticipantsClearAll?.addEventListener('click', () => {
        participantItems.forEach((item) => {
          const checkbox = item.querySelector('input[type="checkbox"]');
          if (checkbox) {
            checkbox.checked = false;
          }
        });
      });
    }

    const syncPraiseRatingDecisionFlow = () => {
      const selectedDecision = (praiseEvaluationDecision?.value || '').toLowerCase();
      const returningForReview = selectedDecision === 'reviewed';

      if (praiseRatingModalTitle) {
        praiseRatingModalTitle.textContent = returningForReview ? 'Return Supervisor Rating for Review' : 'Approve Supervisor Rating';
      }

      if (praiseRatingDecisionHint) {
        praiseRatingDecisionHint.textContent = returningForReview
          ? 'Returning for review requires remarks to guide the supervisor.'
          : 'Approving finalizes this supervisor rating for reporting.';
      }

      if (praiseEvaluationRemarks) {
        praiseEvaluationRemarks.required = returningForReview;
        praiseEvaluationRemarks.placeholder = returningForReview
          ? 'Add required feedback for supervisor revision.'
          : 'Optional note for the employee file.';
      }

      if (praiseRatingSubmitBtn) {
        praiseRatingSubmitBtn.textContent = returningForReview ? 'Return for Review' : 'Save Decision';
      }
    };

    praiseEvaluationDecision?.addEventListener('change', syncPraiseRatingDecisionFlow);

    document.addEventListener('click', (event) => {
      const button = event.target.closest('[data-praise-rating-open]');
      if (!button) {
        return;
      }

      const evaluationId = button.getAttribute('data-evaluation-id') || '';
      const employeeName = button.getAttribute('data-employee-name') || '';
      const cycleName = button.getAttribute('data-cycle-name') || '';
      const currentStatus = (button.getAttribute('data-current-status') || '').trim().toLowerCase();

      if (praiseEvaluationId) praiseEvaluationId.value = evaluationId;
      if (praiseEvaluationEmployee) praiseEvaluationEmployee.value = employeeName;
      if (praiseEvaluationCycle) praiseEvaluationCycle.value = cycleName;
      if (praiseEvaluationDecision) {
        praiseEvaluationDecision.value = currentStatus === 'reviewed' ? 'reviewed' : 'approved';
      }
      if (praiseEvaluationRemarks) {
        praiseEvaluationRemarks.value = '';
        praiseEvaluationRemarks.required = false;
      }

      syncPraiseRatingDecisionFlow();

      openModal('praiseRatingModal');

      if (praiseEvaluationDecision) {
        praiseEvaluationDecision.focus();
      }
    });

    const resetPraiseAwardCategoryForm = () => {
      if (praiseAwardCategoryFormAction) praiseAwardCategoryFormAction.value = 'create_award_category';
      if (praiseAwardCategoryId) praiseAwardCategoryId.value = '';
      if (praiseAwardCategoryModalTitle) praiseAwardCategoryModalTitle.textContent = 'Create Award Category';
      if (praiseAwardCategorySubmitBtn) praiseAwardCategorySubmitBtn.textContent = 'Save Category';
      if (praiseAwardCategoryName) praiseAwardCategoryName.value = '';
      if (praiseAwardCategoryCode) praiseAwardCategoryCode.value = '';
      if (praiseAwardCategoryDescription) praiseAwardCategoryDescription.value = '';
      if (praiseAwardCategoryCriteria) praiseAwardCategoryCriteria.value = '';
      if (praiseAwardCategoryStatus) praiseAwardCategoryStatus.value = '1';
    };

    praiseAwardNewCategoryBtn?.addEventListener('click', () => {
      resetPraiseAwardCategoryForm();
    });

    document.querySelectorAll('[data-modal-close="praiseAwardCategoryModal"]').forEach((button) => {
      button.addEventListener('click', () => {
        resetPraiseAwardCategoryForm();
      });
    });

    document.querySelectorAll('[data-praise-award-edit]').forEach((button) => {
      button.addEventListener('click', () => {
        if (praiseAwardCategoryFormAction) praiseAwardCategoryFormAction.value = 'update_award_category';
        if (praiseAwardCategoryId) praiseAwardCategoryId.value = button.getAttribute('data-award-id') || '';
        if (praiseAwardCategoryModalTitle) praiseAwardCategoryModalTitle.textContent = 'Edit Award Category';
        if (praiseAwardCategorySubmitBtn) praiseAwardCategorySubmitBtn.textContent = 'Update Category';
        if (praiseAwardCategoryName) praiseAwardCategoryName.value = button.getAttribute('data-award-name') || '';
        if (praiseAwardCategoryCode) praiseAwardCategoryCode.value = button.getAttribute('data-award-code') || '';
        if (praiseAwardCategoryDescription) praiseAwardCategoryDescription.value = button.getAttribute('data-award-description') || '';
        if (praiseAwardCategoryCriteria) praiseAwardCategoryCriteria.value = button.getAttribute('data-award-criteria') || '';
        if (praiseAwardCategoryStatus) praiseAwardCategoryStatus.value = button.getAttribute('data-award-active') === '0' ? '0' : '1';

        openModal('praiseAwardCategoryModal');
      });
    });

    document.addEventListener('click', (event) => {
      const button = event.target.closest('[data-dashboard-leave-open]');
      if (!button) {
        return;
      }

      const requestId = button.getAttribute('data-leave-request-id') || '';
      const employeeName = button.getAttribute('data-employee-name') || '';
      const currentStatus = button.getAttribute('data-current-status') || '';
      const dateRange = button.getAttribute('data-date-range') || '';

      if (dashboardLeaveRequestId) dashboardLeaveRequestId.value = requestId;
      if (dashboardLeaveEmployeeName) dashboardLeaveEmployeeName.value = employeeName;
      if (dashboardLeaveCurrentStatus) dashboardLeaveCurrentStatus.value = currentStatus;
      if (dashboardLeaveDateRange) dashboardLeaveDateRange.value = dateRange;

      openModal('dashboardLeaveReviewModal');
    });

    document.addEventListener('click', (event) => {
      const button = event.target.closest('[data-dashboard-notification-open]');
      if (!button) {
        return;
      }

      const notificationId = button.getAttribute('data-notification-id') || '';
      const title = button.getAttribute('data-notification-title') || '';

      if (dashboardNotificationId) dashboardNotificationId.value = notificationId;
      if (dashboardNotificationTitle) dashboardNotificationTitle.textContent = title || '-';

      openModal('dashboardNotificationModal');
    });

    document.addEventListener('click', (event) => {
      const button = event.target.closest('[data-recruitment-job-edit]');
      if (!button) {
        return;
      }

      if (recruitmentEditPostingId) recruitmentEditPostingId.value = button.getAttribute('data-posting-id') || '';
      if (recruitmentEditTitle) recruitmentEditTitle.value = button.getAttribute('data-title') || '';
      if (recruitmentEditOfficeId) recruitmentEditOfficeId.value = button.getAttribute('data-office-id') || '';
      if (recruitmentEditPositionId) recruitmentEditPositionId.value = button.getAttribute('data-position-id') || '';
      if (recruitmentEditDescription) recruitmentEditDescription.value = button.getAttribute('data-description') || '';
      if (recruitmentEditQualifications) recruitmentEditQualifications.value = button.getAttribute('data-qualifications') || '';
      if (recruitmentEditResponsibilities) recruitmentEditResponsibilities.value = button.getAttribute('data-responsibilities') || '';
      if (recruitmentEditOpenDate) recruitmentEditOpenDate.value = button.getAttribute('data-open-date') || '';
      if (recruitmentEditCloseDate) recruitmentEditCloseDate.value = button.getAttribute('data-close-date') || '';
      if (recruitmentEditStatus) recruitmentEditStatus.value = button.getAttribute('data-posting-status') || 'draft';

      openModal('recruitmentEditJobModal');
    });

    document.addEventListener('click', (event) => {
      const button = event.target.closest('[data-recruitment-job-archive]');
      if (!button) {
        return;
      }

      if (recruitmentArchivePostingId) recruitmentArchivePostingId.value = button.getAttribute('data-posting-id') || '';
      if (recruitmentArchivePostingTitle) recruitmentArchivePostingTitle.textContent = button.getAttribute('data-title') || '-';

      openModal('recruitmentArchiveJobModal');
    });

    document.querySelectorAll('[data-praise-nomination-open]').forEach((button) => {
      button.addEventListener('click', () => {
        const nominationId = button.getAttribute('data-nomination-id') || '';
        const nomineeName = button.getAttribute('data-nominee-name') || '';
        const awardName = button.getAttribute('data-award-name') || '';

        if (praiseNominationId) praiseNominationId.value = nominationId;
        if (praiseNomineeName) praiseNomineeName.value = nomineeName;
        if (praiseNominationAward) praiseNominationAward.value = awardName;

        openModal('praiseNominationModal');
      });
    });

    document.querySelectorAll('[data-praise-publish-open]').forEach((button) => {
      button.addEventListener('click', () => {
        const nominationId = button.getAttribute('data-nomination-id') || '';
        const awardeeName = button.getAttribute('data-awardee-name') || '';
        const awardName = button.getAttribute('data-award-name') || '';

        if (praisePublishNominationId) praisePublishNominationId.value = nominationId;
        if (praisePublishAwardeeName) praisePublishAwardeeName.value = awardeeName;
        if (praisePublishAwardName) praisePublishAwardName.value = awardName;

        openModal('praisePublishModal');
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

    const closeDocumentActionMenus = () => {
      document.querySelectorAll('[data-doc-action-menu]').forEach((menu) => {
        menu.classList.add('hidden');
      });
    };

    document.querySelectorAll('[data-doc-action-menu-toggle]').forEach((toggle) => {
      toggle.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();

        const scope = toggle.closest('[data-doc-action-scope]');
        const menu = scope?.querySelector('[data-doc-action-menu]');
        if (!menu) {
          return;
        }

        const willOpen = menu.classList.contains('hidden');
        closeDocumentActionMenus();
        if (willOpen) {
          menu.classList.remove('hidden');
        }
      });
    });

    document.addEventListener('click', (event) => {
      if (!event.target.closest('[data-doc-action-scope]')) {
        closeDocumentActionMenus();
      }
    });

    const escapeHtml = (value) => String(value || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');

    const renderDocumentPreview = (url, extension, title) => {
      if (!documentViewerContent) {
        return;
      }

      const ext = (extension || '').toLowerCase();
      const safeUrl = escapeHtml(url);
      const safeTitle = escapeHtml(title);

      const imageExts = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'];
      const textExts = ['txt', 'csv', 'json', 'xml', 'md', 'log', 'sql', 'php', 'js', 'ts', 'html', 'css'];
      const officeExts = ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'];
      const mediaExts = ['mp4', 'webm', 'ogg', 'mp3', 'wav'];

      const looksPrivateHost = (host) => {
        const normalized = (host || '').toLowerCase();
        if (!normalized) {
          return true;
        }

        if (normalized === 'localhost' || normalized === '127.0.0.1' || normalized === '::1') {
          return true;
        }

        if (/^10\./.test(normalized) || /^192\.168\./.test(normalized)) {
          return true;
        }

        const private172 = normalized.match(/^172\.(\d{1,3})\./);
        if (private172) {
          const secondOctet = Number(private172[1]);
          if (secondOctet >= 16 && secondOctet <= 31) {
            return true;
          }
        }

        return false;
      };

      const canUseOfficeWebViewer = (rawUrl) => {
        if (!rawUrl) {
          return false;
        }

        try {
          const parsed = new URL(rawUrl, window.location.origin);
          if (!['http:', 'https:'].includes(parsed.protocol)) {
            return false;
          }
          return !looksPrivateHost(parsed.hostname);
        } catch (_error) {
          return false;
        }
      };

      if (!url) {
        documentViewerContent.innerHTML = '<div class="text-center text-slate-500">Document file path is not available for preview.</div>';
        return;
      }

      if (ext === 'pdf') {
        documentViewerContent.innerHTML = `<iframe src="${safeUrl}" class="w-full h-full min-h-[60vh] rounded-lg border border-slate-200 bg-white" frameborder="0" title="${safeTitle}"></iframe>`;
        return;
      }

      if (imageExts.includes(ext)) {
        documentViewerContent.innerHTML = `<img src="${safeUrl}" alt="${safeTitle}" class="max-h-full max-w-full w-auto mx-auto rounded-lg border border-slate-200 bg-white">`;
        return;
      }

      if (textExts.includes(ext)) {
        documentViewerContent.innerHTML = `<iframe src="${safeUrl}" class="w-full h-full min-h-[60vh] rounded-lg border border-slate-200 bg-white" frameborder="0" title="${safeTitle}"></iframe>`;
        return;
      }

      if (officeExts.includes(ext)) {
        const extLabel = escapeHtml(ext.toUpperCase());
        if (canUseOfficeWebViewer(url)) {
          const officeViewerUrl = `https://view.officeapps.live.com/op/embed.aspx?src=${encodeURIComponent(url)}`;
          documentViewerContent.innerHTML = `<div class="space-y-3">
            <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-xs text-slate-600">If preview does not load, use <a href="${safeUrl}" target="_blank" rel="noopener" class="font-semibold text-emerald-700 hover:underline">Download/Open File</a>.</div>
            <iframe src="${officeViewerUrl}" class="w-full h-full min-h-[60vh] rounded-lg border border-slate-200 bg-white" frameborder="0" title="${safeTitle}"></iframe>
          </div>`;
          return;
        }

        documentViewerContent.innerHTML = `<div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-800 space-y-2">
          <p class="font-semibold">${extLabel} preview is not available in this environment.</p>
          <p>Office files usually require a public URL for web preview. Download the file to view it locally.</p>
          <a href="${safeUrl}" target="_blank" rel="noopener" class="inline-flex items-center rounded-md bg-amber-600 px-3 py-2 text-xs font-semibold text-white hover:bg-amber-700">Download/Open File</a>
        </div>`;
        return;
      }

      if (mediaExts.includes(ext)) {
        if (['mp4', 'webm', 'ogg'].includes(ext)) {
          documentViewerContent.innerHTML = `<video controls class="w-full h-full min-h-[60vh] rounded-lg border border-slate-200 bg-black"><source src="${safeUrl}">Your browser does not support video preview.</video>`;
          return;
        }

        documentViewerContent.innerHTML = `<audio controls class="w-full max-w-2xl"><source src="${safeUrl}">Your browser does not support audio preview.</audio>`;
        return;
      }

      documentViewerContent.innerHTML = '<div class="text-center text-slate-500">Preview is not supported for this file type. Use Open in New Tab to view or download.</div>';
    };

    document.querySelectorAll('[data-doc-view]').forEach((button) => {
      button.addEventListener('click', () => {
        closeDocumentActionMenus();
        const documentTitle = button.getAttribute('data-document-title') || 'Document Viewer';
        const documentUrl = button.getAttribute('data-document-url') || '';
        const extension = button.getAttribute('data-document-extension') || '';
        const bucket = button.getAttribute('data-document-bucket') || '-';
        const path = button.getAttribute('data-document-path') || '-';

        if (documentViewerTitle) {
          documentViewerTitle.textContent = documentTitle;
        }
        if (documentViewerMeta) {
          const extLabel = extension ? extension.toUpperCase() : 'UNKNOWN';
          documentViewerMeta.textContent = `Type: ${extLabel} • ${bucket}/${path}`;
        }
        if (documentViewerOpenLink) {
          documentViewerOpenLink.setAttribute('href', documentUrl || '#');
          documentViewerOpenLink.classList.toggle('pointer-events-none', !documentUrl);
          documentViewerOpenLink.classList.toggle('opacity-50', !documentUrl);
        }

        renderDocumentPreview(documentUrl, extension, documentTitle);
        openModal('documentViewerModal');
      });
    });

    document.querySelectorAll('[data-modal-close="documentViewerModal"]').forEach((button) => {
      button.addEventListener('click', () => {
        if (documentViewerContent) {
          documentViewerContent.innerHTML = '<div class="text-sm text-slate-500">Select a document to preview.</div>';
        }
      });
    });

    document.querySelectorAll('[data-doc-uploader-open]').forEach((button) => {
      button.addEventListener('click', () => {
        const uploaderEmail = button.getAttribute('data-uploader-email') || 'Uploader';
        const uploaderType = button.getAttribute('data-uploader-type') || 'Unknown';
        const documentsJson = button.getAttribute('data-uploader-documents') || '[]';

        let documents = [];
        try {
          const parsed = JSON.parse(documentsJson);
          if (Array.isArray(parsed)) {
            documents = parsed;
          }
        } catch (_error) {
          documents = [];
        }

        if (uploaderDocumentsTitle) {
          uploaderDocumentsTitle.textContent = uploaderEmail;
        }
        if (uploaderDocumentsMeta) {
          uploaderDocumentsMeta.textContent = `Account Type: ${uploaderType} • Total Documents: ${documents.length}`;
        }

        if (uploaderDocumentsBody) {
          if (documents.length === 0) {
            uploaderDocumentsBody.innerHTML = '<tr><td class="px-4 py-3 text-slate-500" colspan="5">No documents found for this uploader.</td></tr>';
          } else {
            uploaderDocumentsBody.innerHTML = documents.map((doc) => {
              const title = escapeHtml(doc.title || '-');
              const category = escapeHtml(doc.category || 'Uncategorized');
              const status = escapeHtml((doc.status || 'draft').replace(/_/g, ' '));
              const updated = escapeHtml(doc.updated || '-');
              const bucket = escapeHtml(doc.storage_bucket || '-');
              const path = escapeHtml(doc.storage_path || '-');
              return `<tr>
                <td class="px-4 py-3 text-slate-800 font-medium">${title}</td>
                <td class="px-4 py-3 text-slate-700">${category}</td>
                <td class="px-4 py-3 text-slate-700">${status}</td>
                <td class="px-4 py-3 text-slate-700">${updated}</td>
                <td class="px-4 py-3 text-slate-500 text-xs">${bucket}/${path}</td>
              </tr>`;
            }).join('');
          }
        }

        openModal('uploaderDocumentsModal');
      });
    });

    document.querySelectorAll('[data-doc-review]').forEach((button) => {
      button.addEventListener('click', () => {
        closeDocumentActionMenus();
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
        closeDocumentActionMenus();
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

    if (openGeneratePayrollSummary && generatePayrollPeriod) {
      let generationPreviewByPeriod = {};

      if (generationPreviewByPeriodDataNode) {
        try {
          generationPreviewByPeriod = JSON.parse(generationPreviewByPeriodDataNode.textContent || '{}');
        } catch (error) {
          generationPreviewByPeriod = {};
        }
      }

      const formatPayrollMoney = (value) => {
        const numeric = Number(value) || 0;
        return new Intl.NumberFormat('en-PH', {
          style: 'currency',
          currency: 'PHP',
          minimumFractionDigits: 2,
          maximumFractionDigits: 2
        }).format(numeric);
      };

      const escapeHtml = (value) => String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');

      const renderGeneratePayrollPreview = (periodId) => {
        if (!generatePayrollPreviewEmpty || !generatePayrollPreviewContent || !generatePayrollConfirmButton) {
          return;
        }

        const periodData = generationPreviewByPeriod[String(periodId)] || { totals: null, rows: [] };
        const rows = Array.isArray(periodData.rows) ? periodData.rows : [];
        const totals = periodData.totals && typeof periodData.totals === 'object'
          ? periodData.totals
          : {
              employee_count: rows.length,
              gross_pay: rows.reduce((sum, row) => sum + (Number(row.gross_pay) || 0), 0),
              net_pay: rows.reduce((sum, row) => sum + (Number(row.net_pay) || 0), 0)
            };

        const hasRows = rows.length > 0;
        generatePayrollPreviewEmpty.classList.toggle('hidden', hasRows);
        generatePayrollPreviewContent.classList.toggle('hidden', !hasRows);
        generatePayrollConfirmButton.disabled = !hasRows;
        generatePayrollConfirmButton.setAttribute('aria-disabled', hasRows ? 'false' : 'true');

        if (!hasRows) {
          if (generatePayrollPreviewBody) {
            generatePayrollPreviewBody.innerHTML = '';
          }
          return;
        }

        if (generatePayrollPreviewEmployeeCount) {
          generatePayrollPreviewEmployeeCount.textContent = String(Number(totals.employee_count) || rows.length);
        }
        if (generatePayrollPreviewGross) {
          generatePayrollPreviewGross.textContent = formatPayrollMoney(totals.gross_pay);
        }
        if (generatePayrollPreviewNet) {
          generatePayrollPreviewNet.textContent = formatPayrollMoney(totals.net_pay);
        }
        if (generatePayrollPreviewBody) {
          generatePayrollPreviewBody.innerHTML = rows.map((row) => {
            const employeeName = escapeHtml(row.employee_name || 'Unknown Employee');
            const payFrequencyRaw = String(row.pay_frequency || 'semi_monthly');
            const payFrequency = escapeHtml(payFrequencyRaw
              .replace(/_/g, ' ')
              .replace(/\b\w/g, (character) => character.toUpperCase()));
            return `<tr>
              <td class="px-4 py-3 text-slate-700">${employeeName}</td>
              <td class="px-4 py-3 text-slate-600">${payFrequency}</td>
              <td class="px-4 py-3 text-right text-slate-700">${formatPayrollMoney(row.gross_pay)}</td>
              <td class="px-4 py-3 text-right text-slate-800 font-medium">${formatPayrollMoney(row.net_pay)}</td>
            </tr>`;
          }).join('');
        }
      };

      const syncGeneratePayrollSummaryState = () => {
        const selectedPeriodId = (generatePayrollPeriod.value || '').trim();
        const selectedOption = generatePayrollPeriod.options[generatePayrollPeriod.selectedIndex] || null;

        const hasSelection = selectedPeriodId !== '';
        const periodData = hasSelection
          ? (generationPreviewByPeriod[String(selectedPeriodId)] || { totals: null, rows: [] })
          : { totals: null, rows: [] };
        const rows = Array.isArray(periodData.rows) ? periodData.rows : [];
        const totals = periodData.totals && typeof periodData.totals === 'object'
          ? periodData.totals
          : {
              employee_count: rows.length,
              gross_pay: rows.reduce((sum, row) => sum + (Number(row.gross_pay) || 0), 0),
              net_pay: rows.reduce((sum, row) => sum + (Number(row.net_pay) || 0), 0)
            };

        openGeneratePayrollSummary.disabled = !hasSelection;
        openGeneratePayrollSummary.setAttribute('aria-disabled', hasSelection ? 'false' : 'true');

        if (!hasSelection) {
          if (generatePayrollPeriodHint) {
            generatePayrollPeriodHint.textContent = 'Select a period to review estimated employees and totals before generation.';
          }
          if (generatePayrollPreviewQuickEmployees) {
            generatePayrollPreviewQuickEmployees.textContent = 'No period selected';
          }
          if (generatePayrollPreviewQuickNet) {
            generatePayrollPreviewQuickNet.textContent = 'Net: ₱0.00';
          }
          return;
        }

        const employeeCount = Number(totals.employee_count) || rows.length;
        const netPay = Number(totals.net_pay) || 0;
        const label = selectedOption ? (selectedOption.text || '').trim() : 'Selected period';

        if (generatePayrollPeriodHint) {
          generatePayrollPeriodHint.textContent = employeeCount > 0
            ? `${employeeCount} employee(s) eligible for ${label}.`
            : `No active employee salary setup is available for ${label}.`;
        }
        if (generatePayrollPreviewQuickEmployees) {
          generatePayrollPreviewQuickEmployees.textContent = `${employeeCount} employee(s) estimated`;
        }
        if (generatePayrollPreviewQuickNet) {
          generatePayrollPreviewQuickNet.textContent = `Net: ${formatPayrollMoney(netPay)}`;
        }
      };

      generatePayrollPeriod.addEventListener('change', syncGeneratePayrollSummaryState);
      syncGeneratePayrollSummaryState();

      openGeneratePayrollSummary.addEventListener('click', () => {
        const selectedPeriodId = (generatePayrollPeriod.value || '').trim();
        const selectedOption = generatePayrollPeriod.options[generatePayrollPeriod.selectedIndex] || null;
        const selectedPeriodLabel = selectedOption ? (selectedOption.text || '').trim() : '';

        if (!selectedPeriodId) {
          if (typeof window.Swal !== 'undefined') {
            window.Swal.fire({
              icon: 'warning',
              title: 'Payroll period required',
              text: 'Please select a payroll period before reviewing summary.',
              confirmButtonColor: '#0f172a'
            });
          } else {
            window.alert('Please select a payroll period before reviewing summary.');
          }
          generatePayrollPeriod.focus();
          return;
        }

        if (generatePayrollPeriodIdConfirm) generatePayrollPeriodIdConfirm.value = selectedPeriodId;
        if (generatePayrollPeriodLabelConfirm) generatePayrollPeriodLabelConfirm.value = selectedPeriodLabel;
        renderGeneratePayrollPreview(selectedPeriodId);
        openModal('generatePayrollSummaryModal');
      });
    }

    if (releasePayrollRunSelect && releasePayslipsSubmit) {
      const formatPayrollMoney = (value) => {
        const numeric = Number(value) || 0;
        return new Intl.NumberFormat('en-PH', {
          style: 'currency',
          currency: 'PHP',
          minimumFractionDigits: 2,
          maximumFractionDigits: 2
        }).format(numeric);
      };

      const syncReleasePayslipState = () => {
        const selectedRunId = (releasePayrollRunSelect.value || '').trim();
        const selectedOption = releasePayrollRunSelect.options[releasePayrollRunSelect.selectedIndex] || null;
        const hasSelection = selectedRunId !== '';

        releasePayslipsSubmit.disabled = !hasSelection;
        releasePayslipsSubmit.setAttribute('aria-disabled', hasSelection ? 'false' : 'true');

        if (!hasSelection || !selectedOption) {
          if (releasePayslipRunHint) {
            releasePayslipRunHint.textContent = 'Select a payroll batch to review release details before sending payslips.';
          }
          if (releasePayslipRunMeta) {
            releasePayslipRunMeta.textContent = 'No payroll batch selected.';
          }
          return;
        }

        const periodCode = (selectedOption.getAttribute('data-period-code') || '').trim();
        const periodLabel = (selectedOption.getAttribute('data-period-label') || '').trim();
        const status = (selectedOption.getAttribute('data-status') || '').trim();
        const employeeCount = Number(selectedOption.getAttribute('data-employee-count') || 0);
        const totalNet = Number(selectedOption.getAttribute('data-total-net') || 0);

        if (releasePayslipRunHint) {
          releasePayslipRunHint.textContent = `${periodCode} • ${periodLabel} • ${status}`;
        }
        if (releasePayslipRunMeta) {
          releasePayslipRunMeta.textContent = `${employeeCount} employee(s) • Total net ${formatPayrollMoney(totalNet)}.`;
        }
      };

      releasePayrollRunSelect.addEventListener('change', syncReleasePayslipState);
      syncReleasePayslipState();
    }

    document.querySelectorAll('[data-person-profile-add]').forEach((button) => {
      button.addEventListener('click', () => {
        if (profileAction) profileAction.value = 'add';
        if (profilePersonId) profilePersonId.value = '';
        if (profileFirstName) profileFirstName.value = '';
        if (profileMiddleName) profileMiddleName.value = '';
        if (profileSurname) profileSurname.value = '';
        if (profileNameExtension) profileNameExtension.value = '';
        if (profileDateOfBirth) profileDateOfBirth.value = '';
        if (profilePlaceOfBirth) profilePlaceOfBirth.value = '';
        if (profileSexAtBirth) profileSexAtBirth.value = '';
        if (profileCivilStatus) profileCivilStatus.value = '';
        if (profileHeightM) profileHeightM.value = '';
        if (profileWeightKg) profileWeightKg.value = '';
        if (profileBloodType) profileBloodType.value = '';
        if (profileCitizenship) profileCitizenship.value = '';
        if (profileDualCitizenshipCountry) profileDualCitizenshipCountry.value = '';
        if (profileTelephoneNo) profileTelephoneNo.value = '';
        if (profileResidentialHouseNo) profileResidentialHouseNo.value = '';
        if (profileResidentialStreet) profileResidentialStreet.value = '';
        if (profileResidentialSubdivision) profileResidentialSubdivision.value = '';
        if (profileResidentialBarangay) profileResidentialBarangay.value = '';
        if (profileResidentialCity) profileResidentialCity.value = '';
        if (profileResidentialProvince) profileResidentialProvince.value = '';
        if (profileResidentialZipCode) profileResidentialZipCode.value = '';
        if (profilePermanentHouseNo) profilePermanentHouseNo.value = '';
        if (profilePermanentStreet) profilePermanentStreet.value = '';
        if (profilePermanentSubdivision) profilePermanentSubdivision.value = '';
        if (profilePermanentBarangay) profilePermanentBarangay.value = '';
        if (profilePermanentCity) profilePermanentCity.value = '';
        if (profilePermanentProvince) profilePermanentProvince.value = '';
        if (profilePermanentZipCode) profilePermanentZipCode.value = '';
        if (profileUmidNo) profileUmidNo.value = '';
        if (profilePagibigNo) profilePagibigNo.value = '';
        if (profilePhilhealthNo) profilePhilhealthNo.value = '';
        if (profilePsnNo) profilePsnNo.value = '';
        if (profileTinNo) profileTinNo.value = '';
        if (profileEmail) profileEmail.value = '';
        if (profileMobile) profileMobile.value = '';
        if (profileAgencyEmployeeNo) profileAgencyEmployeeNo.value = '';
        if (personalInfoProfileEmployeeLabel) personalInfoProfileEmployeeLabel.textContent = 'Selected employee';
        if (personalInfoProfileModalTitle) personalInfoProfileModalTitle.textContent = 'PDS Section I - Add Personal Information';
        if (personalInfoProfileSubmit) personalInfoProfileSubmit.textContent = 'Save Section I';
        setPdsTabActive('section_i');
        openModal('personalInfoProfileModal');
      });
    });

    document.querySelectorAll('[data-person-profile-open]').forEach((button) => {
      button.addEventListener('click', () => {
        const personId = button.getAttribute('data-person-id') || '';
        const employeeName = button.getAttribute('data-employee-name') || 'Selected employee';
        const firstName = button.getAttribute('data-first-name') || '';
        const middleName = button.getAttribute('data-middle-name') || '';
        const surname = button.getAttribute('data-surname') || '';
        const nameExtension = button.getAttribute('data-name-extension') || '';
        const dateOfBirth = button.getAttribute('data-date-of-birth') || '';
        const placeOfBirth = button.getAttribute('data-place-of-birth') || '';
        const sexAtBirth = button.getAttribute('data-sex-at-birth') || '';
        const civilStatus = button.getAttribute('data-civil-status') || '';
        const heightM = button.getAttribute('data-height-m') || '';
        const weightKg = button.getAttribute('data-weight-kg') || '';
        const bloodType = button.getAttribute('data-blood-type') || '';
        const citizenship = button.getAttribute('data-citizenship') || '';
        const dualCitizenshipCountry = button.getAttribute('data-dual-citizenship-country') || '';
        const telephoneNo = button.getAttribute('data-telephone-no') || '';
        const residentialHouseNo = button.getAttribute('data-residential-house-no') || '';
        const residentialStreet = button.getAttribute('data-residential-street') || '';
        const residentialSubdivision = button.getAttribute('data-residential-subdivision') || '';
        const residentialBarangay = button.getAttribute('data-residential-barangay') || '';
        const residentialCityMunicipality = button.getAttribute('data-residential-city-municipality') || '';
        const residentialProvince = button.getAttribute('data-residential-province') || '';
        const residentialZipCode = button.getAttribute('data-residential-zip-code') || '';
        const permanentHouseNo = button.getAttribute('data-permanent-house-no') || '';
        const permanentStreet = button.getAttribute('data-permanent-street') || '';
        const permanentSubdivision = button.getAttribute('data-permanent-subdivision') || '';
        const permanentBarangay = button.getAttribute('data-permanent-barangay') || '';
        const permanentCityMunicipality = button.getAttribute('data-permanent-city-municipality') || '';
        const permanentProvince = button.getAttribute('data-permanent-province') || '';
        const permanentZipCode = button.getAttribute('data-permanent-zip-code') || '';
        const umidNo = button.getAttribute('data-umid-no') || '';
        const pagibigNo = button.getAttribute('data-pagibig-no') || '';
        const philhealthNo = button.getAttribute('data-philhealth-no') || '';
        const psnNo = button.getAttribute('data-psn-no') || '';
        const tinNo = button.getAttribute('data-tin-no') || '';
        const email = button.getAttribute('data-email') || '';
        const mobile = button.getAttribute('data-mobile') || '';
        const agencyEmployeeNo = button.getAttribute('data-agency-employee-no') || '';

        if (profileFirstName) profileFirstName.value = firstName;
        if (profileMiddleName) profileMiddleName.value = middleName;
        if (profileSurname) profileSurname.value = surname;
        if (profileNameExtension) profileNameExtension.value = nameExtension;
        if (profileDateOfBirth) profileDateOfBirth.value = dateOfBirth;
        if (profilePlaceOfBirth) profilePlaceOfBirth.value = placeOfBirth;
        if (profileSexAtBirth) profileSexAtBirth.value = sexAtBirth;
        if (profileCivilStatus) profileCivilStatus.value = civilStatus;
        if (profileHeightM) profileHeightM.value = heightM;
        if (profileWeightKg) profileWeightKg.value = weightKg;
        if (profileBloodType) profileBloodType.value = bloodType;
        if (profileCitizenship) profileCitizenship.value = citizenship;
        if (profileDualCitizenshipCountry) profileDualCitizenshipCountry.value = dualCitizenshipCountry;
        if (profileTelephoneNo) profileTelephoneNo.value = telephoneNo;
        if (profileResidentialHouseNo) profileResidentialHouseNo.value = residentialHouseNo;
        if (profileResidentialStreet) profileResidentialStreet.value = residentialStreet;
        if (profileResidentialSubdivision) profileResidentialSubdivision.value = residentialSubdivision;
        if (profileResidentialBarangay) profileResidentialBarangay.value = residentialBarangay;
        if (profileResidentialCity) profileResidentialCity.value = residentialCityMunicipality;
        if (profileResidentialProvince) profileResidentialProvince.value = residentialProvince;
        if (profileResidentialZipCode) profileResidentialZipCode.value = residentialZipCode;
        if (profilePermanentHouseNo) profilePermanentHouseNo.value = permanentHouseNo;
        if (profilePermanentStreet) profilePermanentStreet.value = permanentStreet;
        if (profilePermanentSubdivision) profilePermanentSubdivision.value = permanentSubdivision;
        if (profilePermanentBarangay) profilePermanentBarangay.value = permanentBarangay;
        if (profilePermanentCity) profilePermanentCity.value = permanentCityMunicipality;
        if (profilePermanentProvince) profilePermanentProvince.value = permanentProvince;
        if (profilePermanentZipCode) profilePermanentZipCode.value = permanentZipCode;
        if (profileUmidNo) profileUmidNo.value = umidNo;
        if (profilePagibigNo) profilePagibigNo.value = pagibigNo;
        if (profilePhilhealthNo) profilePhilhealthNo.value = philhealthNo;
        if (profilePsnNo) profilePsnNo.value = psnNo;
        if (profileTinNo) profileTinNo.value = tinNo;
        if (profileEmail) profileEmail.value = email;
        if (profileMobile) profileMobile.value = mobile;
        if (profileAgencyEmployeeNo) profileAgencyEmployeeNo.value = agencyEmployeeNo;
        if (personalInfoProfileEmployeeLabel) personalInfoProfileEmployeeLabel.textContent = employeeName;
        if (profilePersonId) profilePersonId.value = personId;
        if (profileAction) profileAction.value = 'edit';
        if (personalInfoProfileModalTitle) personalInfoProfileModalTitle.textContent = 'PDS Section I - Edit Personal Information';
        if (personalInfoProfileSubmit) personalInfoProfileSubmit.textContent = 'Update Section I';
        setPdsTabActive('section_i');
        openModal('personalInfoProfileModal');
      });
    });

    const closePersonActionMenus = () => {
      document.querySelectorAll('[data-person-action-menu]').forEach((menu) => {
        menu.classList.add('hidden');
      });
    };

    document.querySelectorAll('[data-person-action-menu-toggle]').forEach((toggle) => {
      toggle.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();

        const scope = toggle.closest('[data-person-action-scope]');
        const menu = scope?.querySelector('[data-person-action-menu]');
        if (!menu) {
          return;
        }

        const willOpen = menu.classList.contains('hidden');
        closePersonActionMenus();
        if (willOpen) {
          menu.classList.remove('hidden');
        }
      });
    });

    document.querySelectorAll('[data-action-menu-item]').forEach((menuItem) => {
      menuItem.addEventListener('click', (event) => {
        event.preventDefault();
        const action = menuItem.getAttribute('data-action-target') || '';
        if (!action) {
          closePersonActionMenus();
          return;
        }

        const scope = menuItem.closest('[data-person-action-scope]');
        const trigger = scope?.querySelector(`[data-action-trigger="${action}"]`);
        closePersonActionMenus();
        if (trigger) {
          trigger.click();
        }
      });
    });

    document.addEventListener('click', (event) => {
      if (!event.target.closest('[data-person-action-scope]')) {
        closePersonActionMenus();
      }
    });

    if (personalInfoFamilyForm && familyPersonId) {
      const getChildrenRows = () => personalInfoFamilyChildrenTableBody
        ? Array.from(personalInfoFamilyChildrenTableBody.querySelectorAll('tr'))
        : [];

      const createChildRow = () => {
        if (!personalInfoFamilyChildrenTableBody) return null;

        if (personalInfoFamilyChildRowTemplate && personalInfoFamilyChildRowTemplate.content.firstElementChild) {
          return personalInfoFamilyChildRowTemplate.content.firstElementChild.cloneNode(true);
        }

        const fallbackRow = document.createElement('tr');
        fallbackRow.innerHTML = '<td class="px-3 py-2"><input name="children_full_name[]" type="text" class="w-full border border-slate-300 rounded-md px-3 py-2" placeholder="Child full name"></td><td class="px-3 py-2"><input name="children_birth_date[]" type="date" class="w-full border border-slate-300 rounded-md px-3 py-2"></td><td class="px-3 py-2"><button type="button" data-family-child-remove class="px-2.5 py-1 rounded-md border border-rose-200 text-rose-700 hover:bg-rose-50">Remove</button></td>';
        return fallbackRow;
      };

      const addChildRow = () => {
        if (!personalInfoFamilyChildrenTableBody) return;
        const row = createChildRow();
        if (!row) return;
        personalInfoFamilyChildrenTableBody.appendChild(row);
      };

      const resetChildrenRows = () => {
        if (!personalInfoFamilyChildrenTableBody) return;
        personalInfoFamilyChildrenTableBody.innerHTML = '';
        addChildRow();
      };

      personalInfoFamilyAddChildButton?.addEventListener('click', () => {
        addChildRow();
      });

      personalInfoFamilyChildrenTableBody?.addEventListener('click', (event) => {
        const removeButton = event.target.closest('[data-family-child-remove]');
        if (!removeButton || !personalInfoFamilyChildrenTableBody) {
          return;
        }

        event.preventDefault();
        event.stopPropagation();

        const row = removeButton.closest('tr');
        if (!row) {
          return;
        }

        const rows = getChildrenRows();
        if (rows.length <= 1) {
          const fullNameInput = row.querySelector('input[name="children_full_name[]"]');
          const birthDateInput = row.querySelector('input[name="children_birth_date[]"]');
          if (fullNameInput) fullNameInput.value = '';
          if (birthDateInput) birthDateInput.value = '';
          return;
        }

        row.remove();
      });

      document.querySelectorAll('[data-person-family-open]').forEach((button) => {
        button.addEventListener('click', () => {
          const personId = button.getAttribute('data-person-id') || '';
          const employeeName = button.getAttribute('data-employee-name') || 'Selected employee';
          if (familyPersonId) familyPersonId.value = personId;
          if (personalInfoFamilyEmployeeLabel) personalInfoFamilyEmployeeLabel.textContent = employeeName;

          if (familySpouseSurname) familySpouseSurname.value = button.getAttribute('data-spouse-surname') || '';
          if (familySpouseFirstName) familySpouseFirstName.value = button.getAttribute('data-spouse-first-name') || '';
          if (familySpouseMiddleName) familySpouseMiddleName.value = button.getAttribute('data-spouse-middle-name') || '';
          if (familySpouseExtensionName) familySpouseExtensionName.value = button.getAttribute('data-spouse-extension-name') || '';
          if (familySpouseOccupation) familySpouseOccupation.value = button.getAttribute('data-spouse-occupation') || '';
          if (familySpouseEmployerBusinessName) familySpouseEmployerBusinessName.value = button.getAttribute('data-spouse-employer-business-name') || '';
          if (familySpouseBusinessAddress) familySpouseBusinessAddress.value = button.getAttribute('data-spouse-business-address') || '';
          if (familySpouseTelephoneNo) familySpouseTelephoneNo.value = button.getAttribute('data-spouse-telephone-no') || '';

          if (familyFatherSurname) familyFatherSurname.value = button.getAttribute('data-father-surname') || '';
          if (familyFatherFirstName) familyFatherFirstName.value = button.getAttribute('data-father-first-name') || '';
          if (familyFatherMiddleName) familyFatherMiddleName.value = button.getAttribute('data-father-middle-name') || '';
          if (familyFatherExtensionName) familyFatherExtensionName.value = button.getAttribute('data-father-extension-name') || '';

          if (familyMotherSurname) familyMotherSurname.value = button.getAttribute('data-mother-surname') || '';
          if (familyMotherFirstName) familyMotherFirstName.value = button.getAttribute('data-mother-first-name') || '';
          if (familyMotherMiddleName) familyMotherMiddleName.value = button.getAttribute('data-mother-middle-name') || '';
          if (familyMotherExtensionName) familyMotherExtensionName.value = button.getAttribute('data-mother-extension-name') || '';

          resetChildrenRows();
          const childrenJson = button.getAttribute('data-children') || '[]';
          let children = [];
          try {
            const parsed = JSON.parse(childrenJson);
            if (Array.isArray(parsed)) {
              children = parsed;
            }
          } catch (_error) {
            children = [];
          }

          if (children.length > 0) {
            if (personalInfoFamilyChildrenTableBody) {
              personalInfoFamilyChildrenTableBody.innerHTML = '';
            }

            children.forEach((child) => {
              addChildRow();
              const rows = getChildrenRows();
              const row = rows[rows.length - 1];
              if (!row) return;

              const fullNameInput = row.querySelector('input[name="children_full_name[]"]');
              const birthDateInput = row.querySelector('input[name="children_birth_date[]"]');
              if (fullNameInput) fullNameInput.value = child.full_name || '';
              if (birthDateInput) birthDateInput.value = child.birth_date || '';
            });
          }

          setPdsTabActive('section_ii');
          openModal('personalInfoFamilyModal');
        });
      });

      if (getChildrenRows().length === 0) {
        addChildRow();
      }
    }

    if (personalInfoEducationForm && educationPersonId) {
      const educationLevels = ['elementary', 'secondary', 'vocational_trade_course', 'college', 'graduate_studies'];

      const setEducationField = (level, field, value) => {
        const input = personalInfoEducationForm.querySelector(`[data-education-level="${level}"][data-education-field="${field}"]`);
        if (input) {
          input.value = value || '';
        }
      };

      const resetEducationForm = () => {
        educationLevels.forEach((level) => {
          setEducationField(level, 'school_name', '');
          setEducationField(level, 'degree_course', '');
          setEducationField(level, 'attendance_from_year', '');
          setEducationField(level, 'attendance_to_year', '');
          setEducationField(level, 'highest_level_units_earned', '');
          setEducationField(level, 'year_graduated', '');
          setEducationField(level, 'scholarship_honors_received', '');
        });
      };

      document.querySelectorAll('[data-person-education-open]').forEach((button) => {
        button.addEventListener('click', () => {
          const personId = button.getAttribute('data-person-id') || '';
          const employeeName = button.getAttribute('data-employee-name') || 'Selected employee';
          const recordsJson = button.getAttribute('data-educational-backgrounds') || '{}';
          let records = {};

          try {
            const parsed = JSON.parse(recordsJson);
            if (parsed && typeof parsed === 'object') {
              records = parsed;
            }
          } catch (_error) {
            records = {};
          }

          educationPersonId.value = personId;
          if (personalInfoEducationEmployeeLabel) {
            personalInfoEducationEmployeeLabel.textContent = employeeName;
          }

          resetEducationForm();

          educationLevels.forEach((level) => {
            const entry = records[level];
            if (!entry || typeof entry !== 'object') {
              return;
            }

            setEducationField(level, 'school_name', entry.school_name || '');
            setEducationField(level, 'degree_course', entry.degree_course || '');
            setEducationField(level, 'attendance_from_year', entry.attendance_from_year || '');
            setEducationField(level, 'attendance_to_year', entry.attendance_to_year || '');
            setEducationField(level, 'highest_level_units_earned', entry.highest_level_units_earned || '');
            setEducationField(level, 'year_graduated', entry.year_graduated || '');
            setEducationField(level, 'scholarship_honors_received', entry.scholarship_honors_received || '');
          });

          setPdsTabActive('section_iii');
          openModal('personalInfoEducationModal');
        });
      });
    }

    if (
      personalInfoEligibilityForm
      && personalInfoEligibilityAction
      && personalInfoEligibilityId
      && personalInfoEligibilityPersonId
      && personalInfoEligibilityName
      && personalInfoEligibilitySequence
      && personalInfoEligibilitySubmit
      && personalInfoEligibilityTableBody
    ) {
      let activeEligibilityRows = [];

      const toSafeText = (value) => {
        const element = document.createElement('span');
        element.textContent = value == null ? '' : String(value);
        return element.innerHTML;
      };

      const resetEligibilityForm = () => {
        personalInfoEligibilityAction.value = 'add';
        personalInfoEligibilityId.value = '';
        personalInfoEligibilityName.value = '';
        if (personalInfoEligibilityRating) personalInfoEligibilityRating.value = '';
        if (personalInfoEligibilityExamDate) personalInfoEligibilityExamDate.value = '';
        if (personalInfoEligibilityExamPlace) personalInfoEligibilityExamPlace.value = '';
        if (personalInfoEligibilityLicenseNo) personalInfoEligibilityLicenseNo.value = '';
        if (personalInfoEligibilityLicenseValidity) personalInfoEligibilityLicenseValidity.value = '';
        personalInfoEligibilitySequence.value = '1';
        personalInfoEligibilitySubmit.textContent = 'Save Eligibility';
      };

      const renderEligibilityRows = () => {
        if (!Array.isArray(activeEligibilityRows) || activeEligibilityRows.length === 0) {
          personalInfoEligibilityTableBody.innerHTML = '<tr><td colspan="4" class="px-3 py-3 text-slate-500">No civil service eligibility records yet.</td></tr>';
          return;
        }

        const sortedRows = [...activeEligibilityRows].sort((left, right) => {
          const leftSequence = Number(left.sequence_no || 0);
          const rightSequence = Number(right.sequence_no || 0);
          if (leftSequence !== rightSequence) {
            return leftSequence - rightSequence;
          }
          return String(left.eligibility_name || '').localeCompare(String(right.eligibility_name || ''));
        });

        personalInfoEligibilityTableBody.innerHTML = sortedRows.map((entry) => {
          const entryId = toSafeText(entry.id || '');
          const name = toSafeText(entry.eligibility_name || '-');
          const rating = toSafeText(entry.rating || '-');
          const examDate = toSafeText(entry.exam_date || '-');
          const examPlace = toSafeText(entry.exam_place || '-');
          const licenseNo = toSafeText(entry.license_no || '-');
          const licenseValidity = toSafeText(entry.license_validity || '-');
          const sequenceNo = Number(entry.sequence_no || 1);

          return `
            <tr>
              <td class="px-3 py-2">
                <p class="font-medium text-slate-700">${name}</p>
                <p class="text-xs text-slate-500">Rating: ${rating}</p>
              </td>
              <td class="px-3 py-2">
                <p class="text-slate-700">${examDate}</p>
                <p class="text-xs text-slate-500">${examPlace}</p>
              </td>
              <td class="px-3 py-2">
                <p class="text-slate-700">${licenseNo}</p>
                <p class="text-xs text-slate-500">Valid until: ${licenseValidity}</p>
              </td>
              <td class="px-3 py-2">
                <div class="flex items-center gap-2">
                  <button
                    type="button"
                    data-eligibility-edit
                    data-id="${entryId}"
                    data-name="${name}"
                    data-rating="${rating === '-' ? '' : rating}"
                    data-exam-date="${examDate === '-' ? '' : examDate}"
                    data-exam-place="${examPlace === '-' ? '' : examPlace}"
                    data-license-no="${licenseNo === '-' ? '' : licenseNo}"
                    data-license-validity="${licenseValidity === '-' ? '' : licenseValidity}"
                    data-sequence="${sequenceNo}"
                    class="px-2.5 py-1 rounded-md border border-slate-300 text-slate-700 hover:bg-slate-100"
                  >Edit</button>
                  <button
                    type="button"
                    data-eligibility-delete
                    data-id="${entryId}"
                    class="px-2.5 py-1 rounded-md border border-rose-200 text-rose-700 hover:bg-rose-50"
                  >Delete</button>
                </div>
              </td>
            </tr>
          `;
        }).join('');
      };

      document.querySelectorAll('[data-person-eligibility-open]').forEach((button) => {
        button.addEventListener('click', () => {
          const personId = button.getAttribute('data-person-id') || '';
          const employeeName = button.getAttribute('data-employee-name') || 'Selected employee';
          const encodedRows = button.getAttribute('data-civil-eligibilities') || '[]';
          let parsedRows = [];

          try {
            const decodedRows = JSON.parse(encodedRows);
            if (Array.isArray(decodedRows)) {
              parsedRows = decodedRows;
            }
          } catch (_error) {
            parsedRows = [];
          }

          activeEligibilityRows = parsedRows;
          personalInfoEligibilityPersonId.value = personId;
          if (personalInfoEligibilityDeletePersonId) {
            personalInfoEligibilityDeletePersonId.value = personId;
          }
          if (personalInfoEligibilityEmployeeLabel) {
            personalInfoEligibilityEmployeeLabel.textContent = employeeName;
          }

          resetEligibilityForm();
          renderEligibilityRows();
          openModal('personalInfoEligibilityModal');
        });
      });

      personalInfoEligibilityTableBody.addEventListener('click', (event) => {
        const editButton = event.target.closest('[data-eligibility-edit]');
        if (editButton) {
          personalInfoEligibilityAction.value = 'edit';
          personalInfoEligibilityId.value = editButton.getAttribute('data-id') || '';
          personalInfoEligibilityName.value = editButton.getAttribute('data-name') || '';
          if (personalInfoEligibilityRating) personalInfoEligibilityRating.value = editButton.getAttribute('data-rating') || '';
          if (personalInfoEligibilityExamDate) personalInfoEligibilityExamDate.value = editButton.getAttribute('data-exam-date') || '';
          if (personalInfoEligibilityExamPlace) personalInfoEligibilityExamPlace.value = editButton.getAttribute('data-exam-place') || '';
          if (personalInfoEligibilityLicenseNo) personalInfoEligibilityLicenseNo.value = editButton.getAttribute('data-license-no') || '';
          if (personalInfoEligibilityLicenseValidity) personalInfoEligibilityLicenseValidity.value = editButton.getAttribute('data-license-validity') || '';
          personalInfoEligibilitySequence.value = editButton.getAttribute('data-sequence') || '1';
          personalInfoEligibilitySubmit.textContent = 'Update Eligibility';
          return;
        }

        const deleteButton = event.target.closest('[data-eligibility-delete]');
        if (!deleteButton || !personalInfoEligibilityDeleteForm || !personalInfoEligibilityDeleteId || !personalInfoEligibilityDeletePersonId) {
          return;
        }

        const submitDelete = () => {
          personalInfoEligibilityDeleteId.value = deleteButton.getAttribute('data-id') || '';
          personalInfoEligibilityDeleteForm.requestSubmit();
        };

        if (window.Swal) {
          Swal.fire({
            icon: 'warning',
            title: 'Delete eligibility record?',
            text: 'This action cannot be undone.',
            showCancelButton: true,
            confirmButtonText: 'Delete',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#be123c'
          }).then((result) => {
            if (result.isConfirmed) {
              submitDelete();
            }
          });
          return;
        }

        if (window.confirm('Delete this eligibility record?')) {
          submitDelete();
        }
      });

      personalInfoEligibilityResetButton?.addEventListener('click', () => {
        resetEligibilityForm();
      });
    }

    if (
      personalInfoWorkExperienceForm
      && personalInfoWorkExperienceAction
      && personalInfoWorkExperienceId
      && personalInfoWorkExperiencePersonId
      && personalInfoWorkExperienceDateFrom
      && personalInfoWorkExperiencePosition
      && personalInfoWorkExperienceOffice
      && personalInfoWorkExperienceSequence
      && personalInfoWorkExperienceSubmit
      && personalInfoWorkExperienceTableBody
    ) {
      let activeWorkExperienceRows = [];

      const toSafeText = (value) => {
        const element = document.createElement('span');
        element.textContent = value == null ? '' : String(value);
        return element.innerHTML;
      };

      const toGovernmentLabel = (value) => {
        if (value === true || value === 'true') return 'Yes';
        if (value === false || value === 'false') return 'No';
        return '-';
      };

      const toGovernmentFormValue = (value) => {
        if (value === true || value === 'true') return 'true';
        if (value === false || value === 'false') return 'false';
        return '';
      };

      const resetWorkExperienceForm = () => {
        personalInfoWorkExperienceAction.value = 'add';
        personalInfoWorkExperienceId.value = '';
        personalInfoWorkExperienceDateFrom.value = '';
        if (personalInfoWorkExperienceDateTo) personalInfoWorkExperienceDateTo.value = '';
        personalInfoWorkExperiencePosition.value = '';
        personalInfoWorkExperienceOffice.value = '';
        if (personalInfoWorkExperienceSalary) personalInfoWorkExperienceSalary.value = '';
        if (personalInfoWorkExperienceSalaryGrade) personalInfoWorkExperienceSalaryGrade.value = '';
        if (personalInfoWorkExperienceAppointmentStatus) personalInfoWorkExperienceAppointmentStatus.value = '';
        if (personalInfoWorkExperienceGovernment) personalInfoWorkExperienceGovernment.value = '';
        if (personalInfoWorkExperienceSeparationReason) personalInfoWorkExperienceSeparationReason.value = '';
        if (personalInfoWorkExperienceAchievements) personalInfoWorkExperienceAchievements.value = '';
        personalInfoWorkExperienceSequence.value = '1';
        personalInfoWorkExperienceSubmit.textContent = 'Save Work Experience';
      };

      const renderWorkExperienceRows = () => {
        if (!Array.isArray(activeWorkExperienceRows) || activeWorkExperienceRows.length === 0) {
          personalInfoWorkExperienceTableBody.innerHTML = '<tr><td colspan="4" class="px-3 py-3 text-slate-500">No work experience records yet.</td></tr>';
          return;
        }

        const sortedRows = [...activeWorkExperienceRows].sort((left, right) => {
          const leftSequence = Number(left.sequence_no || 0);
          const rightSequence = Number(right.sequence_no || 0);
          if (leftSequence !== rightSequence) {
            return leftSequence - rightSequence;
          }
          return String(right.inclusive_date_from || '').localeCompare(String(left.inclusive_date_from || ''));
        });

        personalInfoWorkExperienceTableBody.innerHTML = sortedRows.map((entry) => {
          const entryId = toSafeText(entry.id || '');
          const dateFrom = toSafeText(entry.inclusive_date_from || '');
          const dateTo = toSafeText(entry.inclusive_date_to || '');
          const positionTitle = toSafeText(entry.position_title || '-');
          const officeCompany = toSafeText(entry.office_company || '-');
          const monthlySalary = toSafeText(entry.monthly_salary || '-');
          const salaryGrade = toSafeText(entry.salary_grade_step || '-');
          const appointmentStatus = toSafeText(entry.appointment_status || '-');
          const governmentService = toSafeText(toGovernmentLabel(entry.is_government_service));
          const separationReason = toSafeText(entry.separation_reason || '');
          const achievements = toSafeText(entry.achievements || '');
          const sequenceNo = Number(entry.sequence_no || 1);

          return `
            <tr>
              <td class="px-3 py-2">
                <p class="font-medium text-slate-700">${positionTitle}</p>
                <p class="text-xs text-slate-500">${appointmentStatus} • Gov Service: ${governmentService}</p>
              </td>
              <td class="px-3 py-2">
                <p class="text-slate-700">${dateFrom || '-'} to ${dateTo || 'Present'}</p>
                <p class="text-xs text-slate-500">SG/Step: ${salaryGrade}</p>
              </td>
              <td class="px-3 py-2">
                <p class="text-slate-700">${officeCompany}</p>
                <p class="text-xs text-slate-500">Salary: ${monthlySalary}</p>
              </td>
              <td class="px-3 py-2">
                <div class="flex items-center gap-2">
                  <button
                    type="button"
                    data-work-experience-edit
                    data-id="${entryId}"
                    data-date-from="${dateFrom}"
                    data-date-to="${dateTo}"
                    data-position-title="${positionTitle}"
                    data-office-company="${officeCompany}"
                    data-monthly-salary="${monthlySalary === '-' ? '' : monthlySalary}"
                    data-salary-grade-step="${salaryGrade === '-' ? '' : salaryGrade}"
                    data-appointment-status="${appointmentStatus === '-' ? '' : appointmentStatus}"
                    data-is-government-service="${toGovernmentFormValue(entry.is_government_service)}"
                    data-separation-reason="${separationReason}"
                    data-achievements="${achievements}"
                    data-sequence="${sequenceNo}"
                    class="px-2.5 py-1 rounded-md border border-slate-300 text-slate-700 hover:bg-slate-100"
                  >Edit</button>
                  <button
                    type="button"
                    data-work-experience-delete
                    data-id="${entryId}"
                    class="px-2.5 py-1 rounded-md border border-rose-200 text-rose-700 hover:bg-rose-50"
                  >Delete</button>
                </div>
              </td>
            </tr>
          `;
        }).join('');
      };

      document.querySelectorAll('[data-person-work-experience-open]').forEach((button) => {
        button.addEventListener('click', () => {
          const personId = button.getAttribute('data-person-id') || '';
          const employeeName = button.getAttribute('data-employee-name') || 'Selected employee';
          const encodedRows = button.getAttribute('data-work-experiences') || '[]';
          let parsedRows = [];

          try {
            const decodedRows = JSON.parse(encodedRows);
            if (Array.isArray(decodedRows)) {
              parsedRows = decodedRows;
            }
          } catch (_error) {
            parsedRows = [];
          }

          activeWorkExperienceRows = parsedRows;
          personalInfoWorkExperiencePersonId.value = personId;
          if (personalInfoWorkExperienceDeletePersonId) {
            personalInfoWorkExperienceDeletePersonId.value = personId;
          }
          if (personalInfoWorkExperienceEmployeeLabel) {
            personalInfoWorkExperienceEmployeeLabel.textContent = employeeName;
          }

          resetWorkExperienceForm();
          renderWorkExperienceRows();
          openModal('personalInfoWorkExperienceModal');
        });
      });

      personalInfoWorkExperienceTableBody.addEventListener('click', (event) => {
        const editButton = event.target.closest('[data-work-experience-edit]');
        if (editButton) {
          personalInfoWorkExperienceAction.value = 'edit';
          personalInfoWorkExperienceId.value = editButton.getAttribute('data-id') || '';
          personalInfoWorkExperienceDateFrom.value = editButton.getAttribute('data-date-from') || '';
          if (personalInfoWorkExperienceDateTo) personalInfoWorkExperienceDateTo.value = editButton.getAttribute('data-date-to') || '';
          personalInfoWorkExperiencePosition.value = editButton.getAttribute('data-position-title') || '';
          personalInfoWorkExperienceOffice.value = editButton.getAttribute('data-office-company') || '';
          if (personalInfoWorkExperienceSalary) personalInfoWorkExperienceSalary.value = editButton.getAttribute('data-monthly-salary') || '';
          if (personalInfoWorkExperienceSalaryGrade) personalInfoWorkExperienceSalaryGrade.value = editButton.getAttribute('data-salary-grade-step') || '';
          if (personalInfoWorkExperienceAppointmentStatus) personalInfoWorkExperienceAppointmentStatus.value = editButton.getAttribute('data-appointment-status') || '';
          if (personalInfoWorkExperienceGovernment) personalInfoWorkExperienceGovernment.value = editButton.getAttribute('data-is-government-service') || '';
          if (personalInfoWorkExperienceSeparationReason) personalInfoWorkExperienceSeparationReason.value = editButton.getAttribute('data-separation-reason') || '';
          if (personalInfoWorkExperienceAchievements) personalInfoWorkExperienceAchievements.value = editButton.getAttribute('data-achievements') || '';
          personalInfoWorkExperienceSequence.value = editButton.getAttribute('data-sequence') || '1';
          personalInfoWorkExperienceSubmit.textContent = 'Update Work Experience';
          return;
        }

        const deleteButton = event.target.closest('[data-work-experience-delete]');
        if (!deleteButton || !personalInfoWorkExperienceDeleteForm || !personalInfoWorkExperienceDeleteId || !personalInfoWorkExperienceDeletePersonId) {
          return;
        }

        const submitDelete = () => {
          personalInfoWorkExperienceDeleteId.value = deleteButton.getAttribute('data-id') || '';
          personalInfoWorkExperienceDeleteForm.requestSubmit();
        };

        if (window.Swal) {
          Swal.fire({
            icon: 'warning',
            title: 'Delete work experience record?',
            text: 'This action cannot be undone.',
            showCancelButton: true,
            confirmButtonText: 'Delete',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#be123c'
          }).then((result) => {
            if (result.isConfirmed) {
              submitDelete();
            }
          });
          return;
        }

        if (window.confirm('Delete this work experience record?')) {
          submitDelete();
        }
      });

      personalInfoWorkExperienceResetButton?.addEventListener('click', () => {
        resetWorkExperienceForm();
      });
    }

    document.querySelectorAll('[data-person-assignment-open]').forEach((button) => {
      button.addEventListener('click', () => {
        const personId = button.getAttribute('data-person-id') || '';
        const employeeName = button.getAttribute('data-employee-name') || '';
        if (personalInfoAssignmentPersonId) {
          personalInfoAssignmentPersonId.value = personId;
        }
        if (personalInfoAssignmentEmployeeDisplay) {
          personalInfoAssignmentEmployeeDisplay.value = employeeName;
        }
        openModal('personalInfoAssignmentModal');
      });
    });

    document.querySelectorAll('[data-person-status-open]').forEach((button) => {
      button.addEventListener('click', () => {
        const personId = button.getAttribute('data-person-id') || '';
        const employeeName = button.getAttribute('data-employee-name') || '';
        if (personalInfoStatusPersonId) {
          personalInfoStatusPersonId.value = personId;
        }
        if (personalInfoStatusEmployeeDisplay) {
          personalInfoStatusEmployeeDisplay.value = employeeName;
        }
        openModal('personalInfoStatusModal');
      });
    });

    const personalInfoArchiveForm = document.getElementById('personalInfoArchiveForm');
    const personalInfoArchivePersonId = document.getElementById('personalInfoArchivePersonId');
    const personalInfoArchiveEmployeeName = document.getElementById('personalInfoArchiveEmployeeName');

    document.querySelectorAll('[data-person-profile-archive]').forEach((button) => {
      button.addEventListener('click', () => {
        const personId = button.getAttribute('data-person-id') || '';
        const employeeName = button.getAttribute('data-employee-name') || 'this employee';

        if (!personalInfoArchiveForm || !personalInfoArchivePersonId || !personalInfoArchiveEmployeeName || !personId) {
          return;
        }

        const submitArchive = () => {
          personalInfoArchivePersonId.value = personId;
          personalInfoArchiveEmployeeName.value = employeeName;
          personalInfoArchiveForm.requestSubmit();
        };

        if (window.Swal) {
          Swal.fire({
            icon: 'warning',
            title: 'Archive employee profile?',
            text: `${employeeName} will be marked as archived and removed from active employment records.`,
            showCancelButton: true,
            confirmButtonText: 'Yes, archive',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#be123c'
          }).then((result) => {
            if (result.isConfirmed) {
              submitArchive();
            }
          });
          return;
        }

        if (window.confirm(`Archive ${employeeName}?`)) {
          submitArchive();
        }
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
          basePay: option.getAttribute('data-base-pay') || '',
          allowanceTotal: option.getAttribute('data-allowance-total') || '',
          taxDeduction: option.getAttribute('data-tax-deduction') || '',
          governmentDeductions: option.getAttribute('data-government-deductions') || '',
          otherDeductions: option.getAttribute('data-other-deductions') || '',
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

        if (
          payrollBasePay
          && (payload.basePay || payload.monthlyRate)
          && Number(payload.basePay || payload.monthlyRate) > 0
          && Number(payrollBasePay.value || 0) <= 0
        ) {
          payrollBasePay.value = Number(payload.basePay || payload.monthlyRate).toFixed(2);
        }

        if (payrollAllowanceInput && payload.allowanceTotal && Number(payrollAllowanceInput.value || 0) <= 0) {
          payrollAllowanceInput.value = Number(payload.allowanceTotal).toFixed(2);
        }
        if (payrollTaxInput && payload.taxDeduction && Number(payrollTaxInput.value || 0) <= 0) {
          payrollTaxInput.value = Number(payload.taxDeduction).toFixed(2);
        }
        if (payrollGovernmentInput && payload.governmentDeductions && Number(payrollGovernmentInput.value || 0) <= 0) {
          payrollGovernmentInput.value = Number(payload.governmentDeductions).toFixed(2);
        }
        if (payrollOtherDeduction && payload.otherDeductions && Number(payrollOtherDeduction.value || 0) <= 0) {
          payrollOtherDeduction.value = Number(payload.otherDeductions).toFixed(2);
        }

        updatePayrollComputedSummary();
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

      const formatPeso = (value) => `₱${Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

      const updatePayrollComputedSummary = () => {
        const basePay = Number(payrollBasePay?.value || 0);
        const allowance = Number(payrollAllowanceInput?.value || 0);
        const taxDeduction = Number(payrollTaxInput?.value || 0);
        const govDeduction = Number(payrollGovernmentInput?.value || 0);
        const otherDeduction = Number(payrollOtherDeduction?.value || 0);
        const frequency = (payrollPayFrequency?.value || 'semi_monthly').trim().toLowerCase();

        const monthlyRate = Math.max(0, basePay + allowance);
        let divisor = 2;
        if (frequency === 'monthly') divisor = 1;
        if (frequency === 'weekly') divisor = 4;

        const perCycleGross = monthlyRate / divisor;
        const perCycleDeduction = (taxDeduction + govDeduction + otherDeduction) / divisor;
        const perCycleNet = Math.max(0, perCycleGross - perCycleDeduction);

        if (payrollComputedMonthlyRate) payrollComputedMonthlyRate.value = formatPeso(monthlyRate);
        if (payrollEstimatedNetCycle) payrollEstimatedNetCycle.value = formatPeso(perCycleNet);
      };

      const updateEffectivityHint = () => {
        if (!payrollEffectivityHint || !payrollEffectiveFromInput) return;
        const selected = (payrollEffectiveFromInput.value || '').trim();
        const today = new Date();
        const selectedDate = selected ? new Date(`${selected}T00:00:00`) : null;
        if (selectedDate && selectedDate > today) {
          payrollEffectivityHint.textContent = `Scheduled update: this salary setup starts on ${selected} and appears in payroll periods covering that date.`;
          payrollEffectivityHint.className = 'text-xs text-blue-600 mt-2';
          return;
        }

        payrollEffectivityHint.textContent = 'Tip: Future effectivity dates are scheduled and only apply to payroll periods that cover that date.';
        payrollEffectivityHint.className = 'text-xs text-slate-500 mt-2';
      };

      [payrollBasePay, payrollAllowanceInput, payrollTaxInput, payrollGovernmentInput, payrollOtherDeduction, payrollPayFrequency].forEach((el) => {
        el?.addEventListener('input', updatePayrollComputedSummary);
        el?.addEventListener('change', updatePayrollComputedSummary);
      });
      payrollEffectiveFromInput?.addEventListener('change', updateEffectivityHint);
      updatePayrollComputedSummary();
      updateEffectivityHint();
    }

    document.querySelectorAll('[data-payroll-select-employee]').forEach((button) => {
      button.addEventListener('click', () => {
        const employeeLabel = (button.getAttribute('data-employee-label') || '').trim();
        if (!employeeLabel || !payrollEmployeeSearch) return;

        payrollEmployeeSearch.value = employeeLabel;
        payrollEmployeeSearch.dispatchEvent(new Event('change', { bubbles: true }));
        payrollEmployeeSearch.dispatchEvent(new Event('blur', { bubbles: true }));
        payrollEmployeeSearch.focus();
        payrollEmployeeSearch.scrollIntoView({ behavior: 'smooth', block: 'center' });
      });
    });

    document.querySelectorAll('[data-payroll-delete-setup]').forEach((button) => {
      button.addEventListener('click', (event) => {
        event.preventDefault();
        const form = button.closest('form');
        if (!form) return;

        const submitDelete = () => form.submit();

        if (typeof window.Swal !== 'undefined') {
          window.Swal.fire({
            icon: 'warning',
            title: 'Delete salary setup?',
            text: 'This removes the selected setup and recalculates the compensation timeline.',
            showCancelButton: true,
            confirmButtonText: 'Delete',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#be123c'
          }).then((result) => {
            if (result.isConfirmed) submitDelete();
          });
          return;
        }

        if (window.confirm('Delete selected salary setup?')) {
          submitDelete();
        }
      });
    });

    document.querySelectorAll('[data-payroll-delete-batch]').forEach((button) => {
      button.addEventListener('click', (event) => {
        event.preventDefault();
        const form = button.closest('form');
        if (!form) return;

        const submitDelete = () => form.submit();

        if (typeof window.Swal !== 'undefined') {
          window.Swal.fire({
            icon: 'warning',
            title: 'Delete payroll batch?',
            text: 'This will remove the payroll run and all generated payroll items under it.',
            showCancelButton: true,
            confirmButtonText: 'Delete batch',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#be123c'
          }).then((result) => {
            if (result.isConfirmed) submitDelete();
          });
          return;
        }

        if (window.confirm('Delete selected payroll batch?')) {
          submitDelete();
        }
      });
    });

    document.querySelectorAll('[data-payroll-delete-period]').forEach((button) => {
      button.addEventListener('click', (event) => {
        event.preventDefault();
        const form = button.closest('form');
        if (!form) return;

        const submitDelete = () => form.submit();

        if (typeof window.Swal !== 'undefined') {
          window.Swal.fire({
            icon: 'warning',
            title: 'Delete payroll period?',
            text: 'This removes the selected period if no payroll batches exist for it.',
            showCancelButton: true,
            confirmButtonText: 'Delete period',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#be123c'
          }).then((result) => {
            if (result.isConfirmed) submitDelete();
          });
          return;
        }

        if (window.confirm('Delete selected payroll period?')) {
          submitDelete();
        }
      });
    });

    if (personalInfoProfileForm && profileAction && profilePersonId) {
      personalInfoProfileForm.addEventListener('submit', (event) => {
        const mode = (profileAction.value || '').trim().toLowerCase();
        if (mode === 'edit' && !profilePersonId.value) {
          event.preventDefault();
          if (window.Swal) {
            Swal.fire({
              icon: 'warning',
              title: 'Select employee',
              text: 'Use the Edit action from the employee table to update an existing profile.',
              confirmButtonColor: '#0f172a'
            });
          }
        }
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

      const scrollAreas = modal.querySelectorAll('.overflow-y-auto');
      scrollAreas.forEach((element) => {
        element.scrollTop = 0;
      });

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
