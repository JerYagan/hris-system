(() => {
    const normalize = (value) => (value || '').toString().trim().toLowerCase();

    const setupTableFilters = ({ searchInputId, statusFilterId, rowSelector, searchAttr, statusAttr, emptyRowId }) => {
        const searchInput = document.getElementById(searchInputId);
        const statusFilter = document.getElementById(statusFilterId);
        const tableRows = Array.from(document.querySelectorAll(rowSelector));
        const emptyRow = document.getElementById(emptyRowId);

        if (!searchInput || !statusFilter || tableRows.length === 0) {
            return;
        }

        const applyFilters = () => {
            const query = normalize(searchInput.value);
            const status = normalize(statusFilter.value);
            let visibleCount = 0;

            tableRows.forEach((row) => {
                const haystack = normalize(row.getAttribute(searchAttr));
                const rowStatus = normalize(row.getAttribute(statusAttr));

                const matchesSearch = query === '' || haystack.includes(query);
                const matchesStatus = status === '' || rowStatus === status;
                const visible = matchesSearch && matchesStatus;

                row.classList.toggle('hidden', !visible);
                if (visible) {
                    visibleCount += 1;
                }
            });

            if (emptyRow) {
                emptyRow.classList.toggle('hidden', visibleCount > 0);
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
        applyFilters();
    };

    const setupStatusModal = ({
        modalId,
        openButtonSelector,
        closeButtonId,
        cancelButtonId,
        formId,
        submitButtonId,
        idInputId,
        codeLabelId,
        currentStatusLabelId,
        statusSelectId,
        mapButtonData,
        confirmMessage,
    }) => {
        const modal = document.getElementById(modalId);
        const openButtons = Array.from(document.querySelectorAll(openButtonSelector));
        const closeButton = document.getElementById(closeButtonId);
        const cancelButton = document.getElementById(cancelButtonId);
        const form = document.getElementById(formId);
        const submitButton = document.getElementById(submitButtonId);

        const idInput = document.getElementById(idInputId);
        const codeLabel = document.getElementById(codeLabelId);
        const currentStatusLabel = document.getElementById(currentStatusLabelId);
        const statusSelect = document.getElementById(statusSelectId);

        if (!modal || !form || !idInput || !statusSelect) {
            return;
        }

        const openModal = (button) => {
            const mapped = mapButtonData(button);

            idInput.value = mapped.id;
            if (codeLabel) {
                codeLabel.textContent = mapped.code;
            }
            if (currentStatusLabel) {
                currentStatusLabel.textContent = mapped.currentStatusLabel;
            }
            statusSelect.value = '';

            modal.classList.remove('hidden');
            modal.classList.add('flex');
        };

        const closeModal = () => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            form.reset();
        };

        openButtons.forEach((button) => {
            button.addEventListener('click', () => openModal(button));
        });

        if (closeButton) {
            closeButton.addEventListener('click', closeModal);
        }
        if (cancelButton) {
            cancelButton.addEventListener('click', closeModal);
        }

        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeModal();
            }
        });

        form.addEventListener('submit', (event) => {
            const selectedStatus = normalize(statusSelect.value);
            if (selectedStatus === '') {
                return;
            }

            const idLabel = codeLabel ? codeLabel.textContent || 'Record' : 'Record';
            const currentLabel = currentStatusLabel ? currentStatusLabel.textContent || '-' : '-';
            const targetLabel = selectedStatus.replace('_', ' ');
            const shouldContinue = window.confirm(confirmMessage(idLabel, currentLabel, targetLabel));

            if (!shouldContinue) {
                event.preventDefault();
                return;
            }

            if (submitButton) {
                submitButton.disabled = true;
                submitButton.classList.add('opacity-60', 'cursor-not-allowed');
            }
        });
    };

    setupTableFilters({
        searchInputId: 'payrollPeriodSearchInput',
        statusFilterId: 'payrollPeriodStatusFilter',
        rowSelector: '[data-payroll-period-row]',
        searchAttr: 'data-payroll-period-search',
        statusAttr: 'data-payroll-period-status',
        emptyRowId: 'payrollPeriodFilterEmptyRow',
    });

    setupTableFilters({
        searchInputId: 'payrollRunSearchInput',
        statusFilterId: 'payrollRunStatusFilter',
        rowSelector: '[data-payroll-run-row]',
        searchAttr: 'data-payroll-run-search',
        statusAttr: 'data-payroll-run-status',
        emptyRowId: 'payrollRunFilterEmptyRow',
    });

    setupStatusModal({
        modalId: 'payrollPeriodStatusModal',
        openButtonSelector: '[data-open-period-modal]',
        closeButtonId: 'payrollPeriodModalClose',
        cancelButtonId: 'payrollPeriodModalCancel',
        formId: 'payrollPeriodForm',
        submitButtonId: 'payrollPeriodSubmit',
        idInputId: 'payrollPeriodId',
        codeLabelId: 'payrollPeriodCode',
        currentStatusLabelId: 'payrollPeriodCurrentStatus',
        statusSelectId: 'payrollPeriodNewStatus',
        mapButtonData: (button) => ({
            id: button.getAttribute('data-period-id') || '',
            code: button.getAttribute('data-period-code') || 'Payroll Period',
            currentStatusLabel: button.getAttribute('data-current-status-label') || '-',
        }),
        confirmMessage: (idLabel, currentLabel, targetLabel) => `Confirm payroll period status change for "${idLabel}" from "${currentLabel}" to "${targetLabel}"?`,
    });

    setupStatusModal({
        modalId: 'payrollRunStatusModal',
        openButtonSelector: '[data-open-run-modal]',
        closeButtonId: 'payrollRunModalClose',
        cancelButtonId: 'payrollRunModalCancel',
        formId: 'payrollRunForm',
        submitButtonId: 'payrollRunSubmit',
        idInputId: 'payrollRunId',
        codeLabelId: 'payrollRunCode',
        currentStatusLabelId: 'payrollRunCurrentStatus',
        statusSelectId: 'payrollRunNewStatus',
        mapButtonData: (button) => ({
            id: button.getAttribute('data-run-id') || '',
            code: button.getAttribute('data-run-short-id') || 'Payroll Run',
            currentStatusLabel: button.getAttribute('data-current-status-label') || '-',
        }),
        confirmMessage: (idLabel, currentLabel, targetLabel) => `Confirm payroll run status change for "${idLabel}" from "${currentLabel}" to "${targetLabel}"?`,
    });
})();
