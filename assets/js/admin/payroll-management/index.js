import {
  initAdminShellInteractions,
  initModalSystem,
  initStatusChangeConfirmations,
  openModal,
} from '/hris-system/assets/js/shared/admin-core.js';

const formatCurrency = (value) => new Intl.NumberFormat('en-PH', {
  style: 'currency',
  currency: 'PHP',
  minimumFractionDigits: 2,
  maximumFractionDigits: 2,
}).format(Number(value) || 0);

const parseJsonScriptNode = (id) => {
  const node = document.getElementById(id);
  if (!node) {
    return {};
  }

  try {
    const parsed = JSON.parse(node.textContent || '{}');
    return parsed && typeof parsed === 'object' ? parsed : {};
  } catch (_error) {
    return {};
  }
};

const MIN_SKELETON_MS = 250;

const escapeText = (value) => String(value ?? '')
  .replace(/&/g, '&amp;')
  .replace(/</g, '&lt;')
  .replace(/>/g, '&gt;')
  .replace(/"/g, '&quot;')
  .replace(/'/g, '&#39;');

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
      source: 'admin-payroll',
    },
  }));
};

const initPayrollSalarySetup = () => {
  const employeeSearch = document.getElementById('payrollEmployeeSearch');
  const employeeOptions = document.getElementById('payrollEmployeeOptions');
  const selectedPersonId = document.getElementById('payrollSelectedPersonId');
  const payFrequency = document.getElementById('payrollPayFrequency');
  const basePayInput = document.getElementById('payrollBasePay');
  const allowanceInput = document.querySelector('#payrollSalarySetupForm input[name="allowance_total"]');
  const taxInput = document.querySelector('#payrollSalarySetupForm input[name="tax_deduction"]');
  const governmentInput = document.querySelector('#payrollSalarySetupForm input[name="government_deductions"]');
  const otherDeductionInput = document.getElementById('payrollOtherDeduction');
  const monthlyRateOutput = document.getElementById('payrollComputedMonthlyRate');
  const netPerCycleOutput = document.getElementById('payrollEstimatedNetCycle');

  if (!employeeSearch || !employeeOptions || !selectedPersonId || !payFrequency || !basePayInput) {
    return;
  }

  const optionMap = new Map();
  Array.from(employeeOptions.querySelectorAll('option')).forEach((option) => {
    const key = (option.getAttribute('value') || '').trim();
    if (key !== '') {
      optionMap.set(key.toLowerCase(), option);
    }
  });

  const readNumber = (input) => Number(input?.value || 0) || 0;

  const recalculate = () => {
    const basePay = Math.max(0, readNumber(basePayInput));
    const allowance = Math.max(0, readNumber(allowanceInput));
    const tax = Math.max(0, readNumber(taxInput));
    const government = Math.max(0, readNumber(governmentInput));
    const otherDeduction = Math.max(0, readNumber(otherDeductionInput));

    const monthlyRate = basePay + allowance;
    let divisor = 1;
    if ((payFrequency.value || '').toLowerCase() === 'semi_monthly') {
      divisor = 2;
    } else if ((payFrequency.value || '').toLowerCase() === 'weekly') {
      divisor = 4;
    }

    const netPerCycle = (monthlyRate - (tax + government + otherDeduction)) / divisor;

    if (monthlyRateOutput) {
      monthlyRateOutput.value = formatCurrency(monthlyRate);
    }
    if (netPerCycleOutput) {
      netPerCycleOutput.value = formatCurrency(netPerCycle);
    }
  };

  const applySelectedEmployee = () => {
    const selected = optionMap.get((employeeSearch.value || '').trim().toLowerCase()) || null;
    if (!selected) {
      selectedPersonId.value = '';
      return;
    }

    selectedPersonId.value = selected.getAttribute('data-person-id') || '';
    basePayInput.value = selected.getAttribute('data-base-pay') || selected.getAttribute('data-monthly-rate') || '0';
    if (allowanceInput) {
      allowanceInput.value = selected.getAttribute('data-allowance-total') || '0';
    }
    if (taxInput) {
      taxInput.value = selected.getAttribute('data-tax-deduction') || '0';
    }
    if (governmentInput) {
      governmentInput.value = selected.getAttribute('data-government-deductions') || '0';
    }
    if (otherDeductionInput) {
      otherDeductionInput.value = selected.getAttribute('data-other-deductions') || '0';
    }
    payFrequency.value = selected.getAttribute('data-pay-frequency') || 'semi_monthly';
    recalculate();
  };

  employeeSearch.addEventListener('change', applySelectedEmployee);
  employeeSearch.addEventListener('blur', applySelectedEmployee);

  [basePayInput, allowanceInput, taxInput, governmentInput, otherDeductionInput, payFrequency].forEach((input) => {
    input?.addEventListener('input', recalculate);
    input?.addEventListener('change', recalculate);
  });

  document.querySelectorAll('[data-payroll-select-employee]').forEach((button) => {
    button.addEventListener('click', () => {
      const label = button.getAttribute('data-employee-label') || '';
      openModal('payrollSalarySetupModal');
      employeeSearch.value = label;
      applySelectedEmployee();
      employeeSearch.focus();
    });
  });

  recalculate();
};

const initPayrollBatchReviewModal = () => {
  const reviewForm = document.getElementById('reviewPayrollBatchForm');
  const runIdInput = document.getElementById('payrollRunId');
  const periodInput = document.getElementById('payrollBatchPeriod');
  const statusInput = document.getElementById('payrollBatchStatus');
  const employeesInput = document.getElementById('payrollBatchEmployees');
  const netInput = document.getElementById('payrollBatchNet');
  const recommendationInput = document.getElementById('payrollBatchRecommendation');
  const submittedAtInput = document.getElementById('payrollBatchSubmittedAt');
  const reviewedAtInput = document.getElementById('payrollBatchReviewedAt');
  const breakdownEmployees = document.getElementById('payrollBatchBreakdownEmployees');
  const breakdownGross = document.getElementById('payrollBatchBreakdownGross');
  const breakdownNet = document.getElementById('payrollBatchBreakdownNet');
  const breakdownRows = document.getElementById('payrollBatchBreakdownRows');
  const breakdownBody = document.getElementById('payrollBatchBreakdownBody');
  const adjustmentSubmitted = document.getElementById('payrollBatchAdjustmentSubmitted');
  const adjustmentPending = document.getElementById('payrollBatchAdjustmentPending');
  const adjustmentApproved = document.getElementById('payrollBatchAdjustmentApproved');
  const adjustmentRejected = document.getElementById('payrollBatchAdjustmentRejected');
  const adjustmentBody = document.getElementById('payrollBatchAdjustmentBody');
  const adjustmentWarning = document.getElementById('payrollBatchAdjustmentApprovalWarning');
  const decisionSelect = document.getElementById('payrollBatchDecision');

  const batchBreakdownByRun = parseJsonScriptNode('payrollBatchBreakdownByRunData');
  const syncReviewConfirmation = () => {
    if (!(reviewForm instanceof HTMLFormElement)) {
      return;
    }

    const decision = String(decisionSelect?.value || 'approved').trim().toLowerCase();
    const cutoffPeriod = String(periodInput?.value || '').trim();
    const decisionLabel = decision === 'approved' ? 'approve' : 'cancel';
    reviewForm.dataset.confirmTitle = decision === 'approved'
      ? 'Approve this payroll batch?'
      : 'Cancel this payroll batch?';
    reviewForm.dataset.confirmText = cutoffPeriod !== ''
      ? `This will ${decisionLabel} the payroll batch for ${cutoffPeriod} and save the admin decision notes.`
      : `This will ${decisionLabel} the selected payroll batch and save the admin decision notes.`;
    reviewForm.dataset.confirmButtonText = decision === 'approved' ? 'Approve batch' : 'Cancel batch';
    reviewForm.dataset.confirmButtonColor = decision === 'approved' ? '#0f172a' : '#dc2626';
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
    if (breakdownGross) breakdownGross.textContent = formatCurrency(totalGross);
    if (breakdownNet) breakdownNet.textContent = formatCurrency(totalNet);
    if (breakdownRows) breakdownRows.textContent = String(rows.length);

    if (!breakdownBody) {
      return;
    }

    if (!rows.length) {
      breakdownBody.innerHTML = '<tr id="payrollBatchBreakdownEmptyRow"><td class="px-3 py-3 text-slate-500" colspan="11">No computation breakdown available for this batch.</td></tr>';
    } else {
      breakdownBody.innerHTML = rows.map((row) => {
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
            <td class="px-3 py-2 text-right text-slate-700">${formatCurrency(Number(row.basic_pay) || 0)}</td>
            <td class="px-3 py-2 text-right text-slate-700">${formatCurrency(Number(row.allowances_total) || 0)}</td>
            <td class="px-3 py-2 text-right text-slate-700">${formatCurrency(Number(row.cto_pay) || 0)}</td>
            <td class="px-3 py-2 text-right text-slate-700">${formatCurrency(Number(row.statutory_deductions) || 0)}</td>
            <td class="px-3 py-2 text-right text-slate-700">${formatCurrency(Number(row.timekeeping_deductions) || 0)}</td>
            <td class="px-3 py-2 text-right text-slate-700">${lateMinutes} min / ${undertimeHours.toFixed(2)} hr</td>
            <td class="px-3 py-2 text-left text-slate-700">${escapeText(leaveCardRemarks)}</td>
            <td class="px-3 py-2 text-right text-slate-700">${formatCurrency(adjustmentNet)}</td>
            <td class="px-3 py-2 text-right text-slate-700">${formatCurrency(Number(row.gross_pay) || 0)}</td>
            <td class="px-3 py-2 text-right text-slate-800 font-medium">${formatCurrency(Number(row.net_pay) || 0)}</td>
          </tr>
        `;
      }).join('');
    }

    if (adjustmentSubmitted) adjustmentSubmitted.textContent = String(submittedCount);
    if (adjustmentPending) adjustmentPending.textContent = String(pendingCount);
    if (adjustmentApproved) adjustmentApproved.textContent = String(approvedCount);
    if (adjustmentRejected) adjustmentRejected.textContent = String(rejectedCount);

    if (adjustmentBody) {
      if (!adjustmentRows.length) {
        adjustmentBody.innerHTML = '<tr id="payrollBatchAdjustmentEmptyRow"><td class="px-3 py-3 text-slate-500" colspan="6">No staff-submitted salary adjustment recommendations in this batch.</td></tr>';
      } else {
        adjustmentBody.innerHTML = adjustmentRows.map((row) => {
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
              <td class="px-3 py-2 text-right text-slate-700">${formatCurrency(Number(row.amount) || 0)}</td>
              <td class="px-3 py-2 text-slate-700">
                <div>${escapeText(recommendationLabel)}</div>
                <div class="text-[11px] text-slate-500">${escapeText(submittedAt)}</div>
                ${notes !== '' ? `<div class="text-[11px] text-slate-500">Notes: ${escapeText(notes)}</div>` : ''}
              </td>
              <td class="px-3 py-2 text-slate-700">${escapeText(adminLabel)}</td>
            </tr>
          `;
        }).join('');
      }
    }

    if (decisionSelect) {
      const approvedOption = Array.from(decisionSelect.options).find((option) => option.value === 'approved');
      if (approvedOption) {
        if (!approvedOption.dataset.defaultLabel) {
          approvedOption.dataset.defaultLabel = approvedOption.textContent || 'Approve';
        }
        if (pendingCount > 0) {
          approvedOption.disabled = true;
          approvedOption.textContent = `Approve (Blocked: ${pendingCount} pending adjustment review${pendingCount > 1 ? 's' : ''})`;
          if (decisionSelect.value === 'approved') {
            decisionSelect.value = 'cancelled';
          }
        } else {
          approvedOption.disabled = false;
          approvedOption.textContent = approvedOption.dataset.defaultLabel;
        }
      }
    }

    if (adjustmentWarning) {
      adjustmentWarning.classList.toggle('hidden', pendingCount === 0);
    }

    syncReviewConfirmation();
  };

  decisionSelect?.addEventListener('change', syncReviewConfirmation);

  document.querySelectorAll('[data-payroll-review]').forEach((button) => {
    button.addEventListener('click', () => {
      const runId = button.getAttribute('data-run-id') || '';

      if (runIdInput) runIdInput.value = runId;
      if (periodInput) periodInput.value = button.getAttribute('data-period-label') || '';
      if (statusInput) statusInput.value = button.getAttribute('data-current-status') || '';
      if (employeesInput) employeesInput.value = button.getAttribute('data-employee-count') || '';
      if (netInput) netInput.value = button.getAttribute('data-total-net') || '';
      if (recommendationInput) recommendationInput.value = button.getAttribute('data-staff-recommendation') || 'Recommend approval';
      if (submittedAtInput) submittedAtInput.value = button.getAttribute('data-staff-submitted') || '-';
      if (reviewedAtInput) reviewedAtInput.value = button.getAttribute('data-admin-reviewed') || '-';
      renderBreakdown(runId);
      openModal('reviewPayrollBatchModal');
    });
  });

  syncReviewConfirmation();
};

const initPayrollGenerationSummary = () => {
  const openButton = document.getElementById('openGeneratePayrollSummary');
  const periodSelect = document.getElementById('generatePayrollPeriod');
  const periodConfirmId = document.getElementById('generatePayrollPeriodIdConfirm');
  const periodConfirmLabel = document.getElementById('generatePayrollPeriodLabelConfirm');
  const previewBody = document.getElementById('generatePayrollPreviewBody');
  const previewEmployeeCount = document.getElementById('generatePayrollPreviewEmployeeCount');
  const previewGross = document.getElementById('generatePayrollPreviewGross');
  const previewNet = document.getElementById('generatePayrollPreviewNet');
  const quickEmployees = document.getElementById('generatePayrollPreviewQuickEmployees');
  const quickNet = document.getElementById('generatePayrollPreviewQuickNet');
  const previewEmpty = document.getElementById('generatePayrollPreviewEmpty');
  const previewContent = document.getElementById('generatePayrollPreviewContent');
  const confirmButton = document.getElementById('generatePayrollConfirmButton');
  const periodHint = document.getElementById('generatePayrollPeriodHint');
  const generateForm = document.getElementById('generatePayrollSummaryForm');

  if (!openButton || !periodSelect) {
    return;
  }

  const generationPreviewByPeriod = parseJsonScriptNode('generationPreviewByPeriodData');

  const render = () => {
    const selectedOption = periodSelect.options[periodSelect.selectedIndex] || null;
    const periodId = selectedOption?.value || '';
    const payload = periodId && generationPreviewByPeriod[periodId] ? generationPreviewByPeriod[periodId] : null;
    const rows = Array.isArray(payload?.rows) ? payload.rows : [];
    const totals = payload && typeof payload === 'object' ? (payload.totals || {}) : {};

    if (periodConfirmId) {
      periodConfirmId.value = periodId;
    }
    if (periodConfirmLabel) {
      periodConfirmLabel.value = selectedOption?.textContent || '';
    }

    if (quickEmployees) {
      quickEmployees.textContent = periodId ? `${rows.length} employee(s)` : 'No period selected';
    }
    if (quickNet) {
      quickNet.textContent = `Net: ${formatCurrency(Number(totals.net_pay) || 0)}`;
    }

    const hasData = rows.length > 0;
    previewEmpty?.classList.toggle('hidden', hasData);
    previewContent?.classList.toggle('hidden', !hasData);
    if (confirmButton) {
      confirmButton.disabled = !hasData;
      confirmButton.setAttribute('aria-disabled', hasData ? 'false' : 'true');
    }

    if (previewEmployeeCount) {
      previewEmployeeCount.textContent = String(Number(totals.employee_count) || rows.length || 0);
    }
    if (previewGross) {
      previewGross.textContent = formatCurrency(Number(totals.gross_pay) || 0);
    }
    if (previewNet) {
      previewNet.textContent = formatCurrency(Number(totals.net_pay) || 0);
    }

    if (previewBody) {
      previewBody.innerHTML = rows.map((row) => `
        <tr>
          <td class="px-4 py-3 text-slate-700">${String(row.employee_name || '-')}</td>
          <td class="px-4 py-3 text-slate-600">${String(row.pay_frequency || '').replace(/_/g, ' ')}</td>
          <td class="px-4 py-3 text-right text-slate-700">${formatCurrency(Number(row.gross_pay) || 0)}</td>
          <td class="px-4 py-3 text-right text-slate-800 font-medium">${formatCurrency(Number(row.net_pay) || 0)}</td>
        </tr>
      `).join('');
    }

    if (periodHint) {
      periodHint.textContent = periodId
        ? 'Review estimated employees and totals before generation.'
        : 'Select a period to review estimated employees and totals before generation.';
    }
  };

  periodSelect.addEventListener('change', render);
  openButton.addEventListener('click', () => {
    render();
    openModal('generatePayrollSummaryModal');
  });

  if (generateForm) {
    generateForm.addEventListener('submit', async (event) => {
      if (generateForm.dataset.confirmed === '1') {
        return;
      }

      event.preventDefault();

      const periodLabel = (periodConfirmLabel?.value || '').trim();
      let confirmed = false;

      if (window.Swal && typeof window.Swal.fire === 'function') {
        const result = await window.Swal.fire({
          title: 'Confirm payroll batch generation?',
          text: periodLabel !== '' ? `Period: ${periodLabel}` : 'Proceed with payroll batch generation.',
          icon: 'question',
          showCancelButton: true,
          confirmButtonText: 'Yes, generate',
          cancelButtonText: 'Cancel',
          reverseButtons: true,
          focusCancel: true,
        });

        confirmed = Boolean(result?.isConfirmed);
      } else {
        confirmed = window.confirm(periodLabel !== ''
          ? `Generate payroll batch for ${periodLabel}?`
          : 'Generate payroll batch now?');
      }

      if (!confirmed) {
        return;
      }

      generateForm.dataset.confirmed = '1';
      generateForm.submit();
    });
  }

  render();
};

const initReleaseBatchMeta = () => {
  const select = document.getElementById('releasePayrollRunSelect');
  const hint = document.getElementById('releasePayslipRunHint');
  const meta = document.getElementById('releasePayslipRunMeta');

  if (!select || !meta) {
    return;
  }

  const render = () => {
    const selected = select.options[select.selectedIndex] || null;
    if (!selected || !selected.value) {
      if (hint) {
        hint.textContent = 'Select a payroll batch to review release details before sending payslips.';
      }
      meta.textContent = 'No payroll batch selected.';
      return;
    }

    const periodCode = selected.getAttribute('data-period-code') || '-';
    const periodLabel = selected.getAttribute('data-period-label') || '-';
    const status = selected.getAttribute('data-status') || '-';
    const employeeCount = selected.getAttribute('data-employee-count') || '0';
    const totalNet = Number(selected.getAttribute('data-total-net') || 0);

    if (hint) {
      hint.textContent = 'Release sends emails immediately and records payroll release activity logs.';
    }
    meta.textContent = `${periodCode} • ${periodLabel} • ${status} • ${employeeCount} employee(s) • Net ${formatCurrency(totalNet)}`;
  };

  select.addEventListener('change', render);
  render();
};

const initAdminPayslipBreakdownModal = (detailUrl = '') => {
  const employeeField = document.getElementById('adminPayslipBreakdownEmployee');
  const periodField = document.getElementById('adminPayslipBreakdownPeriod');
  const noField = document.getElementById('adminPayslipBreakdownNo');
  const statusField = document.getElementById('adminPayslipBreakdownStatus');
  const earningsContainer = document.getElementById('adminPayslipBreakdownEarnings');
  const deductionsContainer = document.getElementById('adminPayslipBreakdownDeductions');
  const grossField = document.getElementById('adminPayslipBreakdownGross');
  const totalDeductionsField = document.getElementById('adminPayslipBreakdownTotalDeductions');
  const netField = document.getElementById('adminPayslipBreakdownNet');
  const attendanceField = document.getElementById('adminPayslipBreakdownAttendance');

  if (!employeeField || !periodField || !noField || !statusField || !earningsContainer || !deductionsContainer || !grossField || !totalDeductionsField || !netField || !attendanceField) {
    return;
  }

  const renderRows = (container, rows) => {
    if (!Array.isArray(rows) || rows.length === 0) {
      container.innerHTML = '<div class="px-4 py-3 text-slate-500 text-xs">No data available.</div>';
      return;
    }

    container.innerHTML = rows.map((row) => {
      const label = escapeText(String(row.label || 'Entry'));
      const amount = Number(row.amount) || 0;

      return `
        <div class="px-4 py-3 flex items-center justify-between gap-2 text-xs">
          <span class="text-slate-600">${label}</span>
          <span class="text-slate-800 font-medium">${formatCurrency(amount)}</span>
        </div>
      `;
    }).join('');
  };

  const renderPayload = (payload) => {
    const basicPay = Number(payload.basic_pay) || 0;
    const ctoPay = Number(payload.cto_pay) || 0;
    const allowancesTotal = Number(payload.allowances_total) || 0;
    const adjustmentEarnings = Number(payload.adjustment_earnings) || 0;

    const statutoryDeductions = Number(payload.statutory_deductions) || 0;
    const timekeepingDeductions = Number(payload.timekeeping_deductions) || 0;
    const adjustmentDeductions = Number(payload.adjustment_deductions) || 0;

    employeeField.textContent = String(payload.employee_name || '-');
    periodField.textContent = String(payload.period_label || '-');
    noField.textContent = String(payload.payslip_no || '-');
    statusField.textContent = String(payload.status_label || '-');

    renderRows(earningsContainer, [
      { label: 'Basic Pay', amount: basicPay },
      { label: 'CTO Leave UT w/ Pay', amount: ctoPay },
      { label: 'Allowances', amount: allowancesTotal },
      { label: 'Approved Adjustment Earnings', amount: adjustmentEarnings },
    ]);

    renderRows(deductionsContainer, [
      { label: 'Statutory / Government Contributions (SSS/Pag-IBIG/PhilHealth)', amount: statutoryDeductions },
      { label: 'Timekeeping Deductions', amount: timekeepingDeductions },
      { label: 'Approved Adjustment Deductions', amount: adjustmentDeductions },
    ]);

    grossField.textContent = formatCurrency(Number(payload.gross_pay) || 0);
    totalDeductionsField.textContent = formatCurrency(Number(payload.deductions_total) || 0);
    netField.textContent = formatCurrency(Number(payload.net_pay) || 0);
    attendanceField.textContent = `Attendance impact: Absent ${Number(payload.absent_days) || 0} day(s), Late ${Number(payload.late_minutes) || 0} minute(s), Undertime ${(Number(payload.undertime_hours) || 0).toFixed(2)} hour(s)`;
  };

  const renderLoadingPayload = () => {
    employeeField.textContent = 'Loading...';
    periodField.textContent = 'Loading...';
    noField.textContent = 'Loading...';
    statusField.textContent = 'Loading...';
    renderRows(earningsContainer, []);
    renderRows(deductionsContainer, []);
    grossField.textContent = formatCurrency(0);
    totalDeductionsField.textContent = formatCurrency(0);
    netField.textContent = formatCurrency(0);
    attendanceField.textContent = 'Loading payslip breakdown...';
  };

  document.querySelectorAll('[data-open-admin-payslip-breakdown]').forEach((button) => {
    if (button.dataset.payrollBreakdownBound === '1') {
      return;
    }

    button.dataset.payrollBreakdownBound = '1';
    button.addEventListener('click', async () => {
      let payload = {};

      const payrollItemId = button.getAttribute('data-payroll-item-id') || '';
      if (detailUrl !== '' && payrollItemId !== '') {
        try {
          const requestUrl = new URL(detailUrl, window.location.href);
          requestUrl.searchParams.set('item_id', payrollItemId);
          renderLoadingPayload();
          openModal('adminPayslipBreakdownModal');

          const response = await fetch(requestUrl.toString(), {
            credentials: 'same-origin',
            headers: {
              'X-Requested-With': 'XMLHttpRequest',
              Accept: 'application/json',
            },
          });

          if (!response.ok) {
            throw new Error(`Payslip detail request failed with status ${response.status}`);
          }

          payload = await response.json();
        } catch (error) {
          console.error(error);
          payload = {
            employee_name: 'Unavailable',
            period_label: '-',
            payslip_no: '-',
            status_label: 'Unavailable',
            absent_days: 0,
            late_minutes: 0,
            undertime_hours: 0,
          };
          attendanceField.textContent = error instanceof Error ? error.message : 'Payslip breakdown could not be loaded.';
        }
      } else {
        try {
          payload = JSON.parse(button.getAttribute('data-payload') || '{}');
        } catch (_error) {
          payload = {};
        }
        openModal('adminPayslipBreakdownModal');
      }

      renderPayload(payload);
    });
  });
};

const initPayrollBatchFilters = ({ reloadBatches }) => {
  const filtersForm = document.getElementById('payrollBatchesFilters');
  const pageInput = document.getElementById('payrollBatchesPage');
  const searchInput = document.getElementById('payrollBatchesSearch');
  const statusFilter = document.getElementById('payrollBatchesStatusFilter');
  const prevButton = document.getElementById('payrollBatchesPrev');
  const nextButton = document.getElementById('payrollBatchesNext');

  if (!filtersForm || !pageInput || !searchInput || !statusFilter) {
    return;
  }

  let searchTimer = 0;

  const submitCurrentState = () => {
    reloadBatches(new FormData(filtersForm));
  };

  searchInput.addEventListener('input', () => {
    pageInput.value = '1';
    window.clearTimeout(searchTimer);
    searchTimer = window.setTimeout(() => {
      submitCurrentState();
    }, 250);
  });

  statusFilter.addEventListener('change', () => {
    pageInput.value = '1';
    submitCurrentState();
  });

  [prevButton, nextButton].forEach((button) => {
    button?.addEventListener('click', () => {
      const targetPage = button.getAttribute('data-payroll-batches-page') || '';
      if (targetPage === '') {
        return;
      }

      pageInput.value = targetPage;
      submitCurrentState();
    });
  });
};

const initPaginatedFilterTable = ({
  tableId,
  rowSelector,
  searchInputId,
  searchAttr,
  filterConfig = [],
  pageInfoId,
  pageLabelId,
  prevButtonId,
  nextButtonId,
  pageSizeSelectId,
  defaultPageSize = 10,
  emptyMessage = 'No entries match your search/filter criteria.',
}) => {
  const table = document.getElementById(tableId);
  if (!table) {
    return;
  }

  if (table.dataset.payrollTableInitialized === '1') {
    return;
  }
  table.dataset.payrollTableInitialized = '1';

  const tbody = table.querySelector('tbody');
  const rows = Array.from(table.querySelectorAll(`tbody ${rowSelector}`));
  const searchInput = searchInputId ? document.getElementById(searchInputId) : null;
  const pageInfo = pageInfoId ? document.getElementById(pageInfoId) : null;
  const pageLabel = pageLabelId ? document.getElementById(pageLabelId) : null;
  const prevButton = prevButtonId ? document.getElementById(prevButtonId) : null;
  const nextButton = nextButtonId ? document.getElementById(nextButtonId) : null;
  const pageSizeSelect = pageSizeSelectId ? document.getElementById(pageSizeSelectId) : null;

  if (!tbody || !pageInfo || !pageLabel || !prevButton || !nextButton) {
    return;
  }

  let currentPage = 1;
  let initialized = false;
  const columnCount = Array.from(table.querySelectorAll('thead th')).length || 1;
  let emptyRow = tbody.querySelector('[data-payroll-filter-empty="true"]');

  if (!emptyRow && rows.length > 0) {
    emptyRow = document.createElement('tr');
    emptyRow.dataset.payrollFilterEmpty = 'true';
    emptyRow.className = 'hidden';
    emptyRow.innerHTML = `<td class="px-4 py-3 text-slate-500" colspan="${columnCount}">${emptyMessage}</td>`;
    tbody.appendChild(emptyRow);
  }

  const getPageSize = () => {
    const value = Number(pageSizeSelect?.value || defaultPageSize);
    return Number.isFinite(value) && value > 0 ? value : defaultPageSize;
  };

  const render = () => {
    const query = String(searchInput?.value || '').trim().toLowerCase();
    const filteredRows = rows.filter((row) => {
      const searchText = String(row.getAttribute(searchAttr) || '').toLowerCase();
      const searchMatch = query === '' || searchText.includes(query);
      if (!searchMatch) {
        return false;
      }

      return filterConfig.every(({ elementId, rowAttr }) => {
        const element = document.getElementById(elementId);
        const selected = String(element?.value || '').trim().toLowerCase();
        if (selected === '') {
          return true;
        }

        const rowValue = String(row.getAttribute(rowAttr) || '').toLowerCase();
        return rowValue === selected;
      });
    });

    const totalRows = filteredRows.length;
    const pageSize = getPageSize();
    const totalPages = Math.max(1, Math.ceil(totalRows / pageSize));
    currentPage = Math.min(Math.max(currentPage, 1), totalPages);

    const startIndex = totalRows === 0 ? 0 : (currentPage - 1) * pageSize;
    const endIndex = totalRows === 0 ? 0 : Math.min(startIndex + pageSize, totalRows);
    const visibleRows = new Set(filteredRows.slice(startIndex, endIndex));

    rows.forEach((row) => {
      row.classList.toggle('hidden', !visibleRows.has(row));
    });

    if (emptyRow) {
      emptyRow.classList.toggle('hidden', totalRows !== 0);
    }

    pageInfo.textContent = totalRows === 0
      ? 'Showing 0 to 0 of 0 entries'
      : `Showing ${startIndex + 1} to ${endIndex} of ${totalRows} entries`;
    pageLabel.textContent = `Page ${totalRows === 0 ? 1 : currentPage} of ${totalRows === 0 ? 1 : totalPages}`;
    prevButton.disabled = totalRows === 0 || currentPage <= 1;
    nextButton.disabled = totalRows === 0 || currentPage >= totalPages;
  };

  const resetToFirstPage = () => {
    if (!initialized) {
      return;
    }

    currentPage = 1;
    render();
  };

  searchInput?.addEventListener('input', resetToFirstPage);
  filterConfig.forEach(({ elementId }) => {
    document.getElementById(elementId)?.addEventListener('change', resetToFirstPage);
  });
  pageSizeSelect?.addEventListener('change', resetToFirstPage);

  prevButton.addEventListener('click', () => {
    if (!initialized || currentPage <= 1) {
      return;
    }

    currentPage -= 1;
    render();
  });

  nextButton.addEventListener('click', () => {
    if (!initialized) {
      return;
    }

    currentPage += 1;
    render();
  });

  const initialize = () => {
    if (initialized) {
      return;
    }

    initialized = true;
    render();
  };

  if (typeof window.IntersectionObserver !== 'function') {
    initialize();
    return;
  }

  const scope = table.closest('section') || table.parentElement || table;
  const observer = new window.IntersectionObserver((entries) => {
    if (!entries.some((entry) => entry.isIntersecting)) {
      return;
    }

    observer.disconnect();
    initialize();
  }, {
    rootMargin: '240px 0px',
  });

  observer.observe(scope);
};

const initBulkSelectionGroup = ({ selectAllId, rowSelector, buttonId }) => {
  const selectAll = document.getElementById(selectAllId);
  const checkboxes = Array.from(document.querySelectorAll(rowSelector));
  const bulkDeleteButton = document.getElementById(buttonId);

  if (!checkboxes.length || !bulkDeleteButton) {
    return;
  }

  if (bulkDeleteButton.dataset.payrollBulkSelectionInitialized === '1') {
    return;
  }
  bulkDeleteButton.dataset.payrollBulkSelectionInitialized = '1';

  const refresh = () => {
    const selected = checkboxes.filter((checkbox) => checkbox.checked).length;
    const allSelected = selected > 0 && selected === checkboxes.length;

    bulkDeleteButton.disabled = selected === 0;
    bulkDeleteButton.setAttribute('aria-disabled', selected === 0 ? 'true' : 'false');

    if (selectAll) {
      selectAll.checked = allSelected;
      selectAll.indeterminate = selected > 0 && !allSelected;
    }
  };

  selectAll?.addEventListener('change', () => {
    checkboxes.forEach((checkbox) => {
      checkbox.checked = Boolean(selectAll.checked);
    });
    refresh();
  });

  checkboxes.forEach((checkbox) => {
    checkbox.addEventListener('change', refresh);
  });

  refresh();
};

const initPayrollBulkSelections = () => {
  initBulkSelectionGroup({
    selectAllId: 'payrollSetupLogsSelectAll',
    rowSelector: '[data-payroll-setup-log-select]',
    buttonId: 'payrollBulkDeleteSetupButton',
  });

  initBulkSelectionGroup({
    selectAllId: 'payrollPeriodsSelectAll',
    rowSelector: '[data-payroll-period-select]',
    buttonId: 'payrollBulkDeletePeriodsButton',
  });

  initBulkSelectionGroup({
    selectAllId: 'payrollBatchesSelectAll',
    rowSelector: '[data-payroll-batch-select]',
    buttonId: 'payrollBulkDeleteBatchesButton',
  });
};

const ensureFlatpickr = async () => {
  if (window.flatpickr) {
    return window.flatpickr;
  }

  await new Promise((resolve, reject) => {
    const existing = document.querySelector('script[data-flatpickr="true"]');
    if (existing) {
      existing.addEventListener('load', () => resolve(), { once: true });
      existing.addEventListener('error', () => reject(new Error('Failed to load Flatpickr.')), { once: true });
      return;
    }

    const script = document.createElement('script');
    script.src = 'https://cdn.jsdelivr.net/npm/flatpickr';
    script.defer = true;
    script.dataset.flatpickr = 'true';
    script.onload = () => resolve();
    script.onerror = () => reject(new Error('Failed to load Flatpickr.'));
    document.head.appendChild(script);
  });

  return window.flatpickr;
};

const initPayrollDatePickers = async () => {
  const effectiveDateInput = document.querySelector('#payrollSalarySetupForm input[name="effective_from"]');
  if (!effectiveDateInput) {
    return;
  }

  const flatpickr = await ensureFlatpickr();
  flatpickr(effectiveDateInput, {
    dateFormat: 'Y-m-d',
    allowInput: true,
  });
};

const initPayrollTables = () => {
  initPaginatedFilterTable({
    tableId: 'payrollEmployeePickerTable',
    rowSelector: 'tr[data-payroll-employee-picker-search]',
    searchInputId: 'payrollEmployeePickerSearch',
    searchAttr: 'data-payroll-employee-picker-search',
    filterConfig: [
      { elementId: 'payrollEmployeePickerStatusFilter', rowAttr: 'data-payroll-employee-picker-status' },
    ],
    pageInfoId: 'payrollEmployeePickerPaginationInfo',
    pageLabelId: 'payrollEmployeePickerPageLabel',
    prevButtonId: 'payrollEmployeePickerPrev',
    nextButtonId: 'payrollEmployeePickerNext',
  });

  initPaginatedFilterTable({
    tableId: 'payrollSetupLogsTable',
    rowSelector: 'tr[data-payroll-setup-log-search]',
    searchInputId: 'payrollSetupLogsSearch',
    searchAttr: 'data-payroll-setup-log-search',
    filterConfig: [
      { elementId: 'payrollSetupLogsStatusFilter', rowAttr: 'data-payroll-setup-log-status' },
    ],
    pageInfoId: 'payrollSetupLogsPageInfo',
    pageLabelId: 'payrollSetupLogsPageLabel',
    prevButtonId: 'payrollSetupLogsPrev',
    nextButtonId: 'payrollSetupLogsNext',
    pageSizeSelectId: 'payrollSetupLogsPageSize',
  });

  initPaginatedFilterTable({
    tableId: 'payrollPeriodsTable',
    rowSelector: 'tr[data-payroll-period-search]',
    searchInputId: 'payrollPeriodsSearch',
    searchAttr: 'data-payroll-period-search',
    filterConfig: [
      { elementId: 'payrollPeriodsStatusFilter', rowAttr: 'data-payroll-period-status' },
    ],
    pageInfoId: 'payrollPeriodsPageInfo',
    pageLabelId: 'payrollPeriodsPageLabel',
    prevButtonId: 'payrollPeriodsPrev',
    nextButtonId: 'payrollPeriodsNext',
  });

  initPaginatedFilterTable({
    tableId: 'payrollPayslipsTable',
    rowSelector: 'tr[data-payroll-payslip-search]',
    searchInputId: 'payrollPayslipsSearch',
    searchAttr: 'data-payroll-payslip-search',
    filterConfig: [
      { elementId: 'payrollPayslipsStatusFilter', rowAttr: 'data-payroll-payslip-status' },
    ],
    pageInfoId: 'payrollPayslipsPageInfo',
    pageLabelId: 'payrollPayslipsPageLabel',
    prevButtonId: 'payrollPayslipsPrev',
    nextButtonId: 'payrollPayslipsNext',
  });
};

const initAdminPayrollAsync = () => {
  const region = document.getElementById('adminPayrollAsyncRegion');
  const summaryUrl = region?.getAttribute('data-payroll-summary-url') || '';
  const setupUrl = region?.getAttribute('data-payroll-setup-url') || '';
  const batchesUrl = region?.getAttribute('data-payroll-batches-url') || '';
  const payslipDetailUrl = region?.getAttribute('data-payroll-payslip-detail-url') || '';
  const tabButtons = Array.from(document.querySelectorAll('[data-payroll-tab]'));
  const tabPanels = Array.from(document.querySelectorAll('[data-payroll-tab-panel]'));

  const summarySkeleton = document.getElementById('adminPayrollSummarySkeleton');
  const summaryContent = document.getElementById('adminPayrollSummaryContent');
  const summaryError = document.getElementById('adminPayrollSummaryError');
  const summaryRetry = document.getElementById('adminPayrollSummaryRetry');

  const setupSkeleton = document.getElementById('adminPayrollSetupSkeleton');
  const setupContent = document.getElementById('adminPayrollSetupContent');
  const setupError = document.getElementById('adminPayrollSetupError');
  const setupRetry = document.getElementById('adminPayrollSetupRetry');

  const batchesSkeleton = document.getElementById('adminPayrollBatchesSkeleton');
  const batchesContent = document.getElementById('adminPayrollBatchesContent');
  const batchesError = document.getElementById('adminPayrollBatchesError');
  const batchesRetry = document.getElementById('adminPayrollBatchesRetry');

  const summaryState = { requestId: 0 };
  const setupState = { requestId: 0 };
  const batchState = { requestId: 0 };
  const loadedSections = {
    summary: false,
    setup: false,
    batches: false,
  };
  const pendingSections = {
    summary: false,
    setup: false,
    batches: false,
  };

  if (!region || summaryUrl === '' || setupUrl === '' || batchesUrl === '' || !summarySkeleton || !summaryContent || !summaryError || !summaryRetry || !setupSkeleton || !setupContent || !setupError || !setupRetry || !batchesSkeleton || !batchesContent || !batchesError || !batchesRetry || !tabButtons.length || !tabPanels.length) {
    return false;
  }

  const buildSectionUrl = (baseUrl, params) => {
    const url = new URL(baseUrl, window.location.href);

    if (!(params instanceof FormData)) {
      return url.toString();
    }

    Array.from(params.entries()).forEach(([key, value]) => {
      url.searchParams.set(key, String(value));
    });

    return url.toString();
  };

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
      throw new Error(`Payroll partial request failed with status ${response.status}`);
    }

    const html = await response.text();
    if (html.trim() === '') {
      throw new Error('Payroll partial returned an empty response.');
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

  const loadSection = async ({ sectionName, url, params, skeleton, content, errorBox, retryButton, onSuccess, state }) => {
    setLoadingState({ skeleton, content, errorBox, retryButton }, true);
    state.requestId += 1;
    const requestId = state.requestId;
    const startedAt = window.performance?.now?.() ?? Date.now();
    const requestUrl = buildSectionUrl(url, params);
    if (sectionName === 'Summary') {
      pendingSections.summary = true;
    } else if (sectionName === 'Salary Setup') {
      pendingSections.setup = true;
    } else if (sectionName === 'Approval Batches') {
      pendingSections.batches = true;
    }

    try {
      const html = await fetchPartialHtml(requestUrl);
      const fetchElapsed = (window.performance?.now?.() ?? Date.now()) - startedAt;
      const remaining = Math.max(0, MIN_SKELETON_MS - fetchElapsed);

      return await new Promise((resolve) => {
        window.setTimeout(() => {
          if (requestId !== state.requestId) {
            resolve(false);
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
          if (sectionName === 'Summary') {
            pendingSections.summary = false;
          } else if (sectionName === 'Salary Setup') {
            pendingSections.setup = false;
          } else if (sectionName === 'Approval Batches') {
            pendingSections.batches = false;
          }
          emitQaPerfSectionMetric({
            section: sectionName,
            status: 'success',
            fetchMs: fetchElapsed,
            displayMs: displayElapsed,
            url: requestUrl,
          });
          resolve(true);
        }, remaining);
      });
    } catch (error) {
      console.error(error);
      showErrorState({ skeleton, content, errorBox, retryButton });
      if (sectionName === 'Summary') {
        pendingSections.summary = false;
      } else if (sectionName === 'Salary Setup') {
        pendingSections.setup = false;
      } else if (sectionName === 'Approval Batches') {
        pendingSections.batches = false;
      }
      const elapsed = (window.performance?.now?.() ?? Date.now()) - startedAt;
      emitQaPerfSectionMetric({
        section: sectionName,
        status: 'error',
        fetchMs: elapsed,
        displayMs: elapsed,
        detail: error instanceof Error ? error.message : 'Section load failed.',
        url: requestUrl,
      });
      return false;
    }
  };

  const initPayrollRefreshButtons = () => {
    if (region.dataset.payrollRefreshBound === '1') {
      return;
    }

    region.dataset.payrollRefreshBound = '1';
    region.addEventListener('click', (event) => {
      const trigger = event.target instanceof Element
        ? event.target.closest('[data-payroll-refresh-tab]')
        : null;
      if (!trigger) {
        return;
      }

      const tabName = trigger.getAttribute('data-payroll-refresh-tab') || 'summary';
      if (tabName === 'setup') {
        loadSetup().catch(console.error);
        return;
      }

      if (tabName === 'batches') {
        loadBatches().catch(console.error);
        return;
      }

      loadSummary().catch(console.error);
    });
  };

  const afterBatchesSuccess = () => {
    initModalSystem();
    initStatusChangeConfirmations();
    initPayrollBulkSelections();
    initPayrollBatchReviewModal();
    initPayrollBatchFilters({ reloadBatches: loadBatches });
  };

  const afterSummarySuccess = () => {
    initModalSystem();
    initStatusChangeConfirmations();
    initPayrollTables();
    initPayrollSalarySetup();
    initPayrollBulkSelections();
    initPayrollBatchReviewModal();
    initPayrollGenerationSummary();
    initReleaseBatchMeta();
    initAdminPayslipBreakdownModal(payslipDetailUrl);
    initPayrollDatePickers().catch(console.error);
  };

  const afterSetupSuccess = () => {
    initModalSystem();
    initStatusChangeConfirmations();
    initPayrollTables();
    initPayrollSalarySetup();
    initPayrollBulkSelections();
    initPayrollDatePickers().catch(console.error);
  };

  const loadSummary = () => loadSection({
    sectionName: 'Summary',
    url: summaryUrl,
    skeleton: summarySkeleton,
    content: summaryContent,
    errorBox: summaryError,
    retryButton: summaryRetry,
    state: summaryState,
    onSuccess: () => {
      loadedSections.summary = true;
      afterSummarySuccess();
    },
  });

  const loadSetup = () => loadSection({
    sectionName: 'Salary Setup',
    url: setupUrl,
    skeleton: setupSkeleton,
    content: setupContent,
    errorBox: setupError,
    retryButton: setupRetry,
    state: setupState,
    onSuccess: () => {
      loadedSections.setup = true;
      afterSetupSuccess();
    },
  });

  function loadBatches(params) {
    return loadSection({
      sectionName: 'Approval Batches',
      url: batchesUrl,
      params,
      skeleton: batchesSkeleton,
      content: batchesContent,
      errorBox: batchesError,
      retryButton: batchesRetry,
      state: batchState,
      onSuccess: () => {
        loadedSections.batches = true;
        afterBatchesSuccess();
      },
    });
  }
  const activateTab = (tabName) => {
    tabButtons.forEach((button) => {
      const isActive = button.getAttribute('data-payroll-tab') === tabName;
      button.setAttribute('aria-selected', isActive ? 'true' : 'false');
      button.classList.toggle('border-slate-900', isActive);
      button.classList.toggle('bg-slate-900', isActive);
      button.classList.toggle('text-white', isActive);
      button.classList.toggle('border-slate-300', !isActive);
      button.classList.toggle('bg-white', !isActive);
      button.classList.toggle('text-slate-700', !isActive);
    });

    tabPanels.forEach((panel) => {
      panel.classList.toggle('hidden', panel.getAttribute('data-payroll-tab-panel') !== tabName);
    });
  };

  const ensureTabLoaded = (tabName) => {
    if (tabName === 'summary') {
      if (loadedSections.summary || pendingSections.summary) {
        return;
      }
      loadSummary().catch(console.error);
      return;
    }

    if (tabName === 'batches') {
      if (loadedSections.batches || pendingSections.batches) {
        return;
      }
      loadBatches().catch(console.error);
      return;
    }

    if (tabName === 'setup') {
      if (loadedSections.setup || pendingSections.setup) {
        return;
      }
      loadSetup().catch(console.error);
      return;
    }

  };

  tabButtons.forEach((button) => {
    button.addEventListener('click', () => {
      const tabName = button.getAttribute('data-payroll-tab') || 'summary';
      activateTab(tabName);
      ensureTabLoaded(tabName);
    });
  });

  summaryRetry.addEventListener('click', () => {
    loadSummary().catch(console.error);
  });

  setupRetry.addEventListener('click', () => {
    loadSetup().catch(console.error);
  });

  batchesRetry.addEventListener('click', () => {
    loadBatches().catch(console.error);
  });
  initPayrollRefreshButtons();
  activateTab('summary');
  ensureTabLoaded('summary');

  return true;
};

export default function initAdminPayrollManagementPage() {
  initAdminShellInteractions();
  initModalSystem();
  initStatusChangeConfirmations();

  if (initAdminPayrollAsync()) {
    return;
  }

  initPayrollTables();
  initPayrollSalarySetup();
  initPayrollBulkSelections();
  initPayrollBatchReviewModal();
  initPayrollGenerationSummary();
  initReleaseBatchMeta();
  initAdminPayslipBreakdownModal();
  initPayrollDatePickers().catch(console.error);

  document.querySelectorAll('[data-payroll-refresh-tab]').forEach((button) => {
    if (button.dataset.payrollRefreshFallbackBound === '1') {
      return;
    }

    button.dataset.payrollRefreshFallbackBound = '1';
    button.addEventListener('click', () => {
      window.location.reload();
    });
  });
}
