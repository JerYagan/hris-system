(() => {
    const normalize = (value) => (value || '').toString().trim().toLowerCase();

    const searchInput = document.getElementById('praiseNominationSearchInput');
    const statusFilter = document.getElementById('praiseNominationStatusFilter');
    const rows = Array.from(document.querySelectorAll('[data-praise-nomination-row]'));
    const emptyRow = document.getElementById('praiseNominationFilterEmptyRow');

    const modal = document.getElementById('praiseNominationReviewModal');
    const modalOpenButtons = Array.from(document.querySelectorAll('[data-open-praise-nomination-modal]'));
    const modalCloseButton = document.getElementById('praiseNominationModalClose');
    const modalCancelButton = document.getElementById('praiseNominationModalCancel');
    const reviewForm = document.getElementById('praiseNominationReviewForm');
    const submitButton = document.getElementById('praiseNominationSubmit');

    const nominationIdInput = document.getElementById('praiseNominationIdInput');
    const nomineeLabel = document.getElementById('praiseNomineeLabel');
    const awardLabel = document.getElementById('praiseAwardLabel');
    const currentStatusLabel = document.getElementById('praiseNominationCurrentStatusLabel');
    const decisionSelect = document.getElementById('praiseNominationDecisionSelect');

    const applyFilters = () => {
        if (!searchInput || !statusFilter || rows.length === 0) {
            return;
        }

        const query = normalize(searchInput.value);
        const status = normalize(statusFilter.value);
        let visibleCount = 0;

        rows.forEach((row) => {
            const rowSearch = normalize(row.getAttribute('data-praise-nomination-search'));
            const rowStatus = normalize(row.getAttribute('data-praise-nomination-status'));
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
        if (!modal || !nominationIdInput) {
            return;
        }

        const nominationId = button.getAttribute('data-nomination-id') || '';
        const nomineeName = button.getAttribute('data-nominee-name') || '-';
        const awardName = button.getAttribute('data-award-name') || '-';
        const currentStatus = button.getAttribute('data-current-status') || '';
        const currentStatusText = button.getAttribute('data-current-status-label') || '-';

        nominationIdInput.value = nominationId;
        if (nomineeLabel) {
            nomineeLabel.textContent = nomineeName;
        }
        if (awardLabel) {
            awardLabel.textContent = awardName;
        }
        if (currentStatusLabel) {
            currentStatusLabel.textContent = currentStatusText;
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
            const nominee = nomineeLabel ? nomineeLabel.textContent || 'Nominee' : 'Nominee';
            const oldStatus = currentStatusLabel ? currentStatusLabel.textContent || '-' : '-';
            const decision = normalize(decisionSelect ? decisionSelect.value : '');

            if (decision === '') {
                return;
            }

            const decisionLabel = decision.replaceAll('_', ' ');
            const shouldContinue = window.confirm(`Confirm nomination status change for "${nominee}" from "${oldStatus}" to "${decisionLabel}"?`);
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
