import {
  bindTableFilters,
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
  const escapeText = (value) => String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');

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
      breakdownBody.innerHTML = '<tr id="payrollBatchBreakdownEmptyRow"><td class="px-3 py-3 text-slate-500" colspan="10">No computation breakdown available for this batch.</td></tr>';
    } else {
      breakdownBody.innerHTML = rows.map((row) => {
        const adjustmentNet = (Number(row.adjustment_earnings) || 0) - (Number(row.adjustment_deductions) || 0);
        return `
          <tr>
            <td class="px-3 py-2 text-slate-700">${escapeText(row.employee_name || '-')}</td>
            <td class="px-3 py-2 text-right text-slate-700">${formatCurrency(Number(row.basic_pay) || 0)}</td>
            <td class="px-3 py-2 text-right text-slate-700">${formatCurrency(Number(row.allowances_total) || 0)}</td>
            <td class="px-3 py-2 text-right text-slate-700">${formatCurrency(Number(row.cto_pay) || 0)}</td>
            <td class="px-3 py-2 text-right text-slate-700">${formatCurrency(Number(row.statutory_deductions) || 0)}</td>
            <td class="px-3 py-2 text-right text-slate-700">${formatCurrency(Number(row.timekeeping_deductions) || 0)}</td>
            <td class="px-3 py-2 text-right text-slate-700">${Number(row.absent_days) || 0}/${Number(row.late_minutes) || 0}/${Number(row.undertime_hours) || 0}</td>
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
  };

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

const initSalarySetupLogsPagination = () => {
  const table = document.getElementById('payrollSetupLogsTable');
  const pageSizeSelect = document.getElementById('payrollSetupLogsPageSize');
  const prevButton = document.getElementById('payrollSetupLogsPrev');
  const nextButton = document.getElementById('payrollSetupLogsNext');
  const pageInfo = document.getElementById('payrollSetupLogsPageInfo');

  if (!table || !pageSizeSelect || !prevButton || !nextButton || !pageInfo) {
    return;
  }

  const rows = Array.from(table.querySelectorAll('tbody tr')).filter((row) => row.querySelector('td'));
  let currentPage = 1;

  const render = () => {
    const visibleRows = rows.filter((row) => row.style.display !== 'none');
    const pageSize = Math.max(1, Number(pageSizeSelect.value) || 10);
    const totalPages = Math.max(1, Math.ceil(visibleRows.length / pageSize));
    currentPage = Math.min(Math.max(currentPage, 1), totalPages);

    visibleRows.forEach((row, index) => {
      const inPage = index >= (currentPage - 1) * pageSize && index < currentPage * pageSize;
      row.classList.toggle('hidden', !inPage);
    });

    pageInfo.textContent = `Showing page ${currentPage} of ${totalPages} (${visibleRows.length} total)`;
    prevButton.disabled = currentPage <= 1;
    nextButton.disabled = currentPage >= totalPages;
  };

  pageSizeSelect.addEventListener('change', () => {
    currentPage = 1;
    render();
  });
  prevButton.addEventListener('click', () => {
    currentPage -= 1;
    render();
  });
  nextButton.addEventListener('click', () => {
    currentPage += 1;
    render();
  });

  const observer = new MutationObserver(() => {
    currentPage = 1;
    render();
  });
  rows.forEach((row) => observer.observe(row, { attributes: true, attributeFilter: ['style'] }));

  render();
};

const initBulkSelectionGroup = ({ selectAllId, rowSelector, buttonId }) => {
  const selectAll = document.getElementById(selectAllId);
  const checkboxes = Array.from(document.querySelectorAll(rowSelector));
  const bulkDeleteButton = document.getElementById(buttonId);

  if (!checkboxes.length || !bulkDeleteButton) {
    return;
  }

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
  bindTableFilters({
    tableId: 'payrollEmployeePickerTable',
    searchInputId: 'payrollEmployeePickerSearch',
    searchDataAttr: 'data-payroll-employee-picker-search',
    statusFilterId: 'payrollEmployeePickerStatusFilter',
    statusDataAttr: 'data-payroll-employee-picker-status',
  });

  bindTableFilters({
    tableId: 'payrollSetupLogsTable',
    searchInputId: 'payrollSetupLogsSearch',
    searchDataAttr: 'data-payroll-setup-log-search',
    statusFilterId: 'payrollSetupLogsStatusFilter',
    statusDataAttr: 'data-payroll-setup-log-status',
  });

  bindTableFilters({
    tableId: 'payrollBatchesTable',
    searchInputId: 'payrollBatchesSearch',
    searchDataAttr: 'data-payroll-batch-search',
    statusFilterId: 'payrollBatchesStatusFilter',
    statusDataAttr: 'data-payroll-batch-status',
  });

  bindTableFilters({
    tableId: 'payrollPayslipsTable',
    searchInputId: 'payrollPayslipsSearch',
    searchDataAttr: 'data-payroll-payslip-search',
    statusFilterId: 'payrollPayslipsStatusFilter',
    statusDataAttr: 'data-payroll-payslip-status',
  });
};

export default function initAdminPayrollManagementPage() {
  initAdminShellInteractions();
  initModalSystem();
  initStatusChangeConfirmations();
  initPayrollTables();
  initPayrollSalarySetup();
  initSalarySetupLogsPagination();
  initPayrollBulkSelections();
  initPayrollBatchReviewModal();
  initPayrollGenerationSummary();
  initReleaseBatchMeta();
  initPayrollDatePickers().catch(console.error);
}
