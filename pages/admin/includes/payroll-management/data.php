<?php

$employeesResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/employment_records?select=person_id,person:people!employment_records_person_id_fkey(id,first_name,surname)&is_current=eq.true&limit=1000',
    $headers
);

$compensationsResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/employee_compensations?select=id,person_id,monthly_rate,pay_frequency,effective_from,effective_to,created_at&order=created_at.desc&limit=5000',
    $headers
);

$periodsResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/payroll_periods?select=id,period_code,period_start,period_end,payout_date,status,created_at&order=period_end.desc&limit=300',
    $headers
);

$runsResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/payroll_runs?select=id,payroll_period_id,run_status,approved_at,generated_at,created_at,payroll_period:payroll_periods(period_code,period_start,period_end,payout_date,status),office:offices(office_name)&order=created_at.desc&limit=800',
    $headers
);

$itemsResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/payroll_items?select=id,payroll_run_id,person_id,gross_pay,deductions_total,net_pay,created_at,person:people(first_name,surname)&order=created_at.desc&limit=5000',
    $headers
);

$payslipsResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/payslips?select=id,payroll_item_id,payslip_no,pdf_storage_path,released_at,created_at&order=created_at.desc&limit=5000',
    $headers
);

$employmentRecords = isSuccessful($employeesResponse) ? (array)$employeesResponse['data'] : [];
$compensationRows = isSuccessful($compensationsResponse) ? (array)$compensationsResponse['data'] : [];
$periodRows = isSuccessful($periodsResponse) ? (array)$periodsResponse['data'] : [];
$runRows = isSuccessful($runsResponse) ? (array)$runsResponse['data'] : [];
$itemRows = isSuccessful($itemsResponse) ? (array)$itemsResponse['data'] : [];
$payslipRows = isSuccessful($payslipsResponse) ? (array)$payslipsResponse['data'] : [];

$dataLoadError = null;
$responseChecks = [
    ['employees', $employeesResponse],
    ['compensation', $compensationsResponse],
    ['payroll periods', $periodsResponse],
    ['payroll runs', $runsResponse],
    ['payroll items', $itemsResponse],
    ['payslips', $payslipsResponse],
];

foreach ($responseChecks as [$label, $response]) {
    if (isSuccessful($response)) {
        continue;
    }

    $message = ucfirst((string)$label) . ' query failed (HTTP ' . (int)($response['status'] ?? 0) . ').';
    $raw = trim((string)($response['raw'] ?? ''));
    if ($raw !== '') {
        $message .= ' ' . $raw;
    }

    $dataLoadError = $dataLoadError ? ($dataLoadError . ' ' . $message) : $message;
}

$employeeMap = [];
foreach ($employmentRecords as $record) {
    $personId = (string)($record['person_id'] ?? '');
    if ($personId === '' || isset($employeeMap[$personId])) {
        continue;
    }

    $firstName = trim((string)($record['person']['first_name'] ?? ''));
    $surname = trim((string)($record['person']['surname'] ?? ''));
    $displayName = trim($firstName . ' ' . $surname);
    if ($displayName === '') {
        $displayName = 'Unknown Employee';
    }

    $employeeMap[$personId] = [
        'id' => $personId,
        'name' => $displayName,
    ];
}

$latestCompensationByPerson = [];
foreach ($compensationRows as $row) {
    $personId = (string)($row['person_id'] ?? '');
    if ($personId === '' || isset($latestCompensationByPerson[$personId])) {
        continue;
    }
    $latestCompensationByPerson[$personId] = $row;
}

$employeesForSetup = array_values($employeeMap);
usort($employeesForSetup, static function (array $left, array $right): int {
    return strcmp((string)$left['name'], (string)$right['name']);
});

$periodById = [];
foreach ($periodRows as $period) {
    $periodId = (string)($period['id'] ?? '');
    if ($periodId === '') {
        continue;
    }
    $periodById[$periodId] = $period;
}

$runById = [];
$batchRows = [];
$itemsByRun = [];
foreach ($itemRows as $item) {
    $runId = (string)($item['payroll_run_id'] ?? '');
    if ($runId === '') {
        continue;
    }
    if (!isset($itemsByRun[$runId])) {
        $itemsByRun[$runId] = [];
    }
    $itemsByRun[$runId][] = $item;
}

foreach ($runRows as $run) {
    $runId = (string)($run['id'] ?? '');
    if ($runId === '') {
        continue;
    }
    $runById[$runId] = $run;

    $period = is_array($run['payroll_period'] ?? null) ? $run['payroll_period'] : [];
    $periodCode = (string)($period['period_code'] ?? 'Uncoded Period');
    $periodStart = (string)($period['period_start'] ?? '');
    $periodEnd = (string)($period['period_end'] ?? '');
    $periodLabel = $periodStart !== '' && $periodEnd !== ''
        ? date('M d, Y', strtotime($periodStart)) . ' - ' . date('M d, Y', strtotime($periodEnd))
        : $periodCode;

    $runItems = (array)($itemsByRun[$runId] ?? []);
    $employeeIds = [];
    $totalGross = 0.0;
    $totalNet = 0.0;
    foreach ($runItems as $item) {
        $personId = (string)($item['person_id'] ?? '');
        if ($personId !== '') {
            $employeeIds[$personId] = true;
        }
        $totalGross += (float)($item['gross_pay'] ?? 0);
        $totalNet += (float)($item['net_pay'] ?? 0);
    }

    $batchRows[] = [
        'id' => $runId,
        'period_code' => $periodCode,
        'period_label' => $periodLabel,
        'status' => (string)($run['run_status'] ?? 'draft'),
        'employee_count' => count($employeeIds),
        'total_gross' => $totalGross,
        'total_net' => $totalNet,
        'generated_at' => (string)($run['generated_at'] ?? ''),
    ];
}

$currentCutoffLabel = 'No payroll period found';
$currentCutoffEmployeeCount = 0;
$currentCutoffGross = 0.0;
$currentCutoffNet = 0.0;

if (!empty($periodRows)) {
    $latestPeriod = $periodRows[0];
    $latestPeriodId = (string)($latestPeriod['id'] ?? '');
    $periodCode = (string)($latestPeriod['period_code'] ?? 'Current Cutoff');
    $periodStart = (string)($latestPeriod['period_start'] ?? '');
    $periodEnd = (string)($latestPeriod['period_end'] ?? '');

    $currentCutoffLabel = $periodStart !== '' && $periodEnd !== ''
        ? date('M d, Y', strtotime($periodStart)) . ' - ' . date('M d, Y', strtotime($periodEnd))
        : $periodCode;

    $personCounter = [];
    foreach ($itemRows as $item) {
        $runId = (string)($item['payroll_run_id'] ?? '');
        if ($runId === '') {
            continue;
        }

        $run = $runById[$runId] ?? null;
        if (!is_array($run) || (string)($run['payroll_period_id'] ?? '') !== $latestPeriodId) {
            continue;
        }

        $personId = (string)($item['person_id'] ?? '');
        if ($personId !== '') {
            $personCounter[$personId] = true;
        }

        $currentCutoffGross += (float)($item['gross_pay'] ?? 0);
        $currentCutoffNet += (float)($item['net_pay'] ?? 0);
    }

    $currentCutoffEmployeeCount = count($personCounter);
}

$payslipByPayrollItem = [];
foreach ($payslipRows as $row) {
    $payrollItemId = (string)($row['payroll_item_id'] ?? '');
    if ($payrollItemId === '' || isset($payslipByPayrollItem[$payrollItemId])) {
        continue;
    }
    $payslipByPayrollItem[$payrollItemId] = $row;
}

$payslipTableRows = [];
foreach ($itemRows as $item) {
    $itemId = (string)($item['id'] ?? '');
    $runId = (string)($item['payroll_run_id'] ?? '');
    if ($itemId === '' || $runId === '') {
        continue;
    }

    $run = $runById[$runId] ?? null;
    if (!is_array($run)) {
        continue;
    }

    $period = is_array($run['payroll_period'] ?? null) ? $run['payroll_period'] : [];
    $periodStart = (string)($period['period_start'] ?? '');
    $periodEnd = (string)($period['period_end'] ?? '');
    $periodLabel = $periodStart !== '' && $periodEnd !== ''
        ? date('M d, Y', strtotime($periodStart)) . ' - ' . date('M d, Y', strtotime($periodEnd))
        : (string)($period['period_code'] ?? 'Uncoded Period');

    $employeeName = trim(((string)($item['person']['first_name'] ?? '')) . ' ' . ((string)($item['person']['surname'] ?? '')));
    if ($employeeName === '') {
        $employeeName = 'Unknown Employee';
    }

    $payslip = $payslipByPayrollItem[$itemId] ?? null;
    $releasedAt = cleanText($payslip['released_at'] ?? null);
    $status = $releasedAt ? 'released' : 'pending';

    $payslipTableRows[] = [
        'item_id' => $itemId,
        'employee_name' => $employeeName,
        'period_label' => $periodLabel,
        'gross_pay' => (float)($item['gross_pay'] ?? 0),
        'deductions_total' => (float)($item['deductions_total'] ?? 0),
        'net_pay' => (float)($item['net_pay'] ?? 0),
        'status' => $status,
        'payslip_no' => (string)($payslip['payslip_no'] ?? '-'),
        'pdf_storage_path' => cleanText($payslip['pdf_storage_path'] ?? null),
    ];
}

$releaseEligibleRuns = array_values(array_filter($batchRows, static function (array $row): bool {
    $status = strtolower((string)($row['status'] ?? 'draft'));
    return in_array($status, ['approved', 'released', 'computed'], true);
}));
