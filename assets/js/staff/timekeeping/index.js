(() => {
    const normalize = (value) => (value || '').toString().trim().toLowerCase();

    const bindTableFilters = ({ searchId, statusId, rowSelector, emptyRowId, searchAttr, statusAttr }) => {
        const searchInput = document.getElementById(searchId);
        const statusFilter = document.getElementById(statusId);
        const rows = Array.from(document.querySelectorAll(rowSelector));
        const emptyRow = document.getElementById(emptyRowId);

        if (!searchInput || !statusFilter || rows.length === 0) {
            return;
        }

        const applyFilters = () => {
            const query = normalize(searchInput.value);
            const status = normalize(statusFilter.value);
            let visibleCount = 0;

            rows.forEach((row) => {
                const rowSearch = normalize(row.getAttribute(searchAttr));
                const rowStatus = normalize(row.getAttribute(statusAttr));
                const isVisible = (query === '' || rowSearch.includes(query)) && (status === '' || rowStatus === status);
                row.classList.toggle('hidden', !isVisible);
                if (isVisible) {
                    visibleCount += 1;
                }
            });

            if (emptyRow) {
                emptyRow.classList.toggle('hidden', visibleCount > 0);
            }
        };

        let debounceTimer;
        searchInput.addEventListener('input', () => {
            if (debounceTimer) {
                window.clearTimeout(debounceTimer);
            }
            debounceTimer = window.setTimeout(applyFilters, 150);
        });

        statusFilter.addEventListener('change', applyFilters);
        applyFilters();
    };

    const bindModalWorkflow = ({
        modalId,
        formId,
        submitId,
        closeId,
        cancelId,
        openSelector,
        fill,
        titleField,
        currentField,
        decisionField,
        entityLabel,
    }) => {
        const modal = document.getElementById(modalId);
        const form = document.getElementById(formId);
        const submitButton = document.getElementById(submitId);
        const closeButton = document.getElementById(closeId);
        const cancelButton = document.getElementById(cancelId);
        const openButtons = Array.from(document.querySelectorAll(openSelector));

        if (!modal || !form || openButtons.length === 0) {
            return;
        }

        const closeModal = () => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            form.reset();
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.classList.remove('opacity-60', 'cursor-not-allowed');
            }
        };

        openButtons.forEach((button) => {
            button.addEventListener('click', () => {
                fill(button);
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

        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeModal();
            }
        });

        form.addEventListener('submit', (event) => {
            const titleText = titleField ? titleField.textContent || entityLabel : entityLabel;
            const oldStatusLabel = currentField ? currentField.textContent || '-' : '-';
            const decisionValue = normalize(decisionField ? decisionField.value : '');

            if (decisionValue === '') {
                return;
            }

            const readableDecision = decisionValue.replaceAll('_', ' ');
            const confirmText = `Confirm ${entityLabel} status change for "${titleText}" from "${oldStatusLabel}" to "${readableDecision}"?`;
            if (!window.confirm(confirmText)) {
                event.preventDefault();
                return;
            }

            if (submitButton) {
                submitButton.disabled = true;
                submitButton.classList.add('opacity-60', 'cursor-not-allowed');
            }
        });
    };

    bindTableFilters({
        searchId: 'leaveSearchInput',
        statusId: 'leaveStatusFilter',
        rowSelector: '[data-leave-row]',
        emptyRowId: 'leaveFilterEmptyRow',
        searchAttr: 'data-leave-search',
        statusAttr: 'data-leave-status',
    });

    bindTableFilters({
        searchId: 'overtimeSearchInput',
        statusId: 'overtimeStatusFilter',
        rowSelector: '[data-overtime-row]',
        emptyRowId: 'overtimeFilterEmptyRow',
        searchAttr: 'data-overtime-search',
        statusAttr: 'data-overtime-status',
    });

    bindTableFilters({
        searchId: 'adjustmentSearchInput',
        statusId: 'adjustmentStatusFilter',
        rowSelector: '[data-adjustment-row]',
        emptyRowId: 'adjustmentFilterEmptyRow',
        searchAttr: 'data-adjustment-search',
        statusAttr: 'data-adjustment-status',
    });

    const leaveId = document.getElementById('leaveRequestId');
    const leaveEmployee = document.getElementById('leaveEmployeeName');
    const leaveCurrent = document.getElementById('leaveCurrentStatus');
    const leaveDateRange = document.getElementById('leaveDateRange');
    const leaveDecision = document.getElementById('leaveDecision');

    bindModalWorkflow({
        modalId: 'leaveRequestModal',
        formId: 'leaveForm',
        submitId: 'leaveSubmit',
        closeId: 'leaveModalClose',
        cancelId: 'leaveModalCancel',
        openSelector: '[data-open-leave-modal]',
        fill: (button) => {
            if (leaveId) {
                leaveId.value = button.getAttribute('data-request-id') || '';
            }
            if (leaveEmployee) {
                leaveEmployee.textContent = button.getAttribute('data-employee-name') || '-';
            }
            if (leaveCurrent) {
                leaveCurrent.textContent = button.getAttribute('data-current-status-label') || '-';
            }
            if (leaveDateRange) {
                leaveDateRange.textContent = button.getAttribute('data-date-range') || '-';
            }
            if (leaveDecision) {
                leaveDecision.value = button.getAttribute('data-current-status') || '';
            }
        },
        titleField: leaveEmployee,
        currentField: leaveCurrent,
        decisionField: leaveDecision,
        entityLabel: 'leave request',
    });

    const overtimeId = document.getElementById('overtimeRequestId');
    const overtimeEmployee = document.getElementById('overtimeEmployeeName');
    const overtimeCurrent = document.getElementById('overtimeCurrentStatus');
    const overtimeWindow = document.getElementById('overtimeRequestedWindow');
    const overtimeDecision = document.getElementById('overtimeDecision');

    bindModalWorkflow({
        modalId: 'overtimeRequestModal',
        formId: 'overtimeForm',
        submitId: 'overtimeSubmit',
        closeId: 'overtimeModalClose',
        cancelId: 'overtimeModalCancel',
        openSelector: '[data-open-overtime-modal]',
        fill: (button) => {
            if (overtimeId) {
                overtimeId.value = button.getAttribute('data-request-id') || '';
            }
            if (overtimeEmployee) {
                overtimeEmployee.textContent = button.getAttribute('data-employee-name') || '-';
            }
            if (overtimeCurrent) {
                overtimeCurrent.textContent = button.getAttribute('data-current-status-label') || '-';
            }
            if (overtimeWindow) {
                overtimeWindow.textContent = button.getAttribute('data-requested-window') || '-';
            }
            if (overtimeDecision) {
                overtimeDecision.value = button.getAttribute('data-current-status') || '';
            }
        },
        titleField: overtimeEmployee,
        currentField: overtimeCurrent,
        decisionField: overtimeDecision,
        entityLabel: 'overtime request',
    });

    const adjustmentId = document.getElementById('adjustmentRequestId');
    const adjustmentEmployee = document.getElementById('adjustmentEmployeeName');
    const adjustmentCurrent = document.getElementById('adjustmentCurrentStatus');
    const adjustmentWindow = document.getElementById('adjustmentRequestedWindow');
    const adjustmentDecision = document.getElementById('adjustmentDecision');

    bindModalWorkflow({
        modalId: 'adjustmentRequestModal',
        formId: 'adjustmentForm',
        submitId: 'adjustmentSubmit',
        closeId: 'adjustmentModalClose',
        cancelId: 'adjustmentModalCancel',
        openSelector: '[data-open-adjustment-modal]',
        fill: (button) => {
            if (adjustmentId) {
                adjustmentId.value = button.getAttribute('data-request-id') || '';
            }
            if (adjustmentEmployee) {
                adjustmentEmployee.textContent = button.getAttribute('data-employee-name') || '-';
            }
            if (adjustmentCurrent) {
                adjustmentCurrent.textContent = button.getAttribute('data-current-status-label') || '-';
            }
            if (adjustmentWindow) {
                adjustmentWindow.textContent = button.getAttribute('data-requested-window') || '-';
            }
            if (adjustmentDecision) {
                adjustmentDecision.value = button.getAttribute('data-current-status') || '';
            }
        },
        titleField: adjustmentEmployee,
        currentField: adjustmentCurrent,
        decisionField: adjustmentDecision,
        entityLabel: 'time adjustment',
    });
})();
