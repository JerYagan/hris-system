<?php

$state = $state ?? cleanText($_GET['state'] ?? null);
$message = $message ?? cleanText($_GET['message'] ?? null);
$dataLoadError = null;

$payrollSummary = [
    'latest_period_label' => '-',
    'latest_net_pay' => 0.0,
    'latest_gross_pay' => 0.0,
    'latest_deductions' => 0.0,
];

$employeePayrollRows = [];
$payrollYears = [];

if (!(bool)($employeeContextResolved ?? false)) {
    $dataLoadError = (string)($employeeContextError ?? 'Employee context could not be resolved.');
    return;
}

$payrollItemsResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/payroll_items?select=id,payroll_run_id,gross_pay,deductions_total,net_pay,created_at,payroll_run:payroll_runs(run_status,payroll_period:payroll_periods(period_code,period_start,period_end,payout_date,status))'
    . '&person_id=eq.' . rawurlencode((string)$employeePersonId)
    . '&order=created_at.desc&limit=400',
    $headers
);

if (!isSuccessful($payrollItemsResponse)) {
    $dataLoadError = 'Unable to load payroll history right now.';
    return;
}

$itemRows = (array)($payrollItemsResponse['data'] ?? []);
if (empty($itemRows)) {
    return;
}

$payrollItemIds = [];
foreach ($itemRows as $itemRaw) {
    $item = (array)$itemRaw;
    $itemId = (string)($item['id'] ?? '');
    if ($itemId !== '') {
        $payrollItemIds[] = $itemId;
    }
}

if (empty($payrollItemIds)) {
    return;
}

$inClause = implode(',', array_map(static fn(string $id): string => rawurlencode($id), $payrollItemIds));

$payslipsResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/payslips?select=id,payroll_item_id,payslip_no,pdf_storage_path,released_at,viewed_at,created_at'
    . '&payroll_item_id=in.(' . $inClause . ')'
    . '&order=created_at.desc&limit=400',
    $headers
);

$adjustmentsResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/payroll_adjustments?select=id,payroll_item_id,adjustment_type,adjustment_code,description,amount,created_at'
    . '&payroll_item_id=in.(' . $inClause . ')'
    . '&order=created_at.asc&limit=2000',
    $headers
);

$payslipByPayrollItem = [];
if (isSuccessful($payslipsResponse)) {
    foreach ((array)($payslipsResponse['data'] ?? []) as $payslipRaw) {
        $payslip = (array)$payslipRaw;
        $payrollItemId = (string)($payslip['payroll_item_id'] ?? '');
        if ($payrollItemId === '' || isset($payslipByPayrollItem[$payrollItemId])) {
            continue;
        }
        $payslipByPayrollItem[$payrollItemId] = $payslip;
    }
}

$adjustmentsByPayrollItem = [];
if (isSuccessful($adjustmentsResponse)) {
    foreach ((array)($adjustmentsResponse['data'] ?? []) as $adjustmentRaw) {
        $adjustment = (array)$adjustmentRaw;
        $payrollItemId = (string)($adjustment['payroll_item_id'] ?? '');
        if ($payrollItemId === '') {
            continue;
        }

        if (!isset($adjustmentsByPayrollItem[$payrollItemId])) {
            $adjustmentsByPayrollItem[$payrollItemId] = [];
        }

        $adjustmentsByPayrollItem[$payrollItemId][] = [
            'id' => (string)($adjustment['id'] ?? ''),
            'adjustment_type' => strtolower((string)($adjustment['adjustment_type'] ?? 'deduction')),
            'adjustment_code' => (string)($adjustment['adjustment_code'] ?? ''),
            'description' => (string)($adjustment['description'] ?? ''),
            'amount' => (float)($adjustment['amount'] ?? 0),
        ];
    }
}

foreach ($itemRows as $index => $itemRaw) {
    $item = (array)$itemRaw;
    $itemId = (string)($item['id'] ?? '');
    if ($itemId === '') {
        continue;
    }

    $run = (array)($item['payroll_run'] ?? []);
    $period = (array)($run['payroll_period'] ?? []);

    $periodStart = (string)($period['period_start'] ?? '');
    $periodEnd = (string)($period['period_end'] ?? '');
    $periodCode = (string)($period['period_code'] ?? '');
    $periodLabel = ($periodStart !== '' && $periodEnd !== '')
        ? (date('M d, Y', strtotime($periodStart)) . ' - ' . date('M d, Y', strtotime($periodEnd)))
        : ($periodCode !== '' ? $periodCode : 'Payroll Period');

    $payslip = (array)($payslipByPayrollItem[$itemId] ?? []);
    $releasedAt = cleanText($payslip['released_at'] ?? null);
    $status = $releasedAt !== null ? 'released' : 'pending';

    $adjustments = (array)($adjustmentsByPayrollItem[$itemId] ?? []);
    $earnings = [];
    $deductions = [];

    foreach ($adjustments as $adjustment) {
        if ((string)($adjustment['adjustment_type'] ?? '') === 'earning') {
            $earnings[] = $adjustment;
        } else {
            $deductions[] = $adjustment;
        }
    }

    $grossPay = (float)($item['gross_pay'] ?? 0);
    $totalDeductions = (float)($item['deductions_total'] ?? 0);
    $netPay = (float)($item['net_pay'] ?? 0);

    if (empty($earnings)) {
        $earnings[] = [
            'adjustment_type' => 'earning',
            'adjustment_code' => 'GROSS',
            'description' => 'Gross Pay',
            'amount' => $grossPay,
        ];
    }

    if (empty($deductions)) {
        $deductions[] = [
            'adjustment_type' => 'deduction',
            'adjustment_code' => 'DEDUCTIONS',
            'description' => 'Total Deductions',
            'amount' => $totalDeductions,
        ];
    }

    $yearSource = $periodEnd !== '' ? $periodEnd : ($periodStart !== '' ? $periodStart : (string)($item['created_at'] ?? ''));
    $year = (int)date('Y', strtotime($yearSource));
    if ($year > 1900) {
        $payrollYears[$year] = $year;
    }

    $employeePayrollRows[] = [
        'item_id' => $itemId,
        'period_label' => $periodLabel,
        'period_year' => $year > 1900 ? (string)$year : '',
        'gross_pay' => $grossPay,
        'deductions_total' => $totalDeductions,
        'net_pay' => $netPay,
        'status' => $status,
        'run_status' => strtolower((string)($run['run_status'] ?? 'draft')),
        'payslip_id' => (string)($payslip['id'] ?? ''),
        'payslip_no' => (string)($payslip['payslip_no'] ?? '-'),
        'pdf_storage_path' => cleanText($payslip['pdf_storage_path'] ?? null),
        'released_at' => $releasedAt,
        'viewed_at' => cleanText($payslip['viewed_at'] ?? null),
        'created_at' => (string)($item['created_at'] ?? ''),
        'earnings' => $earnings,
        'deductions' => $deductions,
    ];

    if ($index === 0) {
        $payrollSummary['latest_period_label'] = $periodLabel;
        $payrollSummary['latest_net_pay'] = $netPay;
        $payrollSummary['latest_gross_pay'] = $grossPay;
        $payrollSummary['latest_deductions'] = $totalDeductions;
    }
}

if (!empty($payrollYears)) {
    rsort($payrollYears);
}
