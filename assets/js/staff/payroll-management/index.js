const MIN_SKELETON_MS = 250;

const emitQaPerfSectionMetric = ({ section, status = 'success', fetchMs = null, displayMs = null, detail = '', url = '' }) => {
    if (typeof document === 'undefined' || typeof window.CustomEvent !== 'function') {
        return;
    }

    document.dispatchEvent(new CustomEvent('hris:qa-perf-section', {
        detail: {
            page: window.location.pathname,
            section,
            status,
            fetch_ms: fetchMs,
            display_ms: displayMs,
            detail,
            url,
            source: 'staff-payroll',
        },
    }));
};

const initStaffPayrollInteractive = () => {
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
        const prevPageButton = document.getElementById('computePayrollPrevPage');
        const nextPageButton = document.getElementById('computePayrollNextPage');
        const pageLabel = document.getElementById('computePayrollPageLabel');
        const previewByPeriod = parseJsonScript('payrollComputePreviewData');
        let currentPage = 1;
        const pageSize = 10;

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
                if (pageLabel) {
                    pageLabel.textContent = 'Page 1';
                }
                prevPageButton?.setAttribute('disabled', 'disabled');
                nextPageButton?.setAttribute('disabled', 'disabled');
                return;
            }

            const totalPages = Math.max(1, Math.ceil(rows.length / pageSize));
            currentPage = Math.min(Math.max(currentPage, 1), totalPages);
            const startIndex = (currentPage - 1) * pageSize;
            const visibleRows = rows.slice(startIndex, startIndex + pageSize);

            const fragment = document.createDocumentFragment();
            visibleRows.forEach((entry) => {
                const tr = document.createElement('tr');
                tr.innerHTML = `<td class="px-3 py-2 text-slate-700">${(entry.employee_name || entry.full_name || '-').toString()}</td><td class="px-3 py-2 text-right text-slate-700">${currencyFormatter.format(Number(entry.estimated_net || 0))}</td>`;
                fragment.appendChild(tr);
            });
            employeesBody.appendChild(fragment);

            if (employeeCount) {
                employeeCount.textContent = `${rows.length} employee(s)`;
            }
            if (pageLabel) {
                pageLabel.textContent = `Page ${currentPage} of ${totalPages}`;
            }
            if (prevPageButton) {
                prevPageButton.disabled = currentPage <= 1;
            }
            if (nextPageButton) {
                nextPageButton.disabled = currentPage >= totalPages;
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
                currentPage = 1;
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

        prevPageButton?.addEventListener('click', () => {
            currentPage -= 1;
            renderEmployees(periodIdInput.value || '');
        });

        nextPageButton?.addEventListener('click', () => {
            currentPage += 1;
            renderEmployees(periodIdInput.value || '');
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
        const backdrop = document.getElementById('generatePayslipModalBackdrop');
        const form = document.getElementById('generatePayslipForm');
        const submitButton = document.getElementById('generatePayslipSubmit');
        const runIdInput = document.getElementById('generatePayslipRunId');
        const periodInput = document.getElementById('generatePayslipBatchPeriod');
        const statusInput = document.getElementById('generatePayslipBatchStatus');
        const employeesInput = document.getElementById('generatePayslipBatchEmployees');
        const netInput = document.getElementById('generatePayslipBatchNet');
        const recommendationInput = document.getElementById('generatePayslipBatchRecommendation');
        const submittedAtInput = document.getElementById('generatePayslipBatchSubmittedAt');
        const reviewedAtInput = document.getElementById('generatePayslipBatchReviewedAt');

        const breakdownEmployees = document.getElementById('generatePayslipBreakdownEmployees');
        const breakdownGross = document.getElementById('generatePayslipBreakdownGross');
        const breakdownNet = document.getElementById('generatePayslipBreakdownNet');
        const breakdownRows = document.getElementById('generatePayslipBreakdownRows');
        const breakdownBody = document.getElementById('generatePayslipBreakdownBody');
        const breakdownPrev = document.getElementById('generatePayslipBreakdownPrev');
        const breakdownNext = document.getElementById('generatePayslipBreakdownNext');
        const breakdownPageLabel = document.getElementById('generatePayslipBreakdownPageLabel');

        const adjustmentSubmitted = document.getElementById('generatePayslipAdjustmentSubmitted');
        const adjustmentPending = document.getElementById('generatePayslipAdjustmentPending');
        const adjustmentApproved = document.getElementById('generatePayslipAdjustmentApproved');
        const adjustmentRejected = document.getElementById('generatePayslipAdjustmentRejected');
        const adjustmentBody = document.getElementById('generatePayslipAdjustmentBody');
        const adjustmentPrev = document.getElementById('generatePayslipAdjustmentPrev');
        const adjustmentNext = document.getElementById('generatePayslipAdjustmentNext');
        const adjustmentPageLabel = document.getElementById('generatePayslipAdjustmentPageLabel');

        const batchBreakdownByRun = parseJsonScript('payrollBatchBreakdownByRunData');
        let currentBreakdownPage = 1;
        let currentAdjustmentPage = 1;
        const modalPageSize = 10;

        if (!modal || !form || !runIdInput) {
            return;
        }

        const escapeText = (value) => String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');

        const closeModal = () => {
            modal.classList.add('hidden');
            modal.classList.remove('block');
            form.dataset.confirmed = '0';
        };

        const renderBreakdown = (runId) => {
            const payload = runId && batchBreakdownByRun[runId] ? batchBreakdownByRun[runId] : null;
            const rows = Array.isArray(payload?.rows) ? payload.rows : [];
            const adjustmentRows = Array.isArray(payload?.adjustment_rows) ? payload.adjustment_rows : [];
            const adjustmentSummary = payload && typeof payload === 'object' && payload.adjustment_summary && typeof payload.adjustment_summary === 'object'
                ? payload.adjustment_summary
                : {};
            const submittedCount = Number(adjustmentSummary.submitted_count) || 0;
            const pendingCount = Number(adjustmentSummary.pending_count) || 0;
            const approvedCount = Number(adjustmentSummary.approved_count) || 0;
            const rejectedCount = Number(adjustmentSummary.rejected_count) || 0;
            const employeeCount = Number(payload?.employee_count) || 0;
            const totalGross = Number(payload?.total_gross) || 0;
            const totalNet = Number(payload?.total_net) || 0;

            if (breakdownEmployees) breakdownEmployees.textContent = String(employeeCount);
            if (breakdownGross) breakdownGross.textContent = currencyFormatter.format(totalGross);
            if (breakdownNet) breakdownNet.textContent = currencyFormatter.format(totalNet);
            if (breakdownRows) breakdownRows.textContent = String(rows.length);

            if (breakdownBody) {
                if (!rows.length) {
                    breakdownBody.innerHTML = '<tr id="generatePayslipBreakdownEmptyRow"><td class="px-3 py-3 text-slate-500" colspan="11">No computation breakdown available for this batch.</td></tr>';
                    if (breakdownPageLabel) {
                        breakdownPageLabel.textContent = 'Page 1';
                    }
                    breakdownPrev?.setAttribute('disabled', 'disabled');
                    breakdownNext?.setAttribute('disabled', 'disabled');
                } else {
                    const breakdownTotalPages = Math.max(1, Math.ceil(rows.length / modalPageSize));
                    currentBreakdownPage = Math.min(Math.max(currentBreakdownPage, 1), breakdownTotalPages);
                    const visibleRows = rows.slice((currentBreakdownPage - 1) * modalPageSize, currentBreakdownPage * modalPageSize);
                    breakdownBody.innerHTML = visibleRows.map((row) => {
                        const adjustmentNet = (Number(row.adjustment_earnings) || 0) - (Number(row.adjustment_deductions) || 0);
                        const lateMinutes = Number(row.late_minutes) || 0;
                        const undertimeHours = Number(row.undertime_hours) || 0;
                        const absentDays = Number(row.absent_days) || 0;
                        const leaveCardRemarks = absentDays > 0
                            ? `Absence impact: ${absentDays} day(s)`
                            : 'No absence impact';
                        return `
                            <tr>
                                <td class="px-3 py-2 text-slate-700">${escapeText(row.employee_name || '-')}</td>
                                <td class="px-3 py-2 text-right text-slate-700">${currencyFormatter.format(Number(row.basic_pay) || 0)}</td>
                                <td class="px-3 py-2 text-right text-slate-700">${currencyFormatter.format(Number(row.allowances_total) || 0)}</td>
                                <td class="px-3 py-2 text-right text-slate-700">${currencyFormatter.format(Number(row.cto_pay) || 0)}</td>
                                <td class="px-3 py-2 text-right text-slate-700">${currencyFormatter.format(Number(row.statutory_deductions) || 0)}</td>
                                <td class="px-3 py-2 text-right text-slate-700">${currencyFormatter.format(Number(row.timekeeping_deductions) || 0)}</td>
                                <td class="px-3 py-2 text-right text-slate-700">${lateMinutes} min / ${undertimeHours.toFixed(2)} hr</td>
                                <td class="px-3 py-2 text-left text-slate-700">${escapeText(leaveCardRemarks)}</td>
                                <td class="px-3 py-2 text-right text-slate-700">${currencyFormatter.format(adjustmentNet)}</td>
                                <td class="px-3 py-2 text-right text-slate-700">${currencyFormatter.format(Number(row.gross_pay) || 0)}</td>
                                <td class="px-3 py-2 text-right text-slate-800 font-medium">${currencyFormatter.format(Number(row.net_pay) || 0)}</td>
                            </tr>
                        `;
                    }).join('');
                    if (breakdownPageLabel) {
                        breakdownPageLabel.textContent = `Page ${currentBreakdownPage} of ${breakdownTotalPages}`;
                    }
                    if (breakdownPrev) {
                        breakdownPrev.disabled = currentBreakdownPage <= 1;
                    }
                    if (breakdownNext) {
                        breakdownNext.disabled = currentBreakdownPage >= breakdownTotalPages;
                    }
                }
            }

            if (adjustmentSubmitted) adjustmentSubmitted.textContent = String(submittedCount);
            if (adjustmentPending) adjustmentPending.textContent = String(pendingCount);
            if (adjustmentApproved) adjustmentApproved.textContent = String(approvedCount);
            if (adjustmentRejected) adjustmentRejected.textContent = String(rejectedCount);

            if (adjustmentBody) {
                if (!adjustmentRows.length) {
                    adjustmentBody.innerHTML = '<tr id="generatePayslipAdjustmentEmptyRow"><td class="px-3 py-3 text-slate-500" colspan="6">No staff-submitted salary adjustment recommendations in this batch.</td></tr>';
                    if (adjustmentPageLabel) {
                        adjustmentPageLabel.textContent = 'Page 1';
                    }
                    adjustmentPrev?.setAttribute('disabled', 'disabled');
                    adjustmentNext?.setAttribute('disabled', 'disabled');
                } else {
                    const adjustmentTotalPages = Math.max(1, Math.ceil(adjustmentRows.length / modalPageSize));
                    currentAdjustmentPage = Math.min(Math.max(currentAdjustmentPage, 1), adjustmentTotalPages);
                    const visibleAdjustmentRows = adjustmentRows.slice((currentAdjustmentPage - 1) * modalPageSize, currentAdjustmentPage * modalPageSize);
                    adjustmentBody.innerHTML = visibleAdjustmentRows.map((row) => {
                        const recommendation = String(row.staff_recommendation || '').trim();
                        const recommendationLabel = recommendation === '' ? 'Not submitted' : recommendation.replace(/_/g, ' ');
                        const adminLabel = String(row.admin_status_label || 'Pending').trim() || 'Pending';
                        const submittedAt = String(row.staff_recommendation_label || '-').trim() || '-';
                        const notes = String(row.staff_recommendation_notes || '').trim();

                        return `
                            <tr>
                                <td class="px-3 py-2 text-slate-700 font-medium">${escapeText(row.adjustment_code || '-')}</td>
                                <td class="px-3 py-2 text-slate-700">${escapeText(row.employee_name || '-')}</td>
                                <td class="px-3 py-2 text-slate-700">${escapeText(row.adjustment_type_label || '-')}</td>
                                <td class="px-3 py-2 text-right text-slate-700">${currencyFormatter.format(Number(row.amount) || 0)}</td>
                                <td class="px-3 py-2 text-slate-700">
                                    <div>${escapeText(recommendationLabel)}</div>
                                    <div class="text-[11px] text-slate-500">${escapeText(submittedAt)}</div>
                                    ${notes !== '' ? `<div class="text-[11px] text-slate-500">Notes: ${escapeText(notes)}</div>` : ''}
                                </td>
                                <td class="px-3 py-2 text-slate-700">${escapeText(adminLabel)}</td>
                            </tr>
                        `;
                    }).join('');
                    if (adjustmentPageLabel) {
                        adjustmentPageLabel.textContent = `Page ${currentAdjustmentPage} of ${adjustmentTotalPages}`;
                    }
                    if (adjustmentPrev) {
                        adjustmentPrev.disabled = currentAdjustmentPage <= 1;
                    }
                    if (adjustmentNext) {
                        adjustmentNext.disabled = currentAdjustmentPage >= adjustmentTotalPages;
                    }
                }
            }

            if (submitButton) {
                submitButton.disabled = rows.length === 0;
            }
        };

        openButtons.forEach((button) => {
            button.addEventListener('click', () => {
                if (button.disabled) {
                    return;
                }

                const runId = button.getAttribute('data-run-id') || '';
                runIdInput.value = runId;
                if (periodInput) periodInput.value = button.getAttribute('data-period-label') || '';
                if (statusInput) statusInput.value = button.getAttribute('data-current-status') || '';
                if (employeesInput) employeesInput.value = button.getAttribute('data-employee-count') || '';
                if (netInput) netInput.value = button.getAttribute('data-total-net') || '';
                if (recommendationInput) recommendationInput.value = button.getAttribute('data-staff-recommendation') || 'Recommend approval';
                if (submittedAtInput) submittedAtInput.value = button.getAttribute('data-staff-submitted') || '-';
                if (reviewedAtInput) reviewedAtInput.value = button.getAttribute('data-admin-reviewed') || '-';
                currentBreakdownPage = 1;
                currentAdjustmentPage = 1;
                renderBreakdown(runId);

                modal.classList.remove('hidden');
                modal.classList.add('block');
            });
        });

        if (closeButton) {
            closeButton.addEventListener('click', closeModal);
        }
        if (cancelButton) {
            cancelButton.addEventListener('click', closeModal);
        }

        if (backdrop) {
            backdrop.addEventListener('click', closeModal);
        }

        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeModal();
            }
        });

        breakdownPrev?.addEventListener('click', () => {
            currentBreakdownPage -= 1;
            renderBreakdown(runIdInput.value || '');
        });

        breakdownNext?.addEventListener('click', () => {
            currentBreakdownPage += 1;
            renderBreakdown(runIdInput.value || '');
        });

        adjustmentPrev?.addEventListener('click', () => {
            currentAdjustmentPage -= 1;
            renderBreakdown(runIdInput.value || '');
        });

        adjustmentNext?.addEventListener('click', () => {
            currentAdjustmentPage += 1;
            renderBreakdown(runIdInput.value || '');
        });

        form.addEventListener('submit', async (event) => {
            if (form.dataset.confirmed === '1') {
                return;
            }

            event.preventDefault();
            const runShortId = (runIdInput?.value || '').trim() || 'selected run';
            const shouldContinue = await showConfirmation(`Generate payslips for run ${runShortId} after final review?`, 'Generate Payslips');
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
};

const initStaffPayrollAsync = () => {
    const region = document.getElementById('staffPayrollAsyncRegion');
    const summaryUrl = region?.getAttribute('data-payroll-summary-url') || '';
    const secondaryUrl = region?.getAttribute('data-payroll-secondary-url') || '';

    const summarySkeleton = document.getElementById('staffPayrollSummarySkeleton');
    const summaryContent = document.getElementById('staffPayrollSummaryContent');
    const summaryError = document.getElementById('staffPayrollSummaryError');
    const summaryRetry = document.getElementById('staffPayrollSummaryRetry');

    const secondarySkeleton = document.getElementById('staffPayrollSecondarySkeleton');
    const secondaryContent = document.getElementById('staffPayrollSecondaryContent');
    const secondaryError = document.getElementById('staffPayrollSecondaryError');
    const secondaryRetry = document.getElementById('staffPayrollSecondaryRetry');

    const summaryState = { requestId: 0 };
    const secondaryState = { requestId: 0 };

    const buildSectionUrl = (baseUrl, params = null) => {
        const url = new URL(baseUrl, window.location.href);
        const currentUrl = new URL(window.location.href);

        ['payroll_period_page', 'salary_adjustment_page', 'payroll_run_page'].forEach((key) => {
            const value = currentUrl.searchParams.get(key);
            if (value) {
                url.searchParams.set(key, value);
            }
        });

        if (params && typeof params === 'object') {
            Object.entries(params).forEach(([key, value]) => {
                if (value === null || value === undefined || value === '') {
                    url.searchParams.delete(key);
                    return;
                }
                url.searchParams.set(key, String(value));
            });
        }

        return url.toString();
    };

    if (!region || summaryUrl === '' || secondaryUrl === '' || !summarySkeleton || !summaryContent || !summaryError || !summaryRetry || !secondarySkeleton || !secondaryContent || !secondaryError || !secondaryRetry) {
        return false;
    }

    const fetchPartialHtml = async (url) => {
        const response = await fetch(url, {
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (response.redirected) {
            window.location.href = response.url;
            return '';
        }

        if (!response.ok) {
            throw new Error(`Staff payroll partial request failed with status ${response.status}`);
        }

        const html = await response.text();
        if (html.trim() === '') {
            throw new Error('Staff payroll partial returned an empty response.');
        }

        return html;
    };

    const setLoadingState = ({ skeleton, content, errorBox, retryButton }, isLoading) => {
        skeleton.classList.toggle('hidden', !isLoading);
        content.classList.toggle('hidden', isLoading);
        if (isLoading) {
            errorBox.classList.add('hidden');
        }
        retryButton.disabled = isLoading;
    };

    const showErrorState = ({ skeleton, content, errorBox, retryButton }) => {
        skeleton.classList.add('hidden');
        content.classList.add('hidden');
        errorBox.classList.remove('hidden');
        retryButton.disabled = false;
    };

    const loadSection = async ({ sectionName, url, params = null, skeleton, content, errorBox, retryButton, onSuccess, state }) => {
        setLoadingState({ skeleton, content, errorBox, retryButton }, true);
        state.requestId += 1;
        const requestId = state.requestId;
        const startedAt = window.performance?.now?.() ?? Date.now();
        const requestUrl = buildSectionUrl(url, params);

        try {
            const html = await fetchPartialHtml(requestUrl);
            const fetchElapsed = (window.performance?.now?.() ?? Date.now()) - startedAt;
            const remaining = Math.max(0, MIN_SKELETON_MS - fetchElapsed);

            window.setTimeout(() => {
                if (requestId !== state.requestId) {
                    return;
                }

                content.innerHTML = html;
                content.classList.remove('hidden');
                skeleton.classList.add('hidden');
                errorBox.classList.add('hidden');
                retryButton.disabled = false;
                if (typeof onSuccess === 'function') {
                    onSuccess();
                }

                const displayElapsed = (window.performance?.now?.() ?? Date.now()) - startedAt;
                emitQaPerfSectionMetric({
                    section: sectionName,
                    status: 'success',
                    fetchMs: fetchElapsed,
                    displayMs: displayElapsed,
                    url: requestUrl,
                });
            }, remaining);
        } catch (error) {
            console.error(error);
            showErrorState({ skeleton, content, errorBox, retryButton });
            const elapsed = (window.performance?.now?.() ?? Date.now()) - startedAt;
            emitQaPerfSectionMetric({
                section: sectionName,
                status: 'error',
                fetchMs: elapsed,
                displayMs: elapsed,
                detail: error instanceof Error ? error.message : 'Section load failed.',
                url: requestUrl,
            });
        }
    };

    const loadSummary = () => loadSection({
        sectionName: 'Summary',
        url: summaryUrl,
        skeleton: summarySkeleton,
        content: summaryContent,
        errorBox: summaryError,
        retryButton: summaryRetry,
        state: summaryState,
    });

    const loadSecondary = (params = null) => loadSection({
        sectionName: 'Payroll Workspace',
        url: secondaryUrl,
        params,
        skeleton: secondarySkeleton,
        content: secondaryContent,
        errorBox: secondaryError,
        retryButton: secondaryRetry,
        state: secondaryState,
        onSuccess: () => {
            initStaffPayrollInteractive();
        },
    });

    summaryRetry.addEventListener('click', () => {
        loadSummary().catch?.(console.error);
    });

    secondaryRetry.addEventListener('click', () => {
        loadSecondary().catch?.(console.error);
    });

    region.addEventListener('click', (event) => {
        const target = event.target instanceof Element ? event.target : null;
        if (!target) {
            return;
        }

        const refreshButton = target.closest('[data-staff-payroll-refresh-scope]');
        if (refreshButton) {
            loadSecondary().catch?.(console.error);
            return;
        }

        const pageButton = target.closest('[data-staff-payroll-page-scope][data-staff-payroll-page]');
        if (!pageButton) {
            return;
        }

        const scope = pageButton.getAttribute('data-staff-payroll-page-scope') || '';
        const page = Number(pageButton.getAttribute('data-staff-payroll-page') || 1);
        const queryKey = scope === 'periods'
            ? 'payroll_period_page'
            : scope === 'adjustments'
                ? 'salary_adjustment_page'
                : scope === 'runs'
                    ? 'payroll_run_page'
                    : '';

        if (queryKey === '' || !Number.isFinite(page) || page < 1) {
            return;
        }

        const currentUrl = new URL(window.location.href);
        currentUrl.searchParams.set(queryKey, String(page));
        window.history.replaceState({}, '', currentUrl.toString());
        loadSecondary({ [queryKey]: page }).catch?.(console.error);
    });

    loadSummary();
    loadSecondary();
    return true;
};

if (!initStaffPayrollAsync()) {
    initStaffPayrollInteractive();

    document.addEventListener('click', (event) => {
        const target = event.target instanceof Element ? event.target : null;
        if (!target) {
            return;
        }

        const refreshButton = target.closest('[data-staff-payroll-refresh-scope]');
        if (refreshButton) {
            window.location.reload();
            return;
        }

        const pageButton = target.closest('[data-staff-payroll-page-scope][data-staff-payroll-page]');
        if (!pageButton) {
            return;
        }

        const scope = pageButton.getAttribute('data-staff-payroll-page-scope') || '';
        const page = Number(pageButton.getAttribute('data-staff-payroll-page') || 1);
        const queryKey = scope === 'periods'
            ? 'payroll_period_page'
            : scope === 'adjustments'
                ? 'salary_adjustment_page'
                : scope === 'runs'
                    ? 'payroll_run_page'
                    : '';

        if (queryKey === '' || !Number.isFinite(page) || page < 1) {
            return;
        }

        const currentUrl = new URL(window.location.href);
        currentUrl.searchParams.set(queryKey, String(page));
        window.location.href = currentUrl.toString();
    });
}
