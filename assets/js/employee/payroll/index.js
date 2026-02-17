import { runPageStatePass } from '../../shared/ui/page-state.js';

const formatCurrency = (value) => {
  const numeric = Number(value || 0);
  return `₱ ${numeric.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
};

const toggleModal = (modal, open) => {
  if (!modal) return;
  modal.classList.toggle('hidden', !open);
  modal.setAttribute('aria-hidden', open ? 'false' : 'true');
};

const wireYearFilter = () => {
  const filter = document.getElementById('payrollYearFilter');
  const rows = Array.from(document.querySelectorAll('[data-payroll-row]'));
  const empty = document.getElementById('payrollFilterEmpty');

  if (!filter || !rows.length) return;

  const render = () => {
    const selected = (filter.value || '').trim();
    let visible = 0;

    rows.forEach((row) => {
      const rowYear = (row.getAttribute('data-year') || '').trim();
      const show = selected === '' || rowYear === selected;
      row.classList.toggle('hidden', !show);
      if (show) {
        visible += 1;
      }
    });

    if (empty) {
      const isFiltered = selected !== '';
      empty.classList.toggle('hidden', visible > 0 || !isFiltered);
    }
  };

  filter.addEventListener('change', render);
  render();
};

const wirePayslipDetailModal = () => {
  const modal = document.getElementById('payslipModal');
  if (!modal) return;

  const periodEl = document.getElementById('payslipPeriod');
  const payslipNoEl = document.getElementById('payslipNo');
  const statusEl = document.getElementById('payslipStatus');
  const grossEl = document.getElementById('payslipGross');
  const deductionsTotalEl = document.getElementById('payslipTotalDeductions');
  const netEl = document.getElementById('payslipNet');
  const earningsEl = document.getElementById('payslipEarnings');
  const deductionsEl = document.getElementById('payslipDeductions');

  const renderRows = (target, rows, isDeduction = false) => {
    if (!target) return;

    const safeRows = Array.isArray(rows) ? rows : [];
    if (!safeRows.length) {
      target.innerHTML = '<div class="px-4 py-2 text-gray-500">No entries</div>';
      return;
    }

    target.innerHTML = safeRows
      .map((row) => {
        const label = (row.description || row.adjustment_code || 'Entry').toString();
        const amount = formatCurrency(row.amount || 0);
        const amountClass = isDeduction ? 'text-red-600' : 'text-gray-800';
        return `<div class="flex justify-between px-4 py-2"><span>${label}</span><span class="${amountClass}">${amount}</span></div>`;
      })
      .join('');
  };

  document.querySelectorAll('[data-open-payslip-detail]').forEach((button) => {
    button.addEventListener('click', () => {
      const payloadRaw = button.getAttribute('data-payload') || '{}';
      let payload = {};
      try {
        payload = JSON.parse(payloadRaw);
      } catch {
        payload = {};
      }

      if (periodEl) periodEl.textContent = payload.period_label || '-';
      if (payslipNoEl) payslipNoEl.textContent = payload.payslip_no || '-';
      if (statusEl) statusEl.textContent = payload.status_label || '-';
      if (grossEl) grossEl.textContent = formatCurrency(payload.gross_pay || 0);
      if (deductionsTotalEl) deductionsTotalEl.textContent = formatCurrency(payload.deductions_total || 0);
      if (netEl) netEl.textContent = formatCurrency(payload.net_pay || 0);

      renderRows(earningsEl, payload.earnings || [], false);
      renderRows(deductionsEl, payload.deductions || [], true);

      toggleModal(modal, true);
    });
  });

  document.querySelectorAll('[data-close-payslip]').forEach((button) => {
    button.addEventListener('click', () => toggleModal(modal, false));
  });

  modal.addEventListener('click', (event) => {
    if (event.target === modal) {
      toggleModal(modal, false);
    }
  });
};

const initEmployeePayrollPage = () => {
  runPageStatePass({ pageKey: 'employee-payroll' });

  wireYearFilter();
  wirePayslipDetailModal();

  document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;
    const modal = document.getElementById('payslipModal');
    if (modal && !modal.classList.contains('hidden')) {
      toggleModal(modal, false);
    }
  });
};

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initEmployeePayrollPage);
} else {
  initEmployeePayrollPage();
}

export default initEmployeePayrollPage;
