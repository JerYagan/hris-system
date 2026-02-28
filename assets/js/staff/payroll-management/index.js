(() => {
    const normalize = (value) => (value || '').toString().trim().toLowerCase();
    const currencyFormatter = new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });

    const decodeEntities = (rawText) => {
        const textarea = document.createElement('textarea');
        textarea.innerHTML = rawText || '';
        return textarea.value;
    };

    const parseJsonScript = (id) => {
        const node = document.getElementById(id);
        if (!node) {
            return {};
        }

        const decoded = decodeEntities(node.textContent || '{}').trim();
        if (decoded === '') {
            return {};
        }

        try {
            const parsed = JSON.parse(decoded);
            return parsed && typeof parsed === 'object' ? parsed : {};
        } catch (error) {
            return {};
        }
    };

    const showConfirmation = async (message, title = 'Please confirm') => {
        if (window.Swal && typeof window.Swal.fire === 'function') {
            const result = await window.Swal.fire({
                title,
                text: message,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, continue',
                cancelButtonText: 'Cancel',
                reverseButtons: true,
            });
            return Boolean(result && result.isConfirmed);
        }

        return window.confirm(message);
    };

    const showFlashMessage = () => {
        const flashNode = document.getElementById('payrollFlashState');
        if (!flashNode) {
            return;
        }

        const state = normalize(flashNode.getAttribute('data-state'));
        const message = (flashNode.getAttribute('data-message') || '').trim();
        if (!state || !message || !(window.Swal && typeof window.Swal.fire === 'function')) {
            return;
        }

        const icon = state === 'success' ? 'success' : state === 'error' ? 'error' : 'info';
        window.Swal.fire({
            icon,
            text: message,
            confirmButtonText: 'OK',
        });
    };

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

    const setupAdjustmentReviewModal = () => {
        const modal = document.getElementById('salaryAdjustmentReviewModal');
        const openButtons = Array.from(document.querySelectorAll('[data-open-adjustment-modal]'));
        const closeButton = document.getElementById('salaryAdjustmentModalClose');
        const cancelButton = document.getElementById('salaryAdjustmentModalCancel');
        const form = document.getElementById('salaryAdjustmentForm');
        const submitButton = document.getElementById('salaryAdjustmentSubmit');

        const idInput = document.getElementById('salaryAdjustmentId');
        const codeLabel = document.getElementById('salaryAdjustmentCode');
        const employeeLabel = document.getElementById('salaryAdjustmentEmployee');
        const currentStatusLabel = document.getElementById('salaryAdjustmentCurrentStatus');
        const descriptionLabel = document.getElementById('salaryAdjustmentDescription');
        const decisionSelect = document.getElementById('salaryAdjustmentDecision');

        if (!modal || !form || !idInput || !decisionSelect) {
            return;
        }

        const closeModal = () => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            form.reset();
            form.dataset.confirmed = '0';
        };

        openButtons.forEach((button) => {
            button.addEventListener('click', () => {
                idInput.value = button.getAttribute('data-adjustment-id') || '';
                if (codeLabel) {
                    codeLabel.textContent = button.getAttribute('data-adjustment-code') || '-';
                }
                if (employeeLabel) {
                    employeeLabel.textContent = button.getAttribute('data-employee-name') || '-';
                }
                if (currentStatusLabel) {
                    currentStatusLabel.textContent = button.getAttribute('data-current-status') || '-';
                }
                if (descriptionLabel) {
                    descriptionLabel.textContent = button.getAttribute('data-description') || '-';
                }
                decisionSelect.value = '';

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

        form.addEventListener('submit', async (event) => {
            if (form.dataset.confirmed === '1') {
                return;
            }

            event.preventDefault();
            const decision = normalize(decisionSelect.value);
            if (decision === '') {
                return;
            }

            const code = codeLabel ? codeLabel.textContent || 'Adjustment' : 'Adjustment';
            const recommendationLabel = decision === 'approved' ? 'approve' : 'reject';
            const shouldContinue = await showConfirmation(`Submit recommendation to ${recommendationLabel} salary adjustment "${code}" for admin final review?`, 'Salary Adjustment Recommendation');
            if (!shouldContinue) {
                return;
            }

            if (submitButton) {
                submitButton.disabled = true;
                submitButton.classList.add('opacity-60', 'cursor-not-allowed');
            }

            form.dataset.confirmed = '1';
            form.submit();
        });
    };

    const setupExportPayrollCsvModal = () => {
        const modal = document.getElementById('exportPayrollCsvModal');
        const openButtons = Array.from(document.querySelectorAll('[data-open-export-modal]'));
        const closeButton = document.getElementById('exportPayrollCsvModalClose');
        const cancelButton = document.getElementById('exportPayrollCsvModalCancel');
        const form = document.getElementById('exportPayrollCsvForm');
        const submitButton = document.getElementById('exportPayrollCsvSubmit');

        const periodIdInput = document.getElementById('exportPayrollPeriodId');
        const periodCodeLabel = document.getElementById('exportPayrollPeriodCode');
        const periodRangeLabel = document.getElementById('exportPayrollPeriodRange');

        if (!modal || !form || !periodIdInput) {
            return;
        }

        const closeModal = () => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            form.reset();
            form.dataset.confirmed = '0';
        };

        openButtons.forEach((button) => {
            button.addEventListener('click', () => {
                periodIdInput.value = button.getAttribute('data-period-id') || '';
                if (periodCodeLabel) {
                    periodCodeLabel.textContent = button.getAttribute('data-period-code') || '-';
                }
                if (periodRangeLabel) {
                    periodRangeLabel.textContent = button.getAttribute('data-period-range') || '-';
                }

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

        form.addEventListener('submit', async (event) => {
            if (form.dataset.confirmed === '1') {
                return;
            }

            event.preventDefault();
            const periodCode = periodCodeLabel ? periodCodeLabel.textContent || 'selected period' : 'selected period';

            const shouldContinue = await showConfirmation(`Export the full payroll dataset for period ${periodCode}?`, 'Export Payroll CSV');
            if (!shouldContinue) {
                return;
            }

            if (submitButton) {
                submitButton.disabled = true;
                submitButton.classList.add('opacity-60', 'cursor-not-allowed');
            }

            form.dataset.confirmed = '1';
            form.submit();
        });
    };

    const setupCreateAdjustmentModal = () => {
        const modal = document.getElementById('createSalaryAdjustmentModal');
        const openButton = document.getElementById('openCreateSalaryAdjustmentModal');
        const closeButton = document.getElementById('createSalaryAdjustmentModalClose');
        const cancelButton = document.getElementById('createSalaryAdjustmentModalCancel');
        const form = document.getElementById('createSalaryAdjustmentForm');
        const submitButton = document.getElementById('createSalaryAdjustmentSubmit');
        const typeSelect = document.getElementById('createSalaryAdjustmentType');
        const recommendationSelect = document.getElementById('createSalaryAdjustmentRecommendation');

        if (!modal || !openButton || !form) {
            return;
        }

        const closeModal = () => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            form.reset();
            form.dataset.confirmed = '0';
        };

        openButton.addEventListener('click', () => {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
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

        form.addEventListener('submit', async (event) => {
            if (form.dataset.confirmed === '1') {
                return;
            }

            event.preventDefault();
            const adjustmentType = normalize(typeSelect ? typeSelect.value : 'deduction') || 'deduction';
            const recommendation = normalize(recommendationSelect ? recommendationSelect.value : 'draft') || 'draft';
            const shouldContinue = await showConfirmation(
                recommendation === 'draft'
                    ? `Create this ${adjustmentType} salary adjustment as draft?`
                    : `Create this ${adjustmentType} salary adjustment and submit recommendation (${recommendation}) for admin review?`,
                'Create Salary Adjustment'
            );
            if (!shouldContinue) {
                return;
            }

            if (submitButton) {
                submitButton.disabled = true;
                submitButton.classList.add('opacity-60', 'cursor-not-allowed');
            }

            form.dataset.confirmed = '1';
            form.submit();
        });
    };

    const setupComputePayrollModal = () => {
        const modal = document.getElementById('computePayrollModal');
        const openButtons = Array.from(document.querySelectorAll('[data-open-compute-modal]'));
        const closeButton = document.getElementById('computePayrollModalClose');
        const cancelButton = document.getElementById('computePayrollModalCancel');
        const form = document.getElementById('computePayrollForm');
        const submitButton = document.getElementById('computePayrollSubmit');
        const periodIdInput = document.getElementById('computePayrollPeriodId');
        const periodLabel = document.getElementById('computePayrollPeriodLabel');
        const recommendationInput = document.getElementById('computePayrollRecommendation');
        const employeesBody = document.getElementById('computePayrollEmployeesBody');
        const employeeCount = document.getElementById('computePayrollEmployeeCount');
        const previewByPeriod = parseJsonScript('payrollComputePreviewData');

        if (!modal || !form || !periodIdInput || !employeesBody) {
            return;
        }

        const closeModal = () => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            form.dataset.confirmed = '0';
            if (recommendationInput) {
                recommendationInput.value = '';
            }
        };

        const renderEmployees = (periodId) => {
            const payload = previewByPeriod[periodId] && typeof previewByPeriod[periodId] === 'object' ? previewByPeriod[periodId] : {};
            const rows = Array.isArray(payload.employees) ? payload.employees : [];
            employeesBody.innerHTML = '';

            if (rows.length === 0) {
                employeesBody.innerHTML = '<tr><td colspan="2" class="px-3 py-4 text-center text-slate-500">No eligible employees found for this period.</td></tr>';
                if (employeeCount) {
                    employeeCount.textContent = '0 employee(s)';
                }
                if (submitButton) {
                    submitButton.disabled = true;
                }
                return;
            }

            const fragment = document.createDocumentFragment();
            rows.forEach((entry) => {
                const tr = document.createElement('tr');
                tr.innerHTML = `<td class="px-3 py-2 text-slate-700">${(entry.employee_name || entry.full_name || '-').toString()}</td><td class="px-3 py-2 text-right text-slate-700">${currencyFormatter.format(Number(entry.estimated_net || 0))}</td>`;
                fragment.appendChild(tr);
            });
            employeesBody.appendChild(fragment);

            if (employeeCount) {
                employeeCount.textContent = `${rows.length} employee(s)`;
            }
            if (submitButton) {
                submitButton.disabled = false;
            }
        };

        openButtons.forEach((button) => {
            button.addEventListener('click', () => {
                if (button.disabled) {
                    return;
                }

                const periodId = button.getAttribute('data-period-id') || '';
                const periodCode = button.getAttribute('data-period-code') || '-';
                periodIdInput.value = periodId;
                if (periodLabel) {
                    periodLabel.textContent = periodCode;
                }
                if (recommendationInput) {
                    recommendationInput.value = 'Recommend approval';
                }
                renderEmployees(periodId);

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

        form.addEventListener('submit', async (event) => {
            if (form.dataset.confirmed === '1') {
                return;
            }

            event.preventDefault();
            const periodCode = periodLabel ? periodLabel.textContent || 'selected period' : 'selected period';
            const shouldContinue = await showConfirmation(`Compute payroll for ${periodCode}?`, 'Compute Monthly Payroll');
            if (!shouldContinue) {
                return;
            }

            if (submitButton) {
                submitButton.disabled = true;
                submitButton.classList.add('opacity-60', 'cursor-not-allowed');
            }

            form.dataset.confirmed = '1';
            form.submit();
        });
    };

    const setupGeneratePayslipModal = () => {
        const modal = document.getElementById('generatePayslipModal');
        const openButtons = Array.from(document.querySelectorAll('[data-open-generate-modal]'));
        const closeButton = document.getElementById('generatePayslipModalClose');
        const cancelButton = document.getElementById('generatePayslipModalCancel');
        const form = document.getElementById('generatePayslipForm');
        const submitButton = document.getElementById('generatePayslipSubmit');
        const runIdInput = document.getElementById('generatePayslipRunId');
        const runLabel = document.getElementById('generatePayslipRunLabel');
        const employeesBody = document.getElementById('generatePayslipEmployeesBody');
        const employeeCount = document.getElementById('generatePayslipEmployeeCount');
        const previewByRun = parseJsonScript('payrollGeneratePreviewData');

        if (!modal || !form || !runIdInput || !employeesBody) {
            return;
        }

        const closeModal = () => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            form.dataset.confirmed = '0';
        };

        const renderEmployees = (runId) => {
            const payload = previewByRun[runId] && typeof previewByRun[runId] === 'object' ? previewByRun[runId] : {};
            const rows = Array.isArray(payload.employees) ? payload.employees : [];
            employeesBody.innerHTML = '';

            if (rows.length === 0) {
                employeesBody.innerHTML = '<tr><td colspan="2" class="px-3 py-4 text-center text-slate-500">No payroll items found for this run.</td></tr>';
                if (employeeCount) {
                    employeeCount.textContent = '0 employee(s)';
                }
                if (submitButton) {
                    submitButton.disabled = true;
                }
                return;
            }

            const fragment = document.createDocumentFragment();
            rows.forEach((entry) => {
                const tr = document.createElement('tr');
                tr.innerHTML = `<td class="px-3 py-2 text-slate-700">${(entry.employee_name || entry.full_name || '-').toString()}</td><td class="px-3 py-2 text-right text-slate-700">${currencyFormatter.format(Number(entry.net_pay || 0))}</td>`;
                fragment.appendChild(tr);
            });
            employeesBody.appendChild(fragment);

            if (employeeCount) {
                employeeCount.textContent = `${rows.length} employee(s)`;
            }
            if (submitButton) {
                submitButton.disabled = false;
            }
        };

        openButtons.forEach((button) => {
            button.addEventListener('click', () => {
                if (button.disabled) {
                    return;
                }

                const runId = button.getAttribute('data-run-id') || '';
                const shortId = button.getAttribute('data-run-short-id') || '-';
                runIdInput.value = runId;
                if (runLabel) {
                    runLabel.textContent = shortId;
                }
                renderEmployees(runId);

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

        form.addEventListener('submit', async (event) => {
            if (form.dataset.confirmed === '1') {
                return;
            }

            event.preventDefault();
            const runShortId = runLabel ? runLabel.textContent || 'selected run' : 'selected run';
            const shouldContinue = await showConfirmation(`Generate payslips for run ${runShortId}?`, 'Generate Payslips');
            if (!shouldContinue) {
                return;
            }

            if (submitButton) {
                submitButton.disabled = true;
                submitButton.classList.add('opacity-60', 'cursor-not-allowed');
            }

            form.dataset.confirmed = '1';
            form.submit();
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
        searchInputId: 'salaryAdjustmentSearchInput',
        statusFilterId: 'salaryAdjustmentStatusFilter',
        rowSelector: '[data-salary-adjustment-row]',
        searchAttr: 'data-salary-adjustment-search',
        statusAttr: 'data-salary-adjustment-status',
        emptyRowId: 'salaryAdjustmentFilterEmptyRow',
    });

    setupTableFilters({
        searchInputId: 'payrollRunSearchInput',
        statusFilterId: 'payrollRunStatusFilter',
        rowSelector: '[data-payroll-run-row]',
        searchAttr: 'data-payroll-run-search',
        statusAttr: 'data-payroll-run-status',
        emptyRowId: 'payrollRunFilterEmptyRow',
    });

    showFlashMessage();
    setupExportPayrollCsvModal();
    setupAdjustmentReviewModal();
    setupCreateAdjustmentModal();
    setupComputePayrollModal();
    setupGeneratePayslipModal();
})();
