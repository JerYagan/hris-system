(() => {
    const searchInput = document.getElementById('recruitmentSearchInput');
    const statusFilter = document.getElementById('recruitmentStatusFilter');
    const tableRows = Array.from(document.querySelectorAll('[data-recruitment-row]'));
    const filterEmptyRow = document.getElementById('recruitmentFilterEmptyRow');

    const modal = document.getElementById('postingStatusModal');
    const openButtons = Array.from(document.querySelectorAll('[data-open-posting-status-modal]'));
    const closeButton = document.getElementById('postingStatusModalClose');
    const cancelButton = document.getElementById('postingStatusModalCancel');
    const form = document.getElementById('postingStatusForm');
    const submitButton = document.getElementById('postingStatusSubmit');

    const postingIdInput = document.getElementById('postingStatusPostingId');
    const postingTitle = document.getElementById('postingStatusTitle');
    const currentStatus = document.getElementById('postingStatusCurrent');
    const newStatusInput = document.getElementById('postingStatusNew');

    if (!searchInput || !statusFilter || tableRows.length === 0) {
        return;
    }

    const normalize = (value) => (value || '').toString().trim().toLowerCase();

    const applyFilters = () => {
        const query = normalize(searchInput.value);
        const status = normalize(statusFilter.value);
        let visibleCount = 0;

        tableRows.forEach((row) => {
            const haystack = normalize(row.getAttribute('data-recruitment-search'));
            const rowStatus = normalize(row.getAttribute('data-recruitment-status'));
            const visible = (query === '' || haystack.includes(query)) && (status === '' || rowStatus === status);
            row.classList.toggle('hidden', !visible);
            if (visible) {
                visibleCount += 1;
            }
        });

        if (filterEmptyRow) {
            filterEmptyRow.classList.toggle('hidden', visibleCount > 0);
        }
    };

    let debounceTimer = null;
    searchInput.addEventListener('input', () => {
        if (debounceTimer) {
            window.clearTimeout(debounceTimer);
        }
        debounceTimer = window.setTimeout(applyFilters, 150);
    });
    statusFilter.addEventListener('change', applyFilters);

    const closeModal = () => {
        if (!modal) {
            return;
        }

        modal.classList.add('hidden');
        modal.classList.remove('flex');
        if (form) {
            form.reset();
        }
    };

    openButtons.forEach((button) => {
        button.addEventListener('click', () => {
            if (!modal || !postingIdInput || !postingTitle || !currentStatus || !newStatusInput) {
                return;
            }

            postingIdInput.value = button.getAttribute('data-posting-id') || '';
            postingTitle.textContent = button.getAttribute('data-posting-title') || 'Posting';
            currentStatus.textContent = button.getAttribute('data-current-status-label') || '-';
            newStatusInput.value = button.getAttribute('data-current-status') || '';

            modal.classList.remove('hidden');
            modal.classList.add('flex');
        });
    });

    if (closeButton) {
        closeButton.addEventListener('click', closeModal);
    }

    if (cancelButton) {
        cancelButton.addEventListener('click', closeModal);
    }

    if (modal) {
        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeModal();
            }
        });
    }

    if (form) {
        form.addEventListener('submit', (event) => {
            const title = (postingTitle ? postingTitle.textContent : 'posting') || 'posting';
            const oldLabel = (currentStatus ? currentStatus.textContent : '-') || '-';
            const newValue = normalize(newStatusInput ? newStatusInput.value : '');
            if (newValue === '') {
                return;
            }

            const newLabel = newValue.replace('_', ' ');
            const shouldContinue = window.confirm(`Confirm status change for "${title}" from "${oldLabel}" to "${newLabel}"?`);
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

    applyFilters();
})();