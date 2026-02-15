<?php

$normalizeCompensationRow = static function (array $row): array {
    $monthlyRate = (float)($row['monthly_rate'] ?? 0);
    $allowanceTotal = max(0.0, (float)($row['allowance_total'] ?? 0));
    $basePay = isset($row['base_pay'])
        ? max(0.0, (float)$row['base_pay'])
        : max(0.0, $monthlyRate - $allowanceTotal);

    $row['base_pay'] = $basePay;
    $row['allowance_total'] = $allowanceTotal;
    $row['tax_deduction'] = max(0.0, (float)($row['tax_deduction'] ?? 0));
    $row['government_deductions'] = max(0.0, (float)($row['government_deductions'] ?? 0));
    $row['other_deductions'] = max(0.0, (float)($row['other_deductions'] ?? 0));

    return $row;
};

$employeesResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/employment_records?select=person_id,employment_status,person:people!employment_records_person_id_fkey(id,first_name,surname)&is_current=eq.true&limit=1000',
    $headers
);

$compensationsResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/employee_compensations?select=id,person_id,monthly_rate,pay_frequency,effective_from,effective_to,base_pay,allowance_total,tax_deduction,government_deductions,other_deductions,created_at&order=effective_from.desc,created_at.desc&limit=5000',
    $headers
);

if (!isSuccessful($compensationsResponse)) {
    $compensationsResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/employee_compensations?select=id,person_id,monthly_rate,pay_frequency,effective_from,effective_to,created_at&order=effective_from.desc,created_at.desc&limit=5000',
        $headers
    );
}

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
$compensationRows = isSuccessful($compensationsResponse)
    ? array_map($normalizeCompensationRow, (array)$compensationsResponse['data'])
    : [];
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

if (isSuccessful($periodsResponse) && empty($periodRows)) {
    $effectiveMonths = [];
    foreach ($compensationRows as $row) {
        $effectiveFrom = trim((string)($row['effective_from'] ?? ''));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $effectiveFrom)) {
            continue;
        }

        $effectiveMonths[substr($effectiveFrom, 0, 7)] = true;
    }

    if (empty($effectiveMonths)) {
        $effectiveMonths[gmdate('Y-m')] = true;
    }

    ksort($effectiveMonths);

    $payload = [];
    foreach (array_keys($effectiveMonths) as $yearMonth) {
        $monthDate = DateTimeImmutable::createFromFormat('!Y-m-d', $yearMonth . '-01', new DateTimeZone('UTC'));
        if (!$monthDate) {
            continue;
        }

        $firstDay = $monthDate->format('Y-m-01');
        $fifteenth = $monthDate->format('Y-m-15');
        $sixteenth = $monthDate->format('Y-m-16');
        $monthEnd = $monthDate->format('Y-m-t');

        $payload[] = [
            'period_code' => $yearMonth . '-A',
            'period_start' => $firstDay,
            'period_end' => $fifteenth,
            'payout_date' => DateTimeImmutable::createFromFormat('!Y-m-d', $fifteenth, new DateTimeZone('UTC'))->modify('+5 day')->format('Y-m-d'),
            'status' => 'open',
        ];

        if ($sixteenth <= $monthEnd) {
            $payload[] = [
                'period_code' => $yearMonth . '-B',
                'period_start' => $sixteenth,
                'period_end' => $monthEnd,
                'payout_date' => DateTimeImmutable::createFromFormat('!Y-m-d', $monthEnd, new DateTimeZone('UTC'))->modify('+5 day')->format('Y-m-d'),
                'status' => 'open',
            ];
        }
    }

    if (!empty($payload)) {
        apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/payroll_periods',
            array_merge($headers, ['Prefer: return=minimal']),
            $payload
        );

        $periodsResponse = apiRequest(
            'GET',
            $supabaseUrl . '/rest/v1/payroll_periods?select=id,period_code,period_start,period_end,payout_date,status,created_at&order=period_end.desc&limit=300',
            $headers
        );

        if (isSuccessful($periodsResponse)) {
            $periodRows = (array)($periodsResponse['data'] ?? []);
        }
    }
}

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

$setupCompensationByPerson = [];
$todayDate = gmdate('Y-m-d');
foreach ($compensationRows as $row) {
    $personId = (string)($row['person_id'] ?? '');
    if ($personId === '' || isset($setupCompensationByPerson[$personId])) {
        continue;
    }

    $effectiveFrom = (string)($row['effective_from'] ?? '');
    $effectiveTo = (string)($row['effective_to'] ?? '');
    if ($effectiveFrom === '' || $effectiveFrom > $todayDate) {
        continue;
    }
    if ($effectiveTo !== '' && $effectiveTo < $todayDate) {
        continue;
    }

    $setupCompensationByPerson[$personId] = $row;
}

foreach ($latestCompensationByPerson as $personId => $row) {
    if (!isset($setupCompensationByPerson[$personId])) {
        $setupCompensationByPerson[$personId] = $row;
    }
}

$employeesForSetup = array_values($employeeMap);
usort($employeesForSetup, static function (array $left, array $right): int {
    return strcmp((string)$left['name'], (string)$right['name']);
});

$employmentStatusByPerson = [];
foreach ($employmentRecords as $record) {
    $personId = (string)($record['person_id'] ?? '');
    if ($personId === '' || isset($employmentStatusByPerson[$personId])) {
        continue;
    }

    $employmentStatusByPerson[$personId] = strtolower(trim((string)($record['employment_status'] ?? 'active')));
}

$employeePickerRows = [];
foreach ($employeesForSetup as $employee) {
    $personId = (string)($employee['id'] ?? '');
    if ($personId === '') {
        continue;
    }

    $compensation = $setupCompensationByPerson[$personId] ?? null;
    $employeePickerRows[] = [
        'person_id' => $personId,
        'name' => (string)($employee['name'] ?? 'Unknown Employee'),
        'status' => (string)($employmentStatusByPerson[$personId] ?? 'active'),
        'monthly_rate' => is_array($compensation) ? (float)($compensation['monthly_rate'] ?? 0) : 0.0,
        'pay_frequency' => is_array($compensation) ? (string)($compensation['pay_frequency'] ?? 'semi_monthly') : 'semi_monthly',
    ];
}

$activeEmploymentPersonIds = [];
foreach ($employmentRecords as $record) {
    $personId = (string)($record['person_id'] ?? '');
    $employmentStatus = strtolower(trim((string)($record['employment_status'] ?? 'active')));
    if ($personId === '' || $employmentStatus !== 'active') {
        continue;
    }

    $activeEmploymentPersonIds[$personId] = true;
}

$generationPreviewRows = [];
$generationPreviewTotals = [
    'employee_count' => 0,
    'gross_pay' => 0.0,
    'net_pay' => 0.0,
];

foreach ($employeesForSetup as $employee) {
    $personId = (string)($employee['id'] ?? '');
    if ($personId === '' || !isset($activeEmploymentPersonIds[$personId])) {
        continue;
    }

    $compensation = $setupCompensationByPerson[$personId] ?? null;
    if (!is_array($compensation)) {
        continue;
    }

    $monthlyRate = (float)($compensation['monthly_rate'] ?? 0);
    $allowanceMonthly = max(0.0, (float)($compensation['allowance_total'] ?? 0));
    $basePayMonthly = isset($compensation['base_pay'])
        ? max(0.0, (float)$compensation['base_pay'])
        : max(0.0, $monthlyRate - $allowanceMonthly);
    $taxMonthly = max(0.0, (float)($compensation['tax_deduction'] ?? 0));
    $governmentMonthly = max(0.0, (float)($compensation['government_deductions'] ?? 0));
    $otherMonthly = max(0.0, (float)($compensation['other_deductions'] ?? 0));
    if ($monthlyRate <= 0) {
        continue;
    }

    $payFrequency = strtolower((string)($compensation['pay_frequency'] ?? 'semi_monthly'));
    $divisor = 1;
    if ($payFrequency === 'semi_monthly') {
        $divisor = 2;
    } elseif ($payFrequency === 'weekly') {
        $divisor = 4;
    }

    $basicPay = round($basePayMonthly / $divisor, 2);
    $allowancesTotal = round($allowanceMonthly / $divisor, 2);
    $deductionsTotal = round(($taxMonthly + $governmentMonthly + $otherMonthly) / $divisor, 2);
    $grossPay = round($basicPay + $allowancesTotal, 2);
    $netPay = round($grossPay - $deductionsTotal, 2);

    $generationPreviewRows[] = [
        'person_id' => $personId,
        'employee_name' => (string)($employee['name'] ?? 'Unknown Employee'),
        'pay_frequency' => $payFrequency,
        'gross_pay' => $grossPay,
        'net_pay' => $netPay,
    ];

    $generationPreviewTotals['employee_count']++;
    $generationPreviewTotals['gross_pay'] += $grossPay;
    $generationPreviewTotals['net_pay'] += $netPay;
}

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
        'period_id' => (string)($run['payroll_period_id'] ?? ''),
        'period_code' => $periodCode,
        'period_label' => $periodLabel,
        'status' => (string)($run['run_status'] ?? 'draft'),
        'employee_count' => count($employeeIds),
        'total_gross' => $totalGross,
        'total_net' => $totalNet,
        'generated_at' => (string)($run['generated_at'] ?? ''),
    ];
}

$latestBatchByPeriod = [];
foreach ($batchRows as $row) {
    $periodId = (string)($row['period_id'] ?? '');
    if ($periodId === '' || isset($latestBatchByPeriod[$periodId])) {
        continue;
    }
    $latestBatchByPeriod[$periodId] = $row;
}

$payrollEstimateHistoryRows = [];
foreach ($periodRows as $period) {
    $periodId = (string)($period['id'] ?? '');
    if ($periodId === '') {
        continue;
    }

    $periodCode = (string)($period['period_code'] ?? 'PR');
    $periodStart = (string)($period['period_start'] ?? '');
    $periodEnd = (string)($period['period_end'] ?? '');
    $periodLabel = $periodStart !== '' && $periodEnd !== ''
        ? date('M d, Y', strtotime($periodStart)) . ' - ' . date('M d, Y', strtotime($periodEnd))
        : $periodCode;

    $batch = $latestBatchByPeriod[$periodId] ?? null;
    $payrollEstimateHistoryRows[] = [
        'period_id' => $periodId,
        'period_code' => $periodCode,
        'period_label' => $periodLabel,
        'status' => (string)($period['status'] ?? 'open'),
        'estimated_gross' => is_array($batch) ? (float)($batch['total_gross'] ?? 0) : 0.0,
        'estimated_net' => is_array($batch) ? (float)($batch['total_net'] ?? 0) : 0.0,
        'employee_count' => is_array($batch) ? (int)($batch['employee_count'] ?? 0) : 0,
    ];
}

$payrollEstimateHistoryRows = array_slice($payrollEstimateHistoryRows, 0, 8);

$periodsWithExistingRun = [];
foreach ($runRows as $run) {
    $periodId = (string)($run['payroll_period_id'] ?? '');
    $runStatus = strtolower(trim((string)($run['run_status'] ?? 'draft')));
    if ($periodId === '' || $runStatus === 'cancelled') {
        continue;
    }

    $periodsWithExistingRun[$periodId] = true;
}

$generationPeriodOptions = array_values(array_filter($periodRows, static function (array $period) use ($periodsWithExistingRun): bool {
    $periodId = (string)($period['id'] ?? '');
    $status = strtolower((string)($period['status'] ?? 'open'));

    if (!in_array($status, ['open', 'processing', 'posted'], true)) {
        return false;
    }

    if ($periodId !== '' && isset($periodsWithExistingRun[$periodId])) {
        return false;
    }

    return true;
}));

$buildGenerationPreviewForPeriod = static function (string $periodStart, string $periodEnd) use ($activeEmploymentPersonIds, $employeeMap, $compensationRows): array {
    $compensationByPerson = [];
    foreach ($compensationRows as $row) {
        $personId = (string)($row['person_id'] ?? '');
        if ($personId === '' || !isset($activeEmploymentPersonIds[$personId]) || isset($compensationByPerson[$personId])) {
            continue;
        }

        $effectiveFrom = (string)($row['effective_from'] ?? '');
        $effectiveTo = (string)($row['effective_to'] ?? '');
        if ($effectiveFrom === '' || $effectiveFrom > $periodStart) {
            continue;
        }
        if ($effectiveTo !== '' && $effectiveTo < $periodStart) {
            continue;
        }

        $compensationByPerson[$personId] = $row;
    }

    $rows = [];
    $totals = [
        'employee_count' => 0,
        'gross_pay' => 0.0,
        'net_pay' => 0.0,
    ];

    foreach ($activeEmploymentPersonIds as $personId => $_active) {
        $compensation = $compensationByPerson[$personId] ?? null;
        if (!is_array($compensation)) {
            continue;
        }

        $monthlyRate = (float)($compensation['monthly_rate'] ?? 0);
        $allowanceMonthly = max(0.0, (float)($compensation['allowance_total'] ?? 0));
        $basePayMonthly = isset($compensation['base_pay'])
            ? max(0.0, (float)$compensation['base_pay'])
            : max(0.0, $monthlyRate - $allowanceMonthly);
        $taxMonthly = max(0.0, (float)($compensation['tax_deduction'] ?? 0));
        $governmentMonthly = max(0.0, (float)($compensation['government_deductions'] ?? 0));
        $otherMonthly = max(0.0, (float)($compensation['other_deductions'] ?? 0));
        if ($monthlyRate <= 0) {
            continue;
        }

        $payFrequency = strtolower((string)($compensation['pay_frequency'] ?? 'semi_monthly'));
        $divisor = 1;
        if ($payFrequency === 'semi_monthly') {
            $divisor = 2;
        } elseif ($payFrequency === 'weekly') {
            $divisor = 4;
        }

        $basicPay = round($basePayMonthly / $divisor, 2);
        $allowancesTotal = round($allowanceMonthly / $divisor, 2);
        $deductionsTotal = round(($taxMonthly + $governmentMonthly + $otherMonthly) / $divisor, 2);
        $grossPay = round($basicPay + $allowancesTotal, 2);
        $netPay = round($grossPay - $deductionsTotal, 2);

        $rows[] = [
            'person_id' => $personId,
            'employee_name' => (string)($employeeMap[$personId]['name'] ?? 'Unknown Employee'),
            'pay_frequency' => $payFrequency,
            'gross_pay' => $grossPay,
            'net_pay' => $netPay,
        ];

        $totals['employee_count']++;
        $totals['gross_pay'] += $grossPay;
        $totals['net_pay'] += $netPay;
    }

    usort($rows, static function (array $left, array $right): int {
        return strcmp((string)($left['employee_name'] ?? ''), (string)($right['employee_name'] ?? ''));
    });

    return [
        'rows' => $rows,
        'totals' => $totals,
    ];
};

$generationPreviewByPeriod = [];
foreach ($generationPeriodOptions as $period) {
    $periodId = (string)($period['id'] ?? '');
    $periodStart = (string)($period['period_start'] ?? '');
    $periodEnd = (string)($period['period_end'] ?? '');
    if ($periodId === '' || $periodStart === '' || $periodEnd === '') {
        continue;
    }

    $generationPreviewByPeriod[$periodId] = $buildGenerationPreviewForPeriod($periodStart, $periodEnd);
}

foreach ($payrollEstimateHistoryRows as &$historyRow) {
    $historyPeriodId = (string)($historyRow['period_id'] ?? '');
    if ($historyPeriodId === '') {
        continue;
    }

    $hasBatchValues = ((int)($historyRow['employee_count'] ?? 0) > 0)
        || ((float)($historyRow['estimated_gross'] ?? 0) > 0)
        || ((float)($historyRow['estimated_net'] ?? 0) > 0);
    if ($hasBatchValues) {
        continue;
    }

    $preview = $generationPreviewByPeriod[$historyPeriodId] ?? null;
    if (!is_array($preview)) {
        continue;
    }

    $previewTotals = (array)($preview['totals'] ?? []);
    $historyRow['employee_count'] = (int)($previewTotals['employee_count'] ?? 0);
    $historyRow['estimated_gross'] = (float)($previewTotals['gross_pay'] ?? 0);
    $historyRow['estimated_net'] = (float)($previewTotals['net_pay'] ?? 0);
}
unset($historyRow);

if (!empty($generationPeriodOptions)) {
    $defaultPeriodId = (string)($generationPeriodOptions[0]['id'] ?? '');
    $defaultPreview = is_array($generationPreviewByPeriod[$defaultPeriodId] ?? null) ? $generationPreviewByPeriod[$defaultPeriodId] : null;
    if (is_array($defaultPreview)) {
        $generationPreviewRows = (array)($defaultPreview['rows'] ?? []);
        $generationPreviewTotals = (array)($defaultPreview['totals'] ?? ['employee_count' => 0, 'gross_pay' => 0.0, 'net_pay' => 0.0]);
    }
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

if ($currentCutoffEmployeeCount === 0 && !empty($periodRows)) {
    $latestPeriod = $periodRows[0];
    $latestPeriodId = (string)($latestPeriod['id'] ?? '');
    $previewForLatest = is_array($generationPreviewByPeriod[$latestPeriodId] ?? null)
        ? (array)$generationPreviewByPeriod[$latestPeriodId]
        : null;

    if (is_array($previewForLatest)) {
        $latestTotals = (array)($previewForLatest['totals'] ?? []);
        $currentCutoffEmployeeCount = (int)($latestTotals['employee_count'] ?? 0);
        $currentCutoffGross = (float)($latestTotals['gross_pay'] ?? 0);
        $currentCutoffNet = (float)($latestTotals['net_pay'] ?? 0);
    }
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
    return in_array($status, ['approved', 'computed'], true);
}));

$salarySetupLogRows = [];
$todayDateTs = strtotime(gmdate('Y-m-d'));
foreach ($compensationRows as $row) {
    $setupId = (string)($row['id'] ?? '');
    $personId = (string)($row['person_id'] ?? '');
    if ($setupId === '' || $personId === '') {
        continue;
    }

    $effectiveFrom = (string)($row['effective_from'] ?? '');
    $effectiveTo = (string)($row['effective_to'] ?? '');
    $fromTs = $effectiveFrom !== '' ? strtotime($effectiveFrom) : false;
    $toTs = $effectiveTo !== '' ? strtotime($effectiveTo) : false;

    $timelineStatus = 'current';
    if ($fromTs !== false && $fromTs > $todayDateTs) {
        $timelineStatus = 'scheduled';
    } elseif ($toTs !== false && $toTs < $todayDateTs) {
        $timelineStatus = 'ended';
    }

    $salarySetupLogRows[] = [
        'id' => $setupId,
        'person_id' => $personId,
        'employee_name' => (string)($employeeMap[$personId]['name'] ?? ('Employee #' . strtoupper(substr(str_replace('-', '', $personId), 0, 6)))),
        'monthly_rate' => (float)($row['monthly_rate'] ?? 0),
        'pay_frequency' => (string)($row['pay_frequency'] ?? 'semi_monthly'),
        'effective_from' => $effectiveFrom,
        'effective_to' => $effectiveTo,
        'status' => $timelineStatus,
    ];
}
