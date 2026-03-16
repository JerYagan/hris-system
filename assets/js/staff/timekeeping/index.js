(() => {
    const normalize = (value) => (value || '').toString().trim().toLowerCase();
    const normalizeEmployeeCode = (value) => (value || '').toString().trim().toUpperCase();
    const parseJsonContent = (elementId) => {
        const el = document.getElementById(elementId);
        if (!(el instanceof HTMLScriptElement)) {
            return {};
        }

        try {
            const parsed = JSON.parse(el.textContent || '{}');
            return parsed && typeof parsed === 'object' ? parsed : {};
        } catch {
            return {};
        }
    };

    const rfidEmployeeLookup = parseJsonContent('rfidEmployeeLookupData');

    const bindTableFilters = ({
        searchId,
        statusId,
        rowSelector,
        emptyRowId,
        searchAttr,
        statusAttr,
        paginationInfoId,
        prevPageId,
        nextPageId,
        extraPredicate,
        extraFilterTriggerIds,
    }) => {
        const searchInput = searchId ? document.getElementById(searchId) : null;
        const statusFilter = statusId ? document.getElementById(statusId) : null;
        const rows = Array.from(document.querySelectorAll(rowSelector));
        const extraFilterTriggers = Array.isArray(extraFilterTriggerIds)
            ? extraFilterTriggerIds
                .map((id) => document.getElementById(id))
                .filter((element) => element)
            : [];
        const emptyRow = document.getElementById(emptyRowId);
        const paginationInfo = document.getElementById(paginationInfoId);
        const prevPageButton = document.getElementById(prevPageId);
        const nextPageButton = document.getElementById(nextPageId);

        const pageSize = 10;
        let currentPage = 1;
        let currentFilteredRows = rows;

        if (rows.length === 0) {
            if (paginationInfo) {
                paginationInfo.textContent = 'Page 1 of 1';
            }
            if (prevPageButton) {
                prevPageButton.disabled = true;
                prevPageButton.classList.add('opacity-60', 'cursor-not-allowed');
            }
            if (nextPageButton) {
                nextPageButton.disabled = true;
                nextPageButton.classList.add('opacity-60', 'cursor-not-allowed');
            }
            return;
        }

        const updatePaginationUi = (totalFilteredRows) => {
            const totalPages = Math.max(1, Math.ceil(totalFilteredRows / pageSize));
            if (currentPage > totalPages) {
                currentPage = totalPages;
            }

            if (paginationInfo) {
                paginationInfo.textContent = `Page ${currentPage} of ${totalPages}`;
            }

            if (prevPageButton) {
                const disabled = currentPage <= 1 || totalFilteredRows === 0;
                prevPageButton.disabled = disabled;
                prevPageButton.classList.toggle('opacity-60', disabled);
                prevPageButton.classList.toggle('cursor-not-allowed', disabled);
            }

            if (nextPageButton) {
                const disabled = currentPage >= totalPages || totalFilteredRows === 0;
                nextPageButton.disabled = disabled;
                nextPageButton.classList.toggle('opacity-60', disabled);
                nextPageButton.classList.toggle('cursor-not-allowed', disabled);
            }
        };

        const renderCurrentPage = () => {
            const totalFilteredRows = currentFilteredRows.length;
            const start = (currentPage - 1) * pageSize;
            const end = start + pageSize;

            rows.forEach((row) => row.classList.add('hidden'));
            currentFilteredRows.slice(start, end).forEach((row) => row.classList.remove('hidden'));

            if (emptyRow) {
                emptyRow.classList.toggle('hidden', totalFilteredRows > 0);
            }

            updatePaginationUi(totalFilteredRows);
        };

        const applyFilters = () => {
            const query = normalize(searchInput ? searchInput.value : '');
            const status = normalize(statusFilter ? statusFilter.value : '');

            currentFilteredRows = rows.filter((row) => {
                const rowSearch = normalize(searchAttr ? row.getAttribute(searchAttr) : '');
                const rowStatus = normalize(statusAttr ? row.getAttribute(statusAttr) : '');

                const matchesSearch = searchInput ? (query === '' || rowSearch.includes(query)) : true;
                const matchesStatus = statusFilter ? (status === '' || rowStatus === status) : true;
                const matchesExtra = typeof extraPredicate === 'function' ? extraPredicate(row) : true;
                return matchesSearch && matchesStatus && matchesExtra;
            });

            currentPage = 1;
            renderCurrentPage();
        };

        if (searchInput) {
            let debounceTimer;
            searchInput.addEventListener('input', () => {
                if (debounceTimer) {
                    window.clearTimeout(debounceTimer);
                }
                debounceTimer = window.setTimeout(applyFilters, 150);
            });
        }

        if (statusFilter) {
            statusFilter.addEventListener('change', applyFilters);
        }

        extraFilterTriggers.forEach((element) => {
            const eventType = element.tagName === 'INPUT' ? 'input' : 'change';
            element.addEventListener(eventType, applyFilters);
        });

        if (prevPageButton) {
            prevPageButton.addEventListener('click', () => {
                if (currentPage <= 1) {
                    return;
                }
                currentPage -= 1;
                renderCurrentPage();
            });
        }

        if (nextPageButton) {
            nextPageButton.addEventListener('click', () => {
                const totalPages = Math.max(1, Math.ceil(currentFilteredRows.length / pageSize));
                if (currentPage >= totalPages) {
                    return;
                }
                currentPage += 1;
                renderCurrentPage();
            });
        }

        applyFilters();
    };

    const getManilaTodayIsoDate = () => {
        const formatter = new Intl.DateTimeFormat('en-CA', {
            timeZone: 'Asia/Manila',
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
        });

        return formatter.format(new Date());
    };

    const attendanceDatePreset = document.getElementById('attendanceDatePreset');
    const attendanceDateFrom = document.getElementById('attendanceDateFrom');
    const attendanceDateTo = document.getElementById('attendanceDateTo');
    const attendanceTodayIso = getManilaTodayIsoDate();

    const showFilterWarning = (message) => {
        if (window.Swal && typeof window.Swal.fire === 'function') {
            window.Swal.fire({
                icon: 'warning',
                title: 'Invalid filter range',
                text: message,
                confirmButtonColor: '#166534',
            });
            return;
        }

        window.alert(message);
    };

    const syncAttendanceDateInputs = () => {
        const preset = normalize(attendanceDatePreset ? attendanceDatePreset.value : 'today');
        if (!attendanceDateFrom || !attendanceDateTo) {
            return;
        }

        if (preset === 'today') {
            attendanceDateFrom.value = attendanceTodayIso;
            attendanceDateTo.value = attendanceTodayIso;
            attendanceDateFrom.disabled = true;
            attendanceDateTo.disabled = true;
            return;
        }

        attendanceDateFrom.disabled = false;
        attendanceDateTo.disabled = false;

        if (preset === 'all') {
            attendanceDateFrom.value = '';
            attendanceDateTo.value = '';
        }
    };

    if (attendanceDatePreset) {
        syncAttendanceDateInputs();
        attendanceDatePreset.addEventListener('change', syncAttendanceDateInputs);
    }

    if (window.flatpickr && attendanceDateFrom && attendanceDateTo) {
        [attendanceDateFrom, attendanceDateTo].forEach((input) => {
            if (!input || input.dataset.flatpickrInitialized === 'true') {
                return;
            }

            window.flatpickr(input, {
                dateFormat: 'Y-m-d',
                allowInput: true,
            });

            input.dataset.flatpickrInitialized = 'true';
        });
    }

    if (attendanceDateFrom && attendanceDateTo) {
        const validateAttendanceRange = () => {
            const fromValue = normalize(attendanceDateFrom.value).slice(0, 10);
            const toValue = normalize(attendanceDateTo.value).slice(0, 10);

            if (fromValue !== '' && toValue !== '' && fromValue > toValue) {
                attendanceDateTo.value = fromValue;
                showFilterWarning('From date cannot be later than To date. The To date was adjusted automatically.');
            }
        };

        attendanceDateFrom.addEventListener('change', validateAttendanceRange);
        attendanceDateTo.addEventListener('change', validateAttendanceRange);
    }

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
            if (form.dataset.confirmed === '1') {
                form.dataset.confirmed = '0';
                return;
            }

            const titleText = titleField ? titleField.textContent || entityLabel : entityLabel;
            const oldStatusLabel = currentField ? currentField.textContent || '-' : '-';
            const decisionValue = normalize(decisionField ? decisionField.value : '');

            if (decisionValue === '') {
                return;
            }

            event.preventDefault();

            const readableDecision = decisionValue.replaceAll('_', ' ');
            const confirmText = `Submit recommendation for ${entityLabel} of "${titleText}" from "${oldStatusLabel}" to "${readableDecision}"? Final approval will be done by admin.`;

            const continueSubmit = () => {
                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.classList.add('opacity-60', 'cursor-not-allowed');
                }

                form.dataset.confirmed = '1';
                form.requestSubmit();
            };

            if (window.Swal && typeof window.Swal.fire === 'function') {
                window.Swal.fire({
                    title: 'Submit recommendation?',
                    text: confirmText,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, submit recommendation',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#166534',
                }).then((result) => {
                    if (result.isConfirmed) {
                        continueSubmit();
                    }
                });

                return;
            }

            if (window.confirm(confirmText)) {
                continueSubmit();
            }
        });
    };

    const setDefaultDecision = (selectEl, preferredValue) => {
        if (!selectEl) {
            return;
        }

        const preferred = normalize(preferredValue);
        const options = Array.from(selectEl.options || []);

        const matched = options.find((option) => normalize(option.value) === preferred && normalize(option.value) !== '');
        if (matched) {
            selectEl.value = matched.value;
            return;
        }

        const firstAction = options.find((option) => normalize(option.value) !== '');
        if (firstAction) {
            selectEl.value = firstAction.value;
        }
    };

    bindTableFilters({
        searchId: null,
        statusId: null,
        rowSelector: '[data-attendance-row]',
        emptyRowId: '',
        searchAttr: null,
        statusAttr: null,
        paginationInfoId: 'attendancePaginationInfo',
        prevPageId: 'attendancePrevPage',
        nextPageId: 'attendanceNextPage',
        extraFilterTriggerIds: ['attendanceDatePreset', 'attendanceDateFrom', 'attendanceDateTo'],
        extraPredicate: (row) => {
            const rowDate = normalize(row.getAttribute('data-attendance-date') || '').slice(0, 10);
            if (rowDate === '') {
                return false;
            }

            const preset = normalize(attendanceDatePreset ? attendanceDatePreset.value : 'today');
            const fromDate = normalize(attendanceDateFrom ? attendanceDateFrom.value : '').slice(0, 10);
            const toDate = normalize(attendanceDateTo ? attendanceDateTo.value : '').slice(0, 10);

            if (preset === 'today') {
                return rowDate === attendanceTodayIso;
            }

            if (preset === 'all' && fromDate === '' && toDate === '') {
                return true;
            }

            if (fromDate !== '' && rowDate < fromDate) {
                return false;
            }

            if (toDate !== '' && rowDate > toDate) {
                return false;
            }

            return true;
        },
    });

    bindTableFilters({
        searchId: 'leaveSearchInput',
        statusId: 'leaveStatusFilter',
        rowSelector: '[data-leave-row]',
        emptyRowId: 'leaveFilterEmptyRow',
        searchAttr: 'data-leave-search',
        statusAttr: 'data-leave-status',
        paginationInfoId: 'leavePaginationInfo',
        prevPageId: 'leavePrevPage',
        nextPageId: 'leaveNextPage',
    });

    bindTableFilters({
        searchId: 'obSearchInput',
        statusId: 'obStatusFilter',
        rowSelector: '[data-ob-row]',
        emptyRowId: 'obFilterEmptyRow',
        searchAttr: 'data-ob-search',
        statusAttr: 'data-ob-status',
        paginationInfoId: 'obPaginationInfo',
        prevPageId: 'obPrevPage',
        nextPageId: 'obNextPage',
    });

    bindTableFilters({
        searchId: 'adjustmentSearchInput',
        statusId: 'adjustmentStatusFilter',
        rowSelector: '[data-adjustment-row]',
        emptyRowId: 'adjustmentFilterEmptyRow',
        searchAttr: 'data-adjustment-search',
        statusAttr: 'data-adjustment-status',
        paginationInfoId: 'adjustmentPaginationInfo',
        prevPageId: 'adjustmentPrevPage',
        nextPageId: 'adjustmentNextPage',
    });

    const leaveId = document.getElementById('leaveRequestId');
    const leaveEmployee = document.getElementById('leaveEmployeeName');
    const leaveCurrent = document.getElementById('leaveCurrentStatus');
    const leaveDateRange = document.getElementById('leaveDateRange');
    const leaveReason = document.getElementById('leaveReason');
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
            if (leaveReason) {
                leaveReason.textContent = button.getAttribute('data-reason') || '-';
            }
            if (leaveDecision) {
                setDefaultDecision(leaveDecision, button.getAttribute('data-current-status') || '');
            }
        },
        titleField: leaveEmployee,
        currentField: leaveCurrent,
        decisionField: leaveDecision,
        entityLabel: 'leave request',
    });

    const obId = document.getElementById('obRequestId');
    const obModalTitle = document.getElementById('obModalTitle');
    const obEmployee = document.getElementById('obEmployeeName');
    const obRequestType = document.getElementById('obRequestTypeLabel');
    const obCurrent = document.getElementById('obCurrentStatus');
    const obWindow = document.getElementById('obRequestedWindow');
    const obReason = document.getElementById('obReason');
    const obDecision = document.getElementById('obDecision');

    bindModalWorkflow({
        modalId: 'obRequestModal',
        formId: 'obForm',
        submitId: 'obSubmit',
        closeId: 'obModalClose',
        cancelId: 'obModalCancel',
        openSelector: '[data-open-ob-modal]',
        fill: (button) => {
            const requestTypeLabel = button.getAttribute('data-request-type-label') || 'Special Request';
            if (obId) {
                obId.value = button.getAttribute('data-request-id') || '';
            }
            if (obModalTitle) {
                obModalTitle.textContent = `Recommend ${requestTypeLabel} Decision`;
            }
            if (obEmployee) {
                obEmployee.textContent = button.getAttribute('data-employee-name') || '-';
            }
            if (obRequestType) {
                obRequestType.textContent = requestTypeLabel;
            }
            if (obCurrent) {
                obCurrent.textContent = button.getAttribute('data-current-status-label') || '-';
            }
            if (obWindow) {
                obWindow.textContent = button.getAttribute('data-requested-window') || '-';
            }
            if (obReason) {
                obReason.textContent = button.getAttribute('data-reason') || '-';
            }
            if (obDecision) {
                setDefaultDecision(obDecision, button.getAttribute('data-current-status') || '');
            }
        },
        titleField: obEmployee,
        currentField: obCurrent,
        decisionField: obDecision,
        entityLabel: 'special timekeeping request',
    });

    const adjustmentId = document.getElementById('adjustmentRequestId');
    const adjustmentEmployee = document.getElementById('adjustmentEmployeeName');
    const adjustmentCurrent = document.getElementById('adjustmentCurrentStatus');
    const adjustmentWindow = document.getElementById('adjustmentRequestedWindow');
    const adjustmentReason = document.getElementById('adjustmentReason');
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
            if (adjustmentReason) {
                adjustmentReason.textContent = button.getAttribute('data-reason') || '-';
            }
            if (adjustmentDecision) {
                setDefaultDecision(adjustmentDecision, button.getAttribute('data-current-status') || '');
            }
        },
        titleField: adjustmentEmployee,
        currentField: adjustmentCurrent,
        decisionField: adjustmentDecision,
        entityLabel: 'time adjustment',
    });

    const rfidForm = document.getElementById('rfidRegistrationForm');
    const rfidSubmit = document.getElementById('rfidGenerateButton');
    const rfidEmployeeIdInput = document.getElementById('rfidEmployeeId');
    const rfidEmployeeNameInput = document.getElementById('rfidEmployeeName');
    const rfidDepartmentInput = document.getElementById('rfidDepartment');
    const rfidPositionInput = document.getElementById('rfidPosition');

    const applyRfidRegistrationAutofill = () => {
        if (!(rfidEmployeeIdInput instanceof HTMLInputElement)) {
            return;
        }

        const employeeCode = normalizeEmployeeCode(rfidEmployeeIdInput.value);
        const record = employeeCode !== '' ? rfidEmployeeLookup[employeeCode] : null;
        if (!record || typeof record !== 'object') {
            return;
        }

        if (rfidEmployeeNameInput instanceof HTMLInputElement && !rfidEmployeeNameInput.value.trim()) {
            rfidEmployeeNameInput.value = (record.employee_name || '').toString();
        }
        if (rfidDepartmentInput instanceof HTMLInputElement && !rfidDepartmentInput.value.trim()) {
            rfidDepartmentInput.value = (record.office_name || '').toString();
        }
        if (rfidPositionInput instanceof HTMLInputElement && !rfidPositionInput.value.trim()) {
            rfidPositionInput.value = (record.position_title || '').toString();
        }
    };

    if (rfidEmployeeIdInput instanceof HTMLInputElement) {
        rfidEmployeeIdInput.addEventListener('blur', applyRfidRegistrationAutofill);
        rfidEmployeeIdInput.addEventListener('change', applyRfidRegistrationAutofill);
    }

    if (rfidForm) {
        rfidForm.addEventListener('submit', (event) => {
            event.preventDefault();

            if (typeof rfidForm.reportValidity === 'function' && !rfidForm.reportValidity()) {
                return;
            }

            const employeeId = (document.getElementById('rfidEmployeeId')?.value || '').trim();
            const employeeName = (document.getElementById('rfidEmployeeName')?.value || '').trim();
            const uidInput = (document.getElementById('rfidCardUid')?.value || '').trim();
            const generatedUid = uidInput !== ''
                ? uidInput
                : `RFID-${Math.random().toString(36).slice(2, 10).toUpperCase()}`;

            const successText = `RFID card prepared for ${employeeName || 'employee'} (${employeeId || 'N/A'}). Card UID: ${generatedUid}.`;

            if (window.Swal && typeof window.Swal.fire === 'function') {
                window.Swal.fire({
                    title: 'RFID generated',
                    text: successText,
                    icon: 'success',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#166534',
                });
            } else {
                window.alert(successText);
            }

            if (rfidSubmit) {
                rfidSubmit.classList.remove('opacity-60', 'cursor-not-allowed');
                rfidSubmit.disabled = false;
            }
        });
    }

    const rfidAttendanceAssistForm = document.getElementById('rfidAttendanceAssistForm');
    const rfidAttendanceEmployeeId = document.getElementById('rfidAttendanceEmployeeId');
    const rfidAttendanceEmployeeName = document.getElementById('rfidAttendanceEmployeeName');

    const applyRfidAttendanceAutofill = () => {
        if (!(rfidAttendanceEmployeeId instanceof HTMLInputElement)) {
            return;
        }

        const employeeCode = normalizeEmployeeCode(rfidAttendanceEmployeeId.value);
        const record = employeeCode !== '' ? rfidEmployeeLookup[employeeCode] : null;
        if (!record || typeof record !== 'object') {
            return;
        }

        if (rfidAttendanceEmployeeName instanceof HTMLInputElement && !rfidAttendanceEmployeeName.value.trim()) {
            rfidAttendanceEmployeeName.value = (record.employee_name || '').toString();
        }
    };

    if (rfidAttendanceEmployeeId instanceof HTMLInputElement) {
        rfidAttendanceEmployeeId.addEventListener('blur', applyRfidAttendanceAutofill);
        rfidAttendanceEmployeeId.addEventListener('change', applyRfidAttendanceAutofill);
    }

    if (rfidAttendanceAssistForm) {
        rfidAttendanceAssistForm.addEventListener('submit', (event) => {
            event.preventDefault();

            if (typeof rfidAttendanceAssistForm.reportValidity === 'function' && !rfidAttendanceAssistForm.reportValidity()) {
                return;
            }

            const employeeId = (document.getElementById('rfidAttendanceEmployeeId')?.value || '').trim();
            const employeeName = (document.getElementById('rfidAttendanceEmployeeName')?.value || '').trim();
            const loggedAt = new Date().toLocaleString('en-PH', {
                timeZone: 'Asia/Manila',
                month: 'short',
                day: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
            });

            const successText = `Attendance logged for ${employeeName || 'employee'} (${employeeId || 'N/A'}) on ${loggedAt}. Static RFID flow only.`;

            if (window.Swal && typeof window.Swal.fire === 'function') {
                window.Swal.fire({
                    title: 'Attendance logged',
                    text: successText,
                    icon: 'success',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#166534',
                });
            } else {
                window.alert(successText);
            }
        });
    }
})();
