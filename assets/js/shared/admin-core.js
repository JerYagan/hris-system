const qs = (selector, root = document) => root.querySelector(selector);

const debounce = (fn, delay = 250) => {
  let timer = null;
  return (...args) => {
    if (timer) {
      clearTimeout(timer);
    }
    timer = setTimeout(() => fn(...args), delay);
  };
};

const ensureSweetAlert = async () => {
  if (window.Swal) {
    return window.Swal;
  }

  await new Promise((resolve, reject) => {
    const existing = document.querySelector('script[data-swal="true"]');
    if (existing) {
      existing.addEventListener('load', () => resolve(), { once: true });
      existing.addEventListener('error', () => reject(new Error('Failed to load SweetAlert2.')), { once: true });
      return;
    }

    const script = document.createElement('script');
    script.src = 'https://cdn.jsdelivr.net/npm/sweetalert2@11';
    script.defer = true;
    script.dataset.swal = 'true';
    script.onload = () => resolve();
    script.onerror = () => reject(new Error('Failed to load SweetAlert2.'));
    document.head.appendChild(script);
  });

  return window.Swal;
};

export const openModal = (modalId) => {
  const modal = document.getElementById(modalId);
  if (!modal) {
    return;
  }

  modal.classList.remove('hidden');
  modal.setAttribute('aria-hidden', 'false');
  document.body.classList.add('overflow-hidden');
};

export const closeModal = (modalId) => {
  const modal = document.getElementById(modalId);
  if (!modal) {
    return;
  }

  modal.classList.add('hidden');
  modal.setAttribute('aria-hidden', 'true');

  const hasOpenModal = Array.from(document.querySelectorAll('[data-modal]')).some((entry) => !entry.classList.contains('hidden'));
  if (!hasOpenModal) {
    document.body.classList.remove('overflow-hidden');
  }
};

export const initModalSystem = () => {
  document.querySelectorAll('[data-modal-open]').forEach((trigger) => {
    const target = (trigger.getAttribute('data-modal-open') || '').trim();
    if (!target) {
      return;
    }
    trigger.addEventListener('click', () => openModal(target));
  });

  document.querySelectorAll('[data-modal-close]').forEach((trigger) => {
    const target = (trigger.getAttribute('data-modal-close') || '').trim();
    if (!target) {
      return;
    }
    trigger.addEventListener('click', () => closeModal(target));
  });

  document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') {
      return;
    }

    const openModals = Array.from(document.querySelectorAll('[data-modal]')).filter((modal) => !modal.classList.contains('hidden'));
    const lastModal = openModals[openModals.length - 1];
    if (lastModal?.id) {
      closeModal(lastModal.id);
    }
  });
};

export const initAdminShellInteractions = () => {
  const sidebar = qs('#sidebar');
  const toggle = qs('#sidebarToggle');
  const closeButton = qs('#sidebarClose');
  const overlay = qs('#sidebarOverlay');
  const profileToggle = qs('#profileToggle');
  const profileMenu = qs('#profileMenu');
  const notificationToggle = qs('#notificationToggle');
  const notificationMenu = qs('#notificationMenu');
  const categoryToggles = document.querySelectorAll('[data-cat-toggle]');
  const topnav = qs('#topnav');

  let isOpen = false;
  const applySidebarState = () => {
    if (!sidebar || !overlay) {
      return;
    }

    if (isOpen) {
      sidebar.classList.remove('-translate-x-full');
      sidebar.classList.add('translate-x-0');
      overlay.classList.remove('opacity-0', 'pointer-events-none');
      overlay.classList.add('opacity-100');
      sidebar.setAttribute('aria-hidden', 'false');
      return;
    }

    sidebar.classList.add('-translate-x-full');
    sidebar.classList.remove('translate-x-0');
    overlay.classList.remove('opacity-100');
    overlay.classList.add('opacity-0', 'pointer-events-none');
    sidebar.setAttribute('aria-hidden', 'true');
  };

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

  if (profileToggle && profileMenu) {
    profileToggle.addEventListener('click', (event) => {
      event.stopPropagation();
      if (notificationMenu && !notificationMenu.classList.contains('hidden')) {
        notificationMenu.classList.add('hidden');
      }
      profileMenu.classList.toggle('hidden');
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
  }

  document.addEventListener('click', (event) => {
    if (profileMenu && profileToggle && !profileMenu.classList.contains('hidden')) {
      if (!profileMenu.contains(event.target) && !profileToggle.contains(event.target)) {
        profileMenu.classList.add('hidden');
      }
    }

    if (notificationMenu && notificationToggle && !notificationMenu.classList.contains('hidden')) {
      if (!notificationMenu.contains(event.target) && !notificationToggle.contains(event.target)) {
        notificationMenu.classList.add('hidden');
      }
    }
  });

  if (categoryToggles.length) {
    categoryToggles.forEach((toggleButton) => {
      const key = toggleButton.getAttribute('data-cat-toggle');
      const content = document.querySelector(`[data-cat-content="${key}"]`);
      if (!content) {
        return;
      }

      toggleButton.addEventListener('click', () => {
        const expanded = toggleButton.getAttribute('aria-expanded') === 'true';
        toggleButton.setAttribute('aria-expanded', expanded ? 'false' : 'true');
        content.classList.toggle('hidden', expanded);
      });
    });
  }

  if (topnav) {
    let lastScrollY = window.scrollY;
    const navHeight = topnav.offsetHeight;

    window.addEventListener('scroll', () => {
      const currentScrollY = window.scrollY;
      const delta = currentScrollY - lastScrollY;
      if (Math.abs(delta) < 8) {
        return;
      }

      if (delta > 0 && currentScrollY > navHeight) {
        topnav.classList.add('-translate-y-full');
      } else {
        topnav.classList.remove('-translate-y-full');
      }

      lastScrollY = currentScrollY;
    });
  }

  const topnavSearchInput = qs('#topnavGlobalSearch');
  const topnavSearchResults = qs('#topnavSearchResults');
  const topnavSearchResultsBody = qs('#topnavSearchResultsBody');
  const topnavSearchMeta = qs('#topnavSearchMeta');
  const topnavSearchWrapper = qs('#topnavSearchWrapper');

  if (topnavSearchInput && topnavSearchResults && topnavSearchResultsBody && topnavSearchMeta && topnavSearchWrapper) {
    const normalize = (value) => String(value || '').toLowerCase().trim();
    const compactText = (value) => String(value || '').replace(/\s+/g, ' ').trim();
    const escapeHtml = (value) => String(value || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');

    const buildSearchableItems = () => {
      const items = [];
      let autoIdCounter = 0;

      document.querySelectorAll('main table tbody tr').forEach((row, rowIndex) => {
        const cells = Array.from(row.querySelectorAll('td,th')).map((cell) => compactText(cell.textContent)).filter(Boolean);
        if (!cells.length) {
          return;
        }

        if (!row.id) {
          autoIdCounter += 1;
          row.id = `admin-search-row-${rowIndex + 1}-${autoIdCounter}`;
        }

        const title = cells[0] || `Record ${rowIndex + 1}`;
        const preview = cells.join(' ');
        items.push({
          targetId: row.id,
          title,
          subtitle: 'Table Record',
          searchKey: normalize(preview),
          preview: preview.length > 140 ? `${preview.slice(0, 140)}…` : preview,
        });
      });

      return items;
    };

    const openResults = () => topnavSearchResults.classList.remove('hidden');
    const closeResults = () => topnavSearchResults.classList.add('hidden');

    const renderResults = (query) => {
      const q = normalize(query);
      const items = buildSearchableItems();

      if (!q) {
        topnavSearchMeta.textContent = `${items.length} records indexed`;
        topnavSearchResultsBody.innerHTML = '<div class="px-4 py-6 text-sm text-slate-500 text-center">Start typing to search records in this module.</div>';
        openResults();
        return;
      }

      const matches = items.filter((item) => item.searchKey.includes(q)).slice(0, 10);
      topnavSearchMeta.textContent = `${matches.length} record(s)`;

      if (!matches.length) {
        topnavSearchResultsBody.innerHTML = '<div class="px-4 py-6 text-sm text-slate-500 text-center">No matching records found.</div>';
        openResults();
        return;
      }

      topnavSearchResultsBody.innerHTML = matches.map((item) => `
        <button type="button" data-search-target="${escapeHtml(item.targetId)}" class="w-full text-left px-4 py-3 border-b border-slate-100 hover:bg-slate-50 transition-colors">
          <p class="text-sm font-medium text-slate-800">${escapeHtml(item.title)}</p>
          <p class="text-xs text-slate-500 mt-1">${escapeHtml(item.subtitle)}</p>
          <p class="text-[11px] text-slate-400 mt-1 line-clamp-1">${escapeHtml(item.preview)}</p>
        </button>
      `).join('');

      openResults();
    };

    topnavSearchResultsBody.addEventListener('click', (event) => {
      const trigger = event.target.closest('[data-search-target]');
      if (!trigger) {
        return;
      }

      const targetId = trigger.getAttribute('data-search-target') || '';
      const target = document.getElementById(targetId);
      if (!target) {
        return;
      }

      target.scrollIntoView({ behavior: 'smooth', block: 'center' });
      target.classList.add('ring-2', 'ring-emerald-300', 'ring-offset-2', 'ring-offset-white');
      setTimeout(() => target.classList.remove('ring-2', 'ring-emerald-300', 'ring-offset-2', 'ring-offset-white'), 1500);
      closeResults();
    });

    topnavSearchInput.addEventListener('input', debounce(() => {
      renderResults(topnavSearchInput.value || '');
    }, 250));

    topnavSearchInput.addEventListener('focus', () => {
      renderResults(topnavSearchInput.value || '');
    });

    document.addEventListener('click', (event) => {
      if (!topnavSearchWrapper.contains(event.target)) {
        closeResults();
      }
    });
  }
};

export const bindTableFilters = ({
  tableId,
  searchInputId,
  searchDataAttr,
  statusFilterId,
  statusDataAttr,
}) => {
  const table = document.getElementById(tableId);
  if (!table) {
    return () => {};
  }

  const searchInput = searchInputId ? document.getElementById(searchInputId) : null;
  const statusFilter = statusFilterId ? document.getElementById(statusFilterId) : null;

  const rows = Array.from(table.querySelectorAll('tbody tr'));
  const run = () => {
    const query = String(searchInput?.value || '').toLowerCase().trim();
    const selectedStatus = String(statusFilter?.value || '').toLowerCase().trim();

    rows.forEach((row) => {
      const rowSearch = String(row.getAttribute(searchDataAttr) || '').toLowerCase();
      const rowStatus = String(row.getAttribute(statusDataAttr) || '').toLowerCase();
      const searchMatch = query === '' || rowSearch.includes(query);
      const statusMatch = selectedStatus === '' || rowStatus === selectedStatus;
      row.style.display = searchMatch && statusMatch ? '' : 'none';
    });
  };

  if (searchInput) {
    searchInput.addEventListener('input', debounce(run, 220));
  }

  if (statusFilter) {
    statusFilter.addEventListener('change', run);
  }

  run();
  return run;
};

export const initStatusChangeConfirmations = () => {
  const actionMap = {
    review_leave_request_dashboard: {
      title: 'Submit leave decision?',
      text: 'This will update the leave request status and notify relevant users.',
      confirmButtonText: 'Submit decision',
    },
    review_leave_request: {
      title: 'Submit leave decision?',
      text: 'This will update the leave request status and notify relevant users.',
      confirmButtonText: 'Submit decision',
    },
    review_time_adjustment: {
      title: 'Submit adjustment decision?',
      text: 'This updates the adjustment request status and employee notification.',
      confirmButtonText: 'Submit decision',
    },
    review_document: {
      title: 'Submit document review?',
      text: 'The selected review status will be saved to this document.',
      confirmButtonText: 'Save review',
    },
    archive_document: {
      title: 'Archive this document?',
      text: 'Archived documents are removed from active review queues.',
      confirmButtonText: 'Archive',
      confirmButtonColor: '#dc2626',
    },
    archive_job_posting: {
      title: 'Archive this job posting?',
      text: 'This posting will be removed from active recruitment flow.',
      confirmButtonText: 'Archive posting',
      confirmButtonColor: '#dc2626',
    },
    edit_job_posting: {
      title: 'Save job posting changes?',
      text: 'Changes to status, dates, and details will apply immediately.',
      confirmButtonText: 'Save changes',
    },
    review_payroll_batch: {
      title: 'Submit payroll batch decision?',
      text: 'This updates payroll batch status and related approval fields.',
      confirmButtonText: 'Submit decision',
    },
    review_salary_adjustment: {
      title: 'Submit salary adjustment review?',
      text: 'This marks the salary adjustment as approved or rejected.',
      confirmButtonText: 'Submit review',
    },
    save_salary_setup: {
      title: 'Save salary setup?',
      text: 'This updates payroll compensation details and timeline coverage for the selected employee.',
      confirmButtonText: 'Save setup',
    },
    delete_salary_setup: {
      title: 'Delete salary setup entry?',
      text: 'This permanently deletes the selected salary setup entry and refreshes the compensation timeline.',
      confirmButtonText: 'Delete entry',
      confirmButtonColor: '#dc2626',
    },
    delete_salary_setup_bulk: {
      title: 'Delete selected salary setup entries?',
      text: 'This permanently deletes all selected salary setup entries and refreshes their compensation timelines.',
      confirmButtonText: 'Delete selected',
      confirmButtonColor: '#dc2626',
    },
    delete_payroll_period: {
      title: 'Delete payroll period?',
      text: 'This permanently deletes the selected payroll period when no payroll batch depends on it.',
      confirmButtonText: 'Delete period',
      confirmButtonColor: '#dc2626',
    },
    delete_payroll_period_bulk: {
      title: 'Delete selected payroll periods?',
      text: 'This permanently deletes all selected payroll periods that have no payroll batch dependencies.',
      confirmButtonText: 'Delete selected',
      confirmButtonColor: '#dc2626',
    },
    delete_payroll_batch: {
      title: 'Delete payroll batch?',
      text: 'This permanently deletes the selected payroll batch and its related computed items.',
      confirmButtonText: 'Delete batch',
      confirmButtonColor: '#dc2626',
    },
    delete_payroll_batch_bulk: {
      title: 'Delete selected payroll batches?',
      text: 'This permanently deletes all selected payroll batches and their related computed items.',
      confirmButtonText: 'Delete selected',
      confirmButtonColor: '#dc2626',
    },
    update_status: {
      title: 'Update application status?',
      text: 'This changes applicant status and triggers applicant notifications.',
      confirmButtonText: 'Update status',
    },
    update_employee_status: {
      title: 'Update employee status?',
      text: 'This updates the employee employment status record.',
      confirmButtonText: 'Update status',
    },
  };

  document.querySelectorAll('form').forEach((form) => {
    const actionInput = form.querySelector('input[name="form_action"]');
    const actionName = String(actionInput?.value || '').trim();
    const config = actionMap[actionName];
    if (!config) {
      return;
    }

    form.addEventListener('submit', async (event) => {
      if (form.dataset.confirmedSubmit === 'true') {
        return;
      }

      event.preventDefault();
      const requiresReason = config.requireReason === true;

      try {
        const Swal = await ensureSweetAlert();
        const result = await Swal.fire({
          title: config.title,
          text: config.text,
          icon: 'warning',
          showCancelButton: true,
          confirmButtonText: config.confirmButtonText || 'Confirm',
          cancelButtonText: 'Cancel',
          confirmButtonColor: config.confirmButtonColor || '#0f172a',
          reverseButtons: true,
          input: requiresReason ? 'textarea' : undefined,
          inputLabel: requiresReason ? (config.reasonLabel || 'Reason') : undefined,
          inputPlaceholder: requiresReason ? (config.reasonPlaceholder || 'Enter reason') : undefined,
          inputAttributes: requiresReason ? { 'aria-label': config.reasonLabel || 'Reason' } : undefined,
          preConfirm: requiresReason
            ? (value) => {
              const normalized = String(value || '').trim();
              if (normalized.length < 3) {
                Swal.showValidationMessage(config.reasonValidationMessage || 'Please provide a reason.');
                return false;
              }
              return normalized;
            }
            : undefined,
        });

        if (!result.isConfirmed) {
          return;
        }

        if (requiresReason) {
          const reason = String(result.value || '').trim();
          let reasonInput = form.querySelector('input[name="action_reason"]');
          if (!reasonInput) {
            reasonInput = document.createElement('input');
            reasonInput.setAttribute('type', 'hidden');
            reasonInput.setAttribute('name', 'action_reason');
            form.appendChild(reasonInput);
          }
          reasonInput.value = reason;
        }

        form.dataset.confirmedSubmit = 'true';
        form.submit();
      } catch (_error) {
        const accepted = window.confirm(config.text || config.title || 'Proceed with this action?');
        if (!accepted) {
          return;
        }

        if (requiresReason) {
          const enteredReason = window.prompt(config.reasonLabel || 'Enter reason for this action:');
          const normalizedReason = String(enteredReason || '').trim();
          if (normalizedReason.length < 3) {
            return;
          }

          let reasonInput = form.querySelector('input[name="action_reason"]');
          if (!reasonInput) {
            reasonInput = document.createElement('input');
            reasonInput.setAttribute('type', 'hidden');
            reasonInput.setAttribute('name', 'action_reason');
            form.appendChild(reasonInput);
          }
          reasonInput.value = normalizedReason;
        }

        form.dataset.confirmedSubmit = 'true';
        form.submit();
      }
    });
  });
};
