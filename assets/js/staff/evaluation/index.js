(() => {
    const normalize = (value) => (value || '').toString().trim().toLowerCase();

    const searchInput = document.getElementById('evaluationSearchInput');
    const statusFilter = document.getElementById('evaluationStatusFilter');
    const rows = Array.from(document.querySelectorAll('[data-evaluation-row]'));
    const emptyRow = document.getElementById('evaluationFilterEmptyRow');

    const modal = document.getElementById('evaluationReviewModal');
    const modalOpenButtons = Array.from(document.querySelectorAll('[data-open-evaluation-modal]'));
    const modalCloseButton = document.getElementById('evaluationModalClose');
    const modalCancelButton = document.getElementById('evaluationModalCancel');
    const reviewForm = document.getElementById('evaluationReviewForm');
    const submitButton = document.getElementById('evaluationSubmit');

    const evaluationIdInput = document.getElementById('evaluationIdInput');
    const employeeLabel = document.getElementById('evaluationEmployeeLabel');
    const cycleLabel = document.getElementById('evaluationCycleLabel');
    const currentStatusLabel = document.getElementById('evaluationCurrentStatusLabel');
    const decisionSelect = document.getElementById('evaluationDecisionSelect');

    const applyFilters = () => {
        if (!searchInput || !statusFilter || rows.length === 0) {
            return;
        }

        const query = normalize(searchInput.value);
        const status = normalize(statusFilter.value);
        let visibleCount = 0;

        rows.forEach((row) => {
            const rowSearch = normalize(row.getAttribute('data-evaluation-search'));
            const rowStatus = normalize(row.getAttribute('data-evaluation-status'));
            const visible = (query === '' || rowSearch.includes(query)) && (status === '' || rowStatus === status);

            row.classList.toggle('hidden', !visible);
            if (visible) {
                visibleCount += 1;
            }
        });

        if (emptyRow) {
            emptyRow.classList.toggle('hidden', visibleCount > 0);
        }
    };

    if (searchInput && statusFilter && rows.length > 0) {
        let debounceTimer = null;
        searchInput.addEventListener('input', () => {
            if (debounceTimer) {
                window.clearTimeout(debounceTimer);
            }
            debounceTimer = window.setTimeout(applyFilters, 150);
        });
        statusFilter.addEventListener('change', applyFilters);
        applyFilters();
    }

    const closeModal = () => {
        if (!modal) {
            return;
        }

        modal.classList.add('hidden');
        modal.classList.remove('flex');
        if (reviewForm) {
            reviewForm.reset();
        }
        if (submitButton) {
            submitButton.disabled = false;
            submitButton.classList.remove('opacity-60', 'cursor-not-allowed');
        }
    };

    const openModal = (button) => {
        if (!modal || !evaluationIdInput) {
            return;
        }

        const evaluationId = button.getAttribute('data-evaluation-id') || '';
        const employeeName = button.getAttribute('data-employee-name') || '-';
        const cycleName = button.getAttribute('data-cycle-name') || '-';
        const currentStatus = button.getAttribute('data-current-status') || '';
        const currentStatusLabelText = button.getAttribute('data-current-status-label') || '-';

        evaluationIdInput.value = evaluationId;
        if (employeeLabel) {
            employeeLabel.textContent = employeeName;
        }
        if (cycleLabel) {
            cycleLabel.textContent = cycleName;
        }
        if (currentStatusLabel) {
            currentStatusLabel.textContent = currentStatusLabelText;
        }
        if (decisionSelect) {
            decisionSelect.value = currentStatus;
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');
    };

    modalOpenButtons.forEach((button) => {
        button.addEventListener('click', () => openModal(button));
    });

    if (modalCloseButton) {
        modalCloseButton.addEventListener('click', closeModal);
    }
    if (modalCancelButton) {
        modalCancelButton.addEventListener('click', closeModal);
    }

    if (modal) {
        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeModal();
            }
        });
    }

    if (reviewForm) {
        reviewForm.addEventListener('submit', (event) => {
            const employee = employeeLabel ? employeeLabel.textContent || 'Employee' : 'Employee';
            const oldStatus = currentStatusLabel ? currentStatusLabel.textContent || '-' : '-';
            const decision = normalize(decisionSelect ? decisionSelect.value : '');

            if (decision === '') {
                return;
            }

            const decisionLabel = decision.replaceAll('_', ' ');
            const shouldContinue = window.confirm(`Confirm evaluation status change for "${employee}" from "${oldStatus}" to "${decisionLabel}"?`);
            if (!shouldContinue) {
                event.preventDefault();
                return;
            }

            if (submitButton) {
                submitButton.disabled = true;
                submitButton.classList.add('opacity-60', 'cursor-not-allowed');
            }
        });
    }
})();
