(() => {
    const PAGE_SIZE = 10;
    const normalize = (value) => (value || '').toString().trim().toLowerCase();

    const debounce = (fn, wait = 200) => {
        let timer = null;
        return (...args) => {
            if (timer) {
                window.clearTimeout(timer);
            }
            timer = window.setTimeout(() => fn(...args), wait);
        };
    };

    const showFlash = () => {
        const flash = document.getElementById('employeeEvaluationFlash');
        if (!flash || !(window.Swal && typeof window.Swal.fire === 'function')) {
            return;
        }

        const state = normalize(flash.getAttribute('data-state'));
        const message = (flash.getAttribute('data-message') || '').toString().trim();
        if (message === '') {
            return;
        }

        window.Swal.fire({
            icon: state === 'success' ? 'success' : 'error',
            title: state === 'success' ? 'Success' : 'Action Failed',
            text: message,
        });
        flash.classList.add('hidden');
    };

    showFlash();

    const modal = document.getElementById('employeeEvaluationModal');
    const openBtn = document.getElementById('openEmployeeEvaluationModal');
    const closeBtn = document.getElementById('employeeEvaluationModalClose');
    const cancelBtn = document.getElementById('employeeEvaluationModalCancel');
    const form = document.getElementById('employeeEvaluationForm');
    const submitBtn = document.getElementById('employeeEvaluationSubmit');

    const openModal = () => {
        if (!modal) {
            return;
        }
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    };

    const closeModal = () => {
        if (!modal) {
            return;
        }
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        if (form) {
            form.reset();
        }
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.classList.remove('opacity-60', 'cursor-not-allowed');
        }
    };

    if (openBtn) {
        openBtn.addEventListener('click', openModal);
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', closeModal);
    }

    if (cancelBtn) {
        cancelBtn.addEventListener('click', closeModal);
    }

    if (modal) {
        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeModal();
            }
        });
    }

    if (form) {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            const employeeSelect = document.getElementById('employeeEvaluationEmployeeSelect');
            const cycleSelect = document.getElementById('employeeEvaluationCycleSelect');
            const employeeLabel = employeeSelect && employeeSelect.selectedOptions[0]
                ? employeeSelect.selectedOptions[0].textContent || 'Employee'
                : 'Employee';
            const cycleLabel = cycleSelect && cycleSelect.selectedOptions[0]
                ? cycleSelect.selectedOptions[0].textContent || 'Cycle'
                : 'Cycle';

            if (window.Swal && typeof window.Swal.fire === 'function') {
                const result = await window.Swal.fire({
                    icon: 'warning',
                    title: 'Forward Evaluation for Approval',
                    text: `Submit evaluation for ${employeeLabel} under ${cycleLabel}?`,
                    showCancelButton: true,
                    confirmButtonText: 'Forward for Approval',
                    cancelButtonText: 'Cancel',
                    reverseButtons: true,
                });

                if (!result.isConfirmed) {
                    return;
                }
            }

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.classList.add('opacity-60', 'cursor-not-allowed');
            }

            form.submit();
        });
    }

    const rows = Array.from(document.querySelectorAll('[data-employee-evaluation-row]'));
    const searchInput = document.getElementById('employeeEvaluationSearchInput');
    const statusFilter = document.getElementById('employeeEvaluationStatusFilter');
    const emptyRow = document.getElementById('employeeEvaluationFilterEmptyRow');
    const prevBtn = document.getElementById('employeeEvaluationPrevPage');
    const nextBtn = document.getElementById('employeeEvaluationNextPage');
    const pageLabel = document.getElementById('employeeEvaluationPageLabel');

    let currentPage = 1;
    const visibility = new Map();
    rows.forEach((row) => visibility.set(row, true));

    const render = () => {
        const visibleRows = rows.filter((row) => visibility.get(row));
        const totalPages = Math.max(1, Math.ceil(visibleRows.length / PAGE_SIZE));
        if (currentPage > totalPages) {
            currentPage = totalPages;
        }

        const start = (currentPage - 1) * PAGE_SIZE;
        const end = start + PAGE_SIZE;

        rows.forEach((row) => {
            if (!visibility.get(row)) {
                row.classList.add('hidden');
                return;
            }

            const index = visibleRows.indexOf(row);
            row.classList.toggle('hidden', !(index >= start && index < end));
        });

        if (emptyRow) {
            emptyRow.classList.toggle('hidden', visibleRows.length > 0);
        }

        if (pageLabel) {
            pageLabel.textContent = `Page ${currentPage} of ${totalPages}`;
        }

        if (prevBtn) {
            prevBtn.disabled = currentPage <= 1;
        }

        if (nextBtn) {
            nextBtn.disabled = currentPage >= totalPages;
        }
    };

    const applyFilters = () => {
        const query = normalize(searchInput ? searchInput.value : '');
        const status = normalize(statusFilter ? statusFilter.value : '');

        rows.forEach((row) => {
            const haystack = normalize(row.getAttribute('data-employee-evaluation-search'));
            const rowStatus = normalize(row.getAttribute('data-employee-evaluation-status'));
            const visible = (query === '' || haystack.includes(query))
                && (status === '' || rowStatus === status);
            visibility.set(row, visible);
        });

        currentPage = 1;
        render();
    };

    if (searchInput) {
        searchInput.addEventListener('input', debounce(applyFilters, 200));
    }

    if (statusFilter) {
        statusFilter.addEventListener('change', applyFilters);
    }

    if (prevBtn) {
        prevBtn.addEventListener('click', () => {
            if (currentPage <= 1) {
                return;
            }
            currentPage -= 1;
            render();
        });
    }

    if (nextBtn) {
        nextBtn.addEventListener('click', () => {
            currentPage += 1;
            render();
        });
    }

    applyFilters();
})();
