(() => {
    const searchInput = document.getElementById('documentSearchInput');
    const statusFilter = document.getElementById('documentStatusFilter');
    const categoryFilter = document.getElementById('documentCategoryFilter');
    const groupFilter = document.getElementById('documentGroupFilter');
    const filter201Type = document.getElementById('document201TypeFilter');
    const tableRows = Array.from(document.querySelectorAll('[data-doc-row]'));
    const filterEmptyRow = document.getElementById('documentFilterEmptyRow');

    const modal = document.getElementById('documentReviewModal');
    const openModalButtons = Array.from(document.querySelectorAll('[data-open-review-modal]'));
    const closeModalButton = document.getElementById('documentReviewModalClose');
    const cancelModalButton = document.getElementById('documentReviewModalCancel');
    const reviewForm = document.getElementById('documentReviewForm');
    const submitButton = document.getElementById('documentReviewSubmit');

    const documentIdInput = document.getElementById('reviewDocumentId');
    const documentTitleLabel = document.getElementById('reviewDocumentTitle');
    const documentStatusLabel = document.getElementById('reviewDocumentCurrentStatus');
    const reviewStatusSelect = document.getElementById('reviewStatusSelect');

    if (!searchInput || !statusFilter || tableRows.length === 0) {
        return;
    }

    const normalize = (value) => (value || '').toString().trim().toLowerCase();

    const applyFilters = () => {
        const query = normalize(searchInput.value);
        const status = normalize(statusFilter.value);
        const category = normalize(categoryFilter ? categoryFilter.value : '');
        const group = normalize(groupFilter ? groupFilter.value : 'all');
        const file201Type = normalize(filter201Type ? filter201Type.value : '');
        let visibleCount = 0;

        tableRows.forEach((row) => {
            const haystack = normalize(row.getAttribute('data-doc-search'));
            const rowStatus = normalize(row.getAttribute('data-doc-status'));
            const rowCategory = normalize(row.getAttribute('data-doc-category'));
            const rowGroup = normalize(row.getAttribute('data-doc-group'));
            const row201Type = normalize(row.getAttribute('data-doc-201-type'));

            const matchesSearch = query === '' || haystack.includes(query);
            const matchesStatus = status === '' || rowStatus === status;
            const matchesCategory = category === '' || rowCategory === category;
            const matchesGroup = group === 'all' || rowGroup === group;
            const matches201Type = file201Type === '' || row201Type === file201Type;
            const visible = matchesSearch && matchesStatus && matchesCategory && matchesGroup && matches201Type;

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
    if (categoryFilter) {
        categoryFilter.addEventListener('change', applyFilters);
    }
    if (groupFilter) {
        groupFilter.addEventListener('change', () => {
            const selectedGroup = normalize(groupFilter.value);
            if (filter201Type && selectedGroup !== '201') {
                filter201Type.value = '';
            }
            applyFilters();
        });
    }
    if (filter201Type) {
        filter201Type.addEventListener('change', () => {
            if (groupFilter && normalize(groupFilter.value) !== '201' && normalize(filter201Type.value) !== '') {
                groupFilter.value = '201';
            }
            applyFilters();
        });
    }

    const openModal = (button) => {
        if (!modal || !documentIdInput || !documentTitleLabel || !documentStatusLabel) {
            return;
        }

        const documentId = button.getAttribute('data-document-id') || '';
        const documentTitle = button.getAttribute('data-document-title') || 'Document';
        const currentStatus = button.getAttribute('data-current-status') || '-';

        documentIdInput.value = documentId;
        documentTitleLabel.textContent = documentTitle;
        documentStatusLabel.textContent = currentStatus;

        if (reviewStatusSelect) {
            reviewStatusSelect.value = '';
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
        if (reviewForm) {
            reviewForm.reset();
        }
    };

    openModalButtons.forEach((button) => {
        button.addEventListener('click', () => openModal(button));
    });

    if (closeModalButton) {
        closeModalButton.addEventListener('click', closeModal);
    }

    if (cancelModalButton) {
        cancelModalButton.addEventListener('click', closeModal);
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
            const statusValue = normalize(reviewStatusSelect ? reviewStatusSelect.value : '');
            if (statusValue === '') {
                return;
            }

            const decisionLabel = statusValue.replace('_', ' ');
            const documentTitle = (documentTitleLabel ? documentTitleLabel.textContent : 'Document') || 'Document';
            const oldStatus = (documentStatusLabel ? documentStatusLabel.textContent : '-') || '-';
            const shouldContinue = window.confirm(`Confirm status change for "${documentTitle}" from "${oldStatus}" to "${decisionLabel}"?`);

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